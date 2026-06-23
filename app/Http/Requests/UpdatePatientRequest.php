<?php

namespace App\Http\Requests;

use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rule;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('patients.edit');
    }

    public function rules(): array
    {
        $country = config('patient_reference.countries.' . config('patient_reference.setup_country_code'), []);
        $phoneRegex = $country['phone_regex'] ?? null;
        $digitalAddressRegex = $country['digital_address_regex'] ?? null;

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'other_names' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['required', new Enum(Gender::class)],
            'blood_group' => ['nullable', new Enum(BloodGroup::class)],
            'marital_status' => ['nullable', new Enum(MaritalStatus::class)],
            'religion' => ['nullable', 'string', 'max:100'],
            'phone' => array_values(array_filter(['required', 'string', 'max:20', $phoneRegex ? 'regex:' . $phoneRegex : null])),
            'phone_secondary' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:191'],
            'ghana_card_number' => ['nullable', 'string', 'max:30', Rule::unique('patients')->ignore($this->route('patient'))],
            'occupation' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'town' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'digital_address' => array_values(array_filter(['nullable', 'string', 'max:30', $digitalAddressRegex ? 'regex:' . $digitalAddressRegex : null])),
            'avatar' => ['nullable', 'image', 'max:2048'],
            'allergies' => ['nullable', 'string', 'max:1000'],
            'chronic_conditions' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
