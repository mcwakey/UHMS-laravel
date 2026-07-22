<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Models\LegacyMigration\MigrationAuditEvent;
use App\Services\LegacyMigration\Foundation\Security\Phase3ProtectedStoreModelGuard;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessDeniedException;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;
use Illuminate\Support\Facades\DB;

final class MigrationAuditRepository
{
    public function __construct(private readonly ?ProtectedRecordSecurityRepository $security = null) {}

    /** @param array<string, mixed> $attributes */
    public function append(array $attributes): MigrationAuditEvent
    {
        Phase3ProtectedStoreModelGuard::assertLegacyUnsealedMethodAllowed();

        return $this->appendInternal($attributes);
    }

    /** @param array<string, mixed> $attributes */
    private function appendInternal(array $attributes): MigrationAuditEvent
    {
        ProtectedToken::assert($attributes['event_token'] ?? '', 'event_token');

        return MigrationAuditEvent::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function appendProtected(ProtectedStoreOperationContext $context, array $attributes, array $tokenSet, string $domain = 'migration_audit'): MigrationAuditEvent
    {
        return DB::transaction(function () use ($context, $attributes, $tokenSet, $domain): MigrationAuditEvent {
            $attributes = ProtectedTokenSet::apply($attributes, $tokenSet);
            $encodedEventToken = ProtectedTokenSet::primary($tokenSet, 'event_token');
            $event = $this->appendInternal($attributes);
            $this->security()->seal(
                $context,
                $event,
                $encodedEventToken,
                $domain,
                isset($attributes['run_id']) ? (int) $attributes['run_id'] : null,
                isset($attributes['source_snapshot_id']) ? (int) $attributes['source_snapshot_id'] : null,
                isset($attributes['target_snapshot_id']) ? (int) $attributes['target_snapshot_id'] : null,
                (string) $attributes['access_classification'],
                (string) $attributes['retention_classification'],
                $tokenSet,
            );

            return $event;
        }, 3);
    }

    private function security(): ProtectedRecordSecurityRepository
    {
        return $this->security ?? throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-REPOSITORY-BOUNDARY-MISSING-001');
    }
}
