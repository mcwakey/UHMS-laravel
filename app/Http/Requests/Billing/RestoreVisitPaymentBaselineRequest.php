<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates restoration of a visit to its observational baseline — removing the
 * current approved arrangement (Payment Timing Policy Phase 7). A reason is
 * required.
 */
class RestoreVisitPaymentBaselineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('visits.payment_arrangement.restore_baseline') ?? false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
