<?php

namespace App\Http\Requests;

use App\Enums\MissingBillingContextPolicy;
use App\Enums\PaymentGateOperationMode;
use App\Enums\PaymentGateOverrideScopeRule;
use App\Enums\PaymentGateVisitContextRule;
use App\Services\Billing\PaymentGateOperationRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates departmental payment-enforcement operation-policy changes (Payment
 * Timing Policy Phase 4).
 *
 * Only UNWIRED operations may be configured here — existing wired hard gates are
 * read-only in Phase 4 so their production protection cannot be accidentally
 * relaxed. Typed enforcement cannot be selected (the mode enum has no such value).
 */
class UpdatePaymentGateOperationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'operations' => ['sometimes', 'array'],
            'operations.*.mode' => ['required', Rule::enum(PaymentGateOperationMode::class)],
            'operations.*.missing_context' => ['required', Rule::enum(MissingBillingContextPolicy::class)],
            'operations.*.visit_context_rule' => ['required', Rule::enum(PaymentGateVisitContextRule::class)],
            'operations.*.override_scope_rule' => ['required', Rule::enum(PaymentGateOverrideScopeRule::class)],
            'operations.*.emergency_exempt' => ['sometimes', 'boolean'],
            'operations.*.inpatient_exempt' => ['sometimes', 'boolean'],
            'operations.*.typed_enforcement_eligible' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $registry = app(PaymentGateOperationRegistry::class);
            $submitted = (array) $this->input('operations', []);
            $seen = [];

            foreach ($submitted as $operation => $_) {
                $operation = (string) $operation;

                if (isset($seen[$operation])) {
                    $validator->errors()->add("operations.{$operation}", __('payment_gate.errors.duplicate_operation'));

                    continue;
                }
                $seen[$operation] = true;

                $definition = $registry->get($operation);
                if ($definition === null) {
                    $validator->errors()->add("operations.{$operation}", __('payment_gate.errors.unknown_operation'));

                    continue;
                }

                // Existing wired hard gates are read-only in Phase 4.
                if (($definition['production_wired'] ?? false) || ($definition['hard_enforcement'] ?? false)) {
                    $validator->errors()->add("operations.{$operation}", __('payment_gate.errors.hard_gate_read_only'));
                }
            }
        }];
    }
}
