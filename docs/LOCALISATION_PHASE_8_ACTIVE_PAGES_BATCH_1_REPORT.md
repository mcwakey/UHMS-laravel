# UHMS Localisation Phase 8 - Active Pages Translation Batch 1

Date: 2026-06-12

## Scope

Phase 8 Batch 1 continued from the Phase 7 active route/page inventory and focused only on active runtime pages for:

- Appointments
- Visits Inertia index review
- Admin products
- Service renderings

No demo/template/sample pages or `routes/web.php.bak`-only views were translated.

## Active Routes / Views Checked

Active route-linked Blade views checked in this batch:

- `appointments.index`
- `appointments.create`
- `appointments.edit`
- `appointments.show`
- `appointments.calendar`
- `admin.products.index`
- `admin.products.show`
- `admin.products._form_fields`
- `admin.products._edit_modal`
- `admin.products._prices_modal`
- `service-renderings.index`
- `service-renderings.show`
- `service-renderings.reports`

Active frontend/Inertia visit page checked:

- `resources/js/Pages/Visits/Index.vue`

The lowercase/uppercase Inertia visit page paths appear mirrored in the current workspace; the checked uppercase file already reflected the same translated date-range changes.

## Appointments Cleaned

Translated appointment pages and related active surfaces:

- `resources/views/appointments/index.blade.php`
- `resources/views/appointments/create.blade.php`
- `resources/views/appointments/edit.blade.php`
- `resources/views/appointments/show.blade.php`
- `resources/views/appointments/calendar.blade.php`

Work completed:

- Page titles, headings, navigation buttons, form labels, placeholders, table headers, status timeline labels, and empty states now use translation keys.
- Create/edit appointment service and insurance sections were localized.
- Appointment create inline JavaScript now uses a page-local translated string map for patient search, insurance loading, service loading, action tooltips, and empty states.
- Appointment show AJAX feedback strings were localized.
- Appointment enum/status labels were moved from `label()` to `translatedLabel()` where safe.

Known deferred appointment items:

- A few encoded comments and non-visible developer comments remain unchanged.
- Some edit-page inline JavaScript strings remain candidates for a later dedicated JS pass.

## Visits / Inertia Review

Checked:

- `resources/js/Pages/Visits/Index.vue`

Work completed:

- The existing `useTrans()` bridge was reused.
- Date-range preset labels and the picker clear label now use `visits.*` translation keys.
- No new frontend localisation system, package, or framework was introduced.

Deferred:

- Wider billing Inertia pages remain outside this batch except for review notes.
- Visit Blade create/edit/show/preview pages already contained substantial translation coverage from earlier phases, but still need a deeper second JS/string sweep.

## Products Cleaned

Translated active product views and partials:

- `resources/views/admin/products/index.blade.php`
- `resources/views/admin/products/show.blade.php`
- `resources/views/admin/products/_form_fields.blade.php`
- `resources/views/admin/products/_edit_modal.blade.php`
- `resources/views/admin/products/_prices_modal.blade.php`

Work completed:

- Product show tabs, details labels, stock balance labels, billing labels, pricing labels, form labels, modal buttons, and confirmation strings were localized.
- Product type display labels were moved to `translatedLabel()` in index/show/forms.
- Insurance type labels passed to product pricing JavaScript now use `translatedLabel()`.
- Stock/product terminology was kept separate from service terminology.
- Existing cost visibility and permissions were not changed.

Known deferred product items:

- A few encoded title strings and explanatory pricing summary/comment strings remain in the audit for a later encoding-safe cleanup pass.

## Services / Service Rendering Cleaned

Translated active service-rendering views:

- `resources/views/service-renderings/index.blade.php`
- `resources/views/service-renderings/show.blade.php`
- `resources/views/service-renderings/reports.blade.php`

Work completed:

- Worklist title, description, stats, filters, table headers, row fallbacks, action buttons, modal labels, form labels, detail labels, audit trail labels, and report labels were localized.
- Rendering, payment, and report status labels now resolve through shared `statuses.default.*` keys.
- Service rendering workflow logic, billing logic, and state transitions were not changed.

Known deferred service-rendering items:

- Select2 placeholder strings containing encoded ellipses resisted safe patching and remain documented for a later frontend-string pass.
- Some audit-log action values may still fall back to raw status keys if no domain-specific action translation exists.

## Language Files Updated

Updated EN/FR language files:

- `lang/en/appointments.php`
- `lang/fr/appointments.php`
- `lang/en/products.php`
- `lang/fr/products.php`
- `lang/en/services.php`
- `lang/fr/services.php`
- `lang/en/statuses.php`
- `lang/fr/statuses.php`
- `lang/en/visits.php`
- `lang/fr/visits.php`

No new language file families were required.

## JavaScript Strings Translated

Translated JS/frontend strings:

- Appointment create patient search, insurance loading, insurance status/fallback, service loading, add/delete labels, and doctor option fallback strings.
- Appointment show AJAX working/error/success/open feedback strings.
- Visit Inertia date range preset labels and clear label.

Deferred JS/frontend strings:

- Product pricing dynamic row template labels in some generated HTML still include a few raw labels.
- Service-rendering Select2 placeholders with encoded ellipses need a safer targeted pass.
- Billing Inertia pages remain for a future batch.

## Dynamic Labels Updated

Confirmed targeted active batch scan for `->label()` returned no remaining matches after updates.

Updated dynamic display labels include:

- Appointment status and visit type labels.
- Appointment priority and consultation mode labels.
- Product type labels.
- Product insurance type labels passed into pricing JS.
- Visit Inertia date-range labels through `useTrans()`.

## Responsive Fixes

Responsive-safe Bootstrap patterns were preserved while translating:

- Existing table wrappers remain in place for appointment, product, and service-rendering tables.
- Action button groups remain flex/wrapped where already used.
- No Tailwind or new CSS framework was introduced.

## Verification

Completed checks:

- `php artisan view:clear` passed.
- `php artisan config:clear` passed.
- `php artisan cache:clear` passed.
- `php artisan route:list` passed with 714 routes.
- `php artisan view:cache` passed, then compiled views were cleared again with `php artisan view:clear`.
- All EN/FR PHP language files passed `php -l`.
- Nested EN/FR translation key parity passed with `PARITY_OK`.
- Targeted dynamic-label scan found no remaining `->label()` usage in the Phase 8 target paths.
- `php scripts/localisation-audit.php` completed and refreshed `docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md`.

Latest audit output:

- Files scanned: 1258
- Files with candidates: 531
- Candidate strings: 19434

Phase 7 audit baseline was 19639 candidates, so this batch reduced the raw candidate count by 205.

## Remaining Active Untranslated Pages

Still requiring future active-page localisation batches:

- Visit Blade create/edit/show/preview deeper JS and partial sweep.
- Billing Inertia pages and invoice/payment/statement Blade print flows.
- Product pricing explanatory copy and remaining encoded modal title strings.
- Admin service catalogue modal internals beyond the index.
- Procedure, lab, radiology, and service catalogue rendering-adjacent pages.
- Store, stock, suppliers, requisitions, purchase orders, receipts, returns, transfers, adjustments, and valuation pages.
- HR attendance, employees, leave, payroll, and related screens.
- Theatre index, show, report, calendar, rooms, and consumables pages.
- Blood bank pages beyond the dashboard.
- Medication administration pages beyond the admission board.
- Reports, accounting, claims, wards, triage, queues, settings, and shared workflow components.

## Conclusion

Phase 8 Batch 1 completed a focused active-page translation pass for appointments, products, service renderings, and the visit Inertia index. It reduced the audit candidate count, preserved EN/FR parity, kept existing workflows intact, and avoided adding any new localisation system.
