# Consultation Specialty Profile Foundation Report

## Implemented

- Added the specialist consultation profile schema for profiles, sections, templates, structured entries, and doctor consultation preferences.
- Added Eloquent models, relationships, scopes, casts, and the `ConsultationSpecialtyProfileService` foundation.
- Seeded idempotent default specialty profiles for general medicine, physiotherapy, ophthalmology, and dental with configurable section rows.
- Registered specialist profile configuration permissions in the existing role seeder.
- Added English and French localisation keys for seeded profile names and section labels.
- Kept the existing consultation workspace UI untouched.

## Focused Verification

Added:

```bash
php artisan test tests/Feature/Consultations/ConsultationSpecialtyFoundationTest.php
```

The focused test covers seeding, default profile resolution, section ordering and uniqueness, doctor preferences, relationships, and translation key coverage.
