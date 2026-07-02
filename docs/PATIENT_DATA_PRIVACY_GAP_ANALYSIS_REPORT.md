# Patient Data Privacy Gap Analysis Report

Phase: Patient privacy and data protection phase 1.

## Summary

UHMS exposes patient data in many legitimate workflow surfaces. Before this phase, the system mostly relied on page-level permissions like `patients.view`. That is not enough for privacy: reception, billing, clinical, claims, and records staff need different slices of the same patient record.

This phase adds the shared infrastructure for field-level privacy and documents the rollout surface. It does not attempt a risky one-pass rewrite of every screen.

## Highest-Risk Exposure Points Found

| Area | Examples | Sensitive fields | Risk | First permission/mask |
| --- | --- | --- | --- | --- |
| Patient profile | `resources/views/patients/show.blade.php` | phone, email, address, digital address, ID card, insurance member numbers, emergency contacts, allergies/chronic conditions | High | contact, address, identity, insurance, emergency contact, clinical sensitive |
| Patient index/search | `resources/views/patients/index.blade.php`, patient search endpoints | phone, insurance card, emergency contact search hits | High | masked search results |
| Visit/appointment pages | `resources/views/visits/*`, `resources/views/appointments/*` | patient phone, insurance, identifiers in cards and JSON | High | protected patient card fields |
| Consultation workspace | `resources/views/consultations/show.blade.php`, history/preview | allergies, chronic conditions, clinical notes, patient identifiers | Critical | clinical sensitive |
| Lab/pharmacy/service rendering | `resources/views/lab/*`, `resources/views/pharmacy/*`, `resources/views/service-renderings/*` | patient card data, contact, insurance identifiers | High | protected patient cards |
| Billing/invoices/payments | `resources/views/billing/*`, payment links | patient contacts, payer phone/email, insurance member details | High | contact and insurance masks |
| Reports/exports/prints | `resources/views/reports/*`, invoice/receipt/thermal print views | phone, email, identifiers, insurance numbers | High | require `patients.export_sensitive.view` for full data |
| Activity logs | log viewer and model/service logs | changed patient fields and metadata | High | UI masking plus audit sanitising |
| Notifications/SMS/email | integration controllers/templates | phone/email recipient data | High | minimal payloads and masked admin views |
| Dashboards/widgets | dashboard patient lists/cards | patient summary/contact fields | Medium | level-aware components |

## Infrastructure Delivered

- Central field registry: [config/patient_privacy.php](/C:/dev/projects/web/UHMS-laravel/config/patient_privacy.php)
- Authorization service: [PatientFieldAuthorizationService.php](/C:/dev/projects/web/UHMS-laravel/app/Services/PatientFieldAuthorizationService.php)
- Masking service: [PatientMaskingService.php](/C:/dev/projects/web/UHMS-laravel/app/Services/PatientMaskingService.php)
- Orchestration/audit service: [PatientPrivacyService.php](/C:/dev/projects/web/UHMS-laravel/app/Services/PatientPrivacyService.php)
- Blade helper component: [patient-protected-field.blade.php](/C:/dev/projects/web/UHMS-laravel/resources/views/components/patient-protected-field.blade.php)
- Additive permissions in [RoleSeeder.php](/C:/dev/projects/web/UHMS-laravel/database/seeders/RoleSeeder.php)
- Patient profile sensitive-access audit event in [PatientController.php](/C:/dev/projects/web/UHMS-laravel/app/Http/Controllers/Admin/Patients/PatientController.php)
- Audit sanitising expanded in [ActivityLogService.php](/C:/dev/projects/web/UHMS-laravel/app/Services/ActivityLogService.php); model-level patient logging no longer stores phone/email diffs in [Patient.php](/C:/dev/projects/web/UHMS-laravel/app/Models/Patient.php)

## Remaining Gaps

| Gap | Impact | Recommended next step |
| --- | --- | --- |
| Most Blade screens still render raw patient fields | Users with broad page access may see more than their role needs | Convert priority screens to `<x-patient-protected-field>` and privacy service calls |
| AJAX/search endpoints may return raw fields | Masking in Blade would not protect JSON consumers | Update patient search/lookup response builders first |
| Print/PDF/export views still need field-level checks | Printed data can leave the system boundary | Gate full exports by `patients.export_sensitive.view` |
| Activity log UI still needs sensitive field presentation masking | Raw historic values may be visible to log viewers | Add display masking in log viewer rows/properties |
| No break-glass workflow yet | Emergency access needs explicit reason and audit | Add reasoned override after field masking rollout |
| No consent/directive model yet | Patient-specific restrictions cannot be represented | Add privacy directives/consent table in later phase |

## Acceptance Status

- Field classification: complete for known patient model, insurance, emergency-contact, and sensitive clinical fields.
- Permissions: additive permissions added.
- Masking helpers: added.
- Duplicate masking prevention: central services/components added.
- Exposure inventory: documented by module and priority.
- Production behaviour: no broad display changes in this phase.

