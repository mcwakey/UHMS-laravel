<?php

namespace Tests\Feature;

use App\Data\Billing\PaymentGateContext;
use App\Data\Billing\VisitPaymentArrangementApprovalData;
use App\Data\Billing\VisitPaymentArrangementData;
use App\Enums\PaymentGateStage;
use App\Enums\VisitPaymentPolicySource;
use App\Enums\VisitPaymentTimingPolicy;
use App\Enums\VisitType;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\Billing\OperationalVisitPaymentTimingResolver;
use App\Services\Billing\VisitPaymentArrangementService;
use Database\Seeders\PaymentTimingSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalVisitPaymentTimingResolverTest extends TestCase
{
    use RefreshDatabase;

    private VisitPaymentArrangementService $arrangements;

    protected function setUp(): void
    {
        parent::setUp();
        config(['audit_streaming.async_writes' => false]);
        User::factory()->create(); // id 1
        (new PaymentTimingSettingsSeeder)->run();
        $this->arrangements = app(VisitPaymentArrangementService::class);
    }

    private function resolver(): OperationalVisitPaymentTimingResolver
    {
        $r = app(OperationalVisitPaymentTimingResolver::class);
        $r->flush();

        return $r;
    }

    private function visit(): Visit
    {
        return Visit::factory()->create(['patient_id' => Patient::factory()->create()->id, 'visit_type' => VisitType::OUTPATIENT->value]);
    }

    private function approve(Visit $visit, string $policy, ?string $expires = null): \App\Models\VisitPaymentArrangement
    {
        $a = $this->arrangements->request($visit, VisitPaymentArrangementData::fromValidated([
            'requested_policy' => $policy, 'request_reason' => 'x', 'effective_from' => now()->toDateString(),
        ]), User::factory()->create());

        return $this->arrangements->approve($a, VisitPaymentArrangementApprovalData::fromValidated($expires ? ['expires_at' => $expires] : []), User::factory()->create());
    }

    private function ctx(): PaymentGateContext
    {
        return PaymentGateContext::forOperation(PaymentGateStage::COMPLETE, 'consultation.route.complete');
    }

    public function test_eligible_arrangement_overrides_baseline_with_source(): void
    {
        $visit = $this->visit();
        $this->approve($visit, 'pay_after_all_services');

        $decision = $this->resolver()->resolve($visit->fresh(), $this->ctx());
        $this->assertTrue($decision->usedApprovedArrangement);
        $this->assertSame(VisitPaymentPolicySource::APPROVED_ARRANGEMENT, $decision->source);
        $this->assertSame(VisitPaymentTimingPolicy::PAY_AFTER_ALL_SERVICES, $decision->policy);
        $this->assertNotSame(VisitPaymentTimingPolicy::INHERIT, $decision->policy);
    }

    public function test_baseline_applies_without_arrangement(): void
    {
        $visit = $this->visit();
        $decision = $this->resolver()->resolve($visit, $this->ctx());
        $this->assertFalse($decision->usedApprovedArrangement);
        $this->assertNotSame(VisitPaymentPolicySource::APPROVED_ARRANGEMENT, $decision->source);
    }

    public function test_revoked_arrangement_is_ignored(): void
    {
        $visit = $this->visit();
        $a = $this->approve($visit, 'pay_after_all_services');
        $this->arrangements->revoke($a, 'cancelled', User::factory()->create());

        $decision = $this->resolver()->resolve($visit->fresh(), $this->ctx());
        $this->assertFalse($decision->usedApprovedArrangement);
    }

    public function test_expired_arrangement_is_ignored(): void
    {
        $visit = $this->visit();
        $this->approve($visit, 'pay_after_all_services', now()->subDay()->toDateString());
        $this->arrangements->expireDue();

        $decision = $this->resolver()->resolve($visit->fresh(), $this->ctx());
        $this->assertFalse($decision->usedApprovedArrangement);
    }

    public function test_unsupported_visit_type_falls_back_to_baseline(): void
    {
        // consultation.route.complete supports outpatient only; make it inpatient.
        $visit = Visit::factory()->create(['patient_id' => Patient::factory()->create()->id, 'visit_type' => VisitType::INPATIENT->value]);
        $this->approve($visit, 'pay_after_all_services');

        $decision = $this->resolver()->resolve($visit->fresh(), $this->ctx());
        $this->assertFalse($decision->usedApprovedArrangement);
        $this->assertSame('unsupported_visit_type', $decision->arrangementEligibilityReason);
    }
}
