# UHMS Localisation Phase 10 - Active Pages Translation Batch 3 Report

Date: 2026-06-12

## Scope

Phase 10 continued active runtime page localisation from the Phase 9 deferred list. The batch focused on deeper clinical/procedure pages without changing workflows, status transitions, stored enum values, business logic, packages, routes, or localisation architecture.

## Active Views Checked

- Consultations: `resources/views/consultations/show.blade.php`, `resources/views/consultations/history.blade.php`
- Theatre/procedures: `resources/views/theatre/report.blade.php`, `resources/views/theatre/calendar.blade.php`, `resources/views/theatre/consumables/index.blade.php`, `resources/views/theatre/partials/_consumables.blade.php`, `resources/views/theatre/partials/timeline.blade.php`
- Lab/investigations: `resources/views/lab/_consumables.blade.php`, plus prior active lab process/result/print/modal files rechecked
- Medication administration/MAR: `resources/views/medication-administration/admission-show.blade.php`, `resources/views/medication-administration/mar-chart.blade.php`, `resources/views/medication-administration/partials/administer-modal.blade.php`, `resources/views/medication-administration/partials/mar-chart-content.blade.php`
- Blood bank: `resources/views/blood-bank/storage.blade.php`, `resources/views/blood-bank/units.blade.php`, `resources/views/blood-bank/donations.blade.php`, `resources/views/blood-bank/donation-view.blade.php`, `resources/views/blood-bank/donor-profile.blade.php`, `resources/views/blood-bank/requests.blade.php`, `resources/views/blood-bank/reports.blade.php`

## Consultation Work

- Added translated department-history type/status keys to `lang/en/consultations.php` and `lang/fr/consultations.php`.
- Added translation-backed `translatedTypeLabel()` and `translatedStatusLabel()` helpers to `app/Models/VisitDepartmentHistory.php`.
- Updated consultation detail department history badges in `resources/views/consultations/show.blade.php`.

Residual: the large consultation detail workspace still contains many visible literals across clinical tabs, modal labels, and inline JavaScript. It is documented for a dedicated follow-up pass because the file is over 4,000 lines and touches many clinical workflows.

## Theatre / Procedure Work

- Extended `lang/en/theatre.php` and `lang/fr/theatre.php` for procedure reports, checklist labels, vitals headers, anaesthesia/operative/post-op report headings, calendar controls, consumables, and stock summary labels.
- Localised `resources/views/theatre/report.blade.php`.
- Localised `resources/views/theatre/calendar.blade.php`.
- Localised `resources/views/theatre/consumables/index.blade.php`.
- Localised theatre consumables and timeline partial labels.
- Corrected `theatre.visit_label` to be a plain label (`Visit` / `Visite`) because active views use it before a visit number.

## Lab / Investigation Work

- Added `lab.search_product_placeholder` to EN/FR lab files.
- Localised the remaining lab consumable product-search placeholder in `resources/views/lab/_consumables.blade.php`.
- Rechecked the active lab process/results/print/modal files; prior keys cover those deeper screens.

## Medication Administration / MAR Work

- Extended `lang/en/medication_administration.php` and `lang/fr/medication_administration.php` with MAR chart, administration modal, stock source, correction, PRN/SOS, admission detail, and JavaScript error text.
- Localised `resources/views/medication-administration/admission-show.blade.php`.
- Localised `resources/views/medication-administration/mar-chart.blade.php`.
- Localised `resources/views/medication-administration/partials/administer-modal.blade.php`.
- Localised `resources/views/medication-administration/partials/mar-chart-content.blade.php`.
- Translated the MAR AJAX fallback error message through Blade JSON.

## Blood Bank Work

- Extended `lang/en/blood_bank.php` and `lang/fr/blood_bank.php` for storage, unit inventory, donation, donor profile, requests, crossmatch/issue workflows, and reports.
- Localised:
  - `resources/views/blood-bank/storage.blade.php`
  - `resources/views/blood-bank/units.blade.php`
  - `resources/views/blood-bank/donations.blade.php`
  - `resources/views/blood-bank/donation-view.blade.php`
  - `resources/views/blood-bank/donor-profile.blade.php`
  - `resources/views/blood-bank/requests.blade.php`
  - `resources/views/blood-bank/reports.blade.php`
- Translated Select2 placeholders and storage-modal JavaScript titles through Blade JSON.

## Dynamic Labels

Scoped scan completed:

`rg -n --glob '!*.bak' -- "->label\\(|->statusLabel\\(|->typeLabel\\(|getLabelAttribute\\(|displayName\\(|humanName\\(" resources/views/consultations resources/views/theatre resources/views/lab resources/views/investigations resources/views/medication-administration resources/views/blood-bank app/Models/VisitDepartmentHistory.php`

Result: no remaining matches in the scoped active Phase 10 folders/model.

## Responsive Cleanup

- Preserved existing Bootstrap table-responsive wrappers.
- Kept MAR tables and blood-bank request/report tables inside existing responsive containers.
- Avoided changing clinical layout structure or workflow forms.

## Verification

Completed successfully:

- PHP syntax lint for all `lang/en/*.php` and `lang/fr/*.php`
- `php -l app/Models/VisitDepartmentHistory.php`
- `php artisan view:clear`
- `php artisan config:clear`
- `php artisan cache:clear`
- `php artisan route:list`
- `php artisan view:cache`
- `php artisan view:clear`
- Nested EN/FR translation key parity: `PARITY_OK`

## Audit Result

`php scripts/localisation-audit.php`

- Report written: `docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md`
- Files scanned: 1262
- Files with candidates: 521
- Candidates: 18955

## Remaining Active Pages

Remaining active untranslated work is concentrated in:

- `resources/views/consultations/show.blade.php`: large clinical detail workspace, modal labels, tab content, alerts, confirmations, and inline JavaScript strings.
- Some deeper blood-bank donor screening questionnaire labels and report subheaders may still need a final text pass.
- Theatre show/schedule forms have prior enum/status cleanup and report/calendar/consumables coverage, but the interactive show workflow can still use a dedicated text pass.

