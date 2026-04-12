<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ward.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20', 'unique:wards,code,' . $this->route('ward')->id],
            'department_id' => ['nullable', 'exists:departments,id'],
            'capacity' => ['required', 'integer', 'min:1'],
            'floor' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ];
    }
}
