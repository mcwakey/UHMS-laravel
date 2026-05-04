<?php

namespace App\Http\Requests;

use App\Models\Drug;
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
            'items.*.drug_id' => ['nullable', 'integer', 'exists:drugs,id'],
            'items.*.drug_name' => ['required', 'string', 'max:191'],
            'items.*.dosage' => ['required', 'string', 'max:191'],
            'items.*.frequency' => ['required', 'string', 'max:191'],
            'items.*.duration' => ['required', 'string', 'max:191'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.route' => ['required', 'string', 'in:oral,IV,IM,SC,topical,rectal,sublingual,inhaled,nasal,ophthalmic,otic'],
            'items.*.instructions' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->filter(function ($item) {
                return filled($item['drug_id'] ?? null)
                    || filled($item['drug_name'] ?? null)
                    || filled($item['dosage'] ?? null)
                    || filled($item['duration'] ?? null);
            })
            ->values();

        $drugNames = Drug::whereIn('id', $items->pluck('drug_id')->filter()->unique())
            ->pluck('name', 'id');

        $items = $items->map(function ($item) use ($drugNames) {
            if (!empty($item['drug_id']) && isset($drugNames[(int) $item['drug_id']])) {
                $item['drug_name'] = $drugNames[(int) $item['drug_id']];
            }

            return $item;
        })->all();

        $this->merge(['items' => $items]);
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
