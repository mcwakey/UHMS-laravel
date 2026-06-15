<?php

namespace App\Http\Requests;

use App\Enums\EmployeeStatus;
use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('hr.employees.create') || $this->user()->can('hr.employees.edit');
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'exists:users,id'],
            'first_name' => ['required', 'string', 'max:191'],
            'last_name' => ['required', 'string', 'max:191'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:191'],
            'address' => ['nullable', 'string'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position' => ['required', 'string', 'max:191'],
            'hire_date' => ['required', 'date'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'salary_type' => ['nullable', 'in:monthly,hourly,daily'],
            'payment_method' => ['nullable', 'in:bank,mobile_money,cash'],
            'bank_name' => ['nullable', 'string', 'max:191'],
            'bank_account' => ['nullable', 'string', 'max:191'],
            'bank_branch' => ['nullable', 'string', 'max:191'],
            'ssnit_number' => ['nullable', 'string', 'max:191'],
            'tin_number' => ['nullable', 'string', 'max:191'],
            'tax_identification_number' => ['nullable', 'string', 'max:191'],
            'tax_residency_status' => ['nullable', 'in:resident,non_resident,exempt'],
            'paye_exempt' => ['nullable', 'boolean'],
            'tax_relief_amount' => ['nullable', 'numeric', 'min:0'],
            'employee_ssnit_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'employer_ssnit_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'emergency_contact_name' => ['nullable', 'string', 'max:191'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', Rule::enum(EmployeeStatus::class)],
        ];
    }
}
