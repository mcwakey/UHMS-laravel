<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ward.admit');
    }

    public function rules(): array
    {
        return [
            'visit_id' => ['required', 'exists:visits,id'],
            'patient_id' => ['required', 'exists:patients,id'],
            'bed_id' => ['required', 'exists:beds,id'],
            'admitting_diagnosis' => ['nullable', 'string', 'max:2000'],
            'admission_date' => ['nullable', 'date'],
            'expected_discharge_date' => ['nullable', 'date', 'after:today'],
        ];
    }
}
