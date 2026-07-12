<?php

namespace Tests\Feature;

use App\Enums\PaymentTimingCutoverMode;
use App\Models\Setting;
use App\Services\Billing\PaymentTimingCutoverConfigurationService;
use Database\Seeders\PaymentTimingSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTimingCutoverConfigTest extends TestCase
{
    use RefreshDatabase;

    private function service(): PaymentTimingCutoverConfigurationService
    {
        // Fresh (non-singleton) instance so the per-request memo doesn't mask changes.
        return app()->make(PaymentTimingCutoverConfigurationService::class, [
            'operationConfiguration' => app(\App\Services\Billing\PaymentGateOperationConfigurationService::class),
        ]);
    }

    public function test_default_and_invalid_master_mode_resolve_to_disabled(): void
    {
        $this->assertSame(PaymentTimingCutoverMode::DISABLED, $this->service()->configuredMode());

        Setting::setValue('payment_timing', 'cutover_mode', 'nonsense', 'string');
        $this->assertSame(PaymentTimingCutoverMode::DISABLED, $this->service()->configuredMode());
    }

    public function test_environment_force_legacy_overrides_active_database_setting(): void
    {
        Setting::setValue('payment_timing', 'cutover_mode', 'active', 'string');
        config(['payment_timing_cutover.force_legacy' => false]);
        $this->assertSame(PaymentTimingCutoverMode::ACTIVE, $this->service()->effectiveMode());

        config(['payment_timing_cutover.force_legacy' => true]);
        $this->assertSame(PaymentTimingCutoverMode::DISABLED, $this->service()->effectiveMode());
    }

    public function test_seeder_does_not_activate_cutover(): void
    {
        (new PaymentTimingSettingsSeeder)->run();
        $this->assertSame('disabled', Setting::getValue('payment_timing', 'cutover_mode'));
        $this->assertSame(PaymentTimingCutoverMode::DISABLED, $this->service()->configuredMode());
    }

    public function test_operation_uses_typed_only_when_active_and_typed(): void
    {
        Setting::setValue('payment_gate_operations', 'consultation.route.complete', ['mode' => 'typed'], 'json');

        Setting::setValue('payment_timing', 'cutover_mode', 'observe', 'string');
        $this->assertFalse($this->service()->operationUsesTypedPolicy('consultation.route.complete'));

        Setting::setValue('payment_timing', 'cutover_mode', 'active', 'string');
        $this->assertTrue($this->service()->operationUsesTypedPolicy('consultation.route.complete'));

        // Unwired operation can never use typed.
        Setting::setValue('payment_gate_operations', 'theatre.perform', ['mode' => 'typed'], 'json');
        $this->assertFalse($this->service()->operationUsesTypedPolicy('theatre.perform'));
    }
}
