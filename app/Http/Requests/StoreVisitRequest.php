<?php

namespace App\Http\Requests;

use App\Enums\Priority;
use App\Enums\VisitType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('visits.create');
    }

    public function rules(): array
    {
        return [
            'patient_id'             => ['required', 'exists:patients,id'],
            'visit_type'             => ['required', Rule::enum(VisitType::class)],
            'priority'               => ['required', Rule::enum(Priority::class)],
            'department_id'          => ['nullable', 'exists:departments,id'],
            'assigned_doctor_id'     => ['nullable', 'exists:users,id'],
            'chief_complaint'        => ['nullable', 'string', 'max:2000'],
            'notes'                  => ['nullable', 'string', 'max:2000'],
            'visit_date'             => ['nullable', 'date'],
            'start_time'             => ['nullable', 'date_format:H:i'],
            'end_time'               => ['nullable', 'date_format:H:i', 'after:start_time'],
            'visit_insurance_id'     => ['nullable', 'exists:patient_insurances,id'],
            'consultation_mode'      => ['nullable', 'string', 'in:in_person,telehealth,virtual'],
            'meeting_link'           => ['nullable', 'url', 'max:500'],
            // Services array
            'services'               => ['nullable', 'array'],
            'services.*.id'          => ['required_with:services', 'integer', 'exists:service_catalog,id'],
            'services.*.quantity'    => ['nullable', 'integer', 'min:1', 'max:100'],
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
