<?php

namespace App\Http\Requests;

use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\InsuranceType;
use App\Enums\MaritalStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('patients.create');
    }

    protected function prepareForValidation(): void
    {
        $insurances = collect($this->input('insurances', []))
            ->filter(fn ($insurance) => filled($insurance['type'] ?? null)
                || filled($insurance['provider_id'] ?? null)
                || filled($insurance['insurance_tier_id'] ?? null)
                || filled($insurance['membership_number'] ?? null)
                || filled($insurance['policy_number'] ?? null)
                || filled($insurance['expiry_date'] ?? null))
            ->values()
            ->all();

        $this->merge([
            'insurances' => $insurances ?: null,
        ]);
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
            'ghana_card_number' => ['nullable', 'string', 'max:30', 'unique:patients,ghana_card_number'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'town' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'digital_address' => array_values(array_filter(['nullable', 'string', 'max:30', $digitalAddressRegex ? 'regex:' . $digitalAddressRegex : null])),
            'avatar' => ['nullable', 'image', 'max:2048'],
            'allergies' => ['nullable', 'string', 'max:1000'],
            'chronic_conditions' => ['nullable', 'string', 'max:1000'],
            // Emergency contacts (inline during registration)
            'emergency_contacts'                  => ['nullable', 'array', 'max:5'],
            'emergency_contacts.*.name'           => ['required_with:emergency_contacts.*', 'string', 'max:100'],
            'emergency_contacts.*.phone'          => array_values(array_filter(['required_with:emergency_contacts.*', 'string', 'max:20', $phoneRegex ? 'regex:' . $phoneRegex : null])),
            'emergency_contacts.*.phone_secondary' => ['nullable', 'string', 'max:30'],
            'emergency_contacts.*.relationship'   => ['nullable', 'string', 'max:50'],
            // Insurances (inline during registration, multiple)
            'insurances'                          => ['nullable', 'array', 'max:10'],
            'insurances.*.type'                   => ['nullable', new Enum(InsuranceType::class)],
            'insurances.*.provider_id'            => ['required_with:insurances.*', 'exists:insurance_providers,id'],
            'insurances.*.insurance_tier_id'      => ['nullable', 'exists:insurance_tiers,id'],
            'insurances.*.membership_number'      => ['nullable', 'string', 'max:100'],
            'insurances.*.policy_number'          => ['nullable', 'string', 'max:100'],
            'insurances.*.ccc_code'               => ['nullable', 'string', 'max:64'],
            'insurances.*.expiry_date'            => ['nullable', 'date', 'after:today'],
        ];
    }
}
