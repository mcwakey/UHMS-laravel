<?php

namespace Tests\Feature;

use App\Data\Billing\PaymentGateEnforcementEligibility;
use App\Enums\PaymentGateOperationMode;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\Billing\PaymentGateEnforcementEligibilityService;
use App\Services\Billing\PaymentGateOperationCompatibilityService;
use App\Services\Billing\PaymentGateOperationConfigurationService;
use App\Services\Billing\PaymentGateOperationRegistry;
use Database\Seeders\PaymentGateOperationSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PaymentGateOperationPolicyTest extends TestCase
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

    private function registry(): PaymentGateOperationRegistry
    {
        return app(PaymentGateOperationRegistry::class);
    }

    // --- Registry -----------------------------------------------------------

    public function test_registry_exposes_all_operations_with_unique_codes_and_required_defaults(): void
    {
        $operations = $this->registry()->operations();

        $this->assertCount(13, $operations);
        $this->assertSame(array_keys($operations), array_values(array_unique(array_keys($operations))));

        foreach ($operations as $code => $definition) {
            foreach (['stage', 'default_mode', 'missing_billing_context', 'visit_context_rule', 'override_scope_rule', 'workflow_family'] as $key) {
                $this->assertArrayHasKey($key, $definition, "{$code} is missing {$key}");
            }
        }
    }

    public function test_registry_lookup_helpers_agree(): void
    {
        $registry = $this->registry();
        $this->assertTrue($registry->has('consultation.route.complete'));
        $this->assertFalse($registry->has('does.not.exist'));
        $this->assertNull($registry->get('does.not.exist'));
        $this->assertSame(array_keys($registry->operations()), $registry->operationCodes());
    }

    // --- Enums --------------------------------------------------------------

    public function test_mode_enum_labels_resolve_through_localisation(): void
    {
        $this->assertSame(__('payment_gate.modes.disabled'), PaymentGateOperationMode::DISABLED->label());
        $this->assertSame(__('payment_gate.modes.legacy'), PaymentGateOperationMode::LEGACY->label());
    }

    // --- Configuration service ---------------------------------------------

    public function test_unwired_operations_default_to_disabled_and_wired_hard_gates_to_legacy(): void
    {
        $configuration = app(PaymentGateOperationConfigurationService::class);

        $this->assertSame(PaymentGateOperationMode::DISABLED, $configuration->modeFor('consultation.start'));
        $this->assertSame(PaymentGateOperationMode::LEGACY, $configuration->modeFor('pharmacy.item.dispense'));
    }

    public function test_unknown_operation_resolves_to_safe_disabled_policy(): void
    {
        $policy = app(PaymentGateOperationConfigurationService::class)->policyFor('totally.unknown');

        $this->assertSame(PaymentGateOperationMode::DISABLED, $policy->mode);
    }

    public function test_wired_hard_gate_cannot_be_relaxed_to_disabled_via_stored_config(): void
    {
        Setting::setValue(
            PaymentGateOperationConfigurationService::GROUP,
            'pharmacy.item.dispense',
            ['mode' => PaymentGateOperationMode::DISABLED->value],
            'json',
        );

        $mode = app(PaymentGateOperationConfigurationService::class)->modeFor('pharmacy.item.dispense');

        // Clamped back to the compatibility-safe legacy default.
        $this->assertSame(PaymentGateOperationMode::LEGACY, $mode);
    }

    // --- Eligibility --------------------------------------------------------

    public function test_active_payment_gate_calls_are_reported_as_policy_covered(): void
    {
        $eligibility = app(PaymentGateEnforcementEligibilityService::class);

        $this->assertTrue($eligibility->evaluate('consultation.route.complete')->eligible);
        $this->assertTrue($eligibility->evaluate('consultation.next_patient.readiness')->eligible);
        $this->assertTrue($eligibility->evaluate('laboratory.result.enter')->eligible);
        $this->assertTrue($eligibility->evaluate('pharmacy.item.dispense')->eligible);

        foreach (['theatre.perform', 'service.render', 'ambulance.service.render', 'nursing.service.render'] as $unwired) {
            $this->assertFalse($eligibility->evaluate($unwired)->eligible, "{$unwired} has no active payment-gate call");
        }
    }

    public function test_eligibility_reports_expected_primary_reasons(): void
    {
        $eligibility = app(PaymentGateEnforcementEligibilityService::class);

        $this->assertSame(
            PaymentGateEnforcementEligibility::INELIGIBLE_UNWIRED,
            $eligibility->evaluate('theatre.perform')->status,
        );
        $this->assertSame(
            PaymentGateEnforcementEligibility::ELIGIBLE,
            $eligibility->evaluate('pharmacy.item.dispense')->status,
        );
        // Phase 8 — consultation route completion is now typed-eligible.
        $this->assertSame(
            PaymentGateEnforcementEligibility::ELIGIBLE,
            $eligibility->evaluate('consultation.route.complete')->status,
        );
    }

    // --- Compatibility ------------------------------------------------------

    public function test_compatibility_reports_active_gate_calls_as_operational_coverage(): void
    {
        $compatibility = app(PaymentGateOperationCompatibilityService::class);

        $this->assertTrue($compatibility->evaluate('consultation.route.complete')['operational']);
        $this->assertTrue($compatibility->evaluate('laboratory.result.enter')['operational']);
        $this->assertTrue($compatibility->evaluate('pharmacy.item.dispense')['operational']);
        $this->assertFalse($compatibility->evaluate('service.render')['operational']);
    }

    // --- Admin settings -----------------------------------------------------

    public function test_authorised_user_can_view_settings(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings.payment-gate-operations'))
            ->assertOk()
            ->assertSee('consultation.start');
    }

    public function test_authorised_user_can_update_an_unwired_operation_with_audit(): void
    {
        $payload = ['operations' => ['consultation.start' => [
            'mode' => PaymentGateOperationMode::OBSERVE->value,
            'missing_context' => 'preserve_legacy',
            'visit_context_rule' => 'use_visit_policy',
            'override_scope_rule' => 'none',
            'emergency_exempt' => '0',
            'inpatient_exempt' => '0',
            'typed_enforcement_eligible' => '0',
        ]]];

        $this->actingAs($this->admin)
            ->put(route('admin.settings.payment-gate-operations.update'), $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $stored = Setting::getValue(PaymentGateOperationConfigurationService::GROUP, 'consultation.start');
        $this->assertSame(PaymentGateOperationMode::OBSERVE->value, $stored['mode']);
        $this->assertTrue(ActivityLog::where('event', 'PAYMENT_GATE_OPERATION_SETTINGS_UPDATED')->exists());
    }

    public function test_wired_hard_gate_is_read_only_and_rejected(): void
    {
        $payload = ['operations' => ['pharmacy.item.dispense' => [
            'mode' => PaymentGateOperationMode::DISABLED->value,
            'missing_context' => 'preserve_legacy',
            'visit_context_rule' => 'preserve_legacy',
            'override_scope_rule' => 'preserve_legacy',
        ]]];

        $this->actingAs($this->admin)
            ->from(route('admin.settings.payment-gate-operations'))
            ->put(route('admin.settings.payment-gate-operations.update'), $payload)
            ->assertSessionHasErrors('operations.pharmacy.item.dispense');

        $this->assertDatabaseMissing('settings', [
            'group' => PaymentGateOperationConfigurationService::GROUP,
            'key' => 'pharmacy.item.dispense',
        ]);
        $this->assertFalse(ActivityLog::where('event', 'PAYMENT_GATE_OPERATION_SETTINGS_UPDATED')->exists());
    }

    public function test_unauthorised_user_cannot_update_settings(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('admin.settings.payment-gate-operations.update'), ['operations' => []])
            ->assertForbidden();
    }

    // --- Command ------------------------------------------------------------

    public function test_policy_audit_command_runs_read_only_and_succeeds(): void
    {
        $before = ActivityLog::query()->count();
        $exit = Artisan::call('billing:payment-gate-policy-audit', ['--json' => true]);
        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exit);
        $this->assertSame(13, $payload['operations_audited']);
        $this->assertSame($before, ActivityLog::query()->count());
    }

    // --- Seeder -------------------------------------------------------------

    public function test_seeder_is_idempotent_and_preserves_admin_values(): void
    {
        // Admin sets a custom value first.
        Setting::setValue(
            PaymentGateOperationConfigurationService::GROUP,
            'consultation.start',
            ['mode' => PaymentGateOperationMode::OBSERVE->value],
            'json',
        );

        (new PaymentGateOperationSettingsSeeder)->run();
        (new PaymentGateOperationSettingsSeeder)->run();

        // Admin value untouched.
        $stored = Setting::getValue(PaymentGateOperationConfigurationService::GROUP, 'consultation.start');
        $this->assertSame(PaymentGateOperationMode::OBSERVE->value, $stored['mode']);

        // Exactly one row per operation (no duplicates from repeat runs).
        $this->assertSame(13, Setting::where('group', PaymentGateOperationConfigurationService::GROUP)->count());
    }

    // --- Localisation -------------------------------------------------------

    public function test_localisation_files_have_matching_keys(): void
    {
        $en = require lang_path('en/payment_gate.php');
        $fr = require lang_path('fr/payment_gate.php');

        $this->assertSame(array_keys($en), array_keys($fr));
        foreach (['modes', 'missing_context', 'visit_context', 'override_scope', 'eligibility', 'compatibility', 'family', 'errors'] as $group) {
            $this->assertSame(array_keys($en[$group]), array_keys($fr[$group]), "Locale mismatch in {$group}");
        }
    }
}
