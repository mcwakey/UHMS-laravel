<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('hr.payroll.process');
    }

    public function rules(): array
    {
        return [
            'pay_period' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'allowances' => ['nullable', 'numeric', 'min:0'],
            'other_deductions' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
