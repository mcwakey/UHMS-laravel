<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    protected int $droppedItemRows = 0;

    public function authorize(): bool
    {
        return $this->user()->can('store.purchase.create');
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id', 'distinct'],
            'items.*.quantity_ordered' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $rawItems = $this->input('items', []);

        if (! is_array($rawItems)) {
            $rawItems = [];
        }

        $items = collect($rawItems)
            ->filter(function ($item) {
                // Accept legacy drug_id submissions too — they are translated below.
                return is_array($item) && (filled($item['product_id'] ?? null) || filled($item['drug_id'] ?? null));
            })
            ->map(function ($item) {
                $productId = $item['product_id'] ?? null;

                // Translate legacy drug_id -> product_id via the linked Product
                // so that old form posts keep working after the unification.
                if (! $productId && ! empty($item['drug_id'])) {
                    $productId = \App\Models\Drug::whereKey($item['drug_id'])->value('product_id');
                }

                return [
                    'product_id'       => $productId,
                    'item_type'        => 'product',
                    'quantity_ordered' => $item['quantity_ordered'] ?? null,
                    'unit_cost'        => $item['unit_cost'] ?? null,
                ];
            })
            ->filter(fn ($item) => filled($item['product_id']))
            ->values()
            ->all();

        // Stash the count of submitted rows that had no product, so messages() can
        // explain the real cause if everything was stripped.
        $this->droppedItemRows = count($rawItems) - count($items);

        $this->merge(['items' => $items]);
    }

    public function messages(): array
    {
        $base = 'At least one item is required.';
        if (($this->droppedItemRows ?? 0) > 0) {
            $base .= ' (Submitted rows were missing a selected product and were ignored.)';
        }
        return [
            'items.required' => $base,
            'items.min' => $base,
            'items.*.product_id.required' => 'Please select a product for each item.',
            'items.*.product_id.distinct' => 'Each product can only be selected once on the purchase order.',
            'items.*.product_id.exists'   => 'Selected product is invalid.',
            'items.*.quantity_ordered.required' => 'Please enter a quantity for each item.',
            'items.*.unit_cost.required'        => 'Please enter a unit cost for each item.',
        ];
    }
}
