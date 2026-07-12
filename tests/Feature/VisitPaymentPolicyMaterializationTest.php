<?php

namespace Tests\Feature;

use App\Data\Billing\PatientFinancialRiskData;
use App\Enums\PatientFinancialRiskLevel;
use App\Enums\VisitPaymentPolicySource;
use App\Enums\VisitPaymentTimingPolicy;
use App\Enums\VisitType;
use App\Models\ActivityLog;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitPaymentPolicy;
use App\Services\Billing\PatientFinancialRiskService;
use App\Services\Billing\VisitPaymentPolicyMaterializationService;
use Database\Seeders\PaymentTimingSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitPaymentPolicyMaterializationTest extends TestCase
{
    use RefreshDatabase;

    private VisitPaymentPolicyMaterializationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['audit_streaming.async_writes' => false]);
        User::factory()->create(); // id 1 for PatientFactory registered_by
        (new PaymentTimingSettingsSeeder)->run();
        $this->service = app(VisitPaymentPolicyMaterializationService::class);
    }

    private function visit(VisitType $type, ?Patient $patient = null): Visit
    {
        return Visit::factory()->create([
            'patient_id' => ($patient ?? Patient::factory()->create())->id,
            'visit_type' => $type->value,
        ]);
    }

    public function test_visit_creation_auto_materialises_a_policy_without_activity_log(): void
    {
        $visit = $this->visit(VisitType::OUTPATIENT);

        $policy = $visit->paymentPolicy;
        $this->assertNotNull($policy);
        $this->assertNotSame(VisitPaymentTimingPolicy::INHERIT->value, $policy->getRawOriginal('resolved_policy'));
        $this->assertSame(1, $visit->paymentPolicyHistory()->count());
        // Auto (observer) path keeps the clinical hot-path light: no activity log.
        $this->assertFalse(ActivityLog::where('event', 'VISIT_PAYMENT_POLICY_MATERIALIZED')->exists());
    }

    public function test_inpatient_and_emergency_baselines_are_materialised_correctly(): void
    {
        $inpatient = $this->visit(VisitType::INPATIENT);
        $this->assertSame(VisitPaymentTimingPolicy::RUNNING_BILL, $inpatient->paymentPolicy->resolved_policy);
        $this->assertSame(VisitPaymentPolicySource::VISIT_TYPE, $inpatient->paymentPolicy->resolution_source);

        $emergency = $this->visit(VisitType::EMERGENCY);
        $this->assertSame(VisitPaymentTimingPolicy::RUNNING_BILL, $emergency->paymentPolicy->resolved_policy);
        $this->assertSame(VisitPaymentPolicySource::EMERGENCY_POLICY, $emergency->paymentPolicy->resolution_source);
        $this->assertTrue($emergency->paymentPolicy->emergency_protection_snapshot);
    }

    public function test_outpatient_inherits_global_default(): void
    {
        $visit = $this->visit(VisitType::OUTPATIENT);
        // Global default from the seeder is pay_before_service.
        $this->assertSame(VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE, $visit->paymentPolicy->resolved_policy);
        $this->assertSame(VisitPaymentPolicySource::GLOBAL_DEFAULT, $visit->paymentPolicy->resolution_source);
    }

    public function test_materialisation_is_idempotent(): void
    {
        $visit = $this->visit(VisitType::OUTPATIENT);
        $first = $visit->paymentPolicy;

        $again = $this->service->materialize($visit->fresh());

        $this->assertSame($first->id, $again->id);
        $this->assertSame(1, $visit->paymentPolicyHistory()->count());
        $this->assertSame(1, VisitPaymentPolicy::where('visit_id', $visit->id)->count());
    }

    public function test_unique_policy_per_visit(): void
    {
        $visit = $this->visit(VisitType::OUTPATIENT);
        $this->expectException(\Illuminate\Database\QueryException::class);
        VisitPaymentPolicy::factory()->create(['visit_id' => $visit->id]);
    }

    public function test_risk_recommendation_is_stored_separately_from_resolved_policy(): void
    {
        $patient = Patient::factory()->create();
        app(PatientFinancialRiskService::class)->createOrClassify(
            $patient,
            PatientFinancialRiskData::fromValidated([
                'risk_level' => PatientFinancialRiskLevel::HIGH_RISK->value,
                'primary_reason' => 'management_decision', 'reason_details' => 'x',
                'effective_from' => now()->toDateString(),
            ]),
            User::factory()->create(),
        );

        $visit = $this->visit(VisitType::INPATIENT, $patient);
        $policy = $visit->paymentPolicy;

        // Baseline is the resolver's inpatient running_bill — NOT the risk prepay.
        $this->assertSame(VisitPaymentTimingPolicy::RUNNING_BILL, $policy->resolved_policy);
        $this->assertNotSame(VisitPaymentPolicySource::PATIENT_RISK, $policy->resolution_source);
        // Recommendation stored separately.
        $this->assertSame(VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE, $policy->recommended_policy);
        $this->assertTrue($policy->requires_finance_review);
        $this->assertSame(PatientFinancialRiskLevel::HIGH_RISK, $policy->patient_risk_level_snapshot);
    }

    public function test_snapshot_captures_bounded_fields_only(): void
    {
        $patient = Patient::factory()->create();
        app(PatientFinancialRiskService::class)->createOrClassify(
            $patient,
            PatientFinancialRiskData::fromValidated([
                'risk_level' => PatientFinancialRiskLevel::WATCHLIST->value,
                'primary_reason' => 'other', 'reason_details' => 'SECRETdetails',
                'reference' => 'SECRETref',
                'effective_from' => now()->toDateString(),
            ]),
            User::factory()->create(),
        );

        $visit = $this->visit(VisitType::OUTPATIENT, $patient);
        $policy = $visit->paymentPolicy;

        $this->assertNotNull($policy->patient_financial_risk_profile_id);
        $this->assertSame(PatientFinancialRiskLevel::WATCHLIST, $policy->patient_risk_level_snapshot);
        $this->assertNotNull($policy->patient_risk_observed_at);
        // No free-text risk details leak into the snapshot payload.
        $json = json_encode($policy->getAttributes());
        $this->assertStringNotContainsString('SECRETdetails', $json);
        $this->assertStringNotContainsString('SECRETref', $json);
    }

    public function test_refresh_appends_history_on_change_and_is_silent_when_unchanged(): void
    {
        config(['visit_payment_policy.auto_materialize' => false]);
        $patient = Patient::factory()->create();
        $visit = $this->visit(VisitType::OUTPATIENT, $patient);
        $actor = User::factory()->create();

        $policy = $this->service->materialize($visit->fresh(), $actor);
        $this->assertSame(1, $visit->paymentPolicyHistory()->count());

        // Unchanged refresh writes nothing.
        $this->service->refresh($visit->fresh(), $actor);
        $this->assertSame(1, $visit->paymentPolicyHistory()->count());

        // Add a restrictive profile → recommendation changes → refresh records history + activity.
        app(PatientFinancialRiskService::class)->createOrClassify(
            $patient,
            PatientFinancialRiskData::fromValidated([
                'risk_level' => PatientFinancialRiskLevel::HIGH_RISK->value,
                'primary_reason' => 'management_decision', 'reason_details' => 'x',
                'effective_from' => now()->toDateString(),
            ]),
            $actor,
        );

        $this->service->refresh($visit->fresh(), $actor);
        $this->assertSame(2, $visit->paymentPolicyHistory()->count());
        $this->assertTrue(ActivityLog::where('event', 'VISIT_PAYMENT_POLICY_REFRESHED')->exists());
        $this->assertNotNull($visit->paymentPolicy->last_refreshed_at);
    }
}
