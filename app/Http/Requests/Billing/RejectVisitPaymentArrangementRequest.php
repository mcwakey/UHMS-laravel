<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates reject of a per-visit payment arrangement (Payment Timing Policy
 * Phase 7). A reason is required.
 */
class RejectVisitPaymentArrangementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('visits.payment_arrangement.reject') ?? false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
