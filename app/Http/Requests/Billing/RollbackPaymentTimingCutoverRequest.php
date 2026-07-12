<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates an immediate cutover rollback to legacy authority (Payment Timing
 * Policy Phase 8). A reason is required.
 */
class RollbackPaymentTimingCutoverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('billing.payment_timing.cutover.rollback') ?? false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
