<?php

namespace App\Http\Requests\Consultations;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsultationDiagnosisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:2000'],
            'icd_code' => ['nullable', 'string', 'max:20'],
            'icd_code_id' => ['nullable', 'exists:icd_codes,id'],
            'type' => ['nullable', 'in:provisional,final'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

