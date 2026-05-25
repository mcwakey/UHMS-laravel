<?php

namespace App\Http\Requests;

use App\Enums\InsuranceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreInsuranceProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('claims.create');
    }

    protected function prepareForValidation(): void
    {
        $merged = [
            'short_name' => $this->filled('short_name') ? $this->short_name : '',
            'code' => $this->filled('code') ? strtoupper((string) $this->code) : ($this->filled('short_name') ? strtoupper((string) $this->short_name) : null),
        ];

        // Normalize empty verification strings to null so the column stays NULL,
        // not an empty string (which would then be treated as "configured").
        foreach (['verification_driver', 'verification_method', 'verification_channel', 'verification_credentials_key'] as $key) {
            if ($this->exists($key)) {
                $val = trim((string) $this->input($key));
                $merged[$key] = $val === '' ? null : $val;
            }
        }

        // Accept verification_config as raw JSON text from the form, decode to array.
        if ($this->exists('verification_config')) {
            $raw = $this->input('verification_config');
            if (is_string($raw)) {
                $raw = trim($raw);
                if ($raw === '') {
                    $merged['verification_config'] = null;
                } else {
                    $decoded = json_decode($raw, true);
                    $merged['verification_config'] = is_array($decoded) ? $decoded : '__invalid_json__';
                }
            }
        }

        $this->merge($merged);
    }

    public function rules(): array
    {
        $drivers = array_keys((array) config('insurance_verification.drivers', []));

        return [
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'code' => ['nullable', 'string', 'max:60'],
            'type' => ['required', Rule::enum(InsuranceType::class)],
            'insurance_type_id' => ['nullable', 'exists:insurance_types,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'contract_number' => ['nullable', 'string', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['boolean'],
            'requires_claim_submission' => ['nullable', 'boolean'],
            'requires_verification_code' => ['nullable', 'boolean'],
            'verification_code_label' => ['nullable', 'string', 'max:80'],
            'claim_workflow_override' => ['nullable', 'string', 'max:40'],
            'claim_export_format_override' => ['nullable', 'string', 'max:20'],

            // Verification (generic / provider-agnostic)
            'verification_driver' => ['nullable', 'string', Rule::in($drivers)],
            'verification_method' => ['nullable', 'string', 'max:60'],
            'verification_channel' => ['nullable', 'string', 'max:60'],
            'verification_credentials_key' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9_\-]+$/i'],
            'verification_config' => ['nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($this->input('verification_config') === '__invalid_json__') {
                $v->errors()->add('verification_config', 'Verification config must be valid JSON.');
            }
            // The api driver requires a credentials key so the manager can locate creds in services.php.
            if ($this->input('verification_driver') === 'api' && ! $this->filled('verification_credentials_key')) {
                $v->errors()->add('verification_credentials_key', 'API driver requires a credentials key.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'verification_driver.in' => 'Selected verification driver is not registered.',
            'verification_credentials_key.regex' => 'Use only letters, numbers, underscores, or dashes.',
        ];
    }
}
