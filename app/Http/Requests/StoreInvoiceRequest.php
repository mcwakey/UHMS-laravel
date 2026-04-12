<?php

namespace App\Http\Requests;

use App\Enums\BillingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visit_id' => ['required', 'exists:visits,id'],
            'patient_id' => ['required', 'exists:patients,id'],
            'billing_type' => ['required', Rule::in(array_column(BillingType::cases(), 'value'))],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.service_catalog_id' => ['nullable', 'exists:service_catalog,id'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.is_nhis_covered' => ['nullable', 'boolean'],
            'items.*.nhis_approved_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
