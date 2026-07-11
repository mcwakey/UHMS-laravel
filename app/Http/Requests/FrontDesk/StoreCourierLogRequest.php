<?php

namespace App\Http\Requests\FrontDesk;

use App\Enums\FrontDesk\CourierDirection;
use App\Enums\FrontDesk\CourierStatus;
use App\Enums\FrontDesk\CourierType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourierLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('front_desk.couriers.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'signature_required' => $this->boolean('signature_required'),
        ]);
    }

    public function rules(): array
    {
        return [
            'direction' => ['required', Rule::enum(CourierDirection::class)],
            'courier_type' => ['required', Rule::enum(CourierType::class)],
            'sender_name' => ['nullable', 'string', 'max:255'],
            'sender_organization' => ['nullable', 'string', 'max:255'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'recipient_department_id' => ['nullable', 'exists:departments,id'],
            'related_patient_id' => ['nullable', 'exists:patients,id'],
            'related_visit_id' => ['nullable', 'exists:visits,id'],
            'courier_company' => ['nullable', 'string', 'max:255'],
            'messenger_name' => ['nullable', 'string', 'max:255'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'received_or_sent_at' => ['nullable', 'date'],
            'delivered_to' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(CourierStatus::class)],
            'signature_required' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
