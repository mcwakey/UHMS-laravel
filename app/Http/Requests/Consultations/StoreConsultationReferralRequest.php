<?php

namespace App\Http\Requests\Consultations;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsultationReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_id' => ['required', 'exists:departments,id'],
            'service_id' => ['nullable', 'exists:service_catalog,id'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['exists:service_catalog,id'],
            'doctor_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'activate_now' => ['nullable', 'boolean'],
        ];
    }
}

