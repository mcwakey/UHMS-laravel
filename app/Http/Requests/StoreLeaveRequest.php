<?php

namespace App\Http\Requests;

use App\Enums\LeaveType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('hr.leave.create') || $this->user()->can('hr.leave.approve');
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'leave_type' => ['required', Rule::enum(LeaveType::class)],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string'],
        ];
    }
}
