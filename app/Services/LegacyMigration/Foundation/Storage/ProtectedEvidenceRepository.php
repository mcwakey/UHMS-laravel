<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Models\LegacyMigration\ProtectedFoundationModel;
use App\Models\LegacyMigration\ProvenanceRecord;
use App\Models\LegacyMigration\Remediation;
use App\Services\LegacyMigration\Foundation\Security\Phase3ProtectedStoreModelGuard;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessDeniedException;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;
use Illuminate\Support\Facades\DB;

final class ProtectedEvidenceRepository
{
    public function __construct(private readonly ?ProtectedRecordSecurityRepository $security = null) {}

    /** @param array<string, mixed> $attributes */
    public function appendRemediation(array $attributes): Remediation
    {
        Phase3ProtectedStoreModelGuard::assertLegacyUnsealedMethodAllowed();

        return $this->appendRemediationInternal($attributes);
    }

    /** @param array<string, mixed> $attributes */
    private function appendRemediationInternal(array $attributes): Remediation
    {
        ProtectedToken::assert($attributes['protected_source_token'] ?? '', 'protected_source_token');
        ProtectedToken::assert($attributes['remediation_token'] ?? '', 'remediation_token');

        return Remediation::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function appendProvenance(array $attributes): ProvenanceRecord
    {
        Phase3ProtectedStoreModelGuard::assertLegacyUnsealedMethodAllowed();

        return $this->appendProvenanceInternal($attributes);
    }

    /** @param array<string, mixed> $attributes */
    private function appendProvenanceInternal(array $attributes): ProvenanceRecord
    {
        ProtectedToken::assert($attributes['protected_source_token'] ?? '', 'protected_source_token');
        ProtectedToken::assert($attributes['outcome_coordinate_token'] ?? '', 'outcome_coordinate_token');

        return ProvenanceRecord::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function appendRemediationProtected(ProtectedStoreOperationContext $context, array $attributes, array $tokenSet): Remediation
    {
        return DB::transaction(function () use ($context, $attributes, $tokenSet): Remediation {
            $attributes = ProtectedTokenSet::apply($attributes, $tokenSet);
            $encodedSourceToken = ProtectedTokenSet::primary($tokenSet, 'protected_source_token');
            $record = $this->appendRemediationInternal($attributes);
            $this->seal($context, $record, $attributes, $encodedSourceToken, (string) ($attributes['domain'] ?? 'patient_source'), $tokenSet);

            return $record;
        }, 3);
    }

    /** @param array<string, mixed> $attributes */
    public function appendProvenanceProtected(ProtectedStoreOperationContext $context, array $attributes, array $tokenSet): ProvenanceRecord
    {
        return DB::transaction(function () use ($context, $attributes, $tokenSet): ProvenanceRecord {
            $attributes = ProtectedTokenSet::apply($attributes, $tokenSet);
            $encodedSourceToken = ProtectedTokenSet::primary($tokenSet, 'protected_source_token');
            $record = $this->appendProvenanceInternal($attributes);
            $this->seal($context, $record, $attributes, $encodedSourceToken, (string) ($attributes['domain'] ?? 'patient_source'), $tokenSet);

            return $record;
        }, 3);
    }

    /** @param array<string, mixed> $attributes */
    private function seal(ProtectedStoreOperationContext $context, ProtectedFoundationModel $record, array $attributes, string $encodedToken, string $domain, array $tokenSet): void
    {
        $this->security()->seal(
            $context,
            $record,
            $encodedToken,
            $domain,
            isset($attributes['run_id']) ? (int) $attributes['run_id'] : null,
            isset($attributes['source_snapshot_id']) ? (int) $attributes['source_snapshot_id'] : null,
            isset($attributes['target_snapshot_id']) ? (int) $attributes['target_snapshot_id'] : null,
            (string) $attributes['access_classification'],
            (string) $attributes['retention_classification'],
            $tokenSet,
        );
    }

    private function security(): ProtectedRecordSecurityRepository
    {
        return $this->security ?? throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-REPOSITORY-BOUNDARY-MISSING-001');
    }
}
