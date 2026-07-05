# Consultation Specialist Extension - Phase 6 Order Sets Report

## Summary

Phase 6 adds specialty order sets to the consultation workspace for physiotherapy, ophthalmology, and dental workflows. Order sets are preview-first, profile-scoped, and apply only safe item types automatically: consultation tasks and structured specialty-entry patches. Catalogue-sensitive items such as diagnoses, investigations, procedures, drugs, prescriptions, and label-only favorites remain suggestions until a clinician selects or orders them through the existing guarded workflows.

## Files Added

- `database/migrations/2026_07_05_000006_create_consultation_specialty_order_sets_tables.php`
- `app/Models/ConsultationSpecialtyOrderSet.php`
- `app/Models/ConsultationSpecialtyOrderSetItem.php`
- `app/Models/ConsultationSpecialtyOrderSetApplication.php`
- `app/Models/ConsultationSpecialtyOrderSetApplicationItem.php`
- `app/Services/Consultation/Specialty/ConsultationSpecialtyOrderSetService.php`
- `app/Http/Controllers/Doctor/Consultations/ConsultationSpecialtyOrderSetController.php`
- `database/seeders/ConsultationSpecialtyOrderSetSeeder.php`
- `tests/Feature/Consultations/ConsultationSpecialtyOrderSetTest.php`
- `docs/CONSULTATION_SPECIALIST_EXTENSION_PHASE_6_ORDER_SETS_REPORT.md`

## Files Modified

- `app/Models/ConsultationSpecialtyProfile.php`
- `app/Http/Controllers/Doctor/Consultations/Concerns/HandlesConsultationWorkspace.php`
- `app/Services/Consultation/Specialty/ConsultationSpecialtyFavoriteService.php`
- `database/seeders/ConsultationSpecialtySeeder.php`
- `resources/views/consultations/show.blade.php`
- `resources/views/consultations/partials/page-config.blade.php`
- `routes/web.php`
- `lang/en/consultation_specialties.php`
- `lang/fr/consultation_specialties.php`

## Database Changes

Created four order-set tables:

- `consultation_specialty_order_sets` for profile-owned order-set definitions.
- `consultation_specialty_order_set_items` for typed item definitions, optional linked favorites/catalogue models, payloads, apply modes, and sorting.
- `consultation_specialty_order_set_applications` for consultation-route-level application audit records.
- `consultation_specialty_order_set_application_items` for per-item outcomes, target links, payloads, messages, and warnings.

## Safety Model

- Preview is required before apply in the workspace UI.
- Controller actions use the existing consultation mutation guard and `consultations.create` permission.
- Order sets must be active and must match the active resolved specialty profile.
- If no item IDs are submitted, only safe auto-applicable items run.
- Safe auto-applicators are limited to `create_task` and `patch_specialty_entry`.
- Structured-entry patches preserve existing non-empty clinician-entered values unless `overwrite` is explicitly requested.
- Duplicate open consultation tasks with the same title are skipped.
- Suggested/manual items are recorded as suggestions when selected, but they do not create billable or catalogue-backed clinical orders.

## Seeded Starter Order Sets

- Physiotherapy: low back pain starter and stroke rehab starter.
- Ophthalmology: conjunctivitis care and glaucoma review.
- Dental: extraction preparation and dental abscess care.

## Workspace Integration

`HandlesConsultationWorkspace` now hydrates `specialtyOrderSets` beside specialty favorites. The Blade workspace renders a compact order-set panel near the specialty identity strip, opens a preview modal, shows item-level apply/manual status, lets clinicians select safe items, and posts selected item IDs back to the apply endpoint.

## Tests Added

`ConsultationSpecialtyOrderSetTest` covers seeding, profile scoping, preview item statuses, application audit records, dental consent patches, physiotherapy treatment-plan patches, ophthalmology follow-up patches, task creation, non-overwrite behavior, inactive order-set rejection, wrong-profile rejection, workspace hydration, and EN/FR localization keys.

## Checks Run

- `php -l` on the new migration, models, service, controller, seeder, and test.
- `php artisan migrate`
- `php artisan db:seed --class=ConsultationSpecialtySeeder`
- `php artisan test tests/Feature/Consultations/ConsultationSpecialtyOrderSetTest.php`
- `php artisan test tests/Feature/Consultations/ConsultationSpecialtyFoundationTest.php tests/Feature/Consultations/ConsultationSpecialtyResolverTest.php tests/Feature/Consultations/ConsultationSpecialtyLayoutTest.php tests/Feature/Consultations/ConsultationSpecialtyEntryTest.php tests/Feature/Consultations/ConsultationSpecialtyFavoriteTest.php tests/Feature/Consultations/ConsultationSpecialtyOrderSetTest.php tests/Feature/ConsultationWorkspaceStabilisationTest.php`
- `php artisan route:list --name=consultations.specialty-order-sets`
- `php artisan view:cache`
- `php artisan view:clear`

Result: focused specialty/workspace suite passed with 63 tests and 310 assertions.

## Known Issues / Follow-up

- A future admin UI can manage order-set definitions without editing seeders.
- Catalogue-backed apply support can be added later per existing guarded ordering flows.
- Current starter sets intentionally favor conservative suggestions over automatic billable order creation.
