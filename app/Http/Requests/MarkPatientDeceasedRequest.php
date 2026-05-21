<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkPatientDeceasedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('patients.mark_deceased');
    }

    public function rules(): array
    {
        return [
            'deceased_at'    => ['required', 'date', 'before_or_equal:today'],
            'cause_of_death' => ['nullable', 'string', 'max:255'],
            'deceased_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'deceased_at.required'         => 'Date of death is required.',
            'deceased_at.before_or_equal'  => 'Date of death cannot be in the future.',
        ];
    }
}
