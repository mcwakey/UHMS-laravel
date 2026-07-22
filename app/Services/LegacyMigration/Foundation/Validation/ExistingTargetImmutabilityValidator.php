<?php

namespace App\Services\LegacyMigration\Foundation\Validation;

final class ExistingTargetImmutabilityValidator
{
    public function assertImmutable(ExistingTargetEvidence $evidence): void
    {
        if ($evidence->evidenceVersion !== 'existing-target-evidence/1'
            || $evidence->contractVersion !== '2F.1.0'
            || ! self::isDigest($evidence->runToken)
            || ! self::isDigest($evidence->targetSnapshotId)) {
            throw new ValidationException('FOUNDATION_EXISTING_TARGET_COORDINATE_INVALID', [], 'Existing-target evidence is not bound to the approved run and contract coordinate.');
        }
        if (! in_array($evidence->domain, ['patient', 'contact', 'insurance_membership'], true)) {
            throw new ValidationException('FOUNDATION_EXISTING_TARGET_DOMAIN_INVALID', [], 'An existing-target evidence domain is invalid.');
        }
        if (! $evidence->targetExists) {
            $codes = match ($evidence->domain) {
                'patient' => ['LEGACY-PATIENT-TARGET-025'],
                'contact' => ['LEGACY-PATIENT-CHILD-CONTACT-012'],
                'insurance_membership' => ['LEGACY-INSURANCE-TARGET-041'],
            };
            throw new ValidationException('FOUNDATION_EXISTING_TARGET_MISSING', $codes, 'The linked target does not exist.');
        }
        if ($evidence->softDeleted || $evidence->mergedOrRedirected) {
            $codes = match ($evidence->domain) {
                'patient' => ['LEGACY-PATIENT-TARGET-026'],
                'contact' => ['LEGACY-PATIENT-CHILD-TARGET-036'],
                'insurance_membership' => ['LEGACY-INSURANCE-TARGET-041'],
            };
            throw new ValidationException('FOUNDATION_EXISTING_TARGET_STATE_BLOCKED', $codes, 'The linked target state is not eligible for a metadata-only link.');
        }
        if ($evidence->targetReference->domain() !== 'target_record'
            || $evidence->beforeState->domain() !== 'target_record'
            || $evidence->afterState->domain() !== 'target_record'
            || $evidence->lockEvidence->domain() !== 'artifact_integrity'
            || $evidence->lineageEvidence->domain() !== 'idempotency') {
            throw new ValidationException('FOUNDATION_EXISTING_TARGET_EVIDENCE_INVALID', [], 'Existing-target evidence is not protected by the required HMAC domains.');
        }
        $environment = $evidence->targetReference->environment();
        if ($evidence->beforeState->environment() !== $environment
            || $evidence->afterState->environment() !== $environment
            || $evidence->lockEvidence->environment() !== $environment
            || $evidence->lineageEvidence->environment() !== $environment) {
            throw new ValidationException('FOUNDATION_EXISTING_TARGET_EVIDENCE_INVALID', [], 'Existing-target evidence contexts do not match.');
        }
        if ($evidence->intendedTargetMutations !== 0
            || $evidence->observedTargetMutations !== 0
            || ! $evidence->beforeState->hasSameContext($evidence->afterState)
            || ! $evidence->beforeState->matches($evidence->afterState)) {
            $codes = match ($evidence->domain) {
                'patient' => ['LEGACY-PATIENT-TARGET-024'],
                'contact' => ['LEGACY-PATIENT-CHILD-TARGET-036', 'LEGACY-PATIENT-CHILD-CONTACT-014'],
                'insurance_membership' => ['LEGACY-INSURANCE-TARGET-041'],
            };
            throw new ValidationException('FOUNDATION_EXISTING_TARGET_MUTATION', $codes, 'Existing-target mutation is forbidden.');
        }
    }

    private static function isDigest(string $value): bool
    {
        return preg_match('/\A(?:sha256:)?[a-f0-9]{64}\z/i', $value) === 1;
    }
}
