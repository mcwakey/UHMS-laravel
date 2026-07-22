<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Models\LegacyMigration\MigrationAuditEvent;

final class MigrationAuditRepository
{
    /** @param array<string, mixed> $attributes */
    public function append(array $attributes): MigrationAuditEvent
    {
        ProtectedToken::assert($attributes['event_token'] ?? '', 'event_token');

        return MigrationAuditEvent::query()->create($attributes);
    }
}
