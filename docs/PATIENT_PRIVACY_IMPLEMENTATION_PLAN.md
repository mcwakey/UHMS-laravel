# Patient Privacy Implementation Plan

Phase 1 is infrastructure-first. It intentionally does not mass-edit every screen yet.

## Implemented Now

| Area | Files |
| --- | --- |
| Field registry | [config/patient_privacy.php](/C:/dev/projects/web/UHMS-laravel/config/patient_privacy.php) |
| Authorization helper | [PatientFieldAuthorizationService.php](/C:/dev/projects/web/UHMS-laravel/app/Services/PatientFieldAuthorizationService.php) |
| Masking helper | [PatientMaskingService.php](/C:/dev/projects/web/UHMS-laravel/app/Services/PatientMaskingService.php) |
| Facade/orchestration service | [PatientPrivacyService.php](/C:/dev/projects/web/UHMS-laravel/app/Services/PatientPrivacyService.php) |
| Blade field helper | [patient-protected-field.blade.php](/C:/dev/projects/web/UHMS-laravel/resources/views/components/patient-protected-field.blade.php) |
| Permissions | [RoleSeeder.php](/C:/dev/projects/web/UHMS-laravel/database/seeders/RoleSeeder.php) |
| Sensitive access audit | [PatientController.php](/C:/dev/projects/web/UHMS-laravel/app/Http/Controllers/Admin/Patients/PatientController.php) |
| Audit sanitising | [ActivityLogService.php](/C:/dev/projects/web/UHMS-laravel/app/Services/ActivityLogService.php), [Patient.php](/C:/dev/projects/web/UHMS-laravel/app/Models/Patient.php) |
| Test coverage | [PatientPrivacyInfrastructureTest.php](/C:/dev/projects/web/UHMS-laravel/tests/Feature/PatientPrivacyInfrastructureTest.php) |

## Rollout Order

1. Patient search/autocomplete JSON payloads.
2. Patient profile and patient header cards.
3. Visit, appointment, triage, consultation, lab, pharmacy patient summary cards.
4. Dashboards and widgets.
5. Reports, exports, print views, PDFs, receipts.
6. Notifications, SMS, email templates, public payment links.
7. Activity log UI masking for patient-related changed values.
8. API resources/transformers if introduced later.

## Usage Pattern

Blade:

```blade
<x-patient-protected-field field="phone" :value="$patient->phone" />
```

PHP:

```php
$display = app(\App\Services\PatientPrivacyService::class)
    ->display('ghana_card_number', $patient->ghana_card_number);
```

## Guardrails

- Do not add inline `substr` masking in views.
- Do not return raw sensitive values from AJAX just because the view masks them.
- Do not log raw PII in audit metadata.
- Do not grant broad `patients.pii.view` where a narrower permission is enough.

