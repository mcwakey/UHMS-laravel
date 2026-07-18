# Stores Department Workspace — Implementation Report

Dedicated `/stores/*` browser workspace for `DepartmentType::STORES`, following
the established Records / Nursing / Emergency / Inpatient / Investigations /
Pharmacy workspace architecture. All pages are thin browser adapters over the
existing requisition, purchase, stock-ledger, supplier, journey and reporting
services — no inventory, ledger, approval or valuation logic was duplicated.

## 1. What the audit found (and what shaped the scope)

- The inventory backbone already exists under `admin.store.*` and friends:
  - **Stock requisitions** (`Admin\Store\StockRequisitionController` +
    `StockRequisitionService`): index/create/show/approve/issue/acknowledge/cancel
    with `StockRequisitionStatus` (draft → submitted → approved/partially_approved
    → awaiting/partially_acknowledged → completed/cancelled), permissions
    `store.requisition.view/create/approve/issue/acknowledge`. This IS the
    requisition→approval→issue→department-acknowledgement lifecycle the spec
    describes (issue = stock deduction, acknowledgement = department receipt).
  - **Purchase orders / goods receipt** (`PurchaseOrderController`):
    create/submit/approve/**receive**/cancel with `PurchaseOrderStatus`
    (draft/submitted/approved/partially_received/received/cancelled) — receive
    is the goods-receipt step, batch + expiry captured into stock movements.
  - **Supplier returns** (`PurchaseReturnController`): create/approve/post/cancel.
  - **Stock control** (`Admin\Store\StockController` + `StockBalanceMatrixService`,
    `InventoryValuationReportService`, `StockOperationService`): balances matrix,
    valuation (permission `reports.inventory_valuation.view`), ledger,
    adjustments, returns, transfers, batch detail, movement detail, locations.
  - **Products** (`admin.products.*`, `product.view`), **suppliers + ledger**,
    `StockLocationResolver`, `ProductStockMovement/Balance` ledger tables, and
    the operational `admin.reports.stock` report (`can:reports.stock`).
- **Not built (per the "no placeholder pages" rule):** standalone stock-count /
  reconciliation / quarantine / recall / damage / disposal modules do not exist
  in the codebase (no models or services). Damage/loss/expiry corrections are
  represented today through the existing adjustment types and the ledger. These
  spec sections are documented as limitations, ready to mount under `/stores/*`
  when the underlying workflows are built.
- **Scoping reality:** requisitions carry the *requesting* department; there is
  no per-stores-department pool in the schema (a single central store issues to
  all departments), and location filtering already exists on the balance/ledger
  pages. No artificial scope layer was invented — consistent with the pharmacy
  workspace decision.

## 2. Routes (`routes/web.php`)

`Route::prefix('stores')->name('stores.')->middleware('department.type:stores')`
— inserted after the pharmacy group. Names mirror the admin names (minus
`admin.` / `admin.store.`) so the resolver maps them automatically.

| Page | URL | Name | Controller | Permission |
|---|---|---|---|---|
| Dashboard | `/stores` (+ `/dashboard` redirect) | `stores.dashboard[.redirect]` | `Stores\DashboardController` (new) | dept guard |
| Requisitions | `/stores/requisitions[...]` | `stores.stock-requisitions.index/create/store/show/approve/issue/acknowledge/cancel` | `Admin\Store\StockRequisitionController` | `store.requisition.*` |
| Purchase orders | `/stores/purchase-orders[...]` | `stores.purchase-orders.index/create/show` | `Admin\Store\PurchaseOrderController` | `store.purchase.view/create` |
| Supplier returns | `/stores/purchase-returns[...]` | `stores.purchase-returns.index/create/show` | `Admin\Store\PurchaseReturnController` | `store.return.*` |
| Suppliers | `/stores/suppliers[...]` | `stores.suppliers.index/ledger` | `Admin\Store\SupplierController` | `store.purchase.view` |
| Stock | `/stores/stock[...]` | `stores.stock.balances/valuation/ledger/adjustments.*/returns.*/transfers.*/batches.show/movements.show/locations.index` | `Admin\Store\StockController` | `store.purchase.view` (+ `reports.inventory_valuation.view`, `store.purchase.create` for create forms) |
| Products | `/stores/products[/{id}]` | `stores.products.index/show` | `Admin\Pharmacy\ProductController` | `product.view` |
| Handoffs | `/stores/handoffs[...]` | `stores.handoffs.index/refresh/claim/assign/acknowledge/resolve` | Journey controllers | journey capabilities |
| Reports | `/stores/reports` | `stores.reports.index` | `OperationalReportController` (`report=stock`) | `reports.stock` |

PO/supplier/product **mutations** stay on the admin POST/PUT endpoints (non-GET
is never redirected; those actions already redirect with `back()` or into
`admin.store.*` show pages, which the redirect middleware bounces into the
workspace for stores users).

## 3. URL resolution & redirects

- `WorkspaceRouteResolver`: `isStores()`, included in `isDepartmentWorkspace()`,
  `dashboardRouteName() → stores.dashboard`, a `routeName()` branch (explicit
  handoff mapping + `admin.store.*` → `stores.*` to avoid a `stores.store.*`
  prefix + generic `admin.` → `stores.` fallback guarded by `Route::has()`),
  `handoffIndex()`/`handoffRouteName()` cases, `viewContext()` branch
  (`workspaceKey: stores`, `workspaceScope: inventory_operations`) and
  `storesBreadcrumbs()`.
- `records.redirect` added to the admin `store` group and the admin `products`
  group (the `product-stock` group already had it): generic browser GETs bounce
  into `/stores/*` for stores users; JSON / AJAX / signed URLs and all non-GET
  verbs pass through untouched. Other workspaces are unaffected — their mapped
  candidates (`records.store.*`, `pharmacy.store.*`, …) don't exist, so
  `Route::has()` blocks the redirect.
- `EnsureActiveDepartmentType` returns `__('stores.unauthorized')`.
- Login and department switching land on `stores.dashboard` via the resolver.

## 4. Dashboard (inventory operations board)

New `StoresDashboardService` + `dashboards/stores.blade.php` in the modern
design language (insight banner, `BuildsPressure` widget, KPI cards, local
ApexCharts, `badge-soft-*`):

- **Insight** — submitted-requisition backlog with oldest-wait chip, linking to
  the requisition worklist.
- **Pressure** — fulfilment load (pending approval + awaiting issue) with
  approval/issue/acknowledgement/delivery metric chips.
- **KPIs** — pending requisitions, awaiting issue, POs awaiting delivery,
  products below reorder level.
- **Charts** — 7-day stock-in vs stock-out movement trend
  (`product_stock_movements`), on-hand stock by product type donut.
- **Lists** — active requisitions table (→ `stores.stock-requisitions.show`),
  purchase orders awaiting delivery, low-stock products table.

All metric queries are bounded (counts + top-N) and financial valuation is not
shown on the dashboard — the valuation page keeps its own
`reports.inventory_valuation.view` gate.

## 5. Sidebar

`SidebarMenuBuilder`: `STORES` branch in `build()` + `storesSections()` —
Dashboard; Requisitions & Movements (Requisitions, Transfers, Returns,
Adjustments); Stock Control (Balances, Ledger, Valuation, Locations, Products);
Procurement (Purchase Orders, Purchase Returns, Suppliers); Coordination
(Handoffs — capability-gated); Reports; General. All items permission/module
filtered; permissions remain authoritative.

## 6. Localization

New `lang/en/stores.php` + `lang/fr/stores.php` (`unauthorized`,
`workspace.title`, `menu.*`, `dashboard.*`, `breadcrumbs.*`) with recursive
EN/FR parity verified by test.

## 7. Tests

`tests/Feature/StoresWorkspaceTest.php` — 8 tests, 48 assertions, all passing:

1. Workspace URLs + `department.type:stores` + permission middleware.
2. Non-stores department gets 403 on all workspace pages.
3. Sidebar is workspace-specific, permission filtered, active-state correct.
4. Requisition worklist + detail render inside the workspace.
5. Dashboard renders the operations board with accurate pending-requisition
   count; `/stores/dashboard` redirects.
6. Legacy browser routes (`admin.store.stock-requisitions.index`,
   `admin.products.index`) redirect into the workspace; JSON stays put.
7. Login and department switch land on the workspace dashboard.
8. EN/FR recursive locale parity.

Regression runs (all green):

- `FrenchRouteSmokeTest`, `DepartmentMenuProfileTest`, `DepartmentDashboardTest`,
  `PharmacyWorkspaceTest`, `InvestigationsWorkspaceTest`, `PharmacyWorkflowTest`
  — 42 passed.
- Broad workspace suite: `InpatientWorkspaceTest`, `NursingWorkspaceTest`,
  `RecordsWorkspaceTest`, `EmergencyWorkspaceTest`, `DoctorWorkspaceTest`,
  `RoleDashboardTest`, `ModuleAccessTest`, `Journey\JourneyActionLinkTest` —
  69 passed. No pre-existing test needed changes for this workspace.

## 8. Integrity, security & audit notes

- All stock changes continue to flow through the existing services and the
  `product_stock_movements` ledger — the workspace registers **zero** new write
  endpoints of its own; approve/issue/acknowledge/receive/post remain the
  existing audited, permission-gated actions.
- The department-type guard sits on top of the existing `can:` middleware and
  module gates; a broad `stock.view`-style permission alone does not open the
  workspace.
- Valuation and supplier financial pages keep their existing dedicated
  permissions (`reports.inventory_valuation.view`, `store.purchase.view`).
- Pharmacy dispensing and nursing administration are not mounted here — the
  workspace is inventory-only.

## 9. Remaining limitations

- Stock counts, reconciliation, quarantine, recall, damage and disposal have no
  backing models/services yet; when built, mount them under `/stores/*` with
  this same adapter pattern (routes reserved in spec: `stores.stock_counts.*`,
  `stores.quarantine.*`, etc.).
- Requisitions are a single central-store pool; per-stores-department routing
  would need a schema addition (source location on the requisition) first.
