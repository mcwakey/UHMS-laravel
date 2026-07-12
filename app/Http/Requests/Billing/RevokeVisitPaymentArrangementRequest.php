<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates revoke of a per-visit payment arrangement (Payment Timing Policy
 * Phase 7). A reason is required.
 */
class RevokeVisitPaymentArrangementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('visits.payment_arrangement.revoke') ?? false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
