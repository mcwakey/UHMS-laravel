<?php

namespace App\Http\Requests\Billing;

use App\Enums\VisitPaymentArrangementSource;
use App\Enums\VisitPaymentTimingPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a per-visit payment-arrangement request (Payment Timing Policy
 * Phase 7). `inherit` is rejected; a reason is required.
 */
class StoreVisitPaymentArrangementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('visits.payment_arrangement.request') ?? false;
    }

    public function rules(): array
    {
        $operational = array_map(fn ($p) => $p->value, VisitPaymentTimingPolicy::operationalPolicies());

        return [
            'requested_policy' => ['required', Rule::in($operational)],
            'source' => ['nullable', Rule::enum(VisitPaymentArrangementSource::class)],
            'request_reason_code' => ['nullable', 'string', 'max:100'],
            'request_reason' => ['required', 'string', 'max:1000'],
            'supporting_reference' => ['nullable', 'string', 'max:191'],
            'effective_from' => ['required', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }
}
