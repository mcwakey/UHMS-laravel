# Consultation Specialist Extension — Phase 10 Admin Configuration UI Report

## Summary
Implemented admin-facing configuration screens for consultation specialty profiles, sections, resolver mappings, favorites, order sets, order set items, and doctor workspace preference visibility/reset. The new UI manages seeded/configured specialty data without changing consultation clinical workflows.

## Existing Admin Pattern Findings
- Routes live under the authenticated `admin.` prefix in `routes/web.php`, with per-feature `can:*` middleware.
- Admin controllers generally live under `App\Http\Controllers\Admin` or a nested admin namespace and often use controller-level validation.
- Admin list pages use `layouts.app`, card/table layouts, Bootstrap forms, badges, dropdown/inline actions, pagination, and session flash messages.
- Navigation is not hardcoded in Blade; it is built by `App\Services\SidebarMenuBuilder`.
- Existing setup pages such as Departments, Services, Specialties, Roles, ICD codes, and Settings use search/filter lists, inline or compact forms, active/inactive badges, and soft toggle/deactivate conventions where applicable.
- Audit logging convention uses `ActivityLogService` with `LogModule` values and concise structured context.
- Localization for the specialist consultation surface already uses `lang/*/consultation_specialties.php`, so Phase 10 admin keys were added there.

## Files Added
- `app/Http/Controllers/Admin/ConsultationSpecialtyProfileController.php`
- `app/Http/Controllers/Admin/ConsultationSpecialtySectionController.php`
- `app/Http/Controllers/Admin/ConsultationSpecialtyMappingController.php`
- `app/Http/Controllers/Admin/ConsultationSpecialtyFavoriteController.php`
- `app/Http/Controllers/Admin/ConsultationSpecialtyOrderSetController.php`
- `app/Http/Controllers/Admin/ConsultationSpecialtyOrderSetItemController.php`
- `app/Http/Controllers/Admin/DoctorConsultationPreferenceAdminController.php`
- `app/Services/Consultation/Specialty/ConsultationSpecialtyAdminOptions.php`
- `resources/views/admin/consultation-specialties/*`
- `tests/Feature/Consultations/ConsultationSpecialtyAdminConfigurationTest.php`
- `docs/CONSULTATION_SPECIALIST_EXTENSION_PHASE_10_ADMIN_CONFIGURATION_UI_REPORT.md`

## Files Modified
- `routes/web.php`
- `app/Services/SidebarMenuBuilder.php`
- `lang/en/consultation_specialties.php`
- `lang/fr/consultation_specialties.php`

## Admin Screens Added
- Specialty profile list, create, edit, and show/detail.
- Profile sections management.
- Specialty resolver mappings management.
- Specialty favorites management.
- Specialty order set list, create, edit, and show/detail.
- Order set item management from the order set detail page.
- Doctor consultation preference visibility/reset.

## Validation and Safety
- Profile codes, section keys, favorite codes, and order-set codes use slug-like validation.
- `general_medicine` cannot be deleted or deactivated.
- Section components are limited to the registered safe components and generic shell fallback.
- Mapping creation requires at least one target: department, department type, route, or user.
- Favorites and order-set items only accept controlled catalogue model classes.
- Order-set item types and apply modes are constrained to known safe values.
- `patch_specialty_entry` validates target section ownership and target fields against `ConsultationSpecialtySectionSchema` when possible.
- JSON metadata/payload fields must decode to arrays.
- Patch payloads must include a safe `merge` object.
- Nested resources check parent ownership before update/delete.

## Audit Logging
Configuration changes are logged through `ActivityLogService` under `LogModule::CONSULTATION`, including profile, section, mapping, favorite, order set, order set item, reorder, and doctor preference reset events.

## UI Changes
Added a “Consultation Specialties” sidebar entry under Configurations, guarded by `consultation-specialties.view`.

Admin pages use compact card/table screens with search/filter controls where useful, status badges, linked/label-only distinction, sort order fields, warnings for protected/general/history cases, and quick navigation between profile subsections.

## Backward Compatibility
The clinical consultation workspace was not rewritten. Existing specialist forms, favorites, order sets, readiness, summary builder, and doctor personal workspace behavior remain stable. Admin pages configure the same database-backed specialty structures consumed by Phases 1-9.

## Tests Added
`tests/Feature/Consultations/ConsultationSpecialtyAdminConfigurationTest.php` covers:
- Permission protection.
- Profile create/update/duplicate/general protection.
- Section create/duplicate/reorder/unsafe component rejection.
- Mapping validation and resolver behavior.
- Favorite safety and workspace default visibility.
- Order set and order set item validation.
- Deactivate instead of deleting order sets with application history.
- Audit logging and EN/FR localization keys.

## Checks Run
- `php -l` on all new admin/service/test PHP files — passed.
- `php artisan migrate` — passed, nothing to migrate.
- `php artisan db:seed --class=ConsultationSpecialtySeeder` — passed.
- `php artisan test tests/Feature/Consultations/ConsultationSpecialtyAdminConfigurationTest.php` — passed, 8 tests and 61 assertions.
- Focused Phase 1-10 regression suite plus workspace stabilisation — passed, 97 tests and 508 assertions.
- `php artisan route:list` — passed.
- `php artisan view:cache && php artisan view:clear` — passed.

## Known Issues / Follow-up
- Phase 11 billing/service mapping is not implemented.
- Dashboard integration is not implemented.
- Full browser hardening and visual QA are still later work.
- Readiness rules and summary templates remain code/registry-driven except where already safely consumed.
- The admin UI exposes controlled JSON metadata/payload fields; future phases may add richer structured editors for common payloads.
