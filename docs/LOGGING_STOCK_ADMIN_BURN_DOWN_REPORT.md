# Logging Burn-Down — Stock / Procurement / Admin & System

The 8th and final clinical-to-operational module in the audit-trail repair. Where
the earlier seven burn-downs made **patient-related** actions surface on the
patient timeline, this one covers **facility-level** actions (warehouse,
procurement, supplier accounts, system administration). The hard constraint here
is the inverse of the clinical modules: **these events must appear on the global
log but must NEVER carry patient/visit context or pollute a patient timeline.**

## What was wired

Each event is a **distinct lifecycle marker** dual-written at the existing service
funnel, wrapped in `try/catch` so logging can never break a stock/admin action.
None of these attach `patient_id`/`visit_id` (verified by test).

| Service (funnel) | Events | Module |
|---|---|---|
| `StockTransferService` (create/approve/complete/cancel) | `STOCK_TRANSFER_REQUESTED` · `STOCK_TRANSFER_APPROVED` · `STOCK_TRANSFER_RECEIVED` · `STOCK_TRANSFER_CANCELLED` | `STOCK` |
| `StockRequisitionService` (create/approve/issue/acknowledge/cancel) | `STOCK_REQUISITION_CREATED` · `STOCK_REQUISITION_APPROVED` · `STOCK_REQUISITION_ISSUED` · `STOCK_REQUISITION_RECEIVED` · `STOCK_REQUISITION_CANCELLED` | `STOCK` |
| `ProcurementService` (create/submit/approve/receiveItems/cancel) | `PURCHASE_ORDER_CREATED` · `PURCHASE_ORDER_SUBMITTED` · `PURCHASE_ORDER_APPROVED` · `PURCHASE_ORDER_RECEIVED` · `PURCHASE_ORDER_CANCELLED` | `PURCHASE_ORDERS` |
| `SupplierLedgerService` (recordManualEntry) | `SUPPLIER_PAYMENT_RECORDED` · `SUPPLIER_LEDGER_ADJUSTED` | `SUPPLIER_LEDGER` |
| `ModuleService` (enable/disable) | `MODULE_ENABLED` · `MODULE_DISABLED` (severity **WARNING**) | `SETTINGS` |

Cancellations and module toggles are logged at **WARNING** severity (operationally
significant / security-relevant); other lifecycle events default to INFO, supplier
manual entries to NOTICE.

Context keys persisted (added to `ActivityLogService::buildProperties`):
`stock_transfer_id`, `stock_requisition_id`, `purchase_order_id`, `supplier_id`,
`product_id`, `stock_location_id`, `stock_movement_id`, `from_stock_location_id`,
`to_stock_location_id`, `quantity`, `source_type`, `source_id`, `asset_id`,
`target_user_id`, `role_id`, `module_id`, `setting_key`. Each log also carries a
small `metadata` bag (transfer/requisition/PO number, from/to locations, received
qty + value, GRN number, entry type + amount + balance).

## Anti-duplication discipline (no double-logging)

- **Raw movement ledger is NOT re-logged.** `ProductStockMovementService::createMovement`
  writes only `stock_movements` / `stock_balances` (no activity log, no `patient_id`).
  Requisition `issue`/`acknowledge` and PO `receiveItems` emit those movements; we
  log only the **higher-level lifecycle event**, never the individual movement, so
  there is no ledger duplication.
- **Goods-received supplier ledger entry is NOT double-logged.** `ProcurementService::receiveItems`
  auto-calls `SupplierLedgerService::recordEntry` (a `GOODS_RECEIVED` credit) — that
  is already represented by `PURCHASE_ORDER_RECEIVED`, so the auto path stays silent.
  Only **manual** ledger entries (payment / credit note / debit note via
  `recordManualEntry`) log a `SUPPLIER_LEDGER` event.
- **User CRUD stays with `UserObserver`** (`USER_CREATED` / `USER_FIELD_CHANGED` /
  `USER_DELETED`) — not re-wired here.
- **Patient-related consumable usage stays with `ConsumableUsageService`** (logged
  in the earlier stock-consumable burn-down) — generic store/lab/pharmacy stock
  here is a separate, patient-free concern.

## No patient-timeline pollution (the key constraint)

`ActivityContextResolver` only derives patient/visit from a subject's
`patient_id` / `visit_id` / `invoice_id` attributes. `StockTransfer`,
`StockRequisition`, `PurchaseOrder`, `SupplierLedgerEntry`, and `Module` have none
of those columns, so the resolver returns `[null, null]` and these logs are
invisible to `getPatientTimeline()`. A dedicated test asserts **zero** rows under
`STOCK` / `PURCHASE_ORDERS` / `SUPPLIER_LEDGER` / `SETTINGS` carry a `patient_id`
or `visit_id`.

## Sensitive-value masking

No credentials are logged. The module-toggle and any settings logs pass only
boolean/id state. `ActivityLogService::sanitise()` masks `password`, `token`,
`api_key`, `secret`, … to `***MASKED***` in old/new/metadata — covered by a test
that pushes a fake `password`/`api_key` through a `SETTING_UPDATED` log and asserts
they are masked while non-sensitive fields survive.

## Admin/System scope intentionally deferred

Wired the **highest-signal, lowest-risk security event** (module enable/disable).
The broader admin surface is **documented, not force-wired**, to avoid touching
business logic across dozens of controllers in one pass:

- Role / permission grant/revoke (Spatie) — security-relevant, belongs at the
  role-management service/controller with old/new permission sets.
- Settings updates (`config/settings`) — `setting_key` + old/new value (masked).
- Catalogue CRUD (services, drugs, lab tests, procedures, products, assets).
- Stock **adjustments** and **purchase returns** (raw `StockMovement` adjustments).
- Notifications policy/failure events.

These remain on the `logs:audit` Admin list (see below) as the honest backlog.

## Tests

`tests/Feature/StockAdminLogTest.php` — **7 passing** (37 assertions):
transfer lifecycle, PO lifecycle (under `PURCHASE_ORDERS`), module disable/enable
(WARNING, with old/new `is_enabled`), core-module disable is a no-op (logs nothing),
manual supplier payment, **no patient/visit context on any generic log**, sensitive
masking. Full logging suite (`--filter "Log|ActivityLog|Audit"`): **86 passing**.

## `logs:audit` before/after

Unchanged at **73** controllers flagged (Admin 64 · Billing 4 · Doctor 2 · Theatre 2
· Lab 1). This is expected and correct: the heuristic inspects **controller**
source for an inline logging marker, but this burn-down adds logging at the
**service funnel** (`StockTransferController` → `StockTransferService`, etc.), which
the command cannot see. Per the standing constraint, the baseline was **not**
edited to hide these — they remain documented service-funnel false positives, and
`logs:audit` stays **advisory (non-blocking)**.

## Files changed

- `app/Services/StockTransferService.php` — `logTransfer()` helper + 4 calls.
- `app/Services/StockRequisitionService.php` — `logRequisition()` helper + 5 calls.
- `app/Services/ProcurementService.php` — `logPo()` helper + 5 calls.
- `app/Services/SupplierLedgerService.php` — `logManualEntry()` (manual entries only).
- `app/Services/ModuleService.php` — `logModuleToggle()` + capture pre-state in `enable()`.
- `app/Services/ActivityLogService.php` — stock/admin context keys in `buildProperties`.
- `tests/Feature/StockAdminLogTest.php` — new (7 tests).

## Follow-ups (documented backlog)

Roles/permissions, settings values, catalogue CRUD, stock adjustments + purchase
returns, asset register, notifications. Promote `logs:audit` to blocking only after
these land **and** the command gains a baseline/allowlist (mirroring `ui:audit`).
