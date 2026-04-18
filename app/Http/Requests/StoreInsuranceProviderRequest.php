<?php

namespace App\Http\Requests;

use App\Enums\InsuranceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInsuranceProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('claims.create');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'coverage_percentage' => $this->filled('coverage_percentage') ? $this->coverage_percentage : 100,
            'short_name' => $this->filled('short_name') ? $this->short_name : '',
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'type' => ['required', Rule::enum(InsuranceType::class)],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'contract_number' => ['nullable', 'string', 'max:100'],
            'coverage_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'annual_limit' => ['nullable', 'numeric', 'min:0'],
            'per_visit_limit' => ['nullable', 'numeric', 'min:0'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
