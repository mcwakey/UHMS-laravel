# Consultation Specialist Extension Phase 11 Billing Service Mapping Report

## Scope

Implemented specialty-aware billing/service mapping for the consultation workspace. The phase links active specialty profiles to existing billable service catalog records, exposes billing context in the workspace payload, and applies charges only through the existing billing service path.

## Delivered

- Added `consultation_specialty_service_mappings` and `consultation_specialty_billing_applications` tables.
- Added `ConsultationSpecialtyServiceMapping` and `ConsultationSpecialtyBillingApplication` models with profile, service, department, consultation, and audit relationships.
- Added a specialty billing mapping resolver with route, department, department-type, and default mapping priority.
- Added a specialty billing application service that previews charges, checks duplicate billing, requires confirmation, and delegates invoice item creation to `BillingService::addItemToVisitInvoice`.
- Added admin service mapping management under Consultation Specialties with validation, reorder support, audit logging, and an explicit auto-bill acknowledgement guard.
- Added safe seeding via `ConsultationSpecialtyServiceMappingSeeder`; it maps only existing active billable services and never creates fake service catalog charges.
- Added consultation billing preview/apply endpoints with consultation route safety checks and invoice creation permission on apply.
- Added `specialtyBillingContext` to consultation workspace payload and page config.
- Added a compact specialty billing awareness card to the consultation workspace.
- Split large consultation workspace fragments into partials to keep the Phase 3 structure guard intact.
- Removed remaining inline workflow event handlers from the consultation page and moved favorite insertion to delegated JS handling.
- Added English and French localization keys for billing contexts, triggers, statuses, and admin labels.

## Safety Notes

- Billing application is manual by default; seeded mappings set `auto_bill = false`.
- Auto billing in admin configuration requires explicit acknowledgement.
- Duplicate detection checks specialty mapping charges, consultation service charges, and visit-creation service charge source types.
- Billing application rechecks duplicate state inside the transaction before adding the invoice item.
- If a mapping or service is inactive, missing, already billed, or the user lacks invoice permission, the application records a non-applied audit status instead of creating a charge.

## Verification

- `php -l` on new/changed PHP classes, controllers, seeder, and tests.
- `php artisan test tests/Feature/Consultations/ConsultationStructurePhase3Test.php`
- `php artisan test tests/Feature/Consultations/ConsultationSpecialtyAdminConfigurationTest.php tests/Feature/Consultations/ConsultationSpecialtyBillingMappingTest.php`
- `php artisan test tests/Feature/Consultations`

Final focused consultation result: 152 tests passed, 918 assertions.
