<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates suspension of a patient financial-risk profile (Payment Timing
 * Policy Phase 5). A reason is required.
 */
class SuspendPatientFinancialRiskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('patients.financial_risk.review') ?? false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
