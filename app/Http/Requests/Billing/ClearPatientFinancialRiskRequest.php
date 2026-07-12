<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates clearance of a patient financial-risk restriction (Payment Timing
 * Policy Phase 5). A clearance reason is required. Clearance is terminal for the
 * profile.
 */
class ClearPatientFinancialRiskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('patients.financial_risk.clear') ?? false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
