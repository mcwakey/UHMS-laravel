<?php

namespace Tests\Feature;

use App\Data\Billing\LegacyPaymentGateSnapshot;
use App\Data\Billing\VisitPaymentTimingDecision;
use App\Enums\PaymentTimingIntegrationMode;
use App\Enums\VisitPaymentPolicySource;
use App\Enums\VisitPaymentTimingPolicy;
use App\Enums\VisitType;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Visit;
use App\Services\Billing\BillingPolicyService;
use App\Services\Billing\PaymentTimingConfigurationService;
use App\Services\Billing\PaymentTimingPolicyComparisonService;
use App\Services\Billing\VisitPaymentTimingResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class PaymentTimingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_integration_mode_defaults_and_invalid_values_fall_back_to_legacy(): void
    {
        $service = app(PaymentTimingConfigurationService::class);
        $this->assertSame(PaymentTimingIntegrationMode::LEGACY, $service->integrationMode());

        config(['payment_timing.integration.mode' => 'invalid']);
        $this->assertSame(PaymentTimingIntegrationMode::LEGACY, $service->integrationMode());

        config(['payment_timing.integration.mode' => 'observe']);
        $this->assertSame(PaymentTimingIntegrationMode::OBSERVE, $service->integrationMode());
    }

    public function test_legacy_integration_mode_does_not_run_observational_comparison(): void
    {
        config(['payment_timing.integration.mode' => 'legacy', 'billing_policy.enforce' => false]);
        $resolver = Mockery::mock(VisitPaymentTimingResolver::class);
        $resolver->shouldNotReceive('resolve');
        $comparison = Mockery::mock(PaymentTimingPolicyComparisonService::class);
        $comparison->shouldNotReceive('compare');
        $this->app->instance(VisitPaymentTimingResolver::class, $resolver);
        $this->app->instance(PaymentTimingPolicyComparisonService::class, $comparison);

        $decision = app(BillingPolicyService::class)->getInvoiceItemPolicy(new InvoiceItem);
        $this->assertTrue($decision->allowed);
        $this->assertSame(BillingPolicyService::MODE_ADVISORY, $decision->mode);
    }

    public function test_observe_mode_compares_but_returns_the_unchanged_legacy_result(): void
    {
        config(['payment_timing.integration.mode' => 'observe', 'billing_policy.enforce' => false]);
        $visit = new Visit(['visit_type' => VisitType::OUTPATIENT]);
        $item = (new InvoiceItem(['visit_id' => 10]))->setRelation('visit', $visit);
        $typed = new VisitPaymentTimingDecision(
            VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE,
            VisitPaymentPolicySource::GLOBAL_DEFAULT,
            'global_default',
        );

        $resolver = Mockery::mock(VisitPaymentTimingResolver::class);
        $resolver->shouldReceive('resolve')->once()->with($visit)->andReturn($typed);
        $comparison = Mockery::mock(PaymentTimingPolicyComparisonService::class);
        $comparison->shouldReceive('compare')->once()->withArgs(fn (
            Visit $actualVisit,
            LegacyPaymentGateSnapshot $legacy,
            VisitPaymentTimingDecision $actualTyped,
            array $context,
        ) => $actualVisit === $visit
            && $legacy->advisory
            && $actualTyped === $typed
            && $context['gate_operation'] === 'test_operation'
            && $context['payment_gate_stage'] === 'render');
        $this->app->instance(VisitPaymentTimingResolver::class, $resolver);
        $this->app->instance(PaymentTimingPolicyComparisonService::class, $comparison);

        $decision = app(BillingPolicyService::class)->getInvoiceItemPolicy($item, null, 'test_operation');
        $this->assertTrue($decision->allowed);
        $this->assertSame('ENFORCEMENT_DISABLED', $decision->reason);
    }

    public function test_observation_preserves_representative_paid_unpaid_covered_and_running_bill_results(): void
    {
        config([
            'billing_policy.enforce' => true,
            'payment_timing.integration.log_mismatches' => false,
        ]);
        $cases = [
            $this->item(VisitType::OUTPATIENT, ['patient_payable' => 100, 'balance' => 100, 'payment_status' => 'unpaid']),
            $this->item(VisitType::OUTPATIENT, ['patient_payable' => 100, 'paid_amount' => 100, 'balance' => 0, 'payment_status' => 'paid']),
            $this->item(VisitType::OUTPATIENT, ['patient_payable' => 0, 'insurance_covered' => 100, 'balance' => 0]),
            $this->item(VisitType::EMERGENCY, ['patient_payable' => 100, 'balance' => 100]),
            $this->item(VisitType::INPATIENT, ['patient_payable' => 100, 'balance' => 100]),
        ];

        foreach ($cases as $item) {
            config(['payment_timing.integration.mode' => 'legacy']);
            $legacy = app(BillingPolicyService::class)->getInvoiceItemPolicy($item)->toArray();
            config(['payment_timing.integration.mode' => 'observe']);
            $observed = app(BillingPolicyService::class)->getInvoiceItemPolicy($item)->toArray();
            $this->assertSame($legacy, $observed);
        }
    }

    public function test_typed_resolution_and_diagnostic_logging_failures_preserve_legacy_result(): void
    {
        config(['payment_timing.integration.mode' => 'observe', 'billing_policy.enforce' => false]);
        $visit = new Visit(['visit_type' => VisitType::OUTPATIENT]);
        $item = (new InvoiceItem(['visit_id' => 10]))->setRelation('visit', $visit);
        $resolver = Mockery::mock(VisitPaymentTimingResolver::class);
        $resolver->shouldReceive('resolve')->once()->andThrow(new \RuntimeException('resolver unavailable'));
        $this->app->instance(VisitPaymentTimingResolver::class, $resolver);
        Log::shouldReceive('warning')->once()->andThrow(new \RuntimeException('logger unavailable'));

        $decision = app(BillingPolicyService::class)->getInvoiceItemPolicy($item);
        $this->assertTrue($decision->allowed);
        $this->assertSame('ENFORCEMENT_DISABLED', $decision->reason);
    }

    private function item(VisitType $visitType, array $attributes): InvoiceItem
    {
        $visit = (new Visit(['visit_type' => $visitType]))
            ->setRelation('billingOverrides', collect());
        $item = new InvoiceItem(array_merge([
            'patient_payable' => 100,
            'paid_amount' => 0,
            'insurance_covered' => 0,
            'balance' => 100,
            'payment_status' => 'unpaid',
        ], $attributes));

        return $item
            ->setRelation('visit', $visit)
            ->setRelation('invoice', new Invoice(['balance' => $item->balance, 'adjustment_amount' => 0]));
    }
}
