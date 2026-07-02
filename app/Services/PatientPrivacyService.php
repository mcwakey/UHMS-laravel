<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\Patient;
use App\Models\PatientInsurance;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

class PatientPrivacyService
{
    public function __construct(
        private PatientFieldAuthorizationService $authorization,
        private PatientMaskingService $masking,
        private ActivityLogService $activityLog,
    ) {}

    public function canView(string $field, ?Authenticatable $user = null): bool
    {
        return $this->authorization->canViewField($field, $user ?: Auth::user());
    }

    public function display(string $field, mixed $value, ?Authenticatable $user = null): mixed
    {
        $definition = $this->authorization->definition($field);
        if (! $definition) {
            return $value;
        }

        if ($this->canView($field, $user)) {
            return $value;
        }

        return $this->masking->mask($value, $definition['mask'] ?? 'hidden');
    }

    public function displayForExport(string $field, mixed $value, ?Authenticatable $user = null): mixed
    {
        $definition = $this->authorization->definition($field);
        if (! $definition) {
            return $value;
        }

        $level = (int) ($definition['level'] ?? 0);
        if ($level <= 1 || $this->canExportSensitive($user)) {
            return $value;
        }

        return $this->masking->mask($value, $definition['mask'] ?? 'hidden');
    }

    public function canExportSensitive(?Authenticatable $user = null): bool
    {
        $user = $user ?: Auth::user();

        return $user && method_exists($user, 'can') && $user->can('patients.export_sensitive.view');
    }

    public function protectValueForDisplay(string $key, mixed $value, ?Authenticatable $user = null, bool $export = false): mixed
    {
        $field = $this->fieldForKey($key);
        if (! $field) {
            return $value;
        }

        return $export
            ? $this->displayForExport($field, $value, $user)
            : $this->display($field, $value, $user);
    }

    public function protectArrayForDisplay(array $values, ?Authenticatable $user = null, bool $export = false): array
    {
        $protected = [];

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $protected[$key] = $this->protectArrayForDisplay($value, $user, $export);
                continue;
            }

            $protected[$key] = $this->protectValueForDisplay((string) $key, $value, $user, $export);
        }

        return $protected;
    }

    public function classify(string $field): array
    {
        return $this->authorization->definition($field) ?? [
            'level' => null,
            'permission' => null,
            'mask' => 'none',
        ];
    }

    public function patientSearchPayload(Patient $patient, array $extra = []): array
    {
        $gender = $patient->gender;

        return array_merge([
            'id' => $patient->id,
            'text' => "{$patient->patient_number} - {$patient->full_name}",
            'patient_number' => $patient->patient_number,
            'full_name' => $patient->full_name,
            'name' => $patient->full_name,
            'age' => $patient->age,
            'gender' => $gender instanceof \UnitEnum
                ? (method_exists($gender, 'translatedLabel') ? $gender->translatedLabel() : $gender->value)
                : $gender,
            'phone' => $this->display('phone', $patient->phone),
            'phone_secondary' => $this->display('phone_secondary', $patient->phone_secondary),
            'email' => $this->display('email', $patient->email),
            'ghana_card_number' => $this->display('ghana_card_number', $patient->ghana_card_number),
        ], $extra);
    }

    public function insurancePayload(PatientInsurance $insurance): array
    {
        return [
            'membership_number' => $this->display('membership_number', $insurance->membership_number),
            'policy_number' => $this->display('policy_number', $insurance->policy_number),
            'ccc_code' => $this->display('ccc_code', $insurance->ccc_code),
        ];
    }

    /**
     * Logs categories of sensitive data present on a patient profile view without
     * writing the raw values into the audit trail.
     *
     * @param  array<int, string>  $fields
     */
    public function auditPatientProfileView(Patient $patient, array $fields = []): void
    {
        $fields = $fields ?: $this->presentSensitiveFields($patient);
        if ($fields === []) {
            return;
        }

        $categories = collect($fields)
            ->map(fn (string $field) => $this->categoryFor($field))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->activityLog->logPatientAction($patient, config('patient_privacy.audit.profile_view_action'), [
            'severity' => LogSeverity::INFO,
            'metadata' => [
                'sensitive_categories' => $categories,
                'sensitive_field_count' => count($fields),
                'route' => request()?->route()?->getName(),
                'masked_for_current_user' => collect($fields)
                    ->reject(fn (string $field) => $this->canView($field))
                    ->values()
                    ->all(),
            ],
            'description' => 'Sensitive patient profile access',
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function presentSensitiveFields(Patient $patient): array
    {
        $fields = [];

        foreach (array_keys(config('patient_privacy.fields', [])) as $field) {
            if ($this->authorization->level($field) < 2) {
                continue;
            }

            if ($this->patientFieldHasValue($patient, $field)) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    private function patientFieldHasValue(Patient $patient, string $field): bool
    {
        return match ($field) {
            'membership_number', 'policy_number', 'ccc_code' => $patient->relationLoaded('insurances')
                && $patient->insurances->contains(fn ($insurance) => filled($insurance->{$field})),
            'emergency_contact_phone' => $patient->relationLoaded('emergencyContacts')
                && $patient->emergencyContacts->contains(fn ($contact) => filled($contact->phone) || filled($contact->phone_secondary)),
            default => filled($patient->{$field} ?? null),
        };
    }

    private function categoryFor(string $field): ?string
    {
        return match ($field) {
            'phone', 'phone_secondary', 'email' => 'contact',
            'address', 'digital_address' => 'address',
            'ghana_card_number', 'passport_number', 'driving_license_number', 'voter_id_number' => 'identity',
            'membership_number', 'policy_number', 'ccc_code' => 'insurance',
            'emergency_contact_phone', 'emergency_contact_address' => 'emergency_contact',
            default => $this->authorization->level($field) >= 3 ? 'clinical_sensitive' : 'pii',
        };
    }

    private function fieldForKey(string $key): ?string
    {
        $normalised = strtolower($key);
        $normalised = preg_replace('/[^a-z0-9]+/', '_', $normalised) ?: $normalised;
        $normalised = trim($normalised, '_');

        if ($this->authorization->definition($normalised)) {
            return $normalised;
        }

        return match (true) {
            str_contains($normalised, 'phone_secondary'),
                str_contains($normalised, 'secondary_phone') => 'phone_secondary',
            str_contains($normalised, 'payer_phone'),
                str_contains($normalised, 'recipient_phone'),
                str_contains($normalised, 'contact_phone'),
                str_contains($normalised, 'phone_number'),
                str_contains($normalised, 'phone'),
                str_ends_with($normalised, 'phone'),
                $normalised === 'phone' => 'phone',
            str_contains($normalised, 'payer_email'),
                str_contains($normalised, 'recipient_email'),
                str_contains($normalised, 'email'),
                str_ends_with($normalised, 'email'),
                $normalised === 'email' => 'email',
            str_contains($normalised, 'digital_address') => 'digital_address',
            str_contains($normalised, 'ghana_card') => 'ghana_card_number',
            str_contains($normalised, 'membership') => 'membership_number',
            str_contains($normalised, 'policy') => 'policy_number',
            str_contains($normalised, 'ccc') => 'ccc_code',
            str_contains($normalised, 'emergency_contact') && str_contains($normalised, 'address') => 'emergency_contact_address',
            str_contains($normalised, 'emergency_contact') && str_contains($normalised, 'phone') => 'emergency_contact_phone',
            str_contains($normalised, 'allerg') => 'allergies',
            str_contains($normalised, 'chronic') => 'chronic_conditions',
            str_contains($normalised, 'confidential') => 'confidential_clinical_notes',
            default => null,
        };
    }
}
