<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\AccountingPostingAttempt;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class AccountingPostingAttemptService
{
    public function __construct(
        protected AccountingIdempotencyService $idempotency,
        protected ActivityLogService $activityLog,
    ) {}

    public function pending(
        string $sourceModule,
        Model $source,
        string $postingType,
        int $postingVersion = 1,
        array $postingSnapshot = [],
        ?User $actor = null,
    ): AccountingPostingAttempt {
        $key = $this->idempotency->key($source, $source->getKey(), $postingType, $postingVersion);
        $actor ??= auth()->user();

        $attempt = AccountingPostingAttempt::query()->firstOrCreate(
            ['idempotency_key' => $key],
            [
                'source_module' => $sourceModule,
                'source_type' => $this->idempotency->sourceType($source),
                'source_id' => $source->getKey(),
                'posting_type' => $postingType,
                'posting_version' => $postingVersion,
                'status' => 'pending',
                'source_snapshot' => $source->attributesToArray(),
                'posting_snapshot' => $postingSnapshot,
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ],
        );

        if ($attempt->wasRecentlyCreated) {
            $this->event($attempt, 'ACCOUNTING_POSTING_ATTEMPT_CREATED', null, 'pending', actor: $actor);
            $this->audit($attempt, 'ACCOUNTING_POSTING_ATTEMPT_CREATED', $actor);
        }

        return $attempt->fresh();
    }

    public function processing(AccountingPostingAttempt $attempt, ?User $actor = null): AccountingPostingAttempt
    {
        return DB::transaction(function () use ($attempt, $actor) {
            $locked = AccountingPostingAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            if ($locked->status === 'posted') {
                return $locked;
            }
            if (in_array($locked->status, ['waived', 'resolved', 'reversed'], true)) {
                throw ValidationException::withMessages([
                    'posting' => "A {$locked->status} posting attempt cannot be processed.",
                ]);
            }

            $oldStatus = $locked->status;
            $now = now();
            $locked->update([
                'status' => 'processing',
                'attempt_count' => $locked->attempt_count + 1,
                'first_attempted_at' => $locked->first_attempted_at ?? $now,
                'last_attempted_at' => $now,
                'next_retry_at' => null,
                'updated_by' => $actor?->id ?? auth()->id(),
            ]);

            $event = $locked->attempt_count > 1
                ? 'ACCOUNTING_POSTING_ATTEMPT_RETRIED'
                : 'ACCOUNTING_POSTING_ATTEMPT_PROCESSING';
            $this->event($locked, $event, $oldStatus, 'processing', actor: $actor);
            if ($event === 'ACCOUNTING_POSTING_ATTEMPT_RETRIED') {
                $this->audit($locked, $event, $actor);
            }

            return $locked->fresh();
        });
    }

    public function posted(AccountingPostingAttempt $attempt, JournalEntry $journal, ?User $actor = null): AccountingPostingAttempt
    {
        return DB::transaction(function () use ($attempt, $journal, $actor) {
            $locked = AccountingPostingAttempt::query()->lockForUpdate()->findOrFail($attempt->id);

            if ($locked->status === 'posted') {
                if ((int) $locked->journal_entry_id !== (int) $journal->id) {
                    throw ValidationException::withMessages(['posting' => 'The posting identity is already linked to another journal.']);
                }

                return $locked;
            }

            $oldStatus = $locked->status;
            $locked->update([
                'status' => 'posted',
                'journal_entry_id' => $journal->id,
                'error_code' => null,
                'error_message' => null,
                'error_context' => null,
                'next_retry_at' => null,
                'updated_by' => $actor?->id ?? auth()->id(),
            ]);
            $this->event($locked, 'ACCOUNTING_POSTING_ATTEMPT_POSTED', $oldStatus, 'posted', actor: $actor, context: [
                'journal_entry_id' => $journal->id,
            ]);
            $this->audit($locked, 'ACCOUNTING_POSTING_ATTEMPT_POSTED', $actor, [
                'journal_entry_id' => $journal->id,
            ]);

            return $locked->fresh();
        });
    }

    public function failed(
        AccountingPostingAttempt $attempt,
        Throwable|string $error,
        ?string $errorCode = null,
        array $context = [],
        ?User $actor = null,
    ): AccountingPostingAttempt {
        $message = mb_substr($error instanceof Throwable ? $error->getMessage() : $error, 0, 65000);
        $errorCode ??= $error instanceof Throwable ? class_basename($error) : 'POSTING_FAILED';

        return DB::transaction(function () use ($attempt, $message, $errorCode, $context, $actor) {
            $locked = AccountingPostingAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            if ($locked->status === 'posted') {
                throw ValidationException::withMessages(['posting' => 'A posted attempt cannot be marked failed.']);
            }

            $oldStatus = $locked->status;
            $locked->update([
                'status' => 'failed',
                'error_code' => $errorCode,
                'error_message' => $message,
                'error_context' => $context,
                'last_attempted_at' => now(),
                'updated_by' => $actor?->id ?? auth()->id(),
            ]);
            $this->event($locked, 'ACCOUNTING_POSTING_ATTEMPT_FAILED', $oldStatus, 'failed', $errorCode, $message, $context, $actor);
            $this->audit($locked, 'ACCOUNTING_POSTING_ATTEMPT_FAILED', $actor, [
                'error_code' => $errorCode,
                'error_message' => $message,
            ], LogSeverity::WARNING);

            return $locked->fresh();
        });
    }

    public function waive(AccountingPostingAttempt $attempt, User $actor, string $reason): AccountingPostingAttempt
    {
        return $this->resolveState($attempt, $actor, 'waived', 'waiver', $reason, 'ACCOUNTING_POSTING_ATTEMPT_WAIVED');
    }

    public function resolve(AccountingPostingAttempt $attempt, User $actor, string $type, string $reason): AccountingPostingAttempt
    {
        return $this->resolveState($attempt, $actor, 'resolved', $type, $reason, 'ACCOUNTING_POSTING_ATTEMPT_RESOLVED');
    }

    public function resolveWithEvidence(
        AccountingPostingAttempt $attempt,
        User $actor,
        string $type,
        string $reason,
        array $evidence,
    ): AccountingPostingAttempt {
        return $this->resolveState(
            $attempt,
            $actor,
            'resolved',
            $type,
            $reason,
            'ACCOUNTING_POSTING_ATTEMPT_RESOLVED',
            $evidence,
        );
    }

    public function waiveWithEvidence(
        AccountingPostingAttempt $attempt,
        User $actor,
        string $reason,
        array $evidence,
    ): AccountingPostingAttempt {
        return $this->resolveState(
            $attempt,
            $actor,
            'waived',
            'waiver',
            $reason,
            'ACCOUNTING_POSTING_ATTEMPT_WAIVED',
            $evidence,
        );
    }

    public function reversed(AccountingPostingAttempt $attempt, JournalEntry $reversal, User $actor): AccountingPostingAttempt
    {
        return DB::transaction(function () use ($attempt, $reversal, $actor) {
            $locked = AccountingPostingAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            if ($locked->status !== 'posted') {
                throw ValidationException::withMessages(['posting' => 'Only a posted attempt can be marked reversed.']);
            }
            $locked->update([
                'status' => 'reversed',
                'reversal_journal_entry_id' => $reversal->id,
                'resolved_by' => $actor->id,
                'resolved_at' => now(),
                'resolution_type' => 'reversal',
                'resolution_note' => $reversal->reversal_reason,
                'updated_by' => $actor->id,
            ]);
            $this->event($locked, 'ACCOUNTING_POSTING_ATTEMPT_REVERSED', 'posted', 'reversed', actor: $actor, context: [
                'reversal_journal_entry_id' => $reversal->id,
            ]);
            $this->audit($locked, 'ACCOUNTING_POSTING_ATTEMPT_REVERSED', $actor, [
                'reversal_journal_entry_id' => $reversal->id,
            ], LogSeverity::WARNING);

            return $locked->fresh();
        });
    }

    protected function resolveState(
        AccountingPostingAttempt $attempt,
        User $actor,
        string $status,
        string $type,
        string $reason,
        string $event,
        array $evidence = [],
    ): AccountingPostingAttempt {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['resolution_note' => 'A resolution reason is required.']);
        }

        return DB::transaction(function () use ($attempt, $actor, $status, $type, $reason, $event, $evidence) {
            $locked = AccountingPostingAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            if ($locked->status === 'posted') {
                throw ValidationException::withMessages(['posting' => 'A posted attempt must be reversed, not resolved or waived.']);
            }
            if ($locked->status !== 'failed') {
                throw ValidationException::withMessages(['posting' => 'Only a failed posting attempt can be resolved or waived.']);
            }
            $oldStatus = $locked->status;
            $locked->update([
                'status' => $status,
                'resolved_by' => $actor->id,
                'resolved_at' => now(),
                'resolution_type' => $type,
                'resolution_note' => $reason,
                'resolution_journal_entry_id' => $evidence['resolution_journal_entry_id'] ?? null,
                'resolution_source_type' => $evidence['resolution_source_type'] ?? null,
                'resolution_source_id' => $evidence['resolution_source_id'] ?? null,
                'resolution_reference' => $evidence['resolution_reference'] ?? null,
                'resolution_evidence' => $evidence['resolution_evidence'] ?? null,
                'materiality_note' => $evidence['materiality_note'] ?? null,
                'waiver_review_date' => $evidence['waiver_review_date'] ?? null,
                'updated_by' => $actor->id,
            ]);
            $context = array_filter(array_merge(['reason' => $reason], $evidence), fn ($value) => $value !== null && $value !== '');
            $this->event($locked, $event, $oldStatus, $status, actor: $actor, context: $context);
            $this->audit($locked, $event, $actor, $context, LogSeverity::WARNING);

            return $locked->fresh();
        });
    }

    protected function event(
        AccountingPostingAttempt $attempt,
        string $event,
        ?string $from,
        ?string $to,
        ?string $errorCode = null,
        ?string $errorMessage = null,
        array $context = [],
        ?User $actor = null,
    ): void {
        $attempt->events()->create([
            'event_type' => $event,
            'from_status' => $from,
            'to_status' => $to,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'context' => $context,
            'actor_id' => $actor?->id ?? auth()->id(),
            'occurred_at' => now(),
        ]);
    }

    protected function audit(
        AccountingPostingAttempt $attempt,
        string $event,
        ?User $actor = null,
        array $metadata = [],
        LogSeverity $severity = LogSeverity::NOTICE,
    ): void {
        $this->activityLog->log(LogModule::ACCOUNTING, $event, [
            'severity' => $severity,
            'source_type' => $attempt->source_type,
            'source_id' => $attempt->source_id,
            'journal_entry_id' => $attempt->journal_entry_id,
            'causer' => $actor,
            'metadata' => array_merge([
                'posting_attempt_id' => $attempt->id,
                'source_module' => $attempt->source_module,
                'posting_type' => $attempt->posting_type,
                'posting_version' => $attempt->posting_version,
                'status' => $attempt->status,
            ], $metadata),
        ], $attempt, str_replace('_', ' ', ucfirst(strtolower($event))));
    }
}
