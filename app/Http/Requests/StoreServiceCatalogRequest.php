<?php

namespace App\Http\Requests;

use App\Enums\DepartmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'category' => ['required', 'string', 'in:consultation,lab,pharmacy,procedure,imaging,surgery,admin,other'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'department_type' => ['nullable', Rule::enum(DepartmentType::class)],
            'specialties' => ['nullable', 'array'],
            'specialties.*' => ['exists:specialties,id'],
        ];
    }
}
