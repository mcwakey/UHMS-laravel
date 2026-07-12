<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\PaymentTimingSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PaymentTimingCutoverPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['audit_streaming.async_writes' => false]);
        User::factory()->create(); // id 1
        (new PaymentTimingSettingsSeeder)->run();
        foreach ([
            'billing.payment_timing.cutover.view', 'billing.payment_timing.cutover.manage',
            'billing.payment_timing.cutover.activate', 'billing.payment_timing.cutover.rollback',
        ] as $p) {
            Permission::findOrCreate($p, 'web');
        }
    }

    private function user(array $permissions): User
    {
        $u = User::factory()->create();
        $u->givePermissionTo($permissions);

        return $u;
    }

    public function test_page_requires_view_permission(): void
    {
        $this->actingAs($this->user([]))->get(route('admin.billing.payment-timing-cutover.index'))->assertForbidden();
        $this->actingAs($this->user(['billing.payment_timing.cutover.view']))->get(route('admin.billing.payment-timing-cutover.index'))->assertOk();
    }

    public function test_finance_manager_style_user_cannot_activate(): void
    {
        // manage but NOT activate
        $this->actingAs($this->user(['billing.payment_timing.cutover.manage']))
            ->put(route('admin.billing.payment-timing-cutover.master'), ['mode' => 'active', 'reason' => 'go', 'confirm' => '1'])
            ->assertSessionHasErrors('mode');

        $this->assertSame('disabled', Setting::getValue('payment_timing', 'cutover_mode'));
    }

    public function test_admin_can_activate_with_reason_and_confirmation_and_audit(): void
    {
        $this->actingAs($this->user(['billing.payment_timing.cutover.manage', 'billing.payment_timing.cutover.activate']))
            ->put(route('admin.billing.payment-timing-cutover.master'), ['mode' => 'active', 'reason' => 'go-live', 'confirm' => '1'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('active', Setting::getValue('payment_timing', 'cutover_mode'));
        $this->assertTrue(ActivityLog::where('event', 'PAYMENT_TIMING_CUTOVER_MODE_CHANGED')->exists());
    }

    public function test_activation_requires_confirmation(): void
    {
        $this->actingAs($this->user(['billing.payment_timing.cutover.manage', 'billing.payment_timing.cutover.activate']))
            ->put(route('admin.billing.payment-timing-cutover.master'), ['mode' => 'active', 'reason' => 'go'])
            ->assertSessionHasErrors('confirm');
    }

    public function test_typed_operation_requires_acknowledgement_and_rejects_unwired(): void
    {
        $manager = $this->user(['billing.payment_timing.cutover.manage']);

        // Pharmacy typed without acknowledgement is rejected.
        $this->actingAs($manager)->put(route('admin.billing.payment-timing-cutover.operation'), [
            'operation' => 'pharmacy.item.dispense', 'mode' => 'typed', 'reason' => 'x',
        ])->assertSessionHasErrors('compatibility_acknowledged');

        // Unwired operation rejects typed.
        $this->actingAs($manager)->put(route('admin.billing.payment-timing-cutover.operation'), [
            'operation' => 'theatre.perform', 'mode' => 'typed', 'reason' => 'x',
        ])->assertSessionHasErrors('mode');
    }

    public function test_rollback_requires_permission_and_returns_to_legacy(): void
    {
        Setting::setValue('payment_timing', 'cutover_mode', 'active', 'string');

        $this->actingAs($this->user(['billing.payment_timing.cutover.manage']))
            ->post(route('admin.billing.payment-timing-cutover.rollback'), ['reason' => 'stop'])
            ->assertForbidden();

        $this->actingAs($this->user(['billing.payment_timing.cutover.rollback']))
            ->post(route('admin.billing.payment-timing-cutover.rollback'), ['reason' => 'stop'])
            ->assertRedirect();

        $this->assertSame('disabled', Setting::getValue('payment_timing', 'cutover_mode'));
        $this->assertTrue(ActivityLog::where('event', 'PAYMENT_TIMING_FORCE_LEGACY_ROLLBACK')->exists());
    }
}
