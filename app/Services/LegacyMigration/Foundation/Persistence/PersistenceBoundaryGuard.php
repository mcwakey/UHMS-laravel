<?php

namespace App\Services\LegacyMigration\Foundation\Persistence;

use App\Services\LegacyMigration\Foundation\Runtime\ExecutionMode;
use App\Services\LegacyMigration\Foundation\Runtime\MigrationRuntimeContext;
use App\Services\LegacyMigration\Foundation\Validation\ExistingTargetImmutabilityValidator;

final class PersistenceBoundaryGuard
{
    public function __construct(
        private readonly MigrationRuntimeContext $runtime,
        private readonly ExistingTargetImmutabilityValidator $existingTargets = new ExistingTargetImmutabilityValidator,
    ) {}

    public function authorize(
        DomainNeutralPersistenceCommand $command,
        PersistenceBoundaryContext $context,
    ): AuthorizedPersistenceCommand {
        $runtime = $this->runtime->assertActive($command->runToken, $command->targetSnapshotId);

        $this->require($context->runValid, 'FOUNDATION_PERSISTENCE_RUN_INVALID');
        $this->require($context->snapshotsPinned, 'FOUNDATION_PERSISTENCE_SNAPSHOTS_REQUIRED');
        $this->require($context->requiredMappingsResolved, 'FOUNDATION_PERSISTENCE_MAPPING_REQUIRED');
        $this->require($context->idempotencyKeyValid, 'FOUNDATION_PERSISTENCE_IDEMPOTENCY_INVALID');
        $this->require($context->targetCollisionSnapshotCurrent, 'FOUNDATION_PERSISTENCE_COLLISION_SNAPSHOT_STALE');
        $this->require($context->provenanceAvailable, 'FOUNDATION_PERSISTENCE_PROVENANCE_REQUIRED');
        $this->require($context->reconciliationAvailable, 'FOUNDATION_PERSISTENCE_RECONCILIATION_REQUIRED');

        if (in_array($command->operation, [PersistenceOperation::PatientEntity, PersistenceOperation::Alias, PersistenceOperation::EmergencyContact], true)) {
            $this->require($context->patientStateValidated, 'FOUNDATION_PERSISTENCE_PATIENT_STATE_UNVALIDATED');
            if ($runtime->mode === ExecutionMode::Commit) {
                $this->require($context->patientStateCommitApproved, 'FOUNDATION_PERSISTENCE_PATIENT_STATE_BLOCKED');
            }
        }
        if ($command->operation === PersistenceOperation::ExistingTargetLink) {
            $this->require($context->existingTargetEvidence !== null, 'FOUNDATION_PERSISTENCE_EXISTING_TARGET_UNPROVEN');
            if (! hash_equals($command->runToken, $context->existingTargetEvidence->runToken)
                || ! hash_equals($command->targetSnapshotId, $context->existingTargetEvidence->targetSnapshotId)) {
                throw new PersistenceBoundaryException('FOUNDATION_PERSISTENCE_EXISTING_TARGET_COORDINATE_MISMATCH', 'Existing-target proof does not match the persistence coordinate.');
            }
            $this->existingTargets->assertImmutable($context->existingTargetEvidence);
        }
        if ($command->operation === PersistenceOperation::CurrentMembership) {
            $this->require($context->insuranceHistoryComplete, 'FOUNDATION_PERSISTENCE_INSURANCE_HISTORY_INCOMPLETE');
            $this->require($context->insuranceInitializationValidated, 'FOUNDATION_PERSISTENCE_INSURANCE_STATE_UNVALIDATED');
            if ($runtime->mode === ExecutionMode::Commit) {
                $this->require($context->insuranceInitializationCommitApproved, 'FOUNDATION_PERSISTENCE_INSURANCE_STATE_BLOCKED');
            }
        }

        return new AuthorizedPersistenceCommand(
            command: $command,
            mode: $runtime->mode,
            businessWritesAllowed: $runtime->mode === ExecutionMode::Commit
                && $command->operation !== PersistenceOperation::ExistingTargetLink,
            metadataOnly: $command->operation === PersistenceOperation::ExistingTargetLink,
        );
    }

    private function require(bool $condition, string $faultCode): void
    {
        if (! $condition) {
            throw new PersistenceBoundaryException($faultCode, 'A mandatory migration persistence prerequisite is not satisfied.');
        }
    }
}
