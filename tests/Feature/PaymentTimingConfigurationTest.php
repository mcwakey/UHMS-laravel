<?php

namespace Tests\Feature;

use App\Enums\VisitPaymentTimingPolicy;
use App\Enums\VisitType;
use App\Models\Setting;
use App\Services\Billing\PaymentTimingConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PaymentTimingConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_config_defaults_are_typed_and_emergency_protection_is_enabled(): void
    {
        $service = app(PaymentTimingConfigurationService::class);

        $this->assertSame(VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE, $service->globalDefault());
        $this->assertSame(VisitPaymentTimingPolicy::RUNNING_BILL, $service->policyForVisitType(VisitType::EMERGENCY));
        $this->assertTrue($service->neverBlockEmergencyStabilisation());
    }

    public function test_database_values_override_config_and_inherit_resolves_to_global(): void
    {
        Setting::setValue('payment_timing', 'default_policy', 'pay_after_all_services');
        Setting::setValue('payment_timing', 'outpatient_policy', 'inherit');
        Setting::setValue('payment_timing', 'inpatient_policy', 'pay_before_service');

        $service = app(PaymentTimingConfigurationService::class);
        $this->assertSame(VisitPaymentTimingPolicy::PAY_AFTER_ALL_SERVICES, $service->policyForVisitType('outpatient'));
        $this->assertSame(VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE, $service->policyForVisitType(VisitType::INPATIENT));
    }

    public function test_invalid_stored_values_fall_back_safely(): void
    {
        Setting::setValue('payment_timing', 'default_policy', 'legacy_invalid');
        Setting::setValue('payment_timing', 'outpatient_policy', 'also_invalid');
        Cache::flush();

        $service = app(PaymentTimingConfigurationService::class);
        $this->assertSame(VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE, $service->globalDefault());
        $this->assertSame(VisitPaymentTimingPolicy::INHERIT, $service->configuredPolicyForVisitType(VisitType::OUTPATIENT));
    }

    public function test_global_default_never_returns_inherit_even_if_config_is_invalid(): void
    {
        config(['payment_timing.default_policy' => 'inherit']);
        Setting::setValue('payment_timing', 'default_policy', 'inherit');

        $this->assertSame(
            VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE,
            app(PaymentTimingConfigurationService::class)->globalDefault(),
        );
    }
}
