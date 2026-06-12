# UHMS Localisation Phase 7 - Complete Active Route/Page Translation Inventory & Cleanup

Date: 2026-06-12

## Scope

Phase 7 was run from the live application state, not from historical prompts or `routes/web.php.bak`.

The pass focused on:

- Building an active route/page inventory from `php artisan route:list`.
- Mapping direct controller/route `view(...)` references to Blade files under `resources/views`.
- Classifying active route-linked pages separately from templates, backup-only files, and shared partials/components.
- Cleaning another set of high-impact active runtime pages with EN/FR translation parity.
- Re-running localisation audit and framework cache/route checks.

## Active Route And View Inventory

Live route inventory:

- Active Laravel routes: 714
- Live controller/route `view(...)` references found in `app/Http/Controllers` and active route files: 240
- Blade files under `resources/views`: 590
- Direct active route-linked Blade views matched by static inventory: 227

The direct active count intentionally excludes:

- Backup-only route declarations in `routes/web.php.bak`.
- Demo/template views that are not returned by active controllers/routes.
- Shared partials, layouts, and Blade components that are included by active pages but are not returned directly by `view(...)`.
- Inertia pages, which are active runtime pages but are not Blade views.

Active Inertia pages were observed separately, including visits and billing flows such as `Visits/Index` and billing Inertia pages. These still need localisation review in their frontend source files.

## Runtime Areas Confirmed Active

The route/view inventory confirms active runtime coverage across:

- Appointments
- Visits
- Consultations
- Patients and patient merge flows
- Admin products
- Admin services
- Service renderings, procedures, laboratory catalogues, and radiology catalogues
- Store, stock, suppliers, purchase orders, requisitions, receipts, returns, and adjustments
- Theatre
- HR attendance, employees, leave, and payroll
- Blood bank dashboard, donors, donations, requests, reports, storage, and units
- Medication administration admission board, MAR chart, emergency board, reports, and partials
- Billing, invoices, claims, payments, reports, accounting, wards, triage, queue, settings, and shared layout surfaces

## Views Cleaned In This Phase

### `resources/views/appointments/index.blade.php`

The active appointments index was updated to use translation keys for:

- Page title and visible heading
- List/calendar view controls
- New appointment action
- Summary stat labels
- Search, status, doctor, and department filters
- Table headers

The appointment status filter now uses the enum translation method instead of raw enum labels.

### `resources/views/admin/products/index.blade.php`

The active product catalogue index was updated to use translation keys for:

- Page title and heading
- Product count text
- Add product action and modal title
- Search placeholder
- Type, department, status, pricing, billable, and supplier-history filters
- Table headers
- Base price badge, empty-state text, and stock action title

The product type filter now uses the enum translation method instead of raw enum labels.

### `resources/views/admin/services/index.blade.php`

The active service catalogue index was updated to use translation keys for:

- Page title and heading
- Add service action
- Search placeholder
- Category and department filters
- Table headers
- Price management actions
- Status badges
- Edit and activate/deactivate actions
- Edit service modal title

## Translation Files Added

New EN/FR translation files were added:

- `lang/en/appointments.php`
- `lang/fr/appointments.php`
- `lang/en/products.php`
- `lang/fr/products.php`
- `lang/en/services.php`
- `lang/fr/services.php`

## Translation Files Extended

Shared EN/FR translation files were extended:

- `lang/en/common.php`
- `lang/fr/common.php`

Added shared keys include:

- `activate`
- `deactivate`
- `all_statuses`
- `code`
- `departments`
- `all_departments`

## Active Pages Still Requiring Cleanup

Phase 7 completed the inventory and cleaned another high-impact active slice, but the active runtime surface is still larger than one safe pass. Remaining active areas with hardcoded user-facing text include:

- Appointment create, edit, show, calendar, and any inline scripts.
- Visit create/edit/show/preview and Inertia visit pages.
- Consultation show and related clinical workflow pages.
- Product show, product modal internals, pricing modal internals, and stock-linked product screens.
- Service modal internals, service rendering pages, procedure catalogue pages, lab catalogue pages, and radiology catalogue pages.
- Theatre index, show, report, calendar, rooms, and consumables pages.
- Store and stock pages, including purchase orders, receipts, returns, adjustments, requisitions, suppliers, and product stock views.
- HR attendance, employees, leave, payroll, and related management screens.
- Blood bank pages beyond the dashboard.
- Medication administration pages beyond the admission board.
- Reports, billing, invoices, claims, accounting, wards, triage, queues, settings, and shared workflow components.
- Frontend/Inertia JavaScript strings and dynamic labels outside Blade.

## Backup, Demo, And Template Exclusions

The inventory excluded backup-route-only and demo/template files from the active page count. Examples of non-active template/demo surfaces observed under `resources/views` include root-level UI demo pages such as widget, dropdown, table, form, and chart templates.

Several prompt-named directories are not present as standalone `resources/views` directories in the current codebase, including:

- `resources/views/products`
- `resources/views/services`
- `resources/views/stock`
- `resources/views/inventory`
- `resources/views/procurement`
- `resources/views/suppliers`
- `resources/views/purchase-orders`
- `resources/views/stock-requisitions`
- `resources/views/payroll`
- `resources/views/attendance`
- `resources/views/leave`
- `resources/views/users`
- `resources/views/activity-log`
- `resources/views/pdf`
- `resources/views/prints`

Equivalent active functionality exists under other route-linked directories such as `admin`, `store`, `hr`, `billing`, and `settings`, and those areas are included in the active backlog.

## Audit Result

`php scripts/localisation-audit.php` completed successfully.

Latest audit output:

- Files scanned: 1258
- Files with candidates: 538
- Candidate strings: 19639
- Report refreshed: `docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md`

Compared with the previous Phase 6 audit summary of 19694 candidates, the raw candidate count decreased by 55. The file count increased because new translation files were added and scanned.

## Verification

Completed checks:

- `php artisan view:clear` passed.
- `php artisan config:clear` passed.
- `php artisan cache:clear` passed.
- `php artisan route:list` passed with 714 routes.
- All EN/FR PHP language files passed `php -l`.
- Nested EN/FR translation key parity passed with `PARITY_OK`.
- `php scripts/localisation-audit.php` completed and refreshed the coverage report.

Full recursive project PHP lint was not repeated in this phase because previous full-recursive lint attempts timed out on this repository. Phase 7 instead verified the changed language files, all EN/FR language files, framework route/cache commands, and the localisation audit.

## Conclusion

Phase 7 established a current active route/page translation inventory and completed another targeted cleanup pass on high-impact active pages. The application is not yet fully free of hardcoded runtime text; the remaining work is now better bounded by the active inventory and should continue module-by-module from the backlog above.
