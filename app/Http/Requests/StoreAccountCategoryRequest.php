<?php

namespace App\Http\Requests;

use App\Enums\EntryType;
use Illuminate\Foundation\Http\FormRequest;

class StoreAccountCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('accounts.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'type' => ['required', new \Illuminate\Validation\Rules\Enum(EntryType::class)],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
