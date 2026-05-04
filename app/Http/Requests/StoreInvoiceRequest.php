<?php

namespace App\Http\Requests;

use App\Enums\BillingType;
use App\Models\ServiceCatalog;
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

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->filter(function ($item) {
                return filled($item['description'] ?? null)
                    || filled($item['service_catalog_id'] ?? null);
            })
            ->values();

        $services = ServiceCatalog::whereIn('id', $items->pluck('service_catalog_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $items = $items->map(function ($item) use ($services) {
            $service = !empty($item['service_catalog_id']) ? $services->get((int) $item['service_catalog_id']) : null;

            if ($service && blank($item['description'] ?? null)) {
                $item['description'] = $service->name;
            }

            if ($service && blank($item['unit_price'] ?? null)) {
                $item['unit_price'] = $service->price ?? 0;
            }

            $item['quantity'] = $item['quantity'] ?? 1;
            $item['nhis_approved_amount'] = $item['nhis_approved_amount'] ?? 0;

            return $item;
        })->all();

        $this->merge(['items' => $items]);
    }
}
