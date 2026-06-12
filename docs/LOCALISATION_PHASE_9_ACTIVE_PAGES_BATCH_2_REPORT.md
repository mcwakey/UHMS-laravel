# UHMS Localisation Phase 9 - Active Pages Translation Batch 2 Report

Date: 2026-06-12

## Scope

Phase 9 continued the active route/page localisation cleanup from Phase 8, focusing on runtime pages in these areas:

- Consultations
- Theatre / procedures
- Lab, investigations, and procedure catalogue surfaces
- Medication administration
- Blood bank

The work used the active route inventory and existing localisation conventions. No new frontend package, localisation system, route, controller, or workflow was introduced.

## Implemented

### Consultations

- Added `lang/en/consultations.php` and `lang/fr/consultations.php`.
- Localised the consultations queue page title, heading, description, filters, action labels, table headers, empty state, and workflow buttons in `resources/views/consultations/index.blade.php`.
- Converted active consultation status/type/priority enum display calls from raw `label()` to `translatedLabel()` in:
  - `resources/views/consultations/show.blade.php`
  - `resources/views/consultations/history.blade.php`

### Theatre / Procedures

- Added `lang/en/theatre.php` and `lang/fr/theatre.php`.
- Localised the theatre board title, heading, filters, stats, tabs, table headers, action buttons, patient visit label, and empty state in `resources/views/theatre/index.blade.php`.
- Converted theatre enum display calls from raw `label()` to `translatedLabel()` in:
  - `resources/views/theatre/show.blade.php`
  - `resources/views/theatre/report.blade.php`
  - `resources/views/theatre/calendar.blade.php`
  - `resources/views/theatre/partials/schedule-form.blade.php`
  - `resources/views/theatre/rooms/index.blade.php`
  - `resources/views/theatre/rooms/partials/form.blade.php`

### Lab / Investigations / Catalogues

- Converted lab and investigation enum display calls from raw `label()` to `translatedLabel()` in:
  - `resources/views/lab/tests.blade.php`
  - `resources/views/lab/process.blade.php`
  - `resources/views/lab/requests.blade.php`
  - `resources/views/investigations/items/stock.blade.php`

### Medication Administration

- Extended `lang/en/medication_administration.php` and `lang/fr/medication_administration.php`.
- Localised the emergency medication board title, description, stat labels, table headers, action labels, and empty state in `resources/views/medication-administration/emergency-board.blade.php`.
- Localised medication administration report filters, headings, table headers, status labels, and empty states in `resources/views/medication-administration/reports.blade.php`.

### Blood Bank

- Extended `lang/en/blood_bank.php` and `lang/fr/blood_bank.php`.
- Localised donor registry page title, description, filters, table headers, action labels, empty state, and donor registration modal in `resources/views/blood-bank/donors.blade.php`.
- Added `common.gender_other` to both EN and FR locale files for existing gender option rendering.

## Active Route Inventory Notes

The Phase 9 route-linked views checked included:

- Consultations: `consultations.index`, `consultations.show`, `consultations.history`, `consultations.partials.summary-sections`
- Theatre: `theatre.index`, `theatre.show`, `theatre.report`, `theatre.rooms.index`, `theatre.calendar`, `theatre.consumables.index`
- Lab: `lab.results`, `lab.process`, `lab._result_modal`, `lab.print`, `lab.tests`
- Blood bank: `blood-bank.dashboard`, `blood-bank.units`, `blood-bank.storage`, `blood-bank.requests`, `blood-bank.donors`, `blood-bank.donor-profile`, `blood-bank.donations`, `blood-bank.donation-view`, `blood-bank.reports`
- Medication administration: `medication-administration.admission-board`, `medication-administration.admission-show`, `medication-administration.reports`, `medication-administration.mar-chart`, `medication-administration.emergency-board`
- Catalogues: `admin.procedures.index`, `admin.procedures.schedule`, `investigations.items.index`, `theatre.consumables.index`

## Deferred Follow-Up

The following active pages still need deeper text-by-text localisation in later batches:

- Large consultation detail workflows and inline JavaScript strings in `resources/views/consultations/show.blade.php`.
- Theatre show/report/calendar and consumables pages beyond enum/status conversion.
- Lab results/process/print/modal pages beyond enum/result-type conversion.
- Medication admission board, admission show, MAR chart, and MAR partial content beyond reports/emergency board.
- Blood bank dashboard, units, storage, requests, donor profile, donations, donation view, and reports beyond donor registry.

## Verification

Completed successfully:

- `php artisan view:clear`
- `php artisan config:clear`
- `php artisan cache:clear`
- `php artisan route:list`
- `php artisan view:cache`
- `php artisan view:clear`
- PHP syntax lint for all `lang/en/*.php` and `lang/fr/*.php`
- Nested EN/FR translation key parity check: `PARITY_OK`
- Scoped raw enum label scan:
  - `rg -n --glob '!*.bak' -- "->label\(" resources/views/consultations resources/views/theatre resources/views/lab resources/views/investigations resources/views/medication-administration resources/views/blood-bank`
  - Result: no remaining matches in scoped active Batch 2 view folders.

## Audit Result

`php scripts/localisation-audit.php`

- Report written: `docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md`
- Files scanned: 1262
- Files with candidates: 529
- Candidates: 19337
