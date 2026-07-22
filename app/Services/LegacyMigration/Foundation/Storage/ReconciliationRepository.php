<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Models\LegacyMigration\ReconciliationResult;

final class ReconciliationRepository
{
    /** @param array<string, mixed> $attributes */
    public function append(array $attributes): ReconciliationResult
    {
        $result = $attributes['acceptance_result'] ?? null;
        $complete = (bool) ($attributes['measurement_complete'] ?? false);
        $mandatory = (bool) ($attributes['mandatory'] ?? true);
        $difference = $attributes['difference'] ?? null;
        $tolerance = $attributes['tolerance'] ?? 0;

        if ($result === 'passed' && (! $complete || $difference === null || ! $this->isExactZero($difference) || ! $this->isExactZero($tolerance))) {
            throw new StorageIntegrityException('Reconciliation cannot pass without a complete exact zero-difference measurement.');
        }

        if ($mandatory && ! $complete && $result !== 'blocked_not_measured') {
            throw new StorageIntegrityException('A missing mandatory measurement must be blocked_not_measured.');
        }

        ProtectedToken::assert($attributes['idempotency_token'] ?? '', 'idempotency_token');

        return ReconciliationResult::query()->create($attributes);
    }

    private function isExactZero(mixed $value): bool
    {
        return preg_match('/\A[+-]?0+(?:\.0+)?\z/D', (string) $value) === 1;
    }
}
