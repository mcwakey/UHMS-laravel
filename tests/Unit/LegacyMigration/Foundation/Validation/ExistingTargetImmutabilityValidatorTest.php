<?php

namespace Tests\Unit\LegacyMigration\Foundation\Validation;

use App\Services\LegacyMigration\Foundation\Security\ProtectedToken;
use App\Services\LegacyMigration\Foundation\Validation\ExistingTargetEvidence;
use App\Services\LegacyMigration\Foundation\Validation\ExistingTargetImmutabilityValidator;
use App\Services\LegacyMigration\Foundation\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ExistingTargetImmutabilityValidatorTest extends TestCase
{
    #[Test]
    public function metadata_only_evidence_with_zero_delta_is_accepted(): void
    {
        (new ExistingTargetImmutabilityValidator)->assertImmutable($this->evidence('patient'));

        self::addToAssertionCount(1);
    }

    #[Test]
    public function any_existing_target_delta_fails_closed(): void
    {
        try {
            (new ExistingTargetImmutabilityValidator)->assertImmutable($this->evidence('contact', 'c', 'd', observedMutations: 1));
            self::fail('An existing-target delta must be rejected.');
        } catch (ValidationException $exception) {
            self::assertSame('FOUNDATION_EXISTING_TARGET_MUTATION', $exception->faultCode);
            self::assertContains('LEGACY-PATIENT-CHILD-TARGET-036', $exception->violationCodes);
        }
    }

    #[Test]
    public function soft_deleted_or_merged_targets_cannot_be_linked(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('not eligible');

        (new ExistingTargetImmutabilityValidator)->assertImmutable($this->evidence('patient', softDeleted: true));
    }

    #[Test]
    public function plain_or_wrong_domain_evidence_is_rejected(): void
    {
        $evidence = $this->evidence('insurance_membership', stateDomain: 'artifact_integrity');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('HMAC domains');
        (new ExistingTargetImmutabilityValidator)->assertImmutable($evidence);
    }

    private function evidence(
        string $domain,
        string $beforeByte = 'c',
        string $afterByte = 'c',
        bool $softDeleted = false,
        int $observedMutations = 0,
        string $stateDomain = 'target_record',
    ): ExistingTargetEvidence {
        return new ExistingTargetEvidence(
            evidenceVersion: 'existing-target-evidence/1',
            contractVersion: '2F.1.0',
            runToken: str_repeat('a', 64),
            targetSnapshotId: str_repeat('b', 64),
            domain: $domain,
            targetReference: $this->token('target_record', '1'),
            lockEvidence: $this->token('artifact_integrity', '2'),
            lineageEvidence: $this->token('idempotency', '3'),
            targetExists: true,
            softDeleted: $softDeleted,
            mergedOrRedirected: false,
            beforeState: $this->token($stateDomain, $beforeByte),
            afterState: $this->token($stateDomain, $afterByte),
            intendedTargetMutations: 0,
            observedTargetMutations: $observedMutations,
        );
    }

    private function token(string $domain, string $byte): ProtectedToken
    {
        return new ProtectedToken('testing', $domain, 'synthetic-key', 'v1', 'typed-length-prefix/1', str_repeat($byte, 32));
    }
}
