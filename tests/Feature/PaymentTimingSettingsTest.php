<?php

namespace Tests\Feature;

use App\Data\Billing\PaymentGateContext;
use App\Enums\VisitType;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\User;
use App\Models\Visit;
use App\Services\Billing\BillingPolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PaymentTimingSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['audit_streaming.async_writes' => false]);
        Permission::findOrCreate('settings.manage', 'web');
        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo('settings.manage');
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'enabled' => '1',
            'default_policy' => 'pay_after_all_services',
            'outpatient_policy' => 'inherit',
            'inpatient_policy' => 'running_bill',
            'emergency_policy' => 'running_bill',
            'emergency_never_block_stabilisation' => '1',
            'require_settlement_for_pay_after_services' => '1',
            'require_settlement_for_running_bill' => '1',
            'allow_outstanding_balance_override' => '1',
        ], $overrides);
    }

    public function test_authorised_user_can_view_and_update_settings_with_audit(): void
    {
        $this->actingAs($this->admin)->get(route('admin.settings.payment-timing'))->assertOk();
        $this->actingAs($this->admin)
            ->put(route('admin.settings.payment-timing.update'), $this->validPayload())
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('pay_after_all_services', Setting::getValue('payment_timing', 'default_policy'));
        $this->assertSame('inherit', Setting::getValue('payment_timing', 'outpatient_policy'));
        $this->assertTrue(ActivityLog::where('event', 'PAYMENT_TIMING_SETTINGS_UPDATED')->exists());
    }

    public function test_unauthorised_user_cannot_update_settings(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('admin.settings.payment-timing.update'), $this->validPayload())
            ->assertForbidden();
        $this->assertDatabaseMissing('settings', ['group' => 'payment_timing']);
    }

    public function test_global_default_rejects_inherit_without_writing_or_auditing(): void
    {
        Setting::setValue('payment_timing', 'default_policy', 'pay_before_service');

        $this->actingAs($this->admin)
            ->from(route('admin.settings.payment-timing'))
            ->put(route('admin.settings.payment-timing.update'), $this->validPayload(['default_policy' => 'inherit']))
            ->assertSessionHasErrors('default_policy');

        $this->assertSame('pay_before_service', Setting::getValue('payment_timing', 'default_policy'));
        $this->assertFalse(ActivityLog::where('event', 'PAYMENT_TIMING_SETTINGS_UPDATED')->exists());
    }

    public function test_invalid_visit_policy_is_rejected_but_inherit_is_accepted(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.payment-timing.update'), $this->validPayload(['outpatient_policy' => 'invalid']))
            ->assertSessionHasErrors('outpatient_policy');
        $this->assertDatabaseMissing('settings', ['group' => 'payment_timing']);

        $this->actingAs($this->admin)
            ->put(route('admin.settings.payment-timing.update'), $this->validPayload(['outpatient_policy' => 'inherit']))
            ->assertSessionHasNoErrors();
    }

    public function test_unknown_visit_type_policy_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.payment-timing.update'), $this->validPayload(['daycare_policy' => 'inherit']))
            ->assertSessionHasErrors('daycare_policy');

        $this->assertDatabaseMissing('settings', ['group' => 'payment_timing']);
    }

    public function test_enabled_running_bill_setting_controls_payment_gate_behaviour(): void
    {
        Setting::setValue('payment_timing', 'enabled', true, 'boolean');
        Setting::setValue('payment_timing', 'default_policy', 'running_bill', 'string');
        Setting::setValue('payment_timing', 'outpatient_policy', 'inherit', 'string');
        config(['billing_policy.enforce' => true, 'billing_policy.opd.payment_required_before_service' => true]);

        $visit = Visit::factory()->create([
            'patient_id' => Patient::factory()->create()->id,
            'visit_type' => VisitType::OUTPATIENT->value,
        ]);
        $item = (new InvoiceItem([
            'visit_id' => $visit->id,
            'patient_payable' => 100,
            'paid_amount' => 0,
            'insurance_covered' => 0,
            'balance' => 100,
            'payment_status' => 'unpaid',
        ]))->setRelation('visit', $visit)
            ->setRelation('invoice', new Invoice(['balance' => 100, 'adjustment_amount' => 0]));

        $decision = app(BillingPolicyService::class)->getInvoiceItemPolicy(
            $item,
            null,
            PaymentGateContext::triageRouteCompletion(),
        );

        $this->assertTrue($decision->allowed);
        $this->assertSame('typed_baseline', $decision->decisionAuthority);
        $this->assertSame(BillingPolicyService::MODE_RUNNING_BILL, $decision->mode);
    }

    public function test_new_localisation_files_have_matching_keys(): void
    {
        $en = require lang_path('en/payment_timing.php');
        $fr = require lang_path('fr/payment_timing.php');
        $this->assertSame(array_keys($en), array_keys($fr));
        $this->assertSame(array_keys($en['policies']), array_keys($fr['policies']));
        $this->assertSame(array_keys($en['sources']), array_keys($fr['sources']));
        $this->assertSame(array_keys($en['integration']), array_keys($fr['integration']));
        $this->assertSame(array_keys($en['gate_stages']), array_keys($fr['gate_stages']));
    }
}
