<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationMatch;
use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\JournalEntryLine;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Prepares, matches, approves, reopens and reverses formal bank reconciliations.
 *
 *   Adjusted statement balance = statement closing + outstanding deposits
 *       - outstanding withdrawals +/- approved adjustments
 *   Book balance                = GL cash/bank balance at period end
 *   Difference                  = adjusted statement balance - book balance
 *
 * Money precision follows the project's rounding rules; PHP floats are never
 * treated as authoritative — every figure is round()-ed to 2 dp on write.
 */
class BankReconciliationService
{
    /** Book transaction types that may be matched to statement lines. */
    public const MATCHABLE_TYPES = [
        JournalEntryLine::class,
        \App\Models\Payment::class,
    ];

    public function __construct(
        protected GeneralLedgerService $generalLedger,
    ) {}

    /**
     * Create or refresh a reconciliation for a period and compute all balances.
     */
    public function prepare(BankAccount $account, array $data, User $actor): BankReconciliation
    {
        $periodStart = Carbon::parse($data['period_start'])->toDateString();
        $periodEnd = Carbon::parse($data['period_end'])->toDateString();

        if ($periodEnd < $periodStart) {
            throw ValidationException::withMessages([
                'period_end' => __('accounting.period_end_before_start'),
            ]);
        }

        $reconciliation = BankReconciliation::firstOrNew([
            'bank_account_id' => $account->id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ]);

        if ($reconciliation->exists && $reconciliation->isLocked()) {
            throw ValidationException::withMessages([
                'status' => __('accounting.reconciliation_locked'),
            ]);
        }

        $statement = $this->statementBalances($account, $periodStart, $periodEnd, $data);

        $reconciliation->fill([
            'statement_opening_balance' => $statement['opening'],
            'statement_closing_balance' => $statement['closing'],
            'status' => BankReconciliation::STATUS_PREPARED,
            'prepared_by' => $actor->id,
            'prepared_at' => now(),
            'notes' => $data['notes'] ?? $reconciliation->notes,
        ]);
        $reconciliation->save();

        $this->recompute($reconciliation);

        app(ActivityLogService::class)->log(LogModule::ACCOUNTING, 'BANK_RECONCILIATION_PREPARED', [
            'severity' => LogSeverity::NOTICE,
            'causer' => $actor,
            'metadata' => [
                'bank_account_id' => $account->id,
                'bank_reconciliation_id' => $reconciliation->id,
                'difference' => (float) $reconciliation->difference,
            ],
        ], $reconciliation, 'Bank reconciliation prepared: ' . $account->name);

        return $reconciliation->refresh();
    }

    /**
     * Recompute book balances, outstanding items, adjustments and the difference.
     */
    public function recompute(BankReconciliation $reconciliation): BankReconciliation
    {
        $account = $reconciliation->bankAccount()->with('glAccount')->first();
        $glAccount = $account->glAccount;

        $bookClosing = $glAccount
            ? (float) $this->generalLedger->report($glAccount, ['date_to' => $reconciliation->period_end->toDateString()])['closing_balance']
            : 0.0;
        $bookOpening = $glAccount
            ? (float) $this->generalLedger->report($glAccount, ['date_to' => $reconciliation->period_start->copy()->subDay()->toDateString()])['closing_balance']
            : 0.0;

        $outstanding = $this->outstandingBookItems($reconciliation, $glAccount?->id);
        $adjustments = $this->approvedAdjustmentsTotal($reconciliation);

        $adjustedStatement = round(
            (float) $reconciliation->statement_closing_balance
            + $outstanding['deposits']
            - $outstanding['withdrawals']
            + $adjustments,
            2
        );
        $difference = round($adjustedStatement - $bookClosing, 2);

        $reconciliation->update([
            'book_opening_balance' => round($bookOpening, 2),
            'book_closing_balance' => round($bookClosing, 2),
            'outstanding_deposits_total' => $outstanding['deposits'],
            'outstanding_withdrawals_total' => $outstanding['withdrawals'],
            'adjustments_total' => $adjustments,
            'difference' => $difference,
        ]);

        return $reconciliation;
    }

    /**
     * Confirm a match between a statement line and a book transaction.
     */
    public function match(
        BankReconciliation $reconciliation,
        BankStatementLine $line,
        string $matchableType,
        int $matchableId,
        float $amount,
        string $method,
        User $actor,
    ): BankReconciliationMatch {
        $this->assertEditable($reconciliation);

        if ($line->bank_account_id !== $reconciliation->bank_account_id) {
            throw ValidationException::withMessages(['line' => __('accounting.line_wrong_account')]);
        }
        if (! in_array($matchableType, self::MATCHABLE_TYPES, true)) {
            throw ValidationException::withMessages(['matchable' => __('accounting.unsupported_match_source')]);
        }

        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['matched_amount' => __('accounting.match_amount_positive')]);
        }
        if ($amount > $line->remainingToMatch() + 0.0001) {
            throw ValidationException::withMessages(['matched_amount' => __('accounting.match_exceeds_line')]);
        }

        return DB::transaction(function () use ($reconciliation, $line, $matchableType, $matchableId, $amount, $method, $actor) {
            $match = BankReconciliationMatch::create([
                'bank_reconciliation_id' => $reconciliation->id,
                'bank_statement_line_id' => $line->id,
                'matchable_type' => $matchableType,
                'matchable_id' => $matchableId,
                'matched_amount' => $amount,
                'match_method' => in_array($method, BankReconciliationMatch::METHODS, true) ? $method : BankReconciliationMatch::METHOD_MANUAL,
                'status' => BankReconciliationMatch::STATUS_ACTIVE,
                'matched_by' => $actor->id,
                'matched_at' => now(),
            ]);

            $this->refreshLineMatchState($line);
            $this->recompute($reconciliation);

            app(ActivityLogService::class)->log(LogModule::ACCOUNTING, 'BANK_STATEMENT_LINE_MATCHED', [
                'severity' => LogSeverity::NOTICE,
                'causer' => $actor,
                'metadata' => [
                    'bank_reconciliation_id' => $reconciliation->id,
                    'bank_statement_line_id' => $line->id,
                    'matchable_type' => $matchableType,
                    'matchable_id' => $matchableId,
                    'matched_amount' => $amount,
                    'match_method' => $match->match_method,
                ],
            ], $match, 'Bank statement line matched');

            return $match;
        });
    }

    public function unmatch(BankReconciliationMatch $match, string $reason, User $actor): BankReconciliationMatch
    {
        $reconciliation = $match->reconciliation;
        $this->assertEditable($reconciliation);

        if (! $match->isActive()) {
            return $match;
        }

        return DB::transaction(function () use ($match, $reconciliation, $reason, $actor) {
            $match->update([
                'status' => BankReconciliationMatch::STATUS_REVERSED,
                'unmatched_by' => $actor->id,
                'unmatched_at' => now(),
                'unmatch_reason' => $reason,
            ]);

            $this->refreshLineMatchState($match->line);
            $this->recompute($reconciliation);

            app(ActivityLogService::class)->log(LogModule::ACCOUNTING, 'BANK_STATEMENT_LINE_UNMATCHED', [
                'severity' => LogSeverity::WARNING,
                'causer' => $actor,
                'reason' => $reason,
                'metadata' => [
                    'bank_reconciliation_id' => $reconciliation->id,
                    'bank_statement_line_id' => $match->bank_statement_line_id,
                ],
            ], $match, 'Bank statement line unmatched');

            return $match->refresh();
        });
    }

    public function approve(BankReconciliation $reconciliation, User $actor, float $tolerance = 0.0, bool $override = false): BankReconciliation
    {
        if (! in_array($reconciliation->status, [BankReconciliation::STATUS_PREPARED, BankReconciliation::STATUS_REOPENED], true)) {
            throw ValidationException::withMessages(['status' => __('accounting.reconciliation_not_preparable')]);
        }

        $this->recompute($reconciliation);

        if (abs((float) $reconciliation->difference) > abs($tolerance) + 0.0001 && ! $override) {
            throw ValidationException::withMessages([
                'difference' => __('accounting.reconciliation_unexplained_difference', [
                    'difference' => number_format((float) $reconciliation->difference, 2),
                ]),
            ]);
        }

        $reconciliation->update([
            'status' => BankReconciliation::STATUS_APPROVED,
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ]);

        app(ActivityLogService::class)->log(LogModule::ACCOUNTING, 'BANK_RECONCILIATION_APPROVED', [
            'severity' => LogSeverity::WARNING,
            'causer' => $actor,
            'metadata' => [
                'bank_reconciliation_id' => $reconciliation->id,
                'difference' => (float) $reconciliation->difference,
                'override' => $override,
            ],
        ], $reconciliation, 'Bank reconciliation approved');

        return $reconciliation->refresh();
    }

    public function reopen(BankReconciliation $reconciliation, string $reason, User $actor): BankReconciliation
    {
        if ($reconciliation->status !== BankReconciliation::STATUS_APPROVED) {
            throw ValidationException::withMessages(['status' => __('accounting.only_approved_can_reopen')]);
        }
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => __('accounting.reopen_reason_required')]);
        }

        $reconciliation->update([
            'status' => BankReconciliation::STATUS_REOPENED,
            'reopened_by' => $actor->id,
            'reopened_at' => now(),
            'reopen_reason' => $reason,
        ]);

        app(ActivityLogService::class)->log(LogModule::ACCOUNTING, 'BANK_RECONCILIATION_REOPENED', [
            'severity' => LogSeverity::WARNING,
            'causer' => $actor,
            'reason' => $reason,
            'metadata' => ['bank_reconciliation_id' => $reconciliation->id],
        ], $reconciliation, 'Bank reconciliation reopened');

        return $reconciliation->refresh();
    }

    public function reverse(BankReconciliation $reconciliation, string $reason, User $actor): BankReconciliation
    {
        if (! in_array($reconciliation->status, [BankReconciliation::STATUS_APPROVED, BankReconciliation::STATUS_REOPENED], true)) {
            throw ValidationException::withMessages(['status' => __('accounting.reconciliation_not_reversible')]);
        }
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => __('accounting.reversal_reason_required')]);
        }

        $reconciliation->update([
            'status' => BankReconciliation::STATUS_REVERSED,
            'reversed_by' => $actor->id,
            'reversed_at' => now(),
            'reversal_reason' => $reason,
        ]);

        app(ActivityLogService::class)->log(LogModule::ACCOUNTING, 'BANK_RECONCILIATION_REVERSED', [
            'severity' => LogSeverity::WARNING,
            'causer' => $actor,
            'reason' => $reason,
            'metadata' => ['bank_reconciliation_id' => $reconciliation->id],
        ], $reconciliation, 'Bank reconciliation reversed');

        return $reconciliation->refresh();
    }

    /* ── Internals ──────────────────────────────────────────────── */

    protected function assertEditable(BankReconciliation $reconciliation): void
    {
        if (! $reconciliation->isEditable()) {
            throw ValidationException::withMessages([
                'status' => __('accounting.reconciliation_locked'),
            ]);
        }
    }

    protected function refreshLineMatchState(BankStatementLine $line): void
    {
        $matched = round((float) $line->activeMatches()->sum('matched_amount'), 2);
        $gross = $line->grossAmount();
        $remaining = round($gross - $matched, 2);

        $status = match (true) {
            $matched <= 0 => BankStatementLine::MATCH_UNMATCHED,
            $remaining <= 0.0001 => BankStatementLine::MATCH_MATCHED,
            default => BankStatementLine::MATCH_PARTIAL,
        };

        $line->update([
            'matched_amount' => $matched,
            'unmatched_amount' => max(0, $remaining),
            'match_status' => $status,
        ]);
    }

    protected function statementBalances(BankAccount $account, string $periodStart, string $periodEnd, array $data): array
    {
        if (array_key_exists('statement_opening_balance', $data) && array_key_exists('statement_closing_balance', $data)
            && $data['statement_opening_balance'] !== null && $data['statement_closing_balance'] !== null) {
            return [
                'opening' => round((float) $data['statement_opening_balance'], 2),
                'closing' => round((float) $data['statement_closing_balance'], 2),
            ];
        }

        // Fall back to the most recent usable import covering the period end.
        $import = BankStatementImport::query()
            ->where('bank_account_id', $account->id)
            ->whereIn('status', BankStatementImport::USABLE_STATUSES)
            ->whereDate('period_end', '<=', $periodEnd)
            ->orderByDesc('period_end')
            ->orderByDesc('id')
            ->first();

        return [
            'opening' => round((float) ($import?->opening_balance ?? 0), 2),
            'closing' => round((float) ($import?->closing_balance ?? 0), 2),
        ];
    }

    /**
     * Book transactions on the bank GL account up to period end that have not
     * been matched to a statement line: unmatched debits are deposits in
     * transit, unmatched credits are outstanding withdrawals/cheques.
     */
    protected function outstandingBookItems(BankReconciliation $reconciliation, ?int $glAccountId): array
    {
        if (! $glAccountId) {
            return ['deposits' => 0.0, 'withdrawals' => 0.0];
        }

        $matchedIds = BankReconciliationMatch::query()
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->where('status', BankReconciliationMatch::STATUS_ACTIVE)
            ->where('matchable_type', JournalEntryLine::class)
            ->pluck('matchable_id')
            ->all();

        $rows = JournalEntryLine::query()
            ->where('account_id', $glAccountId)
            ->when(! empty($matchedIds), fn ($q) => $q->whereNotIn('id', $matchedIds))
            ->whereHas('journalEntry', function ($q) use ($reconciliation) {
                $q->ledgerAffecting()->whereDate('entry_date', '<=', $reconciliation->period_end->toDateString());
            })
            ->selectRaw('SUM(debit) as debit_total, SUM(credit) as credit_total')
            ->first();

        return [
            'deposits' => round((float) ($rows->debit_total ?? 0), 2),
            'withdrawals' => round((float) ($rows->credit_total ?? 0), 2),
        ];
    }

    protected function approvedAdjustmentsTotal(BankReconciliation $reconciliation): float
    {
        $total = 0.0;
        $adjustments = $reconciliation->adjustments()
            ->whereIn('status', [
                \App\Models\BankReconciliationAdjustment::STATUS_APPROVED,
                \App\Models\BankReconciliationAdjustment::STATUS_POSTED,
            ])
            ->get();

        foreach ($adjustments as $adjustment) {
            $total += $adjustment->signedBankEffect();
        }

        return round($total, 2);
    }
}
