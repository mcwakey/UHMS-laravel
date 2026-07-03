<?php

namespace App\Http\Requests\Consultations;

use App\Enums\Priority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsultationFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'department_id' => ['required', 'exists:departments,id'],
            'service_id' => ['nullable', 'exists:service_catalog,id'],
            'doctor_id' => ['nullable', 'exists:users,id'],
            'reason' => ['required', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'priority' => ['nullable', Rule::enum(Priority::class)],
            'notify_patient' => ['nullable', 'boolean'],
        ];
    }
}

