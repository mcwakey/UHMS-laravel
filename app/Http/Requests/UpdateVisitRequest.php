<?php

namespace App\Http\Requests;

use App\Enums\Priority;
use App\Enums\VisitType;
use App\Http\Requests\Concerns\ValidatesVisitServiceRoutes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVisitRequest extends FormRequest
{
    use ValidatesVisitServiceRoutes;

    public function authorize(): bool
    {
        return $this->user()->can('visits.edit');
    }

    public function rules(): array
    {
        return [
            'visit_type'          => ['required', Rule::enum(VisitType::class)],
            'priority'            => ['required', Rule::enum(Priority::class)],
            'department_id'       => ['nullable', 'exists:departments,id'],
            'chief_complaint'     => ['nullable', 'string', 'max:2000'],
            'notes'               => ['nullable', 'string', 'max:2000'],
            'visit_date'          => ['nullable', 'date'],
            'start_time'          => ['nullable', 'date_format:H:i'],
            'end_time'            => ['nullable', 'date_format:H:i', 'after:start_time'],
            'visit_insurance_id'  => ['nullable', 'exists:patient_insurances,id'],
            'consultation_mode'   => ['nullable', 'string', 'in:in_person,telehealth,virtual'],
            'meeting_link'        => ['nullable', 'url', 'max:500'],

            'services'                         => ['nullable', 'array'],
            'services.*.service_catalog_id'    => ['required_with:services', 'exists:service_catalog,id'],
            'services.*.department_id'         => ['nullable', 'exists:departments,id'],
            'services.*.quantity'              => ['nullable', 'integer', 'min:1', 'max:100'],
            'services.*.doctor_id'             => ['nullable', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'visit_type.required' => 'Please select a visit type.',
        ];
    }
}
