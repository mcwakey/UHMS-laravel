<?php

namespace App\Http\Requests\Consultations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsultationLabRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $visit = $this->route('visit');

        return [
            'target_department_id' => ['required', 'exists:departments,id'],
            'items' => ['required', 'array', 'min:1'],
            'urgency' => ['nullable', 'in:routine,urgent,emergency'],
            'clinical_info' => ['nullable', 'string', 'max:2000'],
            'consultation_route_id' => [
                'nullable',
                Rule::exists('visit_consultation_routes', 'id')->where('visit_id', $visit?->id),
            ],
        ];
    }
}

