<?php

namespace App\Http\Requests;

use App\Enums\Priority;
use App\Enums\VisitType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('appointments.edit');
    }

    public function rules(): array
    {
        return [
            'doctor_id'           => ['nullable', 'exists:users,id'],
            'department_id'       => ['required', 'exists:departments,id'],
            'appointment_date'    => ['required', 'date'],
            'start_time'          => ['required', 'date_format:H:i'],
            'end_time'            => ['nullable', 'date_format:H:i', 'after:start_time'],
            'visit_type'          => ['required', Rule::enum(VisitType::class)],
            'priority'            => ['required', Rule::enum(Priority::class)],
            'chief_complaint'     => ['nullable', 'string', 'max:2000'],
            'reason'              => ['nullable', 'string', 'max:2000'],
            'notes'               => ['nullable', 'string', 'max:2000'],
            'consultation_mode'   => ['nullable', 'string', 'in:in_person,telehealth,virtual'],
            'visit_insurance_id'  => ['nullable', 'exists:patient_insurances,id'],
            'services'            => ['nullable', 'array'],
            'services.*.service_catalog_id' => ['required_with:services', 'exists:service_catalog,id'],
            'services.*.quantity'           => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
