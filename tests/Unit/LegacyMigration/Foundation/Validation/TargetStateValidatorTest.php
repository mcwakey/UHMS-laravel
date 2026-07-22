<?php

namespace Tests\Unit\LegacyMigration\Foundation\Validation;

use App\Services\LegacyMigration\Foundation\Validation\InsuranceInitializationValidator;
use App\Services\LegacyMigration\Foundation\Validation\PatientTargetStateValidator;
use App\Services\LegacyMigration\Foundation\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\LegacyMigration\Phase2FPolicyFixture;

final class TargetStateValidatorTest extends TestCase
{
    #[Test]
    public function patient_recommendations_remain_dry_run_reachable_but_commit_blocked(): void
    {
        $result = (new PatientTargetStateValidator(Phase2FPolicyFixture::load()))->validate($this->patientCandidate());

        self::assertTrue($result->dryRunAllowed);
        self::assertFalse($result->commitAllowed);
        self::assertContains('recommendation_pending_approval', $result->classifications['status']);
        self::assertContains('prohibited_default', $result->classifications['is_active']);
        self::assertContains('LEGACY-PATIENT-STATUS-044', $result->violationCodes);
        self::assertContains('LEGACY-PATIENT-STATUS-045', $result->violationCodes);
        self::assertContains('LEGACY-PATIENT-STATUS-049', $result->violationCodes);

        try {
            $result->assertCommitAllowed();
            self::fail('The unresolved patient tuple must block commit.');
        } catch (ValidationException $exception) {
            self::assertSame('FOUNDATION_TARGET_STATE_COMMIT_BLOCKED', $exception->faultCode);
        }
    }

    #[Test]
    public function omitted_fields_cannot_slip_through_target_defaults(): void
    {
        $patient = $this->patientCandidate();
        unset($patient['is_active'], $patient['registered_by']);

        $result = (new PatientTargetStateValidator(Phase2FPolicyFixture::load()))->validate($patient);

        self::assertContains('is_active', $result->missingExplicitFields);
        self::assertContains('registered_by', $result->missingExplicitFields);
        self::assertFalse($result->commitAllowed);
    }

    #[Test]
    public function unexpected_fields_are_rejected_and_policy_authority_is_pinned(): void
    {
        $patient = $this->patientCandidate();
        $patient['unexpected_target_default'] = true;

        $result = (new PatientTargetStateValidator(Phase2FPolicyFixture::load()))->validate($patient);

        self::assertSame(['unexpected_target_default'], $result->unexpectedFields);
        self::assertContains('FOUNDATION-TARGET-STATE-UNEXPECTED-FIELD', $result->violationCodes);
        self::assertMatchesRegularExpression('/owner-approval-blocked$/', $result->policyAuthority);
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $result->policyFingerprint);
        self::assertFalse($result->commitAllowed);
    }

    #[Test]
    public function insurance_initialization_blocks_current_membership_and_operational_state(): void
    {
        $result = (new InsuranceInitializationValidator(Phase2FPolicyFixture::load()))->validate([
            'patient_id' => 'protected-parent-ref',
            'insurance_provider_id' => 'protected-provider-ref',
            'insurance_tier_id' => null,
            'member_type' => 'holder',
            'card_holder_insurance_id' => null,
            'membership_number' => null,
            'policy_number' => null,
            'ccc_code' => null,
            'start_date' => null,
            'expiry_date' => null,
            'is_primary' => false,
            'is_active' => true,
            'created_at' => null,
            'updated_at' => null,
            'verification' => null,
            'eligibility' => null,
        ]);

        self::assertTrue($result->dryRunAllowed);
        self::assertFalse($result->commitAllowed);
        self::assertContains('target_owned_operational_state', $result->classifications['verification']);
        self::assertContains('prohibited_default', $result->classifications['member_type']);
        self::assertContains('LEGACY-INSURANCE-CONSOLIDATION-026', $result->violationCodes);
    }

    /** @return array<string, mixed> */
    private function patientCandidate(): array
    {
        return [
            'status' => 'active',
            'is_active' => true,
            'is_temporary' => false,
            'temporary_reason' => null,
            'identity_confirmed_at' => null,
            'identity_confirmed_by' => null,
            'merge_status' => 'ACTIVE',
            'merged_to_patient_id' => null,
            'merged_at' => null,
            'merged_by' => null,
            'is_deceased' => false,
            'deceased_at' => null,
            'cause_of_death' => null,
            'deceased_notes' => null,
            'marked_deceased_by' => null,
            'registered_by' => null,
            'deleted_at' => null,
            'created_at' => '2001-02-03T04:05:06+00:00',
            'updated_at' => null,
        ];
    }
}
