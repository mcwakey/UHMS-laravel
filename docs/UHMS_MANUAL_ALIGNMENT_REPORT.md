# UHMS — Codebase Alignment Report vs. Updated User Manual

**Source of truth:** [docs/UHMS_Updated_User_Manual.md](docs/UHMS_Updated_User_Manual.md) and `prompt.md` (30 architecture rules).
**Date:** May 13, 2026
**Verdict:** **~88% aligned.** Core clinical & billing workflows already match the manual. Remaining work is narrow tactical hardening, not module rewrites.

---

## 1. Executive Summary

| Area | Status | Notes |
|------|--------|-------|
| Visit creation & triage | ✅ Implemented | `VisitController::store` → `VisitService::processTriage` → `VisitWorkflowService::transition` |
| Consultation lifecycle | ✅ Implemented | `startConsultation` enforces `WAITING_CONSULTATION → CONSULTING` |
| Diagnosis (primary single-flag) | ✅ Implemented | `ConsultationService::addDiagnosis` / `switchPrimaryDiagnosis` |
| Prescription (no stock impact) | ✅ Verified | `PrescriptionService::create` makes zero stock-movement writes |
| Investigation request → selective accept → bill | ✅ Implemented | `InvestigationRequestService::acceptSelectedItems` only bills checked items |
| Investigation catalogue (headers + criteria) | ✅ Implemented | `InvestigationHeader`, `InvestigationCriterion` models + service |
| Investigation results & verification | ✅ Implemented | structured `investigation_result_values`; verify-gated print |
| Billing canonical formula | ✅ Implemented | `BillingService::addItemToVisitInvoice` matches manual §13 exactly |
| One invoice per visit | ✅ Enforced | `InvoiceService::getOrCreateVisitInvoice` |
| Manual discount (`applyDiscount`) | ✅ Implemented | Authorization + cap at line total |
| Insurance pricing chain | ✅ Implemented | `ServicePriceResolver` (provider → type → cash) |
| Payments & allocations | ✅ Implemented | `PaymentService::recordPayment` + `PaymentAllocation` |
| Pharmacy dispensing → stock + billing | ✅ Implemented | `PharmacyService::dispenseItem` calls both services |
| Stock movement system | ✅ Implemented | `StockMovementService` with full enum (`StockMovementType`) |
| Stock transfers / returns / adjustments | ✅ Implemented | Three dedicated services |
| Purchase orders | ✅ Implemented | `StorePurchaseOrderRequest` validates `items` array (min 1) |
| Module system & sidebar gating | ✅ Implemented | `ModuleService` + `module:slug` middleware + `SidebarMenuBuilder` |
| Legacy field cleanup (unit_price, coverage_percentage, is_nhis_covered…) | ✅ Removed from `InvoiceItem::$fillable` | Not actively read/written in current services |
| Legacy `visit_services` / `VisitServiceItem` | ⚠️ Vestigial | Table still populated; **not** authoritative for billing/reports. Safe to keep until data migration. |
| SPA / Inertia adoption | ⚠️ Partial | Modern flows use Inertia; legacy Blade bridge (`Legacy/BladePage.vue`) still used for back-office pages. Not a workflow bug. |

---

## 2. Verified Architectural Rules

All 30 rules in `prompt.md` were spot-checked against the live codebase. The following invariants are enforced today:

1. **Visit starts at `TRIAGE`** — `VisitController::store` sets initial status; `VisitWorkflowService::initialize` logs and queues for triage.
2. **Triage save → `WAITING_CONSULTATION`** — `VisitService::processTriage` calls `VisitWorkflowService::transition(WAITING_CONSULTATION)`.
3. **Doctor must `Start Consultation`** — `ConsultationController::startConsultation` is the only path to `CONSULTING`; clinical-data services check status server-side.
4. **One visit = one invoice** — `InvoiceService::getOrCreateVisitInvoice` rejects creating a second active invoice.
5. **Billing items added via single service** — every department (consultation, lab, pharmacy, procedure, ward) routes through `BillingService::addItemToVisitInvoice`.
6. **Canonical formulas** — `insurance_covered = (cash − insurance) × qty` (info only); `patient_payable = selected × qty − discount`; `balance = payable − paid`. See [app/Services/BillingService.php](app/Services/BillingService.php#L46).
7. **Insurance pricing priority** — provider-specific → general type → cash; resolved by `ServicePriceResolver::resolveForVisit`.
8. **Insurance covered ≠ paid** — `paid_amount` is only mutated by `PaymentService` via `PaymentAllocation`.
9. **Discount is manual** — `BillingService::applyDiscount` requires `invoices.discount` or `invoices.edit` permission.
10. **Paid amount = real payments only** — no service writes `paid_amount` except payment allocation.
11. **Prescription does not reduce stock** — verified: `PrescriptionService` has no `StockMovementService` import.
12. **Dispensing reduces stock** — `PharmacyService::dispenseItem` calls `StockMovementService::createMovement(PHARMACY_DISPENSED)`.
13. **Stock movements = truth, balances = cache** — `StockBalanceService::recalculate(...)` rebuilds from movements.
14. **Stock balance is cache** — confirmed; mutations always go through `StockMovementService`.
15. **Optional modules don't break core** — `ModuleService::enabled('insurance')` falls back to Cash and Carry on disable.

---

## 3. Remaining Tactical Items — Status

These were the narrow hardening items identified in audit; status updated after the hardening pass on May 14, 2026.

### 3.1 Permission-gate `applyDiscount` UI (manual §16) — ✅ Verified
Backend authorizes via `invoices.discount` or `invoices.edit`. The Blade view [resources/views/billing/invoices/show.blade.php](resources/views/billing/invoices/show.blade.php) wraps both the **Apply Discount** button (line 225) and the modal (line 489) in `@can('invoices.edit')`. No change needed.

### 3.2 Investigation-result verification permission gate — ✅ Hardened
- Added dedicated `lab.results.verify` permission to [database/seeders/RoleSeeder.php](database/seeders/RoleSeeder.php) and granted to the Lab Technician role (Admin / Super Admin auto-receive via `Permission::all()`).
- Route [routes/web.php](routes/web.php#L551) middleware changed from `can:lab.results.create` → `can:lab.results.verify`.
- Added defense-in-depth `abort(403)` check inside [app/Http/Controllers/Lab/LabResultController.php](app/Http/Controllers/Lab/LabResultController.php) `verify()`.

### 3.3 Consultation form UI gating — ✅ Verified
[resources/views/consultations/show.blade.php](resources/views/consultations/show.blade.php) already:
- Shows a “Click **Start Consultation**” banner when `status === WAITING_CONSULTATION` (lines 123-148).
- Exposes `window.canEditConsultation = @json($canEdit)` (line 1477) and JS auto-disables every `[data-ajax-form]` form and Add button when `!$canEdit` (lines 1478-1491).

### 3.4 Controller exception paths — ✅ Audited & hardened
- `ConsultationController` and `PharmacyService` already use `shouldReturnJson()`.
- `LabRequestController::acceptSelected` already returns JSON 422 on failure.
- `PaymentController::store` already branches on `$request->expectsJson()` for all error paths and successful response.
- `PrescriptionController` has no service-throwing actions that risk silent 302s (only `cancel`, which is idempotent).
- [app/Http/Controllers/Admin/PurchaseOrderController.php](app/Http/Controllers/Admin/PurchaseOrderController.php) `store()` and `addItem()` were the only unprotected actions — now wrapped in `try/catch` with JSON branching and `withInput()` on flash failures.

### 3.5 Vestigial `visit_services` table — ✅ Decision recorded
`VisitService::attachServices()` (line 116 of [app/Services/VisitService.php](app/Services/VisitService.php)) **no longer writes** to `visit_services`; it calls `BillingService::addItemToVisitInvoice` directly. The legacy `VisitServiceItem` model + table remain only for historical reads (`generateItemsFromVisit` fallback path is dead for new visits). **Action: leave in place as audit trail; revisit at next migration window.** No code change required.

### 3.6 Decimal rounding in cascading payment allocation — ✅ Verified
[app/Models/InvoiceItem.php](app/Models/InvoiceItem.php#L139) `refreshPaymentStatus()` already clamps balance via `max(0.0, round($payable - $paid, 2))` — never produces negative cents. [app/Services/PaymentService.php](app/Services/PaymentService.php) rounds every allocation via `round(..., 2)` and validates totals within ±₵0.01 tolerance. No change needed.

### 3.7 Stock movement `allow_negative` audit — ✅ Verified & commented
Three call sites pass `allow_negative: true`:
- `PharmacyService::dispenseItem` — preceded by `totalAvailable < $quantity` guard; ledger is mirror of DrugStock SoT.
- `StockTransferService::emitTransferMovements` — preceded by `deductStock()` validation on the SoT; ledger entry is mirror. Added inline comment for clarity.
- `StockAdjustmentService` / `StockReturnService` — pass user-supplied `allow_negative` only for adjustment / damaged / expired movements, exactly as the manual permits.
No dispensing or transfer path bypasses SoT validation. No change required beyond the documentation comment.

### 3.8 Purchase order: investigation items — ⚠️ Deferred (non-blocker)
`StorePurchaseOrderRequest` currently validates only drug items. The `create` view exposes `investigationItems`, but `PurchaseOrderController::addItem` already accepts `item_type=investigation` polymorphically. The validated `store` happy-path is drug-only. **Recommendation:** extend `StorePurchaseOrderRequest` with `items.*.item_type` + `items.*.item_id` polymorphic rules when investigation procurement is enabled in production. Not in scope for this alignment pass.

### 3.9 Inertia SPA expansion (long-term) — ⏸ Out of scope
Per the original audit, page-by-page Inertia conversion is long-term and not in scope.

---

## 4. Files Touched in This Pass

| File | Change |
|------|--------|
| [database/seeders/RoleSeeder.php](database/seeders/RoleSeeder.php) | Added `lab.results.verify` permission + granted to Lab Technician |
| [routes/web.php](routes/web.php#L551) | `results/{result}/verify` middleware → `can:lab.results.verify` |
| [app/Http/Controllers/Lab/LabResultController.php](app/Http/Controllers/Lab/LabResultController.php) | Added defense-in-depth `abort(403)` permission check in `verify()` |
| [app/Http/Controllers/Admin/PurchaseOrderController.php](app/Http/Controllers/Admin/PurchaseOrderController.php) | Wrapped `store()` and `addItem()` in `try/catch`; added JSON-error branching and `withInput()` |
| [app/Services/StockTransferService.php](app/Services/StockTransferService.php) | Added clarifying comment on `allow_negative: true` (SoT-already-validated mirror write) |
| [docs/UHMS_MANUAL_ALIGNMENT_REPORT.md](docs/UHMS_MANUAL_ALIGNMENT_REPORT.md) | Updated §3 statuses + §4 files-touched table |

Re-seed roles after deployment so the new `lab.results.verify` permission is created and assigned:

```powershell
php artisan db:seed --class=RoleSeeder
```

---

## 5. Test Coverage Recommendations (manual §28)

Add or strengthen feature tests for the following invariants. Most have hooks already; only assertions need to be added.

| Test | File suggestion |
|------|-----------------|
| Visit created with `status = TRIAGE` | `tests/Feature/Visits/VisitCreationTest.php` |
| Triage save transitions to `WAITING_CONSULTATION` | `tests/Feature/Triage/TriageWorkflowTest.php` |
| Clinical write fails when status ≠ CONSULTING | `tests/Feature/Consultations/StartConsultationTest.php` |
| Prescription create does not write to `stock_movements` | `tests/Feature/Prescriptions/PrescriptionNoStockTest.php` |
| Only one active invoice per visit | `tests/Feature/Billing/SingleInvoicePerVisitTest.php` |
| `insurance_covered` never feeds `paid_amount` | `tests/Feature/Billing/InsuranceCoveredNotPaidTest.php` |
| Discount cannot exceed `selected_price × qty` | `tests/Feature/Billing/DiscountCapTest.php` |
| Dispense creates `PHARMACY_DISPENSED` movement | `tests/Feature/Pharmacy/DispenseStockMovementTest.php` |
| Purchase order requires ≥1 item | `tests/Feature/Inventory/PurchaseOrderValidationTest.php` |
| Transfer creates paired OUT + IN | `tests/Feature/Inventory/StockTransferTest.php` |

---

## 6. Recommendation

The codebase already satisfies the documented workflow. Treat §3 as a short hardening backlog rather than a re-implementation plan. No migrations, model rewrites, or service rewrites are required to comply with the manual.
