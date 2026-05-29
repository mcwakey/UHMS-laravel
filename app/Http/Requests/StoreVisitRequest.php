<?php

namespace App\Http\Requests;

use App\Enums\Priority;
use App\Enums\VisitType;
use App\Http\Requests\Concerns\ValidatesVisitServiceRoutes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisitRequest extends FormRequest
{
    use ValidatesVisitServiceRoutes;

    public function authorize(): bool
    {
        return $this->user()->can('visits.create');
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'visit_type' => ['required', Rule::enum(VisitType::class)],
            'priority' => ['required', Rule::enum(Priority::class)],
            'chief_complaint' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'visit_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'visit_insurance_id' => ['nullable', 'exists:patient_insurances,id'],
            'insurance_verification_id' => ['nullable', 'exists:insurance_verifications,id'],
            'verification_reference_code' => ['nullable', 'string', 'max:80'],
            'consultation_mode' => ['nullable', 'string', 'in:in_person,telehealth,virtual'],
            'meeting_link' => ['nullable', 'url', 'max:500'],
            'admission_override_reason' => ['nullable', 'string', 'max:1000'],

            // Visit services
            'services' => ['nullable', 'array'],
            'services.*.service_catalog_id' => ['required_with:services', 'exists:service_catalog,id'],
            'services.*.department_id' => ['nullable', 'exists:departments,id'],
            'services.*.quantity' => ['nullable', 'integer', 'min:1', 'max:100'],
            'services.*.doctor_id' => ['nullable', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required' => 'Please select a patient.',
            'patient_id.exists' => 'The selected patient does not exist.',
            'visit_type.required' => 'Please select a visit type.',
        ];
    }
}
