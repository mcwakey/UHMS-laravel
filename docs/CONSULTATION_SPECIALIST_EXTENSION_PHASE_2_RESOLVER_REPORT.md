# Consultation Specialist Extension - Phase 2 Resolver Report

## Summary

Implemented the read-only specialty resolver layer for consultation workspaces. The resolver determines the active specialty profile from route mappings, doctor preferences, user department mappings, department mappings, department type mappings, existing specialty entries, and finally the general medicine fallback.

The visible consultation UI was not changed.

## Files Added

- `app/Data/Consultation/Specialty/ResolvedConsultationSpecialty.php`
- `app/Models/ConsultationSpecialtyProfileMapping.php`
- `app/Services/Consultation/Specialty/ConsultationSpecialtyProfileResolver.php`
- `database/migrations/2026_07_05_000003_create_consultation_specialty_profile_mappings_table.php`
- `tests/Feature/Consultations/ConsultationSpecialtyResolverTest.php`

## Files Modified

- `app/Http/Controllers/Doctor/Consultations/Concerns/HandlesConsultationWorkspace.php`
- `app/Models/ConsultationSpecialtyProfile.php`
- `app/Services/Consultation/Specialty/ConsultationSpecialtyProfileService.php`
- `database/seeders/ConsultationSpecialtySeeder.php`

## Database Changes

Added migration:

- `2026_07_05_000003_create_consultation_specialty_profile_mappings_table.php`

Added table:

- `consultation_specialty_profile_mappings`

Mapping fields:

- `consultation_specialty_profile_id`
- `department_id`
- `consultation_route_id`
- `department_type`
- `user_id`
- `source`
- `priority`
- `is_active`
- `metadata`

## Resolver Priority

Implemented priority:

1. Active consultation route mapping
2. Active doctor preference
3. User primary or assigned department mapping
4. Active department mapping
5. Active department type mapping
6. Existing consultation specialty entry
7. General medicine fallback

Inactive mappings and inactive profiles are ignored. If a selected non-general profile has no visible sections, the resolver safely falls back to general medicine.

## Seeded Mappings

The specialty seeder now creates:

- `department_type = consultation` to `general_medicine`

It also safely maps existing departments only when matching rows already exist:

- Physiotherapy or physio department hints to `physiotherapy`
- Ophthalmology or eye department hints to `ophthalmology`
- Dental department hints to `dental`

No departments are created or altered by these mappings.

## Controller Integration

`specialtyContext` is added as read-only data in `HandlesConsultationWorkspace::show`.

The view does not consume this context yet. Section rendering, save behavior, completion readiness, prescriptions, investigations, and procedures remain unchanged.

## Tests Added

- `tests/Feature/Consultations/ConsultationSpecialtyResolverTest.php`

Coverage includes fallback, department type mapping, department mapping priority, route mapping priority, doctor preferences, inactive mappings/profiles, user primary department mapping, visible ordered sections, existing specialty entries, and controller payload smoke.

## Checks Run

Passed:

- `php artisan migrate`
- `php artisan db:seed --class=ConsultationSpecialtySeeder`
- `php artisan test tests/Feature/Consultations/ConsultationSpecialtyFoundationTest.php`
- `php artisan test tests/Feature/Consultations/ConsultationSpecialtyResolverTest.php`
- `php artisan route:list`
- PHP lint on new and modified PHP files

## Backward Compatibility

The existing consultation UI remains visually unchanged. Phase 2 only adds resolver data to the backend view payload for future layout work.

## Known Issues / Follow-up

- Phase 3 should decide how the layout engine consumes `specialtyContext`.
- Admin configuration UI for specialty profiles and mappings remains out of scope.
- Billing-specific specialty mapping remains out of scope.
