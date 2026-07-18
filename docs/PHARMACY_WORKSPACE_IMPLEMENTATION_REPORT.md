# Pharmacy Department Workspace — Implementation Report

Dedicated `/pharmacy/*` browser workspace for `DepartmentType::PHARMACY`,
following the established Records / Nursing / Emergency / Inpatient /
Investigations workspace architecture. All pages are thin browser adapters over
the existing prescription, dispensing, drug-catalogue, stock, billing, journey
and reporting services — no fulfilment, billing, stock or safety logic was
duplicated.

## 1. What the audit found (and what shaped the scope)

- Prescription fulfilment already lives in `PharmacyService` (FEFO batch
  dispensing via `DrugStock`, over-dispense prevention, billing-gated dispensing
  through `PharmacyBillingSelectionService`, partial dispensing via
  `PrescriptionStatus::PARTIALLY_DISPENSED`, dispensing audit via
  `DispensingRecord` + activity log `pharmacy`).
- Browser pages that genuinely exist: prescription list/detail/bill/cancel
  (`Doctor\PrescriptionController` — already workspace-aware via `routeIs()`),
  dispensing queue/detail/dispense/batch/dosage-print/history
  (`Pharmacy\DispensingController`), drug catalogue (`Admin\Pharmacy\DrugController`),
  product stock balances/ledger (`Admin\Store\ProductStockController`),
  counter sale (`admin.billing.counter-sale.*`), operational pharmacy report
  (`admin.reports.pharmacy` → `OperationalReportController`, `can:reports.pharmacy`),
  journey handoffs, patients.
- **Data-model reality check:** prescriptions carry no *target pharmacy
  department* column — the hospital operates a single pharmacy pool, and stock
  scoping already happens inside `PharmacyService` via its pharmacy
  stock-location resolution (`pharmacyStockLocationIds()` / `StockLocationResolver`).
  Per the spec's own constraint ("do not introduce metrics/scoping that cannot
  be calculated reliably", "only register real functionality"), no artificial
  per-department prescription scope was invented. If multi-pharmacy routing is
  added to the schema later, a `PharmacyWorkspaceScope` can be slotted in
  exactly as `InvestigationWorkspaceScope` was.
- Spec items with no existing backing (collections queue, returns, reversals,
  substitution workflow, counselling records, labels beyond the dosage slip,
  batch pages) were **not** given placeholder pages, per the spec's
  "no empty placeholder pages" rule. The dosage-slip print is the existing
  label artifact and stays reachable from the dispensing pages.

## 2. Routes (`routes/web.php`)

`Route::prefix('pharmacy')->name('pharmacy.')->middleware('department.type:pharmacy')`
— inserted after the investigations group. Names mirror admin names (minus
`admin.` / `admin.pharmacy.`) so the resolver maps them automatically.

| Page | URL | Name | Controller | Permission |
|---|---|---|---|---|
| Dashboard | `/pharmacy` | `pharmacy.dashboard` | `Pharmacy\DashboardController` (new) | dept guard |
| Dashboard redirect | `/pharmacy/dashboard` | `pharmacy.dashboard.redirect` | closure | — |
| Prescriptions | `/pharmacy/prescriptions[/{id}]` | `pharmacy.prescriptions.index/show/bill/cancel` | `Doctor\PrescriptionController` | `prescriptions.view` (+ `pharmacy.dispensing.create` / `prescriptions.create` for actions) |
| Dispensing | `/pharmacy/dispensing[...]` | `pharmacy.dispensing.index/show/print-dosage/dispense-item/batch` | `Pharmacy\DispensingController` | `pharmacy.dispensing.view/create` |
| Dispensing history | `/pharmacy/history` | `pharmacy.history` | same | `pharmacy.dispensing.view` |
| Drug catalogue | `/pharmacy/drugs[...]` | `pharmacy.drugs.index/search/history` | `Admin\Pharmacy\DrugController` | `pharmacy.drugs.manage` |
| Medication stock | `/pharmacy/stock[,/ledger]` | `pharmacy.product-stock.balances/ledger` | `Admin\Store\ProductStockController` | `stock.view` |
| Patients | `/pharmacy/patients[/{id}]` | `pharmacy.patients.index/show` | `Admin\Patients\PatientController` | `patients.view` |
| Handoffs | `/pharmacy/handoffs[...]` | `pharmacy.handoffs.index/refresh/claim/assign/acknowledge/resolve` | Journey controllers | journey capabilities |
| Reports | `/pharmacy/reports` | `pharmacy.reports.index` | `OperationalReportController` (`report=pharmacy`) | `reports.pharmacy` |

Catalogue/stock **mutations** stay on the admin POST/PUT endpoints (non-GET is
never redirected, so existing forms keep working). Counter sale stays at
`admin.billing.counter-sale.create` (billing-owned) and is linked from the menu.

## 3. URL resolution & redirects

- `WorkspaceRouteResolver`: `isPharmacy()`, included in `isDepartmentWorkspace()`,
  `dashboardRouteName() → pharmacy.dashboard`, a `routeName()` branch (explicit
  handoff mapping + `admin.pharmacy.*` → `pharmacy.*` to avoid a doubled prefix
  + generic `admin.` → `pharmacy.` fallback guarded by `Route::has()`),
  `handoffIndex()`/`handoffRouteName()` cases, `viewContext()` branch
  (`workspaceKey: pharmacy`, `workspaceScope: medication_fulfilment`) and
  `pharmacyBreadcrumbs()`.
- `records.redirect` added to the admin `pharmacy` group, the admin
  `prescriptions` view group and the admin `product-stock` group: generic
  browser GETs bounce into `/pharmacy/*` for pharmacy users (and into
  `doctor.prescriptions.*` for doctors, consistent with that workspace). JSON /
  AJAX / signed URLs and all non-GET verbs pass through untouched. All
  prescription/dispensing mutations already redirect with `back()`, so actions
  stay inside the workspace automatically.
- `EnsureActiveDepartmentType` returns `__('pharmacy.unauthorized')`.
- Login and department switching land on `pharmacy.dashboard` via the resolver.

## 4. Dashboard

`/pharmacy` reuses the modern **Pharmacist operations board** —
`PharmacistDashboardService` (dispensing-backlog insight banner, dispensing
pressure widget, medicine/pending-order/low-stock/expiring KPIs, sales trend,
stock-by-category donut, recent prescriptions, expiry alerts, low stock, top
sellers, suppliers) rendered by `dashboards/pharmacist.blade.php` through the
new invokable `Pharmacy\DashboardController`. Stock figures use the pharmacy
stock locations resolved inside the services. Legacy links inside the shared
views bounce into the workspace via the redirect middleware.

## 5. Sidebar

`SidebarMenuBuilder`: `PHARMACY` branch in `build()` + `pharmacySections()` —
Dashboard; Medication Fulfilment (Prescriptions, Dispensing Queue, Dispensing
History, Counter Sale); Stock & Catalogue (Medication Stock, Stock Ledger, Drug
Catalog); Patients & Coordination (Patients, Handoffs — capability-gated);
Reports; General (Notifications, Profile). All items permission/module
filtered; permissions remain authoritative.

## 6. Localization

`lang/en/pharmacy.php` + `lang/fr/pharmacy.php` extended with `unauthorized`,
`workspace.title`, `menu.*`, `breadcrumbs.*`. Recursive EN/FR parity verified
by test.

## 7. Tests

`tests/Feature/PharmacyWorkspaceTest.php` — 8 tests, 45 assertions, all passing:

1. Workspace URLs + `department.type:pharmacy` + permission middleware.
2. Non-pharmacy department gets 403 on all workspace pages.
3. Sidebar is workspace-specific, permission filtered, active-state correct.
4. Prescription worklist + detail render inside the workspace.
5. Dashboard renders the operations board; `/pharmacy/dashboard` redirects.
6. Legacy browser routes (`admin.pharmacy.dispensing.index`,
   `admin.prescriptions.index`) redirect into the workspace; JSON stays put.
7. Login and department switch land on the workspace dashboard.
8. EN/FR recursive locale parity.

Regression runs (all green):

- `PharmacyWorkflowTest`, `ModuleAccessTest`, `DoctorWorkspaceTest`,
  `RoleDashboardTest`, `Journey\JourneyActionLinkTest` — 32 passed.
- `InvestigationsWorkspaceTest`, `InpatientWorkspaceTest`,
  `NursingWorkspaceTest`, `RecordsWorkspaceTest`, `EmergencyWorkspaceTest`,
  `Localization\FrenchRouteSmokeTest`, `DepartmentDashboardTest`,
  `DepartmentMenuProfileTest` — 69→71 passed after one intentional test update:
  `DepartmentMenuProfileTest` asserted a pharmacy-department user gets the
  *generic* sidebar; pharmacy users now get the dedicated workspace menu, so
  that test now uses a finance-department user for the generic-menu assertion
  and gained a new assertion that pharmacy users receive the workspace menu.

## 8. Safety, privacy & audit notes

- Billing gate: dispensing still refuses unpaid items
  (`PharmacyBillingSelectionService` + `PharmacyService::dispenseItem`) — the
  workspace adds no bypass.
- FEFO batch selection, over-dispense prevention, partial-dispensing balance
  tracking and stock deduction all remain inside `PharmacyService`, unchanged.
- Dispensing and prescription changes stay audited via the existing
  `pharmacy` activity log and `DispensingRecord` trail.
- The department-type guard sits *on top of* the existing `can:` middleware and
  module gates; a broad `prescriptions.view` permission alone does not grant
  workspace access.
- Pharmacy dispensing remains distinct from Nursing medication administration
  (`admin.pharmacy.medication-administration.*` untouched, not mounted in the
  workspace).

## 9. Remaining limitations

- Collections/returns/reversals/substitution/counselling have no backing
  models or services yet; when those workflows are built, mount them under
  `/pharmacy/*` following this same adapter pattern.
- Prescriptions are a single hospital-wide pool (no per-pharmacy-department
  targeting column); multi-pharmacy scoping needs a schema addition first.
