# UHMS Localisation Phase 6 - Critical Page Translation Audit & Fix Pass

Date: 2026-06-13

## Scope

Phase 6 audited and corrected the active appointment and product/stock product-management pages identified as critical localisation blockers:

- Appointment create, edit, index, and show pages
- Product index, edit modal, insurance-pricing modal, and product detail page
- Inline JavaScript-generated labels, feedback messages, confirmations, and accessibility attributes
- English and French validation attribute names used by these workflows

No business rules, routes, permissions, pricing resolution, appointment transitions, or persistence behavior were changed.

## Implemented Fixes

### Appointments

- Localised the expired-insurance fallback badge on create and edit pages.
- Brought the edit-page insurance JavaScript in line with the already-localised create-page implementation.
- Localised insurance empty, status, membership, expiry, coverage, load-failure, and unlimited-balance text.
- Localised check-in, no-show, cancellation actions, cancellation modal content, empty state, and AJAX feedback.
- Localised dynamic service Add/Delete accessibility labels and the appointment Actions menu label.
- Localised the patient card heading on the appointment detail page.

### Products and Stock

- Localised product action tooltips and modal action buttons.
- Localised edit and insurance-price modal titles.
- Localised billable-status badges and product pricing-count badges.
- Localised dynamic provider-price row labels and Delete accessibility text.
- Localised the remove-price confirmation and billing-resolution summary.
- Localised the product detail document title.

### Validation

Added matching English and French validation attributes for:

- `appointment_date`
- `cancellation_reason`
- `product_type`
- `code`
- `unit`
- `reorder_level`
- `default_cost`
- `base_price`

## Audit Results

The repository-wide localisation audit was run before and after the fixes.

| Metric | Before | After | Change |
|---|---:|---:|---:|
| Files with candidates | 501 | 496 | -5 |
| Total candidates | 18,480 | 18,440 | -40 |
| Active runtime candidates | 4,305 | 4,302 | -3 |
| Known false positives | 13,440 | 13,403 | -37 |

Targeted searches found no remaining instances of the corrected appointment and product literals or raw action accessibility labels in the audited critical views.

This is not a claim that the whole application has zero untranslated strings. The updated coverage audit still reports 4,302 active-runtime candidates, including items that require module-by-module review and scanner false-positive triage.

## Verification

- `php artisan route:list`: passed, 714 routes discovered.
- `php artisan view:clear`: passed.
- `php artisan config:clear`: passed.
- `php artisan cache:clear`: passed.
- `php artisan view:cache`: passed; compiled views were cleared afterward.
- PHP lint across all `lang/en/*.php` and `lang/fr/*.php`: passed.
- Recursive EN/FR translation-key parity: passed.
- `git diff --check`: passed.
- `php scripts/localisation-audit.php`: passed and regenerated `docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md`.

Authenticated browser walkthroughs were not performed in this command-line pass. The Blade compiler, route registration, language syntax, key parity, and targeted source checks provide the completed automated gate.

## Files Changed

- `lang/en/appointments.php`
- `lang/fr/appointments.php`
- `lang/en/products.php`
- `lang/fr/products.php`
- `lang/en/validation.php`
- `lang/fr/validation.php`
- `resources/views/appointments/create.blade.php`
- `resources/views/appointments/edit.blade.php`
- `resources/views/appointments/index.blade.php`
- `resources/views/appointments/show.blade.php`
- `resources/views/admin/products/index.blade.php`
- `resources/views/admin/products/show.blade.php`
- `resources/views/admin/products/_edit_modal.blade.php`
- `resources/views/admin/products/_prices_modal.blade.php`
- `scripts/localisation-parity-check.php`
- `docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md`
