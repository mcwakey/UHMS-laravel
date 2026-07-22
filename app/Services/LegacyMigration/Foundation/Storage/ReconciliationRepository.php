<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Models\LegacyMigration\ReconciliationResult;
use App\Services\LegacyMigration\Foundation\Security\Phase3ProtectedStoreModelGuard;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessDeniedException;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;
use Illuminate\Support\Facades\DB;

final class ReconciliationRepository
{
    public function __construct(private readonly ?ProtectedRecordSecurityRepository $security = null) {}

    /** @param array<string, mixed> $attributes */
    public function append(array $attributes): ReconciliationResult
    {
        Phase3ProtectedStoreModelGuard::assertLegacyUnsealedMethodAllowed();

        return $this->appendInternal($attributes);
    }

    /** @param array<string, mixed> $attributes */
    private function appendInternal(array $attributes): ReconciliationResult
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

    /** @param array<string,mixed> $attributes @param array<string,array{encoded_token:string,domain:string}> $tokenSet */
    public function appendProtected(ProtectedStoreOperationContext $context, array $attributes, array $tokenSet): ReconciliationResult
    {
        return DB::transaction(function () use ($context, $attributes, $tokenSet): ReconciliationResult {
            $attributes = ProtectedTokenSet::apply($attributes, $tokenSet);
            $primary = ProtectedTokenSet::primary($tokenSet, 'idempotency_token');
            $record = $this->appendInternal($attributes);
            $this->security()->seal($context, $record, $primary, ProtectedTokenSet::domain($tokenSet, 'idempotency_token'), (int) $attributes['run_id'], (int) $attributes['source_snapshot_id'], isset($attributes['target_snapshot_id']) ? (int) $attributes['target_snapshot_id'] : null, (string) $attributes['access_classification'], (string) $attributes['retention_classification'], $tokenSet);

            return $record;
        }, 3);
    }

    public function readProtected(ProtectedStoreOperationContext $context, int $resultId): ProtectedRecordProjection
    {
        return $this->security()->readProjection($context, ReconciliationResult::class, $resultId);
    }

    private function isExactZero(mixed $value): bool
    {
        return preg_match('/\A[+-]?0+(?:\.0+)?\z/D', (string) $value) === 1;
    }

    private function security(): ProtectedRecordSecurityRepository
    {
        return $this->security ?? throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-REPOSITORY-BOUNDARY-MISSING-001');
    }
}
