<?php

namespace Tests\Feature;

use App\Enums\VisitPaymentPolicySource;
use App\Enums\VisitPaymentTimingPolicy;
use App\Enums\VisitType;
use App\Models\Setting;
use App\Models\Visit;
use App\Models\VisitBillingOverride;
use App\Services\Billing\VisitPaymentTimingResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitPaymentTimingResolverTest extends TestCase
{
    use RefreshDatabase;

    private function visit(VisitType $type, array $overrides = []): Visit
    {
        return (new Visit(['visit_type' => $type]))
            ->setRelation('billingOverrides', collect($overrides));
    }

    public function test_explicit_visit_type_and_inherited_global_sources_are_reported(): void
    {
        Setting::setValue('payment_timing', 'default_policy', 'pay_after_all_services');
        Setting::setValue('payment_timing', 'outpatient_policy', 'inherit');
        Setting::setValue('payment_timing', 'inpatient_policy', 'pay_before_service');
        $resolver = app(VisitPaymentTimingResolver::class);

        $outpatient = $resolver->resolve($this->visit(VisitType::OUTPATIENT));
        $this->assertSame(VisitPaymentTimingPolicy::PAY_AFTER_ALL_SERVICES, $outpatient->policy);
        $this->assertSame(VisitPaymentPolicySource::GLOBAL_DEFAULT, $outpatient->source);

        $inpatient = $resolver->resolve($this->visit(VisitType::INPATIENT));
        $this->assertSame(VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE, $inpatient->policy);
        $this->assertSame(VisitPaymentPolicySource::VISIT_TYPE, $inpatient->source);
    }

    public function test_emergency_protection_has_highest_precedence_and_never_returns_inherit(): void
    {
        Setting::setValue('payment_timing', 'emergency_policy', 'pay_before_service');
        Setting::setValue('payment_timing', 'emergency_never_block_stabilisation', true, 'boolean');

        $decision = app(VisitPaymentTimingResolver::class)->resolve($this->visit(VisitType::EMERGENCY));
        $this->assertSame(VisitPaymentTimingPolicy::RUNNING_BILL, $decision->policy);
        $this->assertSame(VisitPaymentPolicySource::EMERGENCY_POLICY, $decision->source);
        $this->assertSame('emergency_stabilisation_protection', $decision->reasonCode);
    }

    public function test_full_visit_override_maps_but_previous_balance_only_override_does_not(): void
    {
        Setting::setValue('payment_timing', 'outpatient_policy', 'pay_before_service');
        $previous = new VisitBillingOverride([
            'override_type' => VisitBillingOverride::TYPE_PREVIOUS_BALANCE_OVERRIDE,
            'scope' => VisitBillingOverride::SCOPE_VISIT,
            'status' => VisitBillingOverride::STATUS_ACTIVE,
        ]);
        $resolver = app(VisitPaymentTimingResolver::class);
        $previousDecision = $resolver->resolve($this->visit(VisitType::OUTPATIENT, [$previous]));
        $this->assertSame(VisitPaymentPolicySource::VISIT_TYPE, $previousDecision->source);
        $this->assertTrue($previousDecision->context['previous_balance_override_present']);

        $deferred = new VisitBillingOverride([
            'override_type' => VisitBillingOverride::TYPE_DEFERRED_OPD_SETTLEMENT,
            'scope' => VisitBillingOverride::SCOPE_VISIT,
            'status' => VisitBillingOverride::STATUS_ACTIVE,
        ]);
        $deferredDecision = $resolver->resolve($this->visit(VisitType::OUTPATIENT, [$deferred]));
        $this->assertSame(VisitPaymentPolicySource::MANUAL_OVERRIDE, $deferredDecision->source);
        $this->assertSame(VisitPaymentTimingPolicy::PAY_AFTER_ALL_SERVICES, $deferredDecision->policy);
    }

    public function test_invalid_configuration_falls_back_to_an_operational_policy(): void
    {
        Setting::setValue('payment_timing', 'default_policy', 'invalid');
        Setting::setValue('payment_timing', 'outpatient_policy', 'invalid');
        $decision = app(VisitPaymentTimingResolver::class)->resolve($this->visit(VisitType::OUTPATIENT));

        $this->assertSame(VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE, $decision->policy);
        $this->assertNotSame(VisitPaymentTimingPolicy::INHERIT, $decision->policy);
    }
}
