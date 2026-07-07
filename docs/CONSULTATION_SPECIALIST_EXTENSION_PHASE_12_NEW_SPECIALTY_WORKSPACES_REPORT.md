# Consultation Specialist Extension Phase 12 New Specialty Workspaces Report

## Scope

Implemented the next batch of personalised consultation workspaces inside the existing shared UHMS consultation workspace. This phase adds Obstetrics / Antenatal, Gynecology, ENT, Pediatrics, Emergency / Casualty, Orthopedics, and Surgery / Surgical OPD as configurable specialty profiles without creating separate hard-coded consultation modules.

## Delivered

- Added seeded active specialty profiles for `obstetrics`, `gynecology`, `ent`, `pediatrics`, `emergency`, `orthopedics`, and `surgery`.
- Added ordered visible section layouts for each new profile, including specialty-only clinical sections and existing shared core sections.
- Added safe department/profile mapping hints for the new specialties so resolver matching can route doctors to the correct personalised workspace.
- Added structured schemas for the new specialist sections, including obstetric, gynecology, ENT, pediatric, emergency, orthopedic, and surgical clinical capture fields.
- Registered representative sidebar/header icons for the new specialty sections so the UI no longer falls back to one repeated generic icon.
- Added profile-specific quick actions for each new workspace.
- Added starter specialty favorites and smart defaults for diagnoses, investigations, procedures, tasks, follow-up instructions, and clinical instructions where appropriate.
- Added starter order sets for antenatal booking, abnormal bleeding review, ear pain review, pediatric fever review, emergency primary survey, fracture review, and wound review.
- Added readiness rules for the new profiles, including emergency handover checks and specialty-specific completion blockers.
- Added summary builder templates for all seven new profiles.
- Added English and French localization for profile names, section labels, quick actions, readiness rules, and summary templates.
- Updated the structured specialist form partial to display readable fallback labels for newly introduced schema fields when no explicit field translation exists.
- Extended focused consultation tests for profile seeding, layout/component resolution, order sets, quick actions, readiness, summaries, favorites, and specialty entries.

## Safety Notes

- The shared consultation workspace remains the only consultation workspace surface.
- No separate specialty controllers, modules, dashboards, or hard-coded pages were added.
- Existing general medicine, physiotherapy, ophthalmology, and dental profiles were not destructively modified.
- New seeders use the existing idempotent `updateOrCreate` patterns.
- No fake patients, visits, invoices, production clinical records, or service catalogue records were created.
- New order sets use existing safe order-set application behavior and do not auto-bill services.
- If a future section is missing a dedicated schema/component, the generic specialist shell fallback remains available.

## Verification

- `php -l app/Services/Consultation/Specialty/ConsultationSpecialtyProfileResolver.php`
- `php -l app/Services/Consultation/Specialty/ConsultationSpecialtySectionSchema.php`
- `php -l app/Services/Consultation/Specialty/ConsultationSpecialtyReadinessService.php`
- `php artisan view:cache`
- `php artisan test tests/Feature/Consultations/ConsultationSpecialtyFoundationTest.php tests/Feature/Consultations/ConsultationSpecialtyLayoutTest.php tests/Feature/Consultations/ConsultationSpecialtyOrderSetTest.php tests/Feature/Consultations/DoctorSpecialtyWorkspaceTest.php tests/Feature/Consultations/ConsultationSpecialtySummaryBuilderTest.php tests/Feature/Consultations/ConsultationSpecialtyReadinessTest.php tests/Feature/Consultations/ConsultationSpecialtyFavoriteTest.php tests/Feature/Consultations/ConsultationSpecialtyEntryTest.php --stop-on-failure`

Final focused consultation result: 77 tests passed, 620 assertions.
