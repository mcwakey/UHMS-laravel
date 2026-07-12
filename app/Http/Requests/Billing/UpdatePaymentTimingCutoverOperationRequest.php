<?php

namespace App\Http\Requests\Billing;

use App\Enums\PaymentGateOperationMode;
use App\Services\Billing\PaymentGateOperationConfigurationService;
use App\Services\Billing\PaymentGateOperationRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates a per-operation cutover-mode change (Payment Timing Policy Phase 8).
 * Only legacy/observe/typed may be chosen; typed requires a wired, registry-
 * approved operation and (for lab/pharmacy) an explicit compatibility
 * acknowledgement. Unknown/unwired operations and invalid typed selections are
 * rejected in the backend — UI manipulation cannot bypass registry safety.
 */
class UpdatePaymentTimingCutoverOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('billing.payment_timing.cutover.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'operation' => ['required', 'string'],
            'mode' => ['required', Rule::in([
                PaymentGateOperationMode::LEGACY->value,
                PaymentGateOperationMode::OBSERVE->value,
                PaymentGateOperationMode::TYPED->value,
            ])],
            'compatibility_acknowledged' => ['nullable', 'boolean'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $registry = app(PaymentGateOperationRegistry::class);
            $config = app(PaymentGateOperationConfigurationService::class);
            $operation = (string) $this->input('operation');

            $definition = $registry->get($operation);
            if ($definition === null) {
                $validator->errors()->add('operation', __('payment_timing_cutover.errors.unknown_operation'));

                return;
            }

            if ($this->input('mode') !== PaymentGateOperationMode::TYPED->value) {
                return; // legacy/observe always permitted for a registered operation
            }

            // Typed requires an eligible wired hard gate approved for typed enforcement.
            if (! $config->operationTypedEligible($operation)) {
                $validator->errors()->add('mode', __('payment_timing_cutover.errors.operation_not_typed_eligible'));
            }

            if (($definition['requires_compatibility_acknowledgement'] ?? false) && ! $this->boolean('compatibility_acknowledged')) {
                $validator->errors()->add('compatibility_acknowledged', __('payment_timing_cutover.errors.acknowledgement_required'));
            }
        }];
    }
}
