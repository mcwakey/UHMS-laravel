<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates submit-for-review and complete-review actions (Payment Timing Policy
 * Phase 5). Reason is optional for review actions.
 */
class ReviewPatientFinancialRiskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('patients.financial_risk.review') ?? false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
