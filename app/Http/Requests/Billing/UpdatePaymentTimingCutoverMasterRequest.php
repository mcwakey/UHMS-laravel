<?php

namespace App\Http\Requests\Billing;

use App\Enums\PaymentTimingCutoverMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates a master cutover-mode change (Payment Timing Policy Phase 8).
 * Moving to ACTIVE additionally requires the activate permission, an explicit
 * confirmation, and a reason.
 */
class UpdatePaymentTimingCutoverMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('billing.payment_timing.cutover.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::enum(PaymentTimingCutoverMode::class)],
            'reason' => ['required', 'string', 'max:1000'],
            'confirm' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('mode') !== PaymentTimingCutoverMode::ACTIVE->value) {
                return;
            }
            if (! ($this->user()?->can('billing.payment_timing.cutover.activate') ?? false)) {
                $validator->errors()->add('mode', __('payment_timing_cutover.errors.activate_not_permitted'));
            }
            if (! $this->boolean('confirm')) {
                $validator->errors()->add('confirm', __('payment_timing_cutover.errors.confirmation_required'));
            }
        }];
    }
}
