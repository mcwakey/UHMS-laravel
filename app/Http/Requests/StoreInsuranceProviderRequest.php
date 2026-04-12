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

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['required', 'string', 'max:50'],
            'type' => ['required', Rule::enum(InsuranceType::class)],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'contract_number' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }
}
