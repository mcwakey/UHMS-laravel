<?php

namespace App\Services;

use App\Accounting\Posting\RetryResult;
use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\AccountingPostingAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class FailedPostingWorkbenchService
{
    public const RESOLUTION_TYPES = [
        'corrected_by_manual_journal',
        'source_cancelled',
        'not_required_after_review',
        'duplicate_source_record',
        'external_adjustment',
        'other',
    ];

    public function __construct(
        protected AccountingPostingHandlerRegistry $handlers,
        protected AccountingPostingAttemptService $attempts,
        protected ActivityLogService $activityLog,
    ) {}

    public function query(array $filters): Builder
    {
        return AccountingPostingAttempt::query()
            ->with(['journalEntry', 'reversalJournalEntry', 'createdBy', 'resolvedBy'])
            ->when(array_key_exists('status', $filters), function (Builder $query) use ($filters) {
                $filters['status'] === null || $filters['status'] === ''
                    ? $query->where('status', 'failed')
                    : $query->where('status', $filters['status']);
            }, fn (Builder $query) => $query->where('status', 'failed'))
            ->when($filters['source_module'] ?? null, fn (Builder $q, $value) => $q->where('source_module', $value))
            ->when($filters['source_type'] ?? null, fn (Builder $q, $value) => $q->where('source_type', 'like', '%'.$value.'%'))
            ->when($filters['posting_type'] ?? null, fn (Builder $q, $value) => $q->where('posting_type', $value))
            ->when($filters['date_from'] ?? null, fn (Builder $q, $value) => $q->whereDate('last_attempted_at', '>=', $value))
            ->when($filters['date_to'] ?? null, fn (Builder $q, $value) => $q->whereDate('last_attempted_at', '<=', $value))
            ->when($filters['attempt_count'] ?? null, fn (Builder $q, $value) => $q->where('attempt_count', '>=', (int) $value))
            ->when($filters['error_code'] ?? null, fn (Builder $q, $value) => $q->where('error_code', 'like', '%'.$value.'%'))
            ->when(isset($filters['has_journal']) && $filters['has_journal'] !== '', fn (Builder $q) => $filters['has_journal'] ? $q->whereNotNull('journal_entry_id') : $q->whereNull('journal_entry_id'))
            ->when(isset($filters['has_reversal']) && $filters['has_reversal'] !== '', fn (Builder $q) => $filters['has_reversal'] ? $q->whereNotNull('reversal_journal_entry_id') : $q->whereNull('reversal_journal_entry_id'))
            ->when($filters['resolved_by'] ?? null, fn (Builder $q, $value) => $q->where('resolved_by', $value))
            ->when($filters['waived_only'] ?? false, fn (Builder $q) => $q->where('status', 'waived'))
            ->latest('last_attempted_at')
            ->latest('id');
    }

    public function dashboard(): array
    {
        $failed = AccountingPostingAttempt::query()->where('status', 'failed');

        return [
            'failed_unresolved' => (clone $failed)->count(),
            'failed_over_7_days' => (clone $failed)->where('last_attempted_at', '<=', now()->subDays(7))->count(),
            'failed_over_30_days' => (clone $failed)->where('last_attempted_at', '<=', now()->subDays(30))->count(),
            'waived' => AccountingPostingAttempt::where('status', 'waived')->count(),
            'resolved' => AccountingPostingAttempt::where('status', 'resolved')->count(),
            'posted_after_retry' => AccountingPostingAttempt::where('status', 'posted')->where('attempt_count', '>', 1)->count(),
            'by_source_module' => (clone $failed)->selectRaw('source_module, COUNT(*) total')->groupBy('source_module')->orderBy('source_module')->pluck('total', 'source_module'),
        ];
    }

    public function retry(AccountingPostingAttempt $attempt, User $actor): RetryResult
    {
        if ($attempt->status !== 'failed') {
            throw ValidationException::withMessages(['posting' => 'Only failed posting attempts can be retried.']);
        }

        $this->audit('FAILED_POSTING_RETRY_REQUESTED', $attempt, $actor);
        $handler = $this->handlers->handlerFor($attempt);
        if (! $handler) {
            $message = __('accounting.unsupported_source_type');
            $processing = $this->attempts->processing($attempt, $actor);
            $this->attempts->failed($processing, $message, 'UNSUPPORTED_SOURCE_TYPE', ['source_type' => $attempt->source_type], $actor);
            $this->audit('FAILED_POSTING_RETRY_FAILED', $attempt->fresh(), $actor, ['reason' => $message], LogSeverity::WARNING);
            return RetryResult::unsupported($message);
        }

        try {
            $managed = str_ends_with($handler::class, 'BasicAccountingPostingHandler');
            $working = $managed ? $attempt : $this->attempts->processing($attempt, $actor);
            $result = $handler->retry($working, $actor);

            if (! $result->attemptManagedByHandler) {
                if ($result->success && $result->journal) {
                    $this->attempts->posted($working, $result->journal, $actor);
                } else {
                    $this->attempts->failed($working, $result->message ?: __('accounting.retry_failed'), actor: $actor);
                }
            }

            $event = $result->success ? 'FAILED_POSTING_RETRY_SUCCEEDED' : 'FAILED_POSTING_RETRY_FAILED';
            $this->audit($event, $attempt->fresh(), $actor, ['message' => $result->message], $result->success ? LogSeverity::NOTICE : LogSeverity::WARNING);
            return $result;
        } catch (Throwable $error) {
            $fresh = $attempt->fresh();
            if ($fresh->status !== 'failed') {
                $this->attempts->failed($fresh, $error, context: ['stage' => 'workbench_retry'], actor: $actor);
            }
            $this->audit('FAILED_POSTING_RETRY_FAILED', $attempt->fresh(), $actor, ['reason' => $error->getMessage()], LogSeverity::WARNING);
            return RetryResult::failed($error->getMessage());
        }
    }

    public function retrySelected(array $ids, User $actor): array
    {
        $summary = ['selected' => count($ids), 'retried' => 0, 'posted' => 0, 'failed_again' => 0, 'unsupported' => 0, 'skipped' => 0];
        $this->auditBatch('FAILED_POSTING_BATCH_RETRY_REQUESTED', $actor, ['ids' => $ids]);

        foreach (AccountingPostingAttempt::query()->whereIn('id', $ids)->orderBy('id')->get() as $attempt) {
            if ($attempt->status !== 'failed') {
                $summary['skipped']++;
                continue;
            }
            $summary['retried']++;
            $result = $this->retry($attempt, $actor);
            if (! $result->supported) {
                $summary['unsupported']++;
            } elseif ($result->success) {
                $summary['posted']++;
            } else {
                $summary['failed_again']++;
            }
        }
        $summary['skipped'] += max(0, count($ids) - array_sum([$summary['retried'], $summary['skipped']]));
        $this->auditBatch('FAILED_POSTING_BATCH_RETRY_COMPLETED', $actor, $summary);

        return $summary;
    }

    public function resolve(AccountingPostingAttempt $attempt, array $data, User $actor): AccountingPostingAttempt
    {
        if (($data['resolution_type'] ?? null) === 'corrected_by_manual_journal' && empty($data['resolution_journal_entry_id'])) {
            throw ValidationException::withMessages(['resolution_journal_entry_id' => __('accounting.corrective_journal_required')]);
        }
        if (empty(trim((string) ($data['resolution_evidence'] ?? ''))) && empty(trim((string) ($data['resolution_reference'] ?? '')))) {
            throw ValidationException::withMessages(['resolution_evidence' => __('accounting.resolution_evidence_required')]);
        }

        $resolved = $this->attempts->resolveWithEvidence($attempt, $actor, $data['resolution_type'], $data['resolution_note'], $data);
        $this->audit('FAILED_POSTING_RESOLVED', $resolved, $actor, ['resolution_type' => $data['resolution_type']], LogSeverity::WARNING);
        return $resolved;
    }

    public function waive(AccountingPostingAttempt $attempt, array $data, User $actor): AccountingPostingAttempt
    {
        if (empty(trim((string) ($data['materiality_note'] ?? '')))) {
            throw ValidationException::withMessages(['materiality_note' => __('accounting.materiality_note_required')]);
        }
        $waived = $this->attempts->waiveWithEvidence($attempt, $actor, $data['waive_reason'], $data);
        $this->audit('FAILED_POSTING_WAIVED', $waived, $actor, ['materiality_note' => $data['materiality_note']], LogSeverity::WARNING);
        return $waived;
    }

    public function unsupported(Collection $attempts): Collection
    {
        return $attempts->filter(fn (AccountingPostingAttempt $attempt) => ! $this->handlers->supports($attempt));
    }

    protected function audit(string $event, AccountingPostingAttempt $attempt, User $actor, array $metadata = [], LogSeverity $severity = LogSeverity::NOTICE): void
    {
        $this->activityLog->log(LogModule::ACCOUNTING, $event, [
            'severity' => $severity,
            'causer' => $actor,
            'source_type' => $attempt->source_type,
            'source_id' => $attempt->source_id,
            'journal_entry_id' => $attempt->journal_entry_id,
            'metadata' => array_merge(['posting_attempt_id' => $attempt->id], $metadata),
        ], $attempt, str_replace('_', ' ', ucfirst(strtolower($event))));
    }

    protected function auditBatch(string $event, User $actor, array $metadata): void
    {
        $this->activityLog->log(LogModule::ACCOUNTING, $event, [
            'severity' => LogSeverity::WARNING,
            'causer' => $actor,
            'metadata' => $metadata,
        ], description: str_replace('_', ' ', ucfirst(strtolower($event))));
    }
}
