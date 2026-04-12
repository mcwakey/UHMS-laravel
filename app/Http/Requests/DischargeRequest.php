<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DischargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ward.discharge');
    }

    public function rules(): array
    {
        return [
            'discharge_summary' => ['required', 'string', 'max:5000'],
            'discharge_instructions' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
