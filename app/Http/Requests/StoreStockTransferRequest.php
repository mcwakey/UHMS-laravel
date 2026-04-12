<?php

namespace App\Http\Requests;

use App\Enums\StockLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('store.transfer.create');
    }

    public function rules(): array
    {
        return [
            'from_location' => ['required', 'string', new Enum(StockLocation::class)],
            'to_location' => ['required', 'string', new Enum(StockLocation::class), 'different:from_location'],
            'transfer_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.drug_id' => ['required', 'exists:drugs,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'to_location.different' => 'Source and destination locations must be different.',
            'items.required' => 'At least one item is required.',
        ];
    }
}
