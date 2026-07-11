<?php

namespace App\Http\Requests\FrontDesk;

use App\Enums\FrontDesk\VisitorContext;
use App\Enums\FrontDesk\VisitorStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisitorLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('front_desk.visitors.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'visitor_context' => ['required', Rule::enum(VisitorContext::class)],
            'visitor_name' => ['required', 'string', 'max:255'],
            'visitor_phone' => ['nullable', 'string', 'max:50'],
            'id_type' => ['nullable', 'string', 'max:100'],
            'id_number' => ['nullable', 'string', 'max:100'],
            'organization' => ['nullable', 'string', 'max:255'],
            'patient_id' => ['nullable', 'exists:patients,id'],
            'visit_id' => ['nullable', 'exists:visits,id'],
            'admission_id' => ['nullable', 'exists:admissions,id'],
            'ward_id' => ['nullable', 'exists:wards,id'],
            'bed_id' => ['nullable', 'exists:beds,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'relationship_to_patient' => ['nullable', 'string', 'max:100'],
            'person_to_see' => ['nullable', 'string', 'max:255'],
            'purpose' => ['nullable', 'string', 'max:2000'],
            'badge_number' => ['nullable', 'string', 'max:100'],
            'vehicle_number' => ['nullable', 'string', 'max:100'],
            'time_in' => ['nullable', 'date'],
            // On creation only "checked in" or "denied" make sense.
            'status' => ['nullable', Rule::in([VisitorStatus::CHECKED_IN->value, VisitorStatus::DENIED->value])],
            'approved_by' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
