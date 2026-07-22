<?php

namespace App\Services;

use App\Models\AccountingPostingAttempt;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\LegacyMigration\Foundation\Runtime\OperationalEffectGate;
use App\Services\LegacyMigration\Foundation\Runtime\ProhibitedSubsystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

class AccountingPostingService
{
    public function __construct(
        protected JournalEntryService $journalEntryService,
        protected AccountingIdempotencyService $idempotency,
        protected AccountingPostingAttemptService $attempts,
    ) {}

    public function postFromSource(string $sourceModule, Model $source, array $lines, array $meta = []): JournalEntry
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::AccountingPosting);

        $postingType = (string) ($meta['posting_type'] ?? 'default');
        $postingVersion = max(1, (int) ($meta['posting_version'] ?? 1));
        $actor = auth()->user() ?? User::query()->firstOrFail();
        $idempotencyKey = $this->idempotency->key($source, $source->getKey(), $postingType, $postingVersion);

        if ($existing = $this->idempotency->postedJournal($idempotencyKey)) {
            return $existing;
        }

        $attempt = $this->attempts->pending(
            $sourceModule,
            $source,
            $postingType,
            $postingVersion,
            ['meta' => $meta, 'lines' => $lines],
            $actor,
        );
        $attempt = $this->attempts->processing($attempt, $actor);

        if ($attempt->status === 'posted' && $attempt->journalEntry) {
            return $attempt->journalEntry;
        }

        try {
            return DB::transaction(function () use (
                $meta,
                $source,
                $sourceModule,
                $lines,
                $idempotencyKey,
                $attempt,
                $actor,
            ) {
                $lockedAttempt = AccountingPostingAttempt::query()
                    ->with('journalEntry')
                    ->lockForUpdate()
                    ->findOrFail($attempt->id);
                if ($lockedAttempt->status === 'posted' && $lockedAttempt->journalEntry) {
                    return $lockedAttempt->journalEntry;
                }

                $entry = $this->journalEntryService->createDraft(array_merge($meta, [
                    'entry_date' => $meta['entry_date'] ?? now()->toDateString(),
                    'description' => $meta['description'] ?? class_basename($source).' posting',
                    'reference_type' => $source::class,
                    'reference_id' => $source->getKey(),
                    'source_module' => $sourceModule,
                    'idempotency_key' => $idempotencyKey,
                    'allow_control_accounts' => $meta['allow_control_accounts'] ?? ($sourceModule !== 'MANUAL'),
                    'lines' => $lines,
                ]));

                $posted = $this->journalEntryService->post($entry, $actor);
                $this->attempts->posted($lockedAttempt, $posted, $actor);

                return $posted;
            });
        } catch (Throwable $error) {
            $this->attempts->failed($attempt, $error, context: [
                'source_module' => $sourceModule,
                'posting_type' => $postingType,
            ], actor: $actor);

            throw $error;
        }
    }
}
