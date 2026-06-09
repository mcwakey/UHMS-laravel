<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'invoice_receivable_id' => ['nullable', 'integer', 'exists:invoice_receivables,id'],
            'payer_type' => ['nullable', Rule::in(['patient', 'insurance', 'sponsor', 'corporate'])],
            'payer_id' => ['nullable', 'integer'],
            'payment_method' => ['required', Rule::in(array_column(PaymentMethod::cases(), 'value'))],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'paid_at' => ['nullable', 'date'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.invoice_item_id' => ['required_with:allocations', 'integer', 'exists:invoice_items,id'],
            'allocations.*.amount' => ['required_with:allocations', 'numeric', 'min:0.01'],
        ];
    }
}
