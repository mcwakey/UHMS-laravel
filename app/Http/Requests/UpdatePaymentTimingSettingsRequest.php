<?php

namespace App\Http\Requests;

use App\Enums\VisitPaymentTimingPolicy;
use App\Enums\VisitType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePaymentTimingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') ?? false;
    }

    public function rules(): array
    {
        $rules = [
            'enabled' => ['sometimes', 'boolean'],
            'default_policy' => ['required', Rule::enum(VisitPaymentTimingPolicy::class), Rule::notIn([VisitPaymentTimingPolicy::INHERIT->value])],
            'emergency_never_block_stabilisation' => ['sometimes', 'boolean'],
            'require_settlement_for_pay_after_services' => ['sometimes', 'boolean'],
            'require_settlement_for_running_bill' => ['sometimes', 'boolean'],
            'allow_outstanding_balance_override' => ['sometimes', 'boolean'],
        ];

        foreach (VisitType::cases() as $visitType) {
            $rules["{$visitType->value}_policy"] = ['required', Rule::enum(VisitPaymentTimingPolicy::class)];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'default_policy.enum' => __('payment_timing.invalid_policy'),
            'default_policy.not_in' => __('payment_timing.global_cannot_inherit'),
            '*_policy.enum' => __('payment_timing.invalid_policy'),
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $allowedPolicyKeys = array_map(
                fn (VisitType $visitType) => "{$visitType->value}_policy",
                VisitType::cases(),
            );
            $allowedPolicyKeys[] = 'default_policy';

            foreach (array_keys($this->all()) as $key) {
                if (str_ends_with($key, '_policy') && ! in_array($key, $allowedPolicyKeys, true)) {
                    $validator->errors()->add($key, __('payment_timing.unknown_visit_type'));
                }
            }
        }];
    }
}
