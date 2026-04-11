<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('prescriptions.create');
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.drug_name' => ['required', 'string', 'max:191'],
            'items.*.dosage' => ['required', 'string', 'max:191'],
            'items.*.frequency' => ['required', 'string', 'max:191'],
            'items.*.duration' => ['required', 'string', 'max:191'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.route' => ['required', 'string', 'in:oral,IV,IM,SC,topical,rectal,sublingual,inhaled,nasal,ophthalmic,otic'],
            'items.*.instructions' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one prescription item is required.',
            'items.min' => 'At least one prescription item is required.',
            'items.*.drug_name.required' => 'Drug name is required for each item.',
            'items.*.dosage.required' => 'Dosage is required for each item.',
            'items.*.frequency.required' => 'Frequency is required for each item.',
            'items.*.duration.required' => 'Duration is required for each item.',
            'items.*.quantity.required' => 'Quantity is required for each item.',
        ];
    }
}
