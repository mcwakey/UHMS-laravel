<?php

namespace App\Services;

use App\Enums\Accounting\JournalEntryStatus;
use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalEntryService
{
    public function __construct(
        protected AccountingPeriodService $periodService,
        protected AccountingSettingsService $settingsService,
    ) {}

    public function createDraft(array $data): JournalEntry
    {
        return DB::transaction(function () use ($data) {
            $period = $this->periodService->ensureDateIsPostable($data['entry_date']);
            $lines = $this->validatedLines($data['lines'] ?? []);

            $entry = JournalEntry::create([
                'journal_number' => $this->nextJournalNumber(),
                'entry_date' => $data['entry_date'],
                'fiscal_year_id' => $period->fiscal_year_id,
                'accounting_period_id' => $period->id,
                'reference_number' => $data['reference_number'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'source_module' => $data['source_module'] ?? 'MANUAL',
                'description' => $data['description'],
                'status' => JournalEntryStatus::DRAFT,
                'created_by' => auth()->id(),
            ]);

            $this->storeLines($entry, $lines);
            $entry->load('lines.account');

            $this->log($entry, 'JOURNAL_ENTRY_CREATED', LogSeverity::NOTICE, newValues: $entry->toArray());

            return $entry;
        });
    }

    public function updateDraft(JournalEntry $entry, array $data): JournalEntry
    {
        if ($entry->status !== JournalEntryStatus::DRAFT) {
            throw ValidationException::withMessages(['journal' => 'Only draft journal entries can be edited.']);
        }

        return DB::transaction(function () use ($entry, $data) {
            $old = $entry->load('lines')->toArray();
            $period = $this->periodService->ensureDateIsPostable($data['entry_date']);
            $lines = $this->validatedLines($data['lines'] ?? []);

            $entry->update([
                'entry_date' => $data['entry_date'],
                'fiscal_year_id' => $period->fiscal_year_id,
                'accounting_period_id' => $period->id,
                'reference_number' => $data['reference_number'] ?? null,
                'description' => $data['description'],
            ]);

            $entry->lines()->delete();
            $this->storeLines($entry, $lines);
            $entry->load('lines.account');

            $this->log($entry, 'JOURNAL_ENTRY_UPDATED', LogSeverity::NOTICE, oldValues: $old, newValues: $entry->toArray());

            return $entry;
        });
    }

    public function post(JournalEntry $entry, User $user): JournalEntry
    {
        if ($entry->status !== JournalEntryStatus::DRAFT) {
            throw ValidationException::withMessages(['journal' => 'Only draft journal entries can be posted.']);
        }

        return DB::transaction(function () use ($entry, $user) {
            $entry->load('lines.account');
            $this->periodService->ensureDateIsPostable($entry->entry_date);
            $this->validatedLines($entry->lines->map(fn ($line) => $line->only([
                'account_id',
                'description',
                'debit',
                'credit',
                'department_id',
                'patient_id',
                'visit_id',
                'invoice_id',
                'supplier_id',
                'sponsor_id',
                'insurance_provider_id',
            ]))->all());

            $old = $entry->getOriginal();
            $entry->update([
                'status' => JournalEntryStatus::POSTED,
                'posted_at' => now(),
                'posted_by' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            $this->log($entry, 'JOURNAL_ENTRY_POSTED', LogSeverity::WARNING, oldValues: $old, newValues: $entry->getAttributes());

            return $entry->refresh()->load('lines.account');
        });
    }

    public function reverse(JournalEntry $entry, string $reason, User $user): JournalEntry
    {
        if ($entry->status !== JournalEntryStatus::POSTED) {
            throw ValidationException::withMessages(['journal' => 'Only posted journal entries can be reversed.']);
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'A reversal reason is required.']);
        }

        return DB::transaction(function () use ($entry, $reason, $user) {
            $period = $this->periodService->ensureDateIsPostable(now());
            $entry->load('lines');

            $reversal = JournalEntry::create([
                'journal_number' => $this->nextJournalNumber(),
                'entry_date' => now()->toDateString(),
                'fiscal_year_id' => $period->fiscal_year_id,
                'accounting_period_id' => $period->id,
                'reference_number' => $entry->journal_number,
                'reference_type' => JournalEntry::class,
                'reference_id' => $entry->id,
                'source_module' => 'REVERSAL',
                'description' => 'Reversal of ' . $entry->journal_number . ': ' . $entry->description,
                'status' => JournalEntryStatus::POSTED,
                'posted_at' => now(),
                'posted_by' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'created_by' => $user->id,
                'reversed_entry_id' => $entry->id,
                'reversal_reason' => $reason,
            ]);

            foreach ($entry->lines as $index => $line) {
                $reversal->lines()->create([
                    'account_id' => $line->account_id,
                    'description' => 'Reversal: ' . ($line->description ?: $entry->description),
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'department_id' => $line->department_id,
                    'patient_id' => $line->patient_id,
                    'visit_id' => $line->visit_id,
                    'invoice_id' => $line->invoice_id,
                    'supplier_id' => $line->supplier_id,
                    'sponsor_id' => $line->sponsor_id,
                    'insurance_provider_id' => $line->insurance_provider_id,
                    'reference_type' => $line->reference_type,
                    'reference_id' => $line->reference_id,
                    'line_order' => $index + 1,
                ]);
            }

            $old = $entry->getOriginal();
            $entry->update([
                'status' => JournalEntryStatus::REVERSED,
                'reversal_reason' => $reason,
            ]);

            $this->log($entry, 'JOURNAL_ENTRY_REVERSED', LogSeverity::WARNING, oldValues: $old, newValues: $entry->getAttributes(), extra: [
                'reversal_journal_entry_id' => $reversal->id,
                'reason' => $reason,
            ]);

            return $reversal->load('lines.account');
        });
    }

    public function cancelDraft(JournalEntry $entry, User $user): JournalEntry
    {
        if ($entry->status !== JournalEntryStatus::DRAFT) {
            throw ValidationException::withMessages(['journal' => 'Only draft journal entries can be cancelled.']);
        }

        $old = $entry->getOriginal();
        $entry->update(['status' => JournalEntryStatus::CANCELLED]);

        $this->log($entry, 'JOURNAL_ENTRY_CANCELLED', LogSeverity::WARNING, oldValues: $old, newValues: $entry->getAttributes(), extra: [
            'cancelled_by' => $user->id,
        ]);

        return $entry;
    }

    public function validateBalanced(array $lines): void
    {
        $this->validatedLines($lines);
    }

    protected function validatedLines(array $lines): array
    {
        $lines = collect($lines)
            ->filter(fn ($line) => ! empty($line['account_id']) || (float) ($line['debit'] ?? 0) > 0 || (float) ($line['credit'] ?? 0) > 0)
            ->values()
            ->all();

        if (count($lines) < 2) {
            throw ValidationException::withMessages(['lines' => 'A journal entry must have at least two lines.']);
        }

        $accounts = Account::query()
            ->whereIn('id', collect($lines)->pluck('account_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($lines as $index => $line) {
            $row = $index + 1;
            $account = $accounts->get((int) ($line['account_id'] ?? 0));
            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);

            if (! $account) {
                throw ValidationException::withMessages(["lines.$index.account_id" => "Line {$row}: choose an account."]);
            }

            if (! $account->is_active) {
                throw ValidationException::withMessages(["lines.$index.account_id" => "Line {$row}: inactive accounts cannot be used."]);
            }

            if ($account->is_control_account && ! $this->settingsService->bool('allow_manual_control_account_posting')) {
                throw ValidationException::withMessages([
                    "lines.$index.account_id" => "Line {$row}: control accounts are locked for manual journals by accounting settings.",
                ]);
            }

            if ($debit > 0 && $credit > 0) {
                throw ValidationException::withMessages(["lines.$index.debit" => "Line {$row}: enter either debit or credit, not both."]);
            }

            if ($debit <= 0 && $credit <= 0) {
                throw ValidationException::withMessages(["lines.$index.debit" => "Line {$row}: enter either a debit or credit amount."]);
            }

            $totalDebit += $debit;
            $totalCredit += $credit;

            $lines[$index]['debit'] = $debit;
            $lines[$index]['credit'] = $credit;
        }

        if (abs($totalDebit - $totalCredit) >= 0.005) {
            throw ValidationException::withMessages([
                'lines' => 'The journal entry is not balanced. Total debits must equal total credits.',
            ]);
        }

        return $lines;
    }

    protected function storeLines(JournalEntry $entry, array $lines): void
    {
        foreach ($lines as $index => $line) {
            $entry->lines()->create([
                'account_id' => $line['account_id'],
                'description' => $line['description'] ?? null,
                'debit' => $line['debit'],
                'credit' => $line['credit'],
                'department_id' => $line['department_id'] ?? null,
                'patient_id' => $line['patient_id'] ?? null,
                'visit_id' => $line['visit_id'] ?? null,
                'invoice_id' => $line['invoice_id'] ?? null,
                'supplier_id' => $line['supplier_id'] ?? null,
                'sponsor_id' => $line['sponsor_id'] ?? null,
                'insurance_provider_id' => $line['insurance_provider_id'] ?? null,
                'reference_type' => $line['reference_type'] ?? null,
                'reference_id' => $line['reference_id'] ?? null,
                'line_order' => $index + 1,
            ]);
        }
    }

    protected function nextJournalNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "JE-{$year}-";
        $last = JournalEntry::query()
            ->where('journal_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('journal_number')
            ->value('journal_number');

        $next = $last ? ((int) substr($last, -6)) + 1 : 1;

        return $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    protected function log(
        JournalEntry $entry,
        string $action,
        LogSeverity $severity,
        array $oldValues = [],
        array $newValues = [],
        array $extra = [],
    ): void {
        app(ActivityLogService::class)->log(LogModule::ACCOUNTING, $action, array_merge($extra, [
            'severity' => $severity,
            'journal_entry_id' => $entry->id,
            'fiscal_year_id' => $entry->fiscal_year_id,
            'accounting_period_id' => $entry->accounting_period_id,
            'source_module' => $entry->source_module,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]), $entry, str_replace('_', ' ', ucfirst(strtolower($action))) . ': ' . $entry->journal_number);
    }
}
