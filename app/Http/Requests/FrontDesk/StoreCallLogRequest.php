<?php

namespace App\Http\Requests\FrontDesk;

use App\Enums\FrontDesk\CallCategory;
use App\Enums\FrontDesk\CallDirection;
use App\Enums\FrontDesk\CallOutcome;
use App\Models\FrontDeskCallLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCallLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('front_desk.calls.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'follow_up_required' => $this->boolean('follow_up_required'),
        ]);
    }

    public function rules(): array
    {
        return [
            'direction' => ['required', Rule::enum(CallDirection::class)],
            'caller_name' => ['nullable', 'string', 'max:255'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'related_patient_id' => ['nullable', 'exists:patients,id'],
            'related_visit_id' => ['nullable', 'exists:visits,id'],
            'category' => ['required', Rule::enum(CallCategory::class)],
            'started_at' => ['nullable', 'date'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'outcome' => ['required', Rule::enum(CallOutcome::class)],
            'follow_up_required' => ['boolean'],
            'follow_up_status' => ['nullable', Rule::in([
                FrontDeskCallLog::FOLLOW_UP_PENDING,
                FrontDeskCallLog::FOLLOW_UP_COMPLETED,
                FrontDeskCallLog::FOLLOW_UP_CANCELLED,
            ])],
            'assigned_follow_up_user_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
