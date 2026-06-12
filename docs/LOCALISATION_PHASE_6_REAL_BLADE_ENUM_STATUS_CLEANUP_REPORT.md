# UHMS Localisation Phase 6 Real Blade / Enum / Status Cleanup Report

Date: 2026-06-12

## Route-To-View Audit Summary

`php artisan route:list` was used to confirm active UHMS route areas before editing Blade files. The active routed Blade areas include:

- `resources/views/medication-administration/*` via admission/emergency medication board controllers.
- `resources/views/blood-bank/*` via blood-bank dashboard, requests, donors, donations, units, storage, and reports controllers.
- `resources/views/theatre/*`, `resources/views/hr/*`, and `resources/views/store/*` via active admin route groups.
- `resources/views/layout/partials/sidebar.blade.php` is used by the live layout, but menu labels are translated through `SidebarMenuBuilder::translateLabel()`.

The legacy `routes/web.php.bak` file references many template/demo pages, but those routes are not active in the live route list.

## Real Active Views Cleaned

- `resources/views/medication-administration/admission-board.blade.php`
  - Localised page title, page description, ward filter option, filter button, table headers, ward fallback, action buttons, and empty state.
- `resources/views/blood-bank/dashboard.blade.php`
  - Localised page title, page description, action buttons, stat card labels, table/card headings, table headers, and empty states.

## Shared Components Cleaned

- `resources/views/components/status-badge.blade.php`
  - Kept existing `statuses.php` lookup order.
  - Added safe fallback to enum `translatedLabel()` before raw enum `label()`.
  - No enum values, constants, colors, or status logic changed.

## Demo / Template Views Excluded

The following audit-heavy files were excluded because they are only referenced by `routes/web.php.bak` or appear to be template/demo assets, not active UHMS runtime routes:

- `resources/views/widgets.blade.php`
- `resources/views/ui-dropdowns.blade.php`
- `resources/views/tables-basic.blade.php`
- `resources/views/ui-modals.blade.php`
- `resources/views/social-feed.blade.php`
- `resources/views/form-select2.blade.php`
- `resources/views/layout-dark.blade.php`
- `resources/views/layout-full-width.blade.php`
- `resources/views/layout-hidden.blade.php`
- `resources/views/layout-hover-view.blade.php`
- `resources/views/layout-mini.blade.php`
- `resources/views/layout-rtl.blade.php`
- `resources/views/components/modal-popup.blade.php`

Recommendation: leave as reference for now or archive/delete in a dedicated template-cleanup phase after confirming no downstream dependency.

## Legacy / Unsure Views

Root-level legacy views such as `doctors.blade.php`, `payments.blade.php`, `email.blade.php`, `patient-details.blade.php`, `staffs.blade.php`, `add-doctor.blade.php`, and similar files remain audit-heavy. They appear tied to the legacy backup route file or starter template pages. They should be reviewed separately before any translation work.

## Enum / Model / Status Labels

- Audited `app/Enums` and found the enum set already exposes `translatedLabel()` broadly.
- Updated the shared status badge component to call `translatedLabel()` when direct `statuses.{domain}` and `statuses.default` keys are unavailable.
- Did not alter stored enum values, canonical constants, colors, transition logic, or model casts.

## Service Event Titles / Labels

Translated safe UI payload labels in:

- `app/Services/PatientMergePreviewService.php`
  - Record table display labels.
  - Demographic field display labels.
  - Merge preview warning messages.

Deferred classification:

- Admission, blood-bank, procedure, lab, emergency, queue, pharmacy, and accounting service `title` / `message` findings still need per-workflow confirmation because many may be stored canonical event titles or audit timeline entries.

## Sidebar / Menu Decision

`app/Services/SidebarMenuBuilder.php` remains an audit false positive. It stores source labels, then translates section titles and item labels through `translateLabel()` before rendering. No menu hierarchy, permissions, modules, route names, icons, or active patterns were changed.

## Language Files Changed

Created:

- `lang/en/blood_bank.php`
- `lang/fr/blood_bank.php`
- `lang/en/medication_administration.php`
- `lang/fr/medication_administration.php`

Updated:

- `lang/en/patients.php`
- `lang/fr/patients.php`

## Audit Result

After Phase 6 cleanup:

- Files scanned: 1252
- Files with candidates: 534
- Candidates: 19694

For comparison, the previous report had:

- Files scanned: 1248
- Files with candidates: 536
- Candidates: 19723

The scan count increased because new language files were added, while candidate count still decreased.

## Verification

Passed:

- `php scripts/localisation-audit.php`
- `php artisan view:clear`
- `php artisan config:clear`
- `php artisan cache:clear`
- `php artisan route:list` (714 routes)
- PHP lint for touched files
- Full EN/FR nested translation key parity: `PARITY_OK`

Not fully completed:

- Full recursive PHP lint over `app,database,routes,config` was attempted and timed out after 300 seconds. Touched-file lint passed.

## Remaining TODOs

1. Continue active Blade cleanup for `consultations/show.blade.php`, `theatre/*`, `store/*`, `hr/*`, and additional blood-bank/medication-administration screens.
2. Build or improve an audit allowlist for template/demo views and language files so future reports highlight real runtime debt more clearly.
3. Review remaining service `title` / `message` findings with product intent before translating stored event titles.
4. Replace direct `->label()` calls in high-traffic Blade views with `->translatedLabel()` or shared components where safe.
