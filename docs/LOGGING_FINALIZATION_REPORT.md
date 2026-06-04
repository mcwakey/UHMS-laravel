# Logging Finalization Pass — `logs:audit` made funnel-aware

The eight logging burn-downs are complete, but `logs:audit` kept reporting **73**
flagged controllers. This pass makes the audit *understand the architecture we
actually built* (service-funnel + observer logging), classifies every finding,
and lays a safe, staged path to a blocking CI gate — **without** adding logging
blindly, changing business logic, or hiding real gaps.

## 1. Count before finalization

**73** controllers flagged (Admin 64 · Billing 4 · Doctor 2 · Theatre 2 · Lab 1).
A single flat number with no way to tell a real gap from a false positive.

## 2. Why the count stayed high despite completed burn-downs

The old command was a one-line heuristic: *"does the controller file itself
contain a logging marker?"*. But UHMS logs at the **service funnel**, not in
controllers — e.g. `StockTransferController` does nothing but call
`StockTransferService`, which logs. The controller has no marker, so it was
flagged even though the action is fully audited. Almost all 73 were this shape.

## 3. What changed — transitive funnel awareness

`logs:audit` now builds the **transitive closure of "logging services"**: a
service counts as logging if it calls `ActivityLogService` directly **or**
delegates to a service that does. Example it now resolves automatically:

```
EmergencyMedicationController
  └─ EmergencyMedicationService        (no direct log)
       └─ MedicationAdministrationLogService   (logs) ✅  → COVERED
```

**65** logging services are detected (direct + transitive) from
`app/Services` + `app/Observers`, seeded by `config/logging_audit.php`. A
controller is then covered if it reaches any of them, or touches a model with a
logging observer (`Invoice`/`Payment`/`User`).

## 4. Classification + severity

Every flagged action now carries a **classification** and an independent
**severity**:

| Classification | Meaning |
|---|---|
| `SERVICE_FUNNEL_COVERED` | reaches a logging service/observer |
| `NEEDS_REVIEW` | delegates to a service **not verified** to log — confirm or wire |
| `KNOWN_BACKLOG` | a real gap consciously deferred (documented in config) |
| `INTENTIONALLY_SKIPPED` | non-auditable / noisy action excluded on purpose |
| `MISSING_LOG` | no funnel anywhere — a genuine gap |

Severity (`CRITICAL · HIGH · MEDIUM · LOW · INFO`) is derived from the action's
risk (e.g. `destroy/merge/reverse/refund/override` → CRITICAL), separate from
classification.

## 5. Result — the 73, classified

| Classification | Count |
|---|--:|
| SERVICE_FUNNEL_COVERED | 42 |
| KNOWN_BACKLOG | 22 |
| **NEEDS_REVIEW** | **7** |
| INTENTIONALLY_SKIPPED | 1 |
| **MISSING_LOG (real gap)** | **1** |

**Real missing logs: 1.** Everything else is either covered, deferred-and-tracked,
or flagged for a quick human confirmation.

### Real remaining gap (MISSING_LOG)

- `Admin/RoleController` (store/update/destroy) — **CRITICAL**. Role/permission
  changes are **not audited** and reach no service that logs. This is the one
  genuine high-risk hole the pass surfaces. (Recommended next: a `SecurityAudit`
  funnel or observer on role/permission pivot changes → `LogModule::ROLES` /
  `PERMISSIONS`, severity SECURITY.)

### Needs review (7) — delegate to an unverified service

These reach a service we have **not** confirmed logs; verify, then either move to
covered or wire a funnel. Kept **out of the baseline** at HIGH/CRITICAL so they
stay loud:

- `Admin/FinancialEntryController` (store/approve/destroy) — CRITICAL → `AccountingService`
- `Admin/CashierShiftController` (verify) — HIGH → `AccountingService`
- `Admin/InsuranceVerificationController` (verify) — HIGH → `InsuranceVerificationService`
- `Admin/LeaveController` (store/approve/reject) — HIGH → `HRService`
- `Admin/PayrollController` (approve) — HIGH → `PayrollService`
- `Admin/PurchaseReturnController` (store/approve/cancel) — HIGH → `PurchaseReturnService`
- `Theatre/TheatreRoomController` (store/update) — MEDIUM → `TheatreRoomService`

### Known backlog (22)

Catalogue/reference-data CRUD (departments, designations, ICD, insurance
providers/tiers, specialties, products, services, drugs via catalogue, stock
locations, suppliers, wards, analyzers, complaint catalogue, investigation items,
account categories, sponsors), patient demographic sub-records, HR reference
(attendance/employee), notifications (broadcast/delete), generic stock
adjustments (`ProductStockController`), emergency ancillary records (vitals/note/
investigation/procedure/identity) not yet on a funnel, and front-desk workflow
(appointments/queue/triage/vitals) + blood-bank intake (donation/donor). All
documented in `config/logging_audit.php → backlog_controllers`.

### Intentionally skipped (1)

- `Admin/NotificationPreferenceController@update` — per-user UI preference, not a
  security/clinical event.

## 6. Service funnels recognized

Seeded in `config/logging_audit.php → covered_service_funnels` (transitive
detection extends this automatically): MedicalRecordEntryLogService, LabService,
InvestigationRequestService, MedicationAdministration(Log)Service, PharmacyService,
PharmacyBillingSelectionService, ProcedureWorkflowService, BillingService,
VisitBillingOverrideService, CreditNoteService, RefundService, ClaimService,
ClaimStatusService, EmergencyTriageService, EmergencyDispositionService,
EmergencyCaseService, EmergencyConsumableService, AdmissionService,
StockTransferService, StockRequisitionService, ProcurementService,
SupplierLedgerService, ModuleService, ConsumableUsageService, StockMovementService,
ServiceRenderingService, PatientMergeService, BloodRequest/Issue/CrossmatchService.
Observers: InvoiceObserver, PaymentObserver, UserObserver.

## 7. Baseline / allowlist

`storage/app/logs-audit-baseline.json` (committed; whitelisted in
`storage/app/.gitignore`). Written via `php artisan logs:audit --write-baseline`
or `composer logs:audit:baseline`. It accepts the current covered/backlog/skipped/
medium findings (**66**) but **refuses to baseline HIGH/CRITICAL real gaps**
(MISSING_LOG + NEEDS_REVIEW) — the **7** financial/security/HR items above stay
visible no matter what. This is the guard against "baselining to silence."

## 8. CI status — still NON-BLOCKING

Unchanged for now. `.github/workflows/ui-audit.yml` runs
`php artisan logs:audit --json || true` (advisory) and uploads the report +
baseline. No `--fail` on logging. The UI gate remains the only blocking step.

New command capabilities (ready, not yet enforced):

```bash
php artisan logs:audit                                   # advisory, classified
php artisan logs:audit --fail --only-real-gaps           # fail on MISSING_LOG only
php artisan logs:audit --fail --min-severity=CRITICAL    # severity threshold
php artisan logs:audit --fail --strict                   # fail on anything not baselined
php artisan logs:audit --write-baseline                  # snapshot accepted findings
composer logs:audit:real-gaps                            # = --fail --only-real-gaps --min-severity=CRITICAL
```

## 9. When to make `logs:audit` blocking — staged promotion

1. **Now — advisory.** Report + artifact only. (current)
2. **Stage 1 — `--fail --only-real-gaps --min-severity=CRITICAL`.** Blocks only on
   CRITICAL `MISSING_LOG`. Enable after the single real gap (RoleController) is
   wired. Effectively a regression guard for brand-new un-funnelled CRITICAL
   actions.
3. **Stage 2 — `--fail --only-real-gaps --min-severity=HIGH`.** After the 7
   NEEDS_REVIEW items are confirmed/wired.
4. **Stage 3 — `--fail --strict`.** After the backlog is burned down and the
   baseline reflects a clean state; then any new un-baselined finding fails.

Mirror the UI gate's placement in the workflow when promoting.

## 10. Files modified

- `config/logging_audit.php` — **new**: funnels, observers, false-positives,
  backlog, skipped, severity tiers, baseline path.
- `app/Console/Commands/LogsAuditCommand.php` — rewritten: transitive funnel
  closure, classification, severity, baseline load/write, `--only-real-gaps`,
  `--min-severity`, `--strict`, `--write-baseline`, `--scan-path`; legacy keys and
  default `--fail` behaviour preserved.
- `storage/app/logs-audit-baseline.json` — **new** (committed baseline).
- `storage/app/.gitignore` — whitelist the logs baseline.
- `tests/Feature/LogsAuditCommandTest.php` — **new** (11 tests).
- `composer.json` — `logs:audit:real-gaps`, `logs:audit:baseline` scripts.
- `.github/workflows/ui-audit.yml` — upload baseline; documented (still non-blocking)
  promotion note.
- `docs/LOGGING_REMAINING_TODOS.md` — separated real gaps / needs-review / backlog /
  false-positives / governance.

## 11. Guarantees honored

No new logging calls were added to satisfy the tool. No business logic changed.
`logs:audit` is **not** blocking. No real high-risk gap is hidden — the baseline
writer actively refuses HIGH/CRITICAL real gaps. Service-funnel logging is now
recognized rather than mis-reported as missing.
