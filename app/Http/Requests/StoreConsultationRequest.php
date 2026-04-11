<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('consultations.create');
    }

    public function rules(): array
    {
        return [
            // Complaints
            'complaints' => ['nullable', 'array'],
            'complaints.*.description' => ['required_with:complaints', 'string', 'max:2000'],
            'complaints.*.duration' => ['nullable', 'string', 'max:191'],
            'complaints.*.severity' => ['nullable', 'in:mild,moderate,severe'],

            // Diagnoses
            'diagnoses' => ['nullable', 'array'],
            'diagnoses.*.icd_code' => ['nullable', 'string', 'max:20'],
            'diagnoses.*.description' => ['required_with:diagnoses', 'string', 'max:2000'],
            'diagnoses.*.type' => ['nullable', 'in:provisional,final'],
            'diagnoses.*.notes' => ['nullable', 'string', 'max:2000'],

            // Investigations
            'investigations' => ['nullable', 'array'],
            'investigations.*.investigation_type' => ['required_with:investigations', 'string', 'max:191'],
            'investigations.*.description' => ['required_with:investigations', 'string', 'max:2000'],
            'investigations.*.urgency' => ['nullable', 'in:routine,urgent,emergency'],
            'investigations.*.notes' => ['nullable', 'string', 'max:2000'],

            // Treatments
            'treatments' => ['nullable', 'array'],
            'treatments.*.type' => ['required_with:treatments', 'in:medication,procedure,referral,advice'],
            'treatments.*.description' => ['required_with:treatments', 'string', 'max:2000'],
        ];
    }
}
