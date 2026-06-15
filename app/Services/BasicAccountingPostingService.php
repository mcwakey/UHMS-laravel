<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\AccountingPostingAttempt;
use App\Models\FinancialEntry;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class BasicAccountingPostingService
{
    public function __construct(
        protected ModuleService $modules,
        protected AccountingPeriodService $periods,
        protected PostingTemplateService $templates,
        protected AccountingPostingService $posting,
        protected AccountingPostingAttemptService $attempts,
        protected JournalEntryService $journals,
        protected ActivityLogService $activityLog,
    ) {}

    public function eligibility(FinancialEntry $entry): array
    {
        $reasons = $this->preconditionErrors($entry);
        $preview = null;

        if (! $reasons) {
            try {
                $this->periods->ensureDateIsPostable($entry->entry_date);
                $preview = $this->templates->preview($entry->loadMissing('category'));
            } catch (Throwable $error) {
                $reasons[] = $error->getMessage();
            }
        }

        return [
            'eligible' => $reasons === [],
            'reasons' => $reasons,
            'preview' => $preview,
        ];
    }

    public function preview(FinancialEntry $entry, ?User $actor = null, bool $audit = true): array
    {
        $result = $this->eligibility($entry);
        if ($audit) {
            $this->audit($entry, 'BASIC_ENTRY_POSTING_PREVIEWED', $actor, [
                'eligible' => $result['eligible'],
                'reasons' => $result['reasons'],
            ]);
        }
        return $result;
    }

    public function post(FinancialEntry $entry, User $actor): array
    {
        $entry->refresh();
        if ($entry->journal_entry_id && $entry->accounting_status === 'posted') {
            return ['success' => true, 'entry' => $entry, 'journal' => $entry->journalEntry, 'idempotent' => true];
        }

        $errors = $this->preconditionErrors($entry);
        if ($errors) {
            return ['success' => false, 'entry' => $entry, 'error' => implode(' ', $errors)];
        }

        try {
            $resolved = $this->templates->preview($entry->loadMissing('category'));
        } catch (Throwable $error) {
            $this->retainPreparationFailure($entry, $actor, $error);
            return ['success' => false, 'entry' => $entry->refresh(), 'error' => $error->getMessage()];
        }

        try {
            $journal = DB::transaction(function () use ($entry, $resolved, $actor) {
                $journal = $this->posting->postFromSource('BASIC_ACCOUNTING', $entry, $resolved['lines'], [
                    'posting_type' => $this->postingType($entry),
                    'posting_version' => max(1, (int) ($entry->posting_version ?: 1)),
                    'entry_date' => $entry->entry_date->toDateString(),
                    'description' => "Basic accounting {$this->entryType($entry)} {$entry->entry_number}: {$entry->description}",
                    'reference_number' => $entry->reference_number ?: $entry->entry_number,
                    'template_snapshot' => $resolved['snapshot'],
                ]);

                $entry->update([
                    'approval_status' => 'approved',
                    'accounting_status' => 'posted',
                    'journal_entry_id' => $journal->id,
                    'accounting_posted_at' => $journal->posted_at ?? now(),
                    'accounting_error' => null,
                    'posted_by' => $actor->id,
                    'posting_version' => max(1, (int) ($entry->posting_version ?: 1)),
                ]);
                $this->audit($entry, 'BASIC_ENTRY_POSTED_TO_GL', $actor, ['journal_entry_id' => $journal->id]);

                return $journal;
            });

            return ['success' => true, 'entry' => $entry->refresh(), 'journal' => $journal, 'idempotent' => false];
        } catch (Throwable $error) {
            $entry->update(['accounting_status' => 'failed', 'accounting_error' => mb_substr($error->getMessage(), 0, 65000)]);
            $this->audit($entry, 'BASIC_ENTRY_POSTING_FAILED', $actor, ['error' => $error->getMessage()], LogSeverity::WARNING);
            return ['success' => false, 'entry' => $entry->refresh(), 'error' => $error->getMessage()];
        }
    }

    public function reverse(FinancialEntry $entry, string $reason, User $actor): array
    {
        if ($entry->accounting_status !== 'posted' || ! $entry->journal_entry_id) {
            return ['success' => false, 'entry' => $entry, 'error' => 'Only a posted Basic Accounting entry can be reversed.'];
        }

        try {
            return DB::transaction(function () use ($entry, $reason, $actor) {
                $journal = JournalEntry::query()->lockForUpdate()->findOrFail($entry->journal_entry_id);
                $reversal = $this->journals->reverse($journal, $reason, $actor);
                $attempt = AccountingPostingAttempt::query()
                    ->where('journal_entry_id', $journal->id)
                    ->first();
                if ($attempt) {
                    $this->attempts->reversed($attempt, $reversal, $actor);
                }
                $entry->update([
                    'accounting_status' => 'reversed',
                    'reversal_journal_entry_id' => $reversal->id,
                    'reversed_at' => now(),
                    'reversed_by' => $actor->id,
                    'reversal_reason' => $reason,
                ]);
                $this->audit($entry, 'BASIC_ENTRY_POSTING_REVERSED', $actor, [
                    'journal_entry_id' => $journal->id,
                    'reversal_journal_entry_id' => $reversal->id,
                    'reason' => $reason,
                ], LogSeverity::WARNING);

                return ['success' => true, 'entry' => $entry->refresh(), 'journal' => $reversal];
            });
        } catch (Throwable $error) {
            return ['success' => false, 'entry' => $entry->refresh(), 'error' => $error->getMessage()];
        }
    }

    protected function preconditionErrors(FinancialEntry $entry): array
    {
        $errors = [];
        if (! $this->modules->enabled('accounting_basic')) {
            $errors[] = 'Basic Accounting is disabled.';
        }
        if (! $this->modules->enabled('accounting_advanced')) {
            $errors[] = 'Advanced Accounting is disabled; the entry remains available in Basic Accounting only.';
        }
        if (! $entry->approved_by && $entry->approval_status !== 'approved') {
            $errors[] = 'The Basic Accounting entry must be approved before posting.';
        }
        if ((float) $entry->amount <= 0) {
            $errors[] = 'The entry amount must be greater than zero.';
        }
        if ($entry->accounting_status === 'reversed') {
            $errors[] = 'The entry has already been reversed.';
        }
        return $errors;
    }

    protected function retainPreparationFailure(FinancialEntry $entry, User $actor, Throwable $error): void
    {
        try {
            $attempt = $this->attempts->pending(
                'BASIC_ACCOUNTING',
                $entry,
                $this->postingType($entry),
                max(1, (int) ($entry->posting_version ?: 1)),
                ['entry' => $entry->attributesToArray(), 'preparation_error' => $error->getMessage()],
                $actor,
            );
            $attempt = $this->attempts->processing($attempt, $actor);
            $this->attempts->failed($attempt, $error, context: ['stage' => 'template_resolution'], actor: $actor);
        } catch (Throwable) {
            // Preserve the source-level failure even if attempt persistence itself is unavailable.
        } finally {
            $entry->update(['accounting_status' => 'failed', 'accounting_error' => mb_substr($error->getMessage(), 0, 65000)]);
            $this->audit($entry, 'BASIC_ENTRY_POSTING_FAILED', $actor, ['error' => $error->getMessage()], LogSeverity::WARNING);
        }
    }

    protected function postingType(FinancialEntry $entry): string
    {
        return 'basic_'.$this->entryType($entry);
    }

    protected function entryType(FinancialEntry $entry): string
    {
        return $entry->type instanceof \BackedEnum ? $entry->type->value : (string) $entry->type;
    }

    protected function audit(
        FinancialEntry $entry,
        string $event,
        ?User $actor,
        array $metadata = [],
        LogSeverity $severity = LogSeverity::NOTICE,
    ): void {
        try {
            $this->activityLog->log(LogModule::ACCOUNTING, $event, [
                'severity' => $severity,
                'causer' => $actor,
                'source_type' => 'financial_entry',
                'source_id' => $entry->id,
                'metadata' => array_merge(['entry_number' => $entry->entry_number], $metadata),
            ], $entry, str_replace('_', ' ', ucfirst(strtolower($event))).": {$entry->entry_number}");
        } catch (Throwable) {
            // Audit outages must not mutate posting outcomes.
        }
    }
}
