<?php

namespace App\Services;

use App\Enums\Accounting\JournalEntryStatus;
use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\PayrollRun;
use App\Models\PayrollSettlement;
use App\Models\PayrollStatutorySettlement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class PayrollAccountingService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_POSTED = 'posted';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REVERSED = 'reversed';

    public function __construct(
        protected JournalEntryService $journals,
        protected AccountingSettingsService $settings,
        protected AccountingPostingAttemptService $attempts,
        protected AccountingIdempotencyService $idempotency,
        protected ActivityLogService $log,
    ) {}

    public function preview(PayrollRun $run): array
    {
        $records = $run->records()->get();

        $gross = round((float) $records->sum('gross_pay'), 2);
        $attendanceDeductions = round((float) $records->sum('attendance_deductions'), 2);
        $salaryExpense = max(0, round($gross - $attendanceDeductions, 2));
        $employerPension = round((float) $records->sum('ssnit_employer'), 2);
        $employeePension = round((float) $records->sum('ssnit_employee'), 2);
        $paye = round((float) $records->sum('tax'), 2);
        $otherDeductions = round((float) $records->sum(fn ($record) => (float) $record->other_pre_tax_deductions + (float) $record->other_deductions), 2);
        $netPay = round((float) $records->sum('net_pay'), 2);

        $debits = [
            'payroll_expense' => $salaryExpense,
            'employer_pension_expense' => $employerPension,
        ];

        $credits = [
            'payroll_payable' => $netPay,
            'paye_payable' => $paye,
            'pension_payable' => round($employeePension + $employerPension, 2),
            'other_deductions_payable' => $otherDeductions,
        ];

        return [
            'record_count' => $records->count(),
            'gross_pay' => $gross,
            'attendance_deductions' => $attendanceDeductions,
            'net_pay' => $netPay,
            'employee_pension' => $employeePension,
            'employer_pension' => $employerPension,
            'paye' => $paye,
            'other_deductions' => $otherDeductions,
            'debits' => $debits,
            'credits' => $credits,
            'total_debit' => round(array_sum($debits), 2),
            'total_credit' => round(array_sum($credits), 2),
        ];
    }

    public function prepareJournal(PayrollRun $run): array
    {
        return [
            'preview' => $this->preview($run),
            'lines' => $this->accrualLines($run),
        ];
    }

    public function postPayroll(PayrollRun $run, User $actor): JournalEntry
    {
        $run->loadMissing('records');
        if (! in_array($run->status, ['approved', 'posted'], true)) {
            throw ValidationException::withMessages(['status' => 'Only approved payroll can be posted to accounting.']);
        }

        $key = $this->idempotency->key($run, $run->id, 'payroll_accrual');
        if ($journal = $this->idempotency->postedJournal($key)) {
            return $journal;
        }

        $snapshot = $this->preview($run);
        $attempt = $this->attempts->pending('PAYROLL', $run, 'payroll_accrual', postingSnapshot: $snapshot, actor: $actor);

        try {
            $attempt = $this->attempts->processing($attempt, $actor);
            $journal = DB::transaction(function () use ($run, $actor, $key) {
                $entry = $this->journals->createDraft([
                    'entry_date' => $run->period_end?->toDateString() ?: now()->toDateString(),
                    'reference_number' => 'PAY-' . $run->pay_period,
                    'reference_type' => PayrollRun::class,
                    'reference_id' => $run->id,
                    'source_module' => 'PAYROLL',
                    'allow_control_accounts' => true,
                    'idempotency_key' => $key,
                    'description' => 'Payroll accrual ' . $run->pay_period,
                    'lines' => $this->accrualLines($run),
                ]);

                return $this->journals->post($entry, $actor);
            });

            $this->attempts->posted($attempt, $journal, $actor);
            $run->forceFill([
                'status' => 'posted',
                'accounting_status' => self::STATUS_POSTED,
                'journal_entry_id' => $journal->id,
                'accounting_posted_at' => now(),
                'accounting_error' => null,
                'settlement_status' => $this->outstandingNetPay($run) > 0 ? 'open' : 'settled',
            ])->save();
            $run->records()->where('status', 'approved')->update(['status' => 'posted']);

            $this->audit('PAYROLL_ACCOUNTING_POSTED', $run, $actor, ['journal_entry_id' => $journal->id] + $snapshot);

            return $journal;
        } catch (Throwable $e) {
            $this->attempts->failed($attempt, $e, actor: $actor);
            $run->forceFill([
                'accounting_status' => self::STATUS_FAILED,
                'accounting_error' => mb_substr($e->getMessage(), 0, 2000),
            ])->save();
            $this->audit('PAYROLL_ACCOUNTING_POSTING_FAILED', $run, $actor, [
                'severity' => LogSeverity::WARNING,
                'error' => $run->accounting_error,
            ]);
            throw $e;
        }
    }

    public function reversePayroll(PayrollRun $run, User $actor, string $reason): JournalEntry
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'A reversal reason is required.']);
        }
        if ((string) $run->accounting_status !== self::STATUS_POSTED || ! $run->journal_entry_id) {
            throw ValidationException::withMessages(['payroll' => 'Only posted payroll accruals can be reversed.']);
        }
        if ($run->settlements()->where('status', 'posted')->exists()) {
            throw ValidationException::withMessages(['settlements' => 'Reverse posted salary settlements before reversing the payroll accrual.']);
        }
        if ($run->statutorySettlements()->where('status', 'posted')->exists()) {
            throw ValidationException::withMessages(['statutory_settlements' => 'Reverse posted PAYE and pension settlements before reversing the payroll accrual.']);
        }

        $journal = JournalEntry::findOrFail($run->journal_entry_id);
        if ($journal->status !== JournalEntryStatus::POSTED) {
            throw ValidationException::withMessages(['journal' => 'The payroll journal is no longer posted.']);
        }

        $reversal = $this->journals->reverse($journal, $reason, $actor);
        $attempt = $this->attemptFor($run, 'payroll_accrual');
        if ($attempt?->status === 'posted') {
            $this->attempts->reversed($attempt, $reversal, $actor);
        }

        $run->forceFill([
            'status' => 'approved',
            'accounting_status' => self::STATUS_REVERSED,
            'reversal_journal_entry_id' => $reversal->id,
            'reversed_at' => now(),
            'reversed_by' => $actor->id,
            'reversal_reason' => $reason,
            'accounting_error' => null,
        ])->save();
        $run->records()->where('status', 'posted')->update(['status' => 'approved']);

        $this->audit('PAYROLL_ACCOUNTING_REVERSED', $run, $actor, [
            'journal_entry_id' => $journal->id,
            'reversal_journal_entry_id' => $reversal->id,
            'reason' => $reason,
        ], LogSeverity::WARNING);

        return $reversal;
    }

    public function settlePayroll(PayrollRun $run, array $data, User $actor): PayrollSettlement
    {
        if ((string) $run->accounting_status !== self::STATUS_POSTED) {
            throw ValidationException::withMessages(['payroll' => 'Post the payroll accrual before recording salary settlement.']);
        }

        $amount = round((float) ($data['amount'] ?? 0), 2);
        $outstanding = $this->outstandingNetPay($run);
        if ($amount <= 0 || $amount > $outstanding + 0.005) {
            throw ValidationException::withMessages(['amount' => "Settlement amount must be greater than zero and not exceed {$outstanding}."]);
        }

        $paymentAccount = $this->paymentAccount((int) ($data['payment_account_id'] ?? 0));
        $payable = $this->requiredAccount('payroll_payable_account_id');

        $settlement = PayrollSettlement::create([
            'payroll_run_id' => $run->id,
            'settlement_number' => $this->nextSettlementNumber(),
            'settlement_date' => $data['settlement_date'] ?? now()->toDateString(),
            'amount' => $amount,
            'payment_account_id' => $paymentAccount->id,
            'status' => 'draft',
            'accounting_status' => self::STATUS_PENDING,
            'notes' => $data['notes'] ?? null,
            'created_by' => $actor->id,
        ]);

        $attempt = $this->attempts->pending('PAYROLL_SETTLEMENT', $settlement, 'salary_settlement', postingSnapshot: [
            'payroll_run_id' => $run->id,
            'pay_period' => $run->pay_period,
            'amount' => $amount,
            'payment_account_id' => $paymentAccount->id,
        ], actor: $actor);

        try {
            $attempt = $this->attempts->processing($attempt, $actor);
            $key = $this->idempotency->key($settlement, $settlement->id, 'salary_settlement');
            $journal = DB::transaction(function () use ($settlement, $run, $actor, $key, $amount, $payable, $paymentAccount) {
                $entry = $this->journals->createDraft([
                    'entry_date' => $settlement->settlement_date->toDateString(),
                    'reference_number' => $settlement->settlement_number,
                    'reference_type' => PayrollSettlement::class,
                    'reference_id' => $settlement->id,
                    'source_module' => 'PAYROLL_SETTLEMENT',
                    'allow_control_accounts' => true,
                    'idempotency_key' => $key,
                    'description' => 'Salary settlement ' . $run->pay_period,
                    'lines' => [
                        $this->line($payable, 'Salary payable settlement ' . $run->pay_period, $amount, 0, $settlement),
                        $this->line($paymentAccount, 'Salary paid ' . $run->pay_period, 0, $amount, $settlement),
                    ],
                ]);

                return $this->journals->post($entry, $actor);
            });

            $this->attempts->posted($attempt, $journal, $actor);
            $settlement->forceFill([
                'status' => 'posted',
                'accounting_status' => self::STATUS_POSTED,
                'journal_entry_id' => $journal->id,
                'posted_at' => now(),
                'posted_by' => $actor->id,
                'accounting_error' => null,
            ])->save();

            $this->refreshSettlementState($run->fresh());
            $this->audit('PAYROLL_SETTLEMENT_POSTED', $settlement, $actor, [
                'journal_entry_id' => $journal->id,
                'payroll_run_id' => $run->id,
                'amount' => $amount,
            ]);

            return $settlement->fresh(['journalEntry', 'paymentAccount']);
        } catch (Throwable $e) {
            $this->attempts->failed($attempt, $e, actor: $actor);
            $settlement->forceFill([
                'status' => 'failed',
                'accounting_status' => self::STATUS_FAILED,
                'accounting_error' => mb_substr($e->getMessage(), 0, 2000),
            ])->save();
            throw $e;
        }
    }

    public function reverseSettlement(PayrollSettlement $settlement, User $actor, string $reason): JournalEntry
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'A reversal reason is required.']);
        }
        if ($settlement->status !== 'posted' || ! $settlement->journal_entry_id) {
            throw ValidationException::withMessages(['settlement' => 'Only posted salary settlements can be reversed.']);
        }

        $journal = JournalEntry::findOrFail($settlement->journal_entry_id);
        $reversal = $this->journals->reverse($journal, $reason, $actor);
        $attempt = $this->attemptFor($settlement, 'salary_settlement');
        if ($attempt?->status === 'posted') {
            $this->attempts->reversed($attempt, $reversal, $actor);
        }

        $settlement->forceFill([
            'status' => 'reversed',
            'accounting_status' => self::STATUS_REVERSED,
            'reversal_journal_entry_id' => $reversal->id,
            'reversed_at' => now(),
            'reversed_by' => $actor->id,
            'reversal_reason' => $reason,
        ])->save();
        $this->refreshSettlementState($settlement->payrollRun);

        $this->audit('PAYROLL_SETTLEMENT_REVERSED', $settlement, $actor, [
            'journal_entry_id' => $journal->id,
            'reversal_journal_entry_id' => $reversal->id,
            'reason' => $reason,
        ], LogSeverity::WARNING);

        return $reversal;
    }

    public function settleStatutoryLiability(PayrollRun $run, array $data, User $actor): PayrollStatutorySettlement
    {
        if ((string) $run->accounting_status !== self::STATUS_POSTED) {
            throw ValidationException::withMessages(['payroll' => 'Post the payroll accrual before settling statutory liabilities.']);
        }

        $type = $this->statutoryType((string) ($data['liability_type'] ?? ''));
        $amount = round((float) ($data['amount'] ?? 0), 2);
        $outstanding = $this->statutoryOutstanding($run, $type);
        if ($amount <= 0 || $amount > $outstanding + 0.005) {
            throw ValidationException::withMessages(['amount' => "Settlement amount must be greater than zero and not exceed {$outstanding}."]);
        }

        $paymentAccount = $this->paymentAccount((int) ($data['payment_account_id'] ?? 0));
        $liabilityAccount = $this->statutoryLiabilityAccount($type);

        $settlement = PayrollStatutorySettlement::create([
            'payroll_run_id' => $run->id,
            'settlement_number' => $this->nextStatutorySettlementNumber($type),
            'liability_type' => $type,
            'settlement_date' => $data['settlement_date'] ?? now()->toDateString(),
            'amount' => $amount,
            'payment_account_id' => $paymentAccount->id,
            'status' => 'draft',
            'accounting_status' => self::STATUS_PENDING,
            'notes' => $data['notes'] ?? null,
            'created_by' => $actor->id,
        ]);

        $postingType = "statutory_{$type}_settlement";
        $attempt = $this->attempts->pending('PAYROLL_STATUTORY_SETTLEMENT', $settlement, $postingType, postingSnapshot: [
            'payroll_run_id' => $run->id,
            'pay_period' => $run->pay_period,
            'liability_type' => $type,
            'amount' => $amount,
            'payment_account_id' => $paymentAccount->id,
        ], actor: $actor);

        try {
            $attempt = $this->attempts->processing($attempt, $actor);
            $key = $this->idempotency->key($settlement, $settlement->id, $postingType);
            $journal = DB::transaction(function () use ($settlement, $run, $actor, $key, $amount, $type, $liabilityAccount, $paymentAccount) {
                $label = $this->statutoryLabel($type);
                $entry = $this->journals->createDraft([
                    'entry_date' => $settlement->settlement_date->toDateString(),
                    'reference_number' => $settlement->settlement_number,
                    'reference_type' => PayrollStatutorySettlement::class,
                    'reference_id' => $settlement->id,
                    'source_module' => 'PAYROLL_STATUTORY_SETTLEMENT',
                    'allow_control_accounts' => true,
                    'idempotency_key' => $key,
                    'description' => "{$label} remittance {$run->pay_period}",
                    'lines' => [
                        $this->line($liabilityAccount, "{$label} payable settlement {$run->pay_period}", $amount, 0, $settlement),
                        $this->line($paymentAccount, "{$label} paid {$run->pay_period}", 0, $amount, $settlement),
                    ],
                ]);

                return $this->journals->post($entry, $actor);
            });

            $this->attempts->posted($attempt, $journal, $actor);
            $settlement->forceFill([
                'status' => 'posted',
                'accounting_status' => self::STATUS_POSTED,
                'journal_entry_id' => $journal->id,
                'posted_at' => now(),
                'posted_by' => $actor->id,
                'accounting_error' => null,
            ])->save();

            $this->audit('PAYROLL_STATUTORY_SETTLEMENT_POSTED', $settlement, $actor, [
                'journal_entry_id' => $journal->id,
                'payroll_run_id' => $run->id,
                'liability_type' => $type,
                'amount' => $amount,
            ]);

            return $settlement->fresh(['journalEntry', 'paymentAccount']);
        } catch (Throwable $e) {
            $this->attempts->failed($attempt, $e, actor: $actor);
            $settlement->forceFill([
                'status' => 'failed',
                'accounting_status' => self::STATUS_FAILED,
                'accounting_error' => mb_substr($e->getMessage(), 0, 2000),
            ])->save();
            throw $e;
        }
    }

    public function reverseStatutorySettlement(PayrollStatutorySettlement $settlement, User $actor, string $reason): JournalEntry
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'A reversal reason is required.']);
        }
        if ($settlement->status !== 'posted' || ! $settlement->journal_entry_id) {
            throw ValidationException::withMessages(['settlement' => 'Only posted statutory settlements can be reversed.']);
        }

        $journal = JournalEntry::findOrFail($settlement->journal_entry_id);
        $reversal = $this->journals->reverse($journal, $reason, $actor);
        $attempt = $this->attemptFor($settlement, 'statutory_'.$settlement->liability_type.'_settlement');
        if ($attempt?->status === 'posted') {
            $this->attempts->reversed($attempt, $reversal, $actor);
        }

        $settlement->forceFill([
            'status' => 'reversed',
            'accounting_status' => self::STATUS_REVERSED,
            'reversal_journal_entry_id' => $reversal->id,
            'reversed_at' => now(),
            'reversed_by' => $actor->id,
            'reversal_reason' => $reason,
        ])->save();

        $this->audit('PAYROLL_STATUTORY_SETTLEMENT_REVERSED', $settlement, $actor, [
            'journal_entry_id' => $journal->id,
            'reversal_journal_entry_id' => $reversal->id,
            'liability_type' => $settlement->liability_type,
            'reason' => $reason,
        ], LogSeverity::WARNING);

        return $reversal;
    }

    public function outstandingNetPay(PayrollRun $run): float
    {
        $net = round((float) $run->records()->sum('net_pay'), 2);
        $settled = round((float) $run->settlements()->where('status', 'posted')->sum('amount'), 2);

        return max(0, round($net - $settled, 2));
    }

    public function statutoryLiability(PayrollRun $run, string $type): float
    {
        $type = $this->statutoryType($type);

        return match ($type) {
            PayrollStatutorySettlement::TYPE_PAYE => round((float) $run->records()->sum('tax'), 2),
            PayrollStatutorySettlement::TYPE_PENSION => round((float) $run->records()->sum('ssnit_employee') + (float) $run->records()->sum('ssnit_employer'), 2),
        };
    }

    public function statutoryOutstanding(PayrollRun $run, string $type): float
    {
        $type = $this->statutoryType($type);
        $settled = round((float) $run->statutorySettlements()
            ->where('liability_type', $type)
            ->where('status', 'posted')
            ->sum('amount'), 2);

        return max(0, round($this->statutoryLiability($run, $type) - $settled, 2));
    }

    protected function accrualLines(PayrollRun $run): array
    {
        $preview = $this->preview($run);

        return array_values(array_filter([
            $this->line($this->requiredAccount('payroll_expense_account_id'), 'Salary expense ' . $run->pay_period, $preview['debits']['payroll_expense'], 0, $run),
            $this->line($this->requiredAccount('employer_pension_expense_account_id'), 'Employer pension expense ' . $run->pay_period, $preview['debits']['employer_pension_expense'], 0, $run),
            $this->line($this->requiredAccount('payroll_payable_account_id'), 'Net salary payable ' . $run->pay_period, 0, $preview['credits']['payroll_payable'], $run),
            $this->line($this->requiredAccount('paye_payable_account_id'), 'PAYE payable ' . $run->pay_period, 0, $preview['credits']['paye_payable'], $run),
            $this->line($this->requiredAccount('pension_payable_account_id'), 'Pension / SSNIT payable ' . $run->pay_period, 0, $preview['credits']['pension_payable'], $run),
            $this->line($this->requiredAccount('payroll_other_deductions_payable_account_id'), 'Other payroll deductions payable ' . $run->pay_period, 0, $preview['credits']['other_deductions_payable'], $run),
        ], fn (array $line) => (float) $line['debit'] > 0 || (float) $line['credit'] > 0));
    }

    protected function line(Account $account, string $description, float $debit, float $credit, PayrollRun|PayrollSettlement|PayrollStatutorySettlement $source): array
    {
        return [
            'account_id' => $account->id,
            'description' => $description,
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
            'reference_type' => $source::class,
            'reference_id' => $source->id,
        ];
    }

    protected function refreshSettlementState(PayrollRun $run): void
    {
        $settled = round((float) $run->settlements()->where('status', 'posted')->sum('amount'), 2);
        $net = round((float) $run->records()->sum('net_pay'), 2);
        $status = $settled <= 0 ? 'open' : ($settled + 0.005 >= $net ? 'settled' : 'partial');

        $run->forceFill([
            'settled_amount' => $settled,
            'settlement_status' => $status,
            'status' => $status === 'settled' ? 'paid' : 'posted',
        ])->save();

        if ($status === 'settled') {
            $run->records()->update(['status' => 'paid', 'paid_at' => now()]);
        } else {
            $run->records()->whereIn('status', ['paid', 'posted'])->update(['status' => 'posted', 'paid_at' => null]);
        }
    }

    protected function requiredAccount(string $key): Account
    {
        $account = $this->settings->account($key);
        if (! $account) {
            throw ValidationException::withMessages(['account' => "Accounting setting {$key} is not configured."]);
        }
        if (! $account->is_active) {
            throw ValidationException::withMessages(['account' => "Accounting account {$account->display_name} is inactive."]);
        }

        return $account;
    }

    protected function paymentAccount(int $accountId): Account
    {
        $account = $accountId > 0 ? Account::find($accountId) : $this->settings->account('default_bank_account_id');
        if (! $account || ! $account->is_active) {
            throw ValidationException::withMessages(['payment_account_id' => 'Choose an active cash or bank account.']);
        }
        if (! $account->is_cash_account && ! $account->is_bank_account) {
            throw ValidationException::withMessages(['payment_account_id' => 'Salary settlement must credit a cash or bank account.']);
        }

        return $account;
    }

    protected function statutoryType(string $type): string
    {
        $type = strtolower(trim($type));
        if (! in_array($type, PayrollStatutorySettlement::TYPES, true)) {
            throw ValidationException::withMessages(['liability_type' => 'Choose PAYE or pension / SSNIT.']);
        }

        return $type;
    }

    protected function statutoryLabel(string $type): string
    {
        return $type === PayrollStatutorySettlement::TYPE_PAYE ? 'PAYE' : 'Pension / SSNIT';
    }

    protected function statutoryLiabilityAccount(string $type): Account
    {
        return $this->requiredAccount($type === PayrollStatutorySettlement::TYPE_PAYE
            ? 'paye_payable_account_id'
            : 'pension_payable_account_id');
    }

    protected function attemptFor(PayrollRun|PayrollSettlement|PayrollStatutorySettlement $source, string $type)
    {
        return \App\Models\AccountingPostingAttempt::query()
            ->where('idempotency_key', $this->idempotency->key($source, $source->id, $type))
            ->first();
    }

    protected function nextSettlementNumber(): string
    {
        $prefix = 'PAYSET-' . now()->format('Y') . '-';
        $last = PayrollSettlement::query()
            ->where('settlement_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('settlement_number')
            ->value('settlement_number');
        $next = $last ? ((int) substr($last, -6)) + 1 : 1;

        return $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    protected function nextStatutorySettlementNumber(string $type): string
    {
        $prefix = strtoupper($type === PayrollStatutorySettlement::TYPE_PAYE ? 'PAYE' : 'SSNIT') . '-' . now()->format('Y') . '-';
        $last = PayrollStatutorySettlement::query()
            ->where('settlement_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('settlement_number')
            ->value('settlement_number');
        $next = $last ? ((int) substr($last, -6)) + 1 : 1;

        return $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    protected function audit(string $action, object $subject, User $actor, array $context = [], LogSeverity $severity = LogSeverity::INFO): void
    {
        $this->log->log(LogModule::ACCOUNTING, $action, array_merge([
            'severity' => $severity,
            'causer' => $actor,
        ], $context), $subject, str_replace('_', ' ', ucfirst(strtolower($action))));
    }
}
