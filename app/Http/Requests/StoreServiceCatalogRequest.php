<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $serviceId = $this->route('service')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:service_catalog,code,' . $serviceId],
            'category' => ['required', 'string', 'in:consultation,lab,pharmacy,procedure,other'],
            'price' => ['required', 'numeric', 'min:0'],
            'nhis_price' => ['nullable', 'numeric', 'min:0'],
            'is_nhis_covered' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
