<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Enums\LogModule;
use App\Enums\MissingBillingContextPolicy;
use App\Enums\PaymentGateOperationMode;
use App\Enums\PaymentGateOverrideScopeRule;
use App\Enums\PaymentGateVisitContextRule;
use App\Enums\VisitPaymentTimingPolicy;
use App\Enums\VisitType;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePaymentGateOperationSettingsRequest;
use App\Http\Requests\UpdatePaymentTimingSettingsRequest;
use App\Models\ServiceCatalog;
use App\Models\Setting;
use App\Services\ActivityLogService;
use App\Services\Billing\PaymentGateEnforcementEligibilityService;
use App\Services\Billing\PaymentGateOperationCompatibilityService;
use App\Services\Billing\PaymentGateOperationConfigurationService;
use App\Services\Billing\PaymentGateOperationRegistry;
use App\Services\Billing\PaymentTimingConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    /**
     * Organization settings.
     */
    public function organization()
    {
        $settings = Setting::getGroup('organization');

        return view('settings.organization', compact('settings'));
    }

    public function updateOrganization(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|email|max:191',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'region' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:191',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('settings', 'public');
            Setting::setValue('organization', 'logo', $path);
        }

        unset($validated['logo']);

        foreach ($validated as $key => $value) {
            Setting::setValue('organization', $key, $value ?? '');
        }

        return back()->with('success', __('messages.settings.organization_updated'));
    }

    /**
     * Invoice settings.
     */
    public function invoice()
    {
        $settings = Setting::getGroup('invoice');

        return view('settings.invoice', compact('settings'));
    }

    public function updateInvoice(Request $request)
    {
        $validated = $request->validate([
            'prefix' => 'required|string|max:10',
            'due_days' => 'required|integer|min:1|max:365',
            'tax_enabled' => 'nullable|boolean',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'tax_label' => 'nullable|string|max:50',
            'footer_note' => 'nullable|string|max:500',
            'terms' => 'nullable|string|max:2000',
        ]);

        $validated['tax_enabled'] = $request->boolean('tax_enabled') ? '1' : '0';

        foreach ($validated as $key => $value) {
            Setting::setValue('invoice', $key, $value ?? '');
        }

        return back()->with('success', __('messages.settings.invoice_updated'));
    }

    /**
     * Payment methods settings.
     */
    public function paymentMethods()
    {
        $settings = Setting::getGroup('payment');

        return view('settings.payment-methods', compact('settings'));
    }

    public function updatePaymentMethods(Request $request)
    {
        $validated = $request->validate([
            'cash_enabled' => 'nullable|boolean',
            'momo_enabled' => 'nullable|boolean',
            'card_enabled' => 'nullable|boolean',
            'insurance_enabled' => 'nullable|boolean',
            'bank_transfer_enabled' => 'nullable|boolean',
            'momo_merchant_id' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_branch' => 'nullable|string|max:100',
        ]);

        $booleanFields = ['cash_enabled', 'momo_enabled', 'card_enabled', 'insurance_enabled', 'bank_transfer_enabled'];
        foreach ($booleanFields as $field) {
            $validated[$field] = $request->boolean($field) ? '1' : '0';
        }

        foreach ($validated as $key => $value) {
            Setting::setValue('payment', $key, $value ?? '');
        }

        return back()->with('success', __('messages.settings.payment_methods_updated'));
    }

    /** Payment timing policy foundation (configuration only in Phase 1). */
    public function paymentTiming(PaymentTimingConfigurationService $configuration)
    {
        $settings = [
            'enabled' => $configuration->enabled(),
            'default_policy' => $configuration->globalDefault()->value,
            'emergency_never_block_stabilisation' => $configuration->neverBlockEmergencyStabilisation(),
            'require_settlement_for_pay_after_services' => $configuration->requiresSettlementForPayAfterServices(),
            'require_settlement_for_running_bill' => $configuration->requiresSettlementForRunningBill(),
            'allow_outstanding_balance_override' => $configuration->allowsOutstandingBalanceOverride(),
        ];

        foreach (VisitType::cases() as $visitType) {
            $settings["{$visitType->value}_policy"] = $configuration->configuredPolicyForVisitType($visitType)->value;
        }

        return view('settings.payment-timing', [
            'settings' => $settings,
            'visitTypes' => VisitType::cases(),
            'globalPolicies' => VisitPaymentTimingPolicy::operationalPolicies(),
            'visitPolicies' => VisitPaymentTimingPolicy::selectable(),
        ]);
    }

    public function updatePaymentTiming(
        UpdatePaymentTimingSettingsRequest $request,
        ActivityLogService $activityLog,
    ) {
        $validated = $request->validated();
        $booleanKeys = [
            'enabled',
            'emergency_never_block_stabilisation',
            'require_settlement_for_pay_after_services',
            'require_settlement_for_running_bill',
            'allow_outstanding_balance_override',
        ];
        foreach ($booleanKeys as $key) {
            $validated[$key] = $request->boolean($key);
        }

        DB::transaction(function () use ($validated, $booleanKeys, $activityLog): void {
            $changes = [];
            foreach ($validated as $key => $value) {
                $type = in_array($key, $booleanKeys, true) ? 'boolean' : 'string';
                $old = Setting::getValue(PaymentTimingConfigurationService::GROUP, $key);
                $normalisedOld = $type === 'boolean' && $old !== null ? (bool) $old : $old;
                if ($normalisedOld === $value) {
                    continue;
                }

                Setting::setValue(PaymentTimingConfigurationService::GROUP, $key, $value, $type);
                $changes[$key] = ['old' => $normalisedOld, 'new' => $value];
            }

            if ($changes !== []) {
                $activityLog->log(LogModule::SETTINGS, 'PAYMENT_TIMING_SETTINGS_UPDATED', [
                    'setting_group' => PaymentTimingConfigurationService::GROUP,
                    'setting_keys' => array_keys($changes),
                    'old_values' => array_map(fn (array $change) => $change['old'], $changes),
                    'new_values' => array_map(fn (array $change) => $change['new'], $changes),
                ], description: 'Payment timing settings updated');
            }
        });

        return back()->with('success', __('payment_timing.updated_successfully'));
    }

    /**
     * Departmental payment enforcement (Payment Timing Policy Phase 4).
     * Read-only diagnostics for every registered operation; only unwired
     * operations are editable — wired hard gates are protected/read-only.
     */
    public function paymentGateOperations(
        PaymentGateOperationRegistry $registry,
        PaymentGateOperationConfigurationService $configuration,
        PaymentGateEnforcementEligibilityService $eligibility,
        PaymentGateOperationCompatibilityService $compatibility,
    ) {
        $groups = [];
        foreach ($registry->operations() as $operation => $definition) {
            $family = (string) ($definition['workflow_family'] ?? 'other');
            $groups[$family][] = [
                'operation' => $operation,
                'definition' => $definition,
                'policy' => $configuration->policyFor($operation),
                'eligibility' => $eligibility->evaluate($operation),
                'compatibility' => $compatibility->evaluate($operation),
                'editable' => ! ($definition['production_wired'] ?? false) && ! ($definition['hard_enforcement'] ?? false),
            ];
        }

        return view('settings.payment-gate-operations', [
            'groups' => $groups,
            'modes' => PaymentGateOperationMode::cases(),
            'missingContextOptions' => MissingBillingContextPolicy::cases(),
            'visitContextOptions' => PaymentGateVisitContextRule::cases(),
            'overrideScopeOptions' => PaymentGateOverrideScopeRule::cases(),
        ]);
    }

    public function updatePaymentGateOperations(
        UpdatePaymentGateOperationSettingsRequest $request,
        ActivityLogService $activityLog,
        PaymentGateOperationRegistry $registry,
    ) {
        $submitted = (array) ($request->validated()['operations'] ?? []);
        $group = PaymentGateOperationConfigurationService::GROUP;

        DB::transaction(function () use ($submitted, $registry, $activityLog, $group, $request): void {
            $changes = [];

            foreach ($submitted as $operation => $data) {
                $operation = (string) $operation;
                // Defence in depth: never persist wired/hard-gate operations here.
                $definition = $registry->get($operation);
                if ($definition === null || ($definition['production_wired'] ?? false) || ($definition['hard_enforcement'] ?? false)) {
                    continue;
                }

                $payload = [
                    'mode' => $data['mode'],
                    'missing_context' => $data['missing_context'],
                    'visit_context_rule' => $data['visit_context_rule'],
                    'override_scope_rule' => $data['override_scope_rule'],
                    'emergency_exempt' => $request->boolean("operations.{$operation}.emergency_exempt"),
                    'inpatient_exempt' => $request->boolean("operations.{$operation}.inpatient_exempt"),
                    'typed_enforcement_eligible' => $request->boolean("operations.{$operation}.typed_enforcement_eligible"),
                ];

                $old = Setting::getValue($group, $operation);
                if (is_array($old) && $old == $payload) {
                    continue; // unchanged
                }

                Setting::setValue($group, $operation, $payload, 'json');
                $changes[$operation] = ['old' => $old, 'new' => $payload];
            }

            if ($changes !== []) {
                $activityLog->log(LogModule::SETTINGS, 'PAYMENT_GATE_OPERATION_SETTINGS_UPDATED', [
                    'setting_group' => $group,
                    'setting_keys' => array_keys($changes),
                    'old_values' => array_map(fn (array $c) => $c['old'], $changes),
                    'new_values' => array_map(fn (array $c) => $c['new'], $changes),
                ], description: 'Payment gate operation settings updated');
            }
        });

        return back()->with('success', __('payment_gate.updated_successfully'));
    }

    /**
     * Ward & Admissions settings.
     */
    public function ward()
    {
        $settings = Setting::getGroup('ward');
        $services = ServiceCatalog::where('is_active', true)->orderBy('name')->get();

        return view('settings.ward', compact('settings', 'services'));
    }

    public function updateWard(Request $request)
    {
        $validated = $request->validate([
            'admission_fee_service_id'  => 'nullable|integer|exists:service_catalog,id',
            'detention_fee_service_id'  => 'nullable|integer|exists:service_catalog,id',
            'consumable_fee_service_id' => 'nullable|integer|exists:service_catalog,id',
        ]);

        Setting::setValue('ward', 'admission_fee_service_id',  $validated['admission_fee_service_id']  ?? '', 'integer');
        Setting::setValue('ward', 'detention_fee_service_id',  $validated['detention_fee_service_id']  ?? '', 'integer');
        Setting::setValue('ward', 'consumable_fee_service_id', $validated['consumable_fee_service_id'] ?? '', 'integer');

        return back()->with('success', __('messages.settings.ward_updated'));
    }
}
