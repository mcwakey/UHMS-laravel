<?php

namespace App\Http\Requests\Consultations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsultationProcedureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $visit = $this->route('visit');

        return [
            'department_id' => ['required', 'exists:departments,id'],
            'service_catalog_id' => ['required', 'exists:service_catalog,id'],
            'procedure_id' => ['nullable', 'exists:procedures,id'],
            'priority' => ['required', 'in:routine,urgent,emergency'],
            'indication' => ['required', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'preferred_datetime' => ['nullable', 'date'],
            'consultation_route_id' => [
                'nullable',
                Rule::exists('visit_consultation_routes', 'id')->where('visit_id', $visit?->id),
            ],
        ];
    }
}

