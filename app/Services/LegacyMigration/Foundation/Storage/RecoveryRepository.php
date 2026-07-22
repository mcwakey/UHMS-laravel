<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Models\LegacyMigration\AtomicIntent;
use App\Models\LegacyMigration\Checkpoint;
use App\Models\LegacyMigration\CompensationRecord;
use App\Services\LegacyMigration\Foundation\Recovery\AtomicUnit;
use App\Services\LegacyMigration\Foundation\Recovery\CompensationPlanRegistry;

final class RecoveryRepository
{
    public function __construct(private readonly CompareAndSet $compareAndSet = new CompareAndSet) {}

    /** @param array<string, mixed> $attributes */
    public function createIntent(array $attributes): AtomicIntent
    {
        ProtectedToken::assert($attributes['intent_token'] ?? '', 'intent_token');

        return AtomicIntent::query()->create($attributes);
    }

    /** @param array<string, mixed> $evidence */
    public function advanceIntent(AtomicIntent $intent, string $expectedState, int $expectedVersion, string $nextState, array $evidence = []): AtomicIntent
    {
        /** @var AtomicIntent $advanced */
        $advanced = $this->compareAndSet->state($intent, $expectedState, $expectedVersion, $nextState, $evidence);

        return $advanced;
    }

    /** @param array<string, mixed> $attributes */
    public function appendCheckpoint(array $attributes): Checkpoint
    {
        if (empty($attributes['transaction_evidence_hash']) || empty($attributes['reconciliation_bundle_hash'])) {
            throw new StorageIntegrityException('A checkpoint requires durable transaction and reconciliation evidence.');
        }

        return Checkpoint::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function appendCompensation(array $attributes): CompensationRecord
    {
        ProtectedToken::assert($attributes['compensation_token'] ?? '', 'compensation_token');

        try {
            $unit = AtomicUnit::from((string) ($attributes['unit_name'] ?? ''));
            (new CompensationPlanRegistry)->forUnit($unit)->assertActionAllowed((string) ($attributes['action_type'] ?? ''));
        } catch (\Throwable) {
            throw new StorageIntegrityException('The compensation action is not authorized for its atomic unit.');
        }

        return CompensationRecord::query()->create($attributes);
    }
}
