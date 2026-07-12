<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates approval of a per-visit payment arrangement (Payment Timing Policy
 * Phase 7). The requester≠approver rule is enforced in the service, not here.
 */
class ApproveVisitPaymentArrangementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('visits.payment_arrangement.approve') ?? false;
    }

    public function rules(): array
    {
        return [
            'decision_reason' => ['nullable', 'string', 'max:1000'],
            'effective_from' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'confirm_stale_risk' => ['nullable', 'boolean'],
        ];
    }
}
