<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Models\LegacyMigration\ProvenanceRecord;
use App\Models\LegacyMigration\Remediation;

final class ProtectedEvidenceRepository
{
    /** @param array<string, mixed> $attributes */
    public function appendRemediation(array $attributes): Remediation
    {
        ProtectedToken::assert($attributes['protected_source_token'] ?? '', 'protected_source_token');
        ProtectedToken::assert($attributes['remediation_token'] ?? '', 'remediation_token');

        return Remediation::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function appendProvenance(array $attributes): ProvenanceRecord
    {
        ProtectedToken::assert($attributes['protected_source_token'] ?? '', 'protected_source_token');
        ProtectedToken::assert($attributes['outcome_coordinate_token'] ?? '', 'outcome_coordinate_token');

        return ProvenanceRecord::query()->create($attributes);
    }
}
