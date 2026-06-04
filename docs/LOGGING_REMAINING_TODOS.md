# Logging / Audit Trail — Remaining TODOs

The patient-timeline root cause is fixed and context now auto-attaches. What
remains is **breadth of coverage** — making sure every important action actually
calls `ActivityLogService` (or a service/observer that does).

## ✅ Done: stock-consumable usage → patient timeline

Patient-related consumable usage now writes a `STOCK / CONSUMABLE_USED` activity
log with explicit context (patient_id, visit_id, emergency_case_id,
investigation_request_id, procedure_request_id, product_id, stock_location_id,
quantity, source_type/source_id, invoice_item_id) and shows on the patient
profile. Wired at both write paths — `ConsumableUsageService::recordUsageForSource`
(investigation/procedure/ward) and `EmergencyConsumableService::useConsumable`
(emergency) — via `ConsumableUsage::toActivityContext()`. `buildProperties` now
persists these context keys. Generic stock movements (no patient) correctly do
**not** attach. Tests: `tests/Feature/ConsumableUsageAuditLogTest.php` (5).
Remaining stock-context follow-ups: MAR drug-administration stock-out (logged in
the MAR module — confirm patient/visit context), and any future non-central
consumable path.

## ✅ Done: consultation clinical entries → patient timeline

`MedicalRecordEntryLogService` now mirrors every clinical entry change
(complaint / HOPC / examination / diagnosis / treatment / prescription /
investigation — create/update/delete/correct) to the central activity log with
full context + old/new values, so they appear on the patient profile timeline.
Session lifecycle (`SESSION_STARTED/RESUMED/COMPLETED`) and `PATTERN_APPLIED` are
logged too. Tests: `ConsultationClinicalLogTest` (7) + a session assertion. See
`docs/LOGGING_CONSULTATION_BURN_DOWN_REPORT.md`. Follow-ups: individual
pattern-created non-complaint records, prescription delete, clinical tasks/
follow-up, note/summary, session lock/reopen/contributor.

## ✅ Done: investigation (lab) department lifecycle → patient timeline

`LabService` + `InvestigationRequestService` now write `INVESTIGATION` activity
logs alongside the existing pathway events: `INVESTIGATION_REQUESTED` (direct/
emergency only — consultation-created are skipped to avoid duplication),
`INVESTIGATION_ACCEPTED` (whole request + selected items), `INVESTIGATION_CANCELLED`,
`RESULT_ENTERED/UPDATED`, `RESULT_VERIFIED`, `RESULT_PRINTED`. Context via
`LabRequest::toActivityContext()` (incl. emergency_case_id). Tests:
`InvestigationLogTest` (4). See `docs/LOGGING_INVESTIGATIONS_BURN_DOWN_REPORT.md`.
Follow-ups: sample collect/receive/reject, result reject/correction-request,
verification reversal, radiology path (no model support yet).

## ✅ Done: MAR / medication administration → patient timeline

`MedicationAdministrationLogService` now dual-writes to the central activity log
(module `MAR`): `DOSE_ADMINISTERED/HELD/MISSED/REFUSED/SKIPPED/CANCELLED/CORRECTED`
(+ PRN), `MEDICATION_ORDER_CREATED/HELD/STOPPED/UPDATED`,
`MEDICATION_SCHEDULE_GENERATED`, and a distinct `ADVERSE_REACTION_RECORDED`.
Context incl. admission_id/emergency_case_id + stock_location_id/stock_movement_id
(traceable to the deduction, no movement-log duplication). Emergency order
creation now routes through the funnel too. Tests: 4 in
`MedicationAdministrationWorkflowTest`. See `docs/LOGGING_MAR_BURN_DOWN_REPORT.md`.
Follow-ups: order-resume action, board bulk-action paths.

## ✅ Done: pharmacy billing + dispensing → patient timeline

`PHARMACY / PRESCRIPTION_ITEMS_BILLED` (per billed item) and `PHARMACY /
DRUG_DISPENSED` / `PARTIAL_DISPENSE_COMPLETED` now log distinctly — billing vs
dispensing vs MAR administration are three separate events. The dispense log
references the stock-movement ids (no ledger duplication); the MAR
`DISPENSED_QUANTITY_RECORDED` sync is suppressed from the timeline so pharmacy
owns the dispense event. Context via `PharmacyBillingSelection`/`DispensingRecord`
`toActivityContext()`. Tests: `PharmacyWorkflowTest` (+1). See
`docs/LOGGING_PHARMACY_BURN_DOWN_REPORT.md`. Follow-ups: pharmacy review/reject,
distinct billing-quantity-reduced, dispense cancel/correct/return (not yet
implemented), out-of-stock event.

## ✅ Done: procedures / theatre lifecycle → patient timeline

`ProcedureWorkflowService::logStatusChange` (the universal status funnel for
accept/reject/bill/cancel/complete + clinical notes + scheduling) now dual-writes
to the activity log: `PROCEDURE_*` events (module PROCEDURE) and theatre-phase
`THEATRE_CASE_SCHEDULED/RESCHEDULED`, `PREOP_CHECKLIST_UPDATED`, `ANAESTHESIA_NOTE_ADDED`,
`PROCEDURE_STARTED`, `OPERATIVE_NOTE_ADDED`, `RECOVERY_NOTE_ADDED` (module THEATRE).
Initial REQUESTED status isn't a transition → consultation request not duplicated;
theatre consumables stay with `ConsumableUsageService`. Removed 2 redundant explicit
logs. **Also fixed a latent bug** (`requestedBy` → `requestingDoctor`) that made
cancel/complete throw + roll back. Tests: `ProcedureTheatreLogTest` (3). See
`docs/LOGGING_PROCEDURES_THEATRE_BURN_DOWN_REPORT.md`. Follow-ups: theatre team
assignment, dedicated room-assign event, report printing, direct/emergency request
initial log.

## ✅ Done: billing / claims → patient timeline

Billing/payment were already covered (`InvoiceObserver` → `INVOICE_CREATED/STATUS_CHANGED`,
`PaymentObserver` → `PAYMENT_RECORDED/REFUNDED`, discount via `BillingService`,
deferred-settlement/overrides via `VisitBillingOverrideService`) — **reused, not
duplicated**. The gap was **Claims**: `ClaimStatusService::transition` now
dual-writes the `CLAIMS` lifecycle (submit/review/approve/reject/paid/appeal/cancel)
and `ClaimService` logs `CLAIM_PREPARED`, `CLAIM_ITEM_ADDED/REMOVED`, `CCC_CODE_UPDATED`.
Generic invoice-item logging deliberately skipped (source modules already log
billing). Tests: `ClaimsLogTest` (3). See `docs/LOGGING_BILLING_CLAIMS_BURN_DOWN_REPORT.md`.
Follow-ups: distinct payment-reversal event, `ClaimPayment` rows, claim-mirror
item edits, invoice/receipt/claim print/export.

## ✅ Done: emergency / admission lifecycle → patient timeline

Reuse-first: `EMERGENCY / CASE_CREATED` (EmergencyCaseService) and `ADMISSION /
ADMITTED|DISCHARGED` (AdmissionService) were already logged — reused, not
duplicated. Gaps filled: `EmergencyTriageService` → `TRIAGE_RECORDED` /
`TRIAGE_OVERRIDDEN` (old/new + reason); `EmergencyDispositionService` →
`EMERGENCY_TRANSFERRED_TO_ADMISSION/THEATRE/OPD`, `EMERGENCY_REFERRED_OUT`,
`EMERGENCY_DEATH_RECORDED/DOA`, `EMERGENCY_LEFT_AGAINST_MEDICAL_ADVICE`,
`EMERGENCY_ABSCONDED`, `EMERGENCY_DISPOSITION_COMPLETED`. The emergency timeline was
deliberately NOT dual-written (it carries MAR/consumable/investigation events).
Emergency→admission logs cleanly as two distinct events. Tests:
`EmergencyCaseManagementTest` (+1). See `docs/LOGGING_EMERGENCY_ADMISSION_BURN_DOWN_REPORT.md`.
Follow-ups: bay/bed/ward assign-transfer-release, emergency vitals, contributor,
nursing notes, discharge summary, print.

## ✅ Done: stock / procurement / admin & system → global log (NOT patient timeline)

Facility-level lifecycle now logs distinctly **without** patient/visit context:
`StockTransferService` → `STOCK_TRANSFER_REQUESTED/APPROVED/RECEIVED/CANCELLED`;
`StockRequisitionService` → `STOCK_REQUISITION_CREATED/APPROVED/ISSUED/RECEIVED/CANCELLED`;
`ProcurementService` → `PURCHASE_ORDER_CREATED/SUBMITTED/APPROVED/RECEIVED/CANCELLED`
(module `PURCHASE_ORDERS`); `SupplierLedgerService::recordManualEntry` →
`SUPPLIER_PAYMENT_RECORDED` / `SUPPLIER_LEDGER_ADJUSTED`; `ModuleService` →
`MODULE_ENABLED` / `MODULE_DISABLED` (module `SETTINGS`, WARNING). Raw movement
ledger (`ProductStockMovementService`) and the auto goods-received supplier entry
are **not** re-logged (covered by the lifecycle events). A test asserts **zero**
stock/admin logs carry `patient_id`/`visit_id`. Sensitive values masked. Tests:
`StockAdminLogTest` (7). See `docs/LOGGING_STOCK_ADMIN_BURN_DOWN_REPORT.md`.
Remaining admin surface (documented, not force-wired): roles/permissions,
settings values, catalogue CRUD, stock adjustments + purchase returns, assets,
notifications.

## Burn-down order — all 8 modules done ✅

1. ~~Consultation~~ · 2. ~~Investigations~~ · 3. ~~MAR~~ · 4. ~~Pharmacy~~ ·
5. ~~Procedures / Theatre~~ · 6. ~~Billing / Claims~~ · 7. ~~Emergency / Admission~~ ·
8. ~~Stock / Procurement / Admin & System~~

### ✅ Done: non-blocking `logs:audit` CI guardrail
`.github/workflows/ui-audit.yml` (now “UI & Logging Audit”) runs
`php artisan logs:audit --json || true` (advisory, never fails the build) + uploads
`storage/reports/logs-audit-report.json`. Composer: `logs:audit`, `logs:audit:json`,
`logs:audit:fail`, `logs:audit:real-gaps`, `logs:audit:baseline`. See
`docs/LOGGING_AUDIT_CI_GUARDRAIL_REPORT.md`.

### ✅ Done: Logging Finalization Pass (funnel-aware audit)
`logs:audit` now builds the **transitive closure of logging services** and
classifies every finding (`SERVICE_FUNNEL_COVERED` / `NEEDS_REVIEW` /
`KNOWN_BACKLOG` / `INTENTIONALLY_SKIPPED` / `MISSING_LOG`) with a separate
severity. Config: `config/logging_audit.php`. Baseline:
`storage/app/logs-audit-baseline.json` (refuses to baseline HIGH/CRITICAL real
gaps). Of the 73: **42 covered · 22 backlog · 7 needs-review · 1 skipped · 1 real
missing**. Tests: `LogsAuditCommandTest` (11). See
`docs/LOGGING_FINALIZATION_REPORT.md`.

#### 🔴 Real gap (MISSING_LOG) — do first
- **`Admin/RoleController`** (store/update/destroy, CRITICAL/SECURITY) — role &
  permission changes are unaudited and reach no logging service. Wire a security
  funnel/observer → `LogModule::ROLES` / `PERMISSIONS`, severity SECURITY, with
  old/new permission sets (mask nothing sensitive — these are names, not secrets).

#### 🟠 Needs review (verify the service logs, else wire) — HIGH/CRITICAL kept out of baseline
- `Admin/FinancialEntryController` → `AccountingService` (financial — CRITICAL)
- `Admin/CashierShiftController` → `AccountingService` (HIGH)
- `Admin/InsuranceVerificationController` → `InsuranceVerificationService` (HIGH)
- `Admin/LeaveController` → `HRService` (HIGH)
- `Admin/PayrollController` → `PayrollService` (HIGH)
- `Admin/PurchaseReturnController` → `PurchaseReturnService` (HIGH — supplier ledger + stock)
- `Theatre/TheatreRoomController` → `TheatreRoomService` (MEDIUM)

#### 🟡 Backlog (deferred, documented in config) — 22
Catalogue/reference CRUD, HR reference, notifications, generic stock adjustments,
emergency ancillary records, front-desk workflow, blood-bank intake. Listed in
`config/logging_audit.php → backlog_controllers`.

#### ⚪ Audit false positives (now auto-recognized) — 42
Controllers that delegate to a logging service/observer (the burn-down funnels).
No longer reported as missing; classified `SERVICE_FUNNEL_COVERED`.

#### 🔧 Governance / future
- Promote `logs:audit` to blocking in stages (see finalization report §9): start
  with `--fail --only-real-gaps --min-severity=CRITICAL` once RoleController lands.
- Keep `config/logging_audit.php` current as funnels/controllers evolve; re-run
  `composer logs:audit:baseline` after a clean burn-down to tighten `--strict`.

### Remaining (lower priority — admin/system polish + the blocking gate)
8. **Stock / procurement core** — ✅ done (transfers, requisitions, POs, supplier
   manual entries, module toggle). **Still open:** roles/permissions grant/revoke,
   settings values, catalogue CRUD (services/drugs/lab tests/procedures/products),
   stock **adjustments** + **purchase returns**, asset register, notifications;
   plus the per-module follow-ups noted in each burn-down report (bed transfers,
   vitals, print/export events, payment reversal, claim mirror, pattern-created
   records, sample collection, etc.).
9. **Make `logs:audit` blocking** only after the remaining admin items land and the
   command gains a baseline/allowlist (like `ui:audit`) so service-funnel false
   positives (the 73, incl. 64 Admin) don't block builds.

Do **not** wire all 75 flagged controllers at once; one module, with a test that
the action appears on both the global log and (where patient-related) the patient
timeline.

## Actions still likely unlogged

`php artisan logs:audit` flags **75 controllers** with mutating actions and no
logging marker. Many delegate to services that log; triage the list and add
explicit logs where genuinely missing. Priority order:

1. **Clinical create/update/delete** — consultation entries (complaint/HOPC/exam/
   diagnosis/treatment/note), clinical tasks, contributors. Prefer service-level
   logs so reason/context are captured (not just observers).
2. **Investigations** — accept/reject, sample collected, result entered/updated/
   verified/rejected, result printed.
3. **Procedures / theatre** — accept/schedule/room+team assign/pre-op/anaesthesia/
   operative/recovery notes/complete/cancel.
4. **MAR** — schedule generated, dose administered/held/missed/refused/skipped,
   reaction, stop, correction (some already logged — confirm patient/visit context).
5. **Pharmacy** — review, bill-selection, quantity-reduce, dispense, partial,
   cancel/correct, return.
6. **Stock** — purchase orders, transfers, requisitions, adjustments, returns,
   consumable usage (attach patient_id/visit_id when used for a patient).
7. **Service rendering** — started/rendered/not-rendered/cancelled.
8. **Admin** — role/permission/module/setting changes, admin password resets.

## Context still to attach

- Stock consumable usage tied to a patient should pass `patient_id`/`visit_id` so
  it lands on the patient timeline (the resolver can't infer it from a stock model
  alone).
- Add more subjects to `ActivityContextResolver` as needed (e.g. blood units →
  via request/visit).

## Modules needing deeper observer coverage

Consider observers for `Patient`, `Visit`, `Admission`, `EmergencyCase` create/
update **in addition to** service logs (observers catch out-of-workflow edits).
Do not rely on observers alone for workflow actions (no reason/context).

## Audit command limitations

`logs:audit` is a heuristic: it inspects controller source only, so it
false-positives when logging happens in a delegated service and false-negatives
when a controller merely references the service without logging a given branch. It
does not (yet) verify patient/visit context is attached per action.

## Severity / filter caveat (MariaDB 10.1)

`getPatientTimeline()` filters by module/action/user/date via real columns.
**Severity** lives in `properties` (longText) and can't be filtered in SQL on
MariaDB 10.1 — filter in PHP, or promote `severity` to a column later.

## Future CI / logging gate

- Add `composer logs:audit` and a non-blocking CI step (mirror `ui:audit`).
- Once the 75-controller list is burned down, enable `logs:audit --fail` for new
  controllers.
- Add a tiny test per high-risk workflow asserting it writes an activity log with
  patient/visit context (a few exist; extend to consultation/MAR/lab/theatre/blood).

## Deploy checklist

1. `php artisan migrate` (adds `patient_id`/`visit_id` columns).
2. `php artisan logs:backfill-context` (one-time; recovers historical context).
3. `php artisan db:seed --class=RoleSeeder` (or sync) for the new `logs.*`
   permissions.
