<?php

namespace Tests\Feature;

use App\Data\Billing\PatientFinancialRiskData;
use App\Enums\PatientFinancialRiskLevel;
use App\Enums\VisitType;
use App\Models\Patient;
use App\Models\PatientFinancialRiskProfile;
use App\Models\User;
use App\Models\Visit;
use App\Services\Billing\PatientFinancialRiskService;
use App\Services\Billing\VisitPaymentPolicyMaterializationService;
use Database\Seeders\PaymentTimingSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitPaymentPolicySnapshotTest extends TestCase
{
    use RefreshDatabase;

    private VisitPaymentPolicyMaterializationService $service;
    private PatientFinancialRiskService $riskService;
    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        config(['audit_streaming.async_writes' => false]);
        $this->actor = User::factory()->create(); // id 1
        (new PaymentTimingSettingsSeeder)->run();
        $this->service = app(VisitPaymentPolicyMaterializationService::class);
        $this->riskService = app(PatientFinancialRiskService::class);
    }

    private function classify(Patient $patient, PatientFinancialRiskLevel $level): PatientFinancialRiskProfile
    {
        return $this->riskService->createOrClassify($patient, PatientFinancialRiskData::fromValidated([
            'risk_level' => $level->value,
            'primary_reason' => 'management_decision', 'reason_details' => 'x',
            'effective_from' => now()->toDateString(),
        ]), $this->actor);
    }

    private function visit(Patient $patient): Visit
    {
        return Visit::factory()->create(['patient_id' => $patient->id, 'visit_type' => VisitType::OUTPATIENT->value]);
    }

    public function test_later_risk_update_does_not_silently_change_existing_snapshot(): void
    {
        $patient = Patient::factory()->create();
        $profile = $this->classify($patient, PatientFinancialRiskLevel::WATCHLIST);
        $visit = $this->visit($patient);

        $this->assertSame(PatientFinancialRiskLevel::WATCHLIST, $visit->paymentPolicy->patient_risk_level_snapshot);

        // Escalate the profile AFTER materialisation.
        $this->riskService->update($profile->fresh(), PatientFinancialRiskData::fromValidated([
            'risk_level' => PatientFinancialRiskLevel::BLOCKED_CREDIT->value,
            'primary_reason' => 'management_decision', 'reason_details' => 'x',
            'effective_from' => now()->toDateString(),
        ]), $this->actor);

        // Existing snapshot unchanged.
        $this->assertSame(PatientFinancialRiskLevel::WATCHLIST, $visit->paymentPolicy->fresh()->patient_risk_level_snapshot);
        // …but now detectable as stale.
        $this->assertTrue($this->service->snapshotIsStale($visit->paymentPolicy->fresh()));
    }

    public function test_cleared_profile_makes_snapshot_stale(): void
    {
        $patient = Patient::factory()->create();
        $profile = $this->classify($patient, PatientFinancialRiskLevel::HIGH_RISK);
        $visit = $this->visit($patient);

        $this->riskService->clear($profile->fresh(), $this->actor, 'resolved');

        $this->assertTrue($this->service->snapshotIsStale($visit->paymentPolicy->fresh()));
    }

    public function test_no_snapshot_but_restrictive_profile_is_stale(): void
    {
        $patient = Patient::factory()->create();
        $visit = $this->visit($patient); // no profile yet → snapshot null
        $this->assertFalse($this->service->snapshotIsStale($visit->paymentPolicy->fresh()));

        $this->classify($patient, PatientFinancialRiskLevel::HIGH_RISK);
        $this->assertTrue($this->service->snapshotIsStale($visit->paymentPolicy->fresh()));
    }

    public function test_explicit_refresh_updates_snapshot_and_does_not_modify_profile(): void
    {
        $patient = Patient::factory()->create();
        $visit = $this->visit($patient);
        $profile = $this->classify($patient, PatientFinancialRiskLevel::HIGH_RISK);
        $profileUpdatedBefore = $profile->fresh()->updated_at;

        $this->service->refresh($visit->fresh(), $this->actor);

        $policy = $visit->paymentPolicy->fresh();
        $this->assertSame(PatientFinancialRiskLevel::HIGH_RISK, $policy->patient_risk_level_snapshot);
        $this->assertFalse($this->service->snapshotIsStale($policy));
        // Refresh must not touch the patient's risk profile.
        $this->assertEquals($profileUpdatedBefore, $profile->fresh()->updated_at);
    }
}
