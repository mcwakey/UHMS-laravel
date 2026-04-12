<?php

namespace App\Http\Requests;

use App\Enums\EntryType;
use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreFinancialEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('accounts.entries.create');
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:account_categories,id'],
            'type' => ['required', new Enum(EntryType::class)],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'payment_method' => ['nullable', new Enum(PaymentMethod::class)],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'receipt_number' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:1000'],
            'entry_date' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
