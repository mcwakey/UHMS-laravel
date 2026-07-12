<?php

namespace App\Http\Requests\Billing;

use App\Enums\PatientFinancialRiskLevel;
use App\Enums\PatientFinancialRiskReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates creation of a patient financial-risk classification (Payment Timing
 * Policy Phase 5). Only whitelisted, typed fields are trusted.
 */
class StorePatientFinancialRiskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('patients.financial_risk.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'risk_level' => ['required', Rule::enum(PatientFinancialRiskLevel::class)],
            'primary_reason' => ['required', Rule::enum(PatientFinancialRiskReason::class)],
            'reason_details' => ['nullable', 'string', 'max:1000'],
            'credit_limit' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'effective_from' => ['required', 'date'],
            'review_due_at' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'reference' => ['nullable', 'string', 'max:191'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $reason = PatientFinancialRiskReason::tryFrom((string) $this->input('primary_reason'));
            if ($reason === null) {
                return;
            }

            $hasDetails = filled($this->input('reason_details'));
            $hasReference = filled($this->input('reference'));

            if ($reason === PatientFinancialRiskReason::OTHER && ! $hasDetails) {
                $validator->errors()->add('reason_details', __('patient_financial_risk.validation.details_required_for_other'));
            }

            if ($reason === PatientFinancialRiskReason::MANAGEMENT_DECISION && ! $hasDetails && ! $hasReference) {
                $validator->errors()->add('reason_details', __('patient_financial_risk.validation.details_or_reference_required_for_management'));
            }
        }];
    }
}
