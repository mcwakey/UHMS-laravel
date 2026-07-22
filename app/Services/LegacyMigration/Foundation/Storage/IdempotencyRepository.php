<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Models\LegacyMigration\IdempotencyRecord;
use Illuminate\Support\Facades\DB;

final class IdempotencyRepository
{
    public function __construct(private readonly CompareAndSet $compareAndSet = new CompareAndSet) {}

    /** @param array<string, mixed> $attributes */
    public function resolveOrCreate(array $attributes): IdempotencyRecord
    {
        ProtectedToken::assert($attributes['idempotency_token'] ?? '', 'idempotency_token');

        return DB::transaction(function () use ($attributes): IdempotencyRecord {
            $existing = IdempotencyRecord::query()
                ->where('domain', $attributes['domain'])
                ->where('idempotency_token', $attributes['idempotency_token'])
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                foreach ([
                    'run_id', 'source_snapshot_id', 'target_snapshot_id',
                    'protected_source_token', 'protected_target_token',
                    'input_fingerprint', 'outcome_type', 'contract_version',
                    'transformation_version', 'canonicalization_version',
                    'hmac_key_version',
                ] as $field) {
                    if ((string) $existing->{$field} !== (string) ($attributes[$field] ?? null)) {
                        throw new StorageIntegrityException('Idempotency key resolved to incompatible lineage.');
                    }
                }

                return $existing;
            }

            return IdempotencyRecord::query()->create($attributes);
        }, 3);
    }

    /** @param array<string, mixed> $outcome */
    public function advance(IdempotencyRecord $record, string $expectedState, int $expectedVersion, string $nextState, array $outcome = []): IdempotencyRecord
    {
        /** @var IdempotencyRecord $advanced */
        $advanced = $this->compareAndSet->state($record, $expectedState, $expectedVersion, $nextState, $outcome + [
            'attempt_count' => DB::raw('attempt_count + 1'),
        ]);

        return $advanced;
    }
}
