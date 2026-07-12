<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Enums\LogModule;
use App\Enums\PaymentGateOperationMode;
use App\Enums\PaymentTimingCutoverMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\RollbackPaymentTimingCutoverRequest;
use App\Http\Requests\Billing\UpdatePaymentTimingCutoverMasterRequest;
use App\Http\Requests\Billing\UpdatePaymentTimingCutoverOperationRequest;
use App\Models\Setting;
use App\Services\ActivityLogService;
use App\Services\Billing\PaymentGateOperationConfigurationService;
use App\Services\Billing\PaymentGateOperationRegistry;
use App\Services\Billing\PaymentTimingCutoverConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Admin operational-payment-cutover interface (Payment Timing Policy Phase 8).
 * Restricted + audited. Activating and rolling back are permission-gated; the
 * environment kill switch always wins over these settings at runtime.
 */
class PaymentTimingCutoverController extends Controller
{
    private const OPERATION_GROUP = 'payment_gate_operations';

    public function index(
        PaymentTimingCutoverConfigurationService $cutover,
        PaymentGateOperationRegistry $registry,
        PaymentGateOperationConfigurationService $operationConfig,
    ) {
        $effective = $cutover->effectiveMode();
        $operations = [];
        foreach ($registry->operations() as $operation => $definition) {
            if (! ($definition['approved_for_typed_enforcement'] ?? false)) {
                continue; // only show typed-eligible (wired, approved) operations
            }
            $policy = $operationConfig->policyFor($operation);
            $acknowledged = (bool) ($policy->metadata['compatibility_acknowledged'] ?? false);
            $requiresAck = (bool) ($definition['requires_compatibility_acknowledgement'] ?? false);

            $blockers = [];
            if ($policy->mode === PaymentGateOperationMode::TYPED) {
                if ($cutover->forceLegacy()) {
                    $blockers[] = 'force_legacy';
                }
                if ($effective !== PaymentTimingCutoverMode::ACTIVE) {
                    $blockers[] = 'master_not_active';
                }
                if ($requiresAck && ! $acknowledged) {
                    $blockers[] = 'acknowledgement_missing';
                }
            }

            $operations[] = [
                'operation' => $operation,
                'wired' => (bool) ($definition['production_wired'] ?? false),
                'mode' => $policy->mode,
                'supported_visit_types' => $definition['typed_supported_visit_types'] ?? [],
                'requires_acknowledgement' => $requiresAck,
                'acknowledged' => $acknowledged,
                'emergency_supported' => (bool) ($definition['emergency_supported'] ?? false),
                'compatibility_description' => $definition['compatibility_change_description'] ?? null,
                'blockers' => $blockers,
            ];
        }

        return view('admin.billing.payment-timing-cutover.index', [
            'configuredMode' => $cutover->configuredMode(),
            'effectiveMode' => $effective,
            'forceLegacy' => $cutover->forceLegacy(),
            'failureFallback' => $cutover->fallbackToLegacyOnFailure(),
            'masterModes' => PaymentTimingCutoverMode::selectable(),
            'operationModes' => [PaymentGateOperationMode::LEGACY, PaymentGateOperationMode::OBSERVE, PaymentGateOperationMode::TYPED],
            'operations' => $operations,
        ]);
    }

    public function updateMaster(UpdatePaymentTimingCutoverMasterRequest $request, ActivityLogService $activityLog): RedirectResponse
    {
        $new = $request->validated()['mode'];
        $reason = $request->validated()['reason'];

        DB::transaction(function () use ($new, $reason, $activityLog): void {
            $old = (string) (Setting::getValue(PaymentTimingCutoverConfigurationService::GROUP, 'cutover_mode') ?? PaymentTimingCutoverMode::DISABLED->value);
            if ($old === $new) {
                return;
            }
            Setting::setValue(PaymentTimingCutoverConfigurationService::GROUP, 'cutover_mode', $new, 'string');
            $activityLog->log(LogModule::SETTINGS, 'PAYMENT_TIMING_CUTOVER_MODE_CHANGED', [
                'reason' => $reason,
                'old_values' => ['cutover_mode' => $old],
                'new_values' => ['cutover_mode' => $new],
            ], description: 'Payment timing cutover mode changed');
        });

        return back()->with('success', __('payment_timing_cutover.flash.master_updated'));
    }

    public function updateOperation(UpdatePaymentTimingCutoverOperationRequest $request, ActivityLogService $activityLog): RedirectResponse
    {
        $data = $request->validated();
        $operation = $data['operation'];
        $mode = $data['mode'];
        $acknowledged = (bool) ($data['compatibility_acknowledged'] ?? false);

        DB::transaction(function () use ($operation, $mode, $acknowledged, $data, $activityLog): void {
            $stored = Setting::getValue(self::OPERATION_GROUP, $operation);
            $stored = is_array($stored) ? $stored : [];
            $oldMode = (string) ($stored['mode'] ?? PaymentGateOperationMode::LEGACY->value);

            $payload = array_merge($stored, [
                'mode' => $mode,
                'compatibility_acknowledged' => $acknowledged,
            ]);
            if ($oldMode === $mode && (bool) ($stored['compatibility_acknowledged'] ?? false) === $acknowledged) {
                return;
            }
            Setting::setValue(self::OPERATION_GROUP, $operation, $payload, 'json');

            $action = $mode === PaymentGateOperationMode::TYPED->value
                ? 'PAYMENT_GATE_TYPED_OPERATION_ENABLED'
                : 'PAYMENT_GATE_TYPED_OPERATION_DISABLED';
            $activityLog->log(LogModule::SETTINGS, $action, [
                'reason' => $data['reason'],
                'setting_keys' => [$operation],
                'old_values' => ['mode' => $oldMode],
                'new_values' => ['mode' => $mode, 'compatibility_acknowledged' => $acknowledged],
            ], description: 'Payment gate typed operation mode changed');
        });

        return back()->with('success', __('payment_timing_cutover.flash.operation_updated'));
    }

    public function rollback(RollbackPaymentTimingCutoverRequest $request, ActivityLogService $activityLog): RedirectResponse
    {
        $reason = $request->validated()['reason'];

        DB::transaction(function () use ($reason, $activityLog): void {
            $old = (string) (Setting::getValue(PaymentTimingCutoverConfigurationService::GROUP, 'cutover_mode') ?? PaymentTimingCutoverMode::DISABLED->value);
            Setting::setValue(PaymentTimingCutoverConfigurationService::GROUP, 'cutover_mode', PaymentTimingCutoverMode::DISABLED->value, 'string');
            $activityLog->log(LogModule::SETTINGS, 'PAYMENT_TIMING_FORCE_LEGACY_ROLLBACK', [
                'reason' => $reason,
                'old_values' => ['cutover_mode' => $old],
                'new_values' => ['cutover_mode' => PaymentTimingCutoverMode::DISABLED->value],
            ], description: 'Payment timing cutover rolled back to legacy');
        });

        return back()->with('success', __('payment_timing_cutover.flash.rolled_back'));
    }
}
