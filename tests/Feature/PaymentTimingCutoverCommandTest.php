<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\PaymentTimingSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PaymentTimingCutoverCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['audit_streaming.async_writes' => false]);
        User::factory()->create(); // id 1
        (new PaymentTimingSettingsSeeder)->run();
    }

    public function test_status_command_reports_modes_as_json(): void
    {
        $exit = Artisan::call('billing:payment-timing-cutover-status', ['--json' => true]);
        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exit);
        $this->assertSame('disabled', $payload['configured_mode']);
        $this->assertSame('disabled', $payload['effective_mode']);
        $this->assertFalse($payload['force_legacy']);
    }

    public function test_audit_command_detects_active_while_force_legacy_read_only(): void
    {
        Setting::setValue('payment_timing', 'cutover_mode', 'active', 'string');
        config(['payment_timing_cutover.force_legacy' => true]);
        $before = ActivityLog::count();

        $exit = Artisan::call('billing:payment-timing-cutover-audit', ['--json' => true]);
        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exit);
        $this->assertContains('master_active_while_force_legacy', $payload['findings']['master'] ?? []);
        $this->assertSame($before, ActivityLog::count());
    }

    public function test_audit_command_flags_typed_on_unwired_operation(): void
    {
        Setting::setValue('payment_gate_operations', 'theatre.perform', ['mode' => 'typed'], 'json');

        Artisan::call('billing:payment-timing-cutover-audit', ['--json' => true]);
        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('operation:theatre.perform', $payload['findings']);
    }

    public function test_preview_reports_missing_context_without_writes(): void
    {
        $exit = Artisan::call('billing:payment-timing-cutover-preview', ['--json' => true]);
        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exit);
        $this->assertSame('missing_context', $payload['error']);
    }
}
