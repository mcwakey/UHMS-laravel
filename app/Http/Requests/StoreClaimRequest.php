<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('claims.create');
    }

    public function rules(): array
    {
        return [
            'insurance_provider_id' => ['required', 'exists:insurance_providers,id'],
            'patient_id' => ['required', 'exists:patients,id'],
            'visit_id' => ['required', 'exists:visits,id'],
            'invoice_id' => ['nullable', 'exists:invoices,id'],
            'claim_date' => ['required', 'date'],
            'period_from' => ['required', 'date'],
            'period_to' => ['required', 'date', 'after_or_equal:period_from'],
            'assigned_doctor_id' => ['nullable', 'exists:users,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_name' => ['required', 'string', 'max:255'],
            'items.*.service_type' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
