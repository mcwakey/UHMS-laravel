# UHMS Phase 17 — Full Test Suite & System-Wide Regression Stabilisation Report

**Date:** 2026-06-14
**Branch:** beta-x
**Scope:** Run and stabilise the full automated test suite after the localisation/responsiveness work, without breaking the Phase 16 localisation lock. No new features.

---

## 1. Summary

Ran the full suite, classified every failure, and fixed **10 of 15** failures with safe, root-cause changes (test-infrastructure seeding, a real audit-logging gap, a permission-setup gap, two localisation-refactor test-expectation updates, and one Inertia-guard allowlist entry). The remaining **5 are domain/feature failures** in the MAR, visit-pathway, and lab modules — introduced by **non-localisation feature commits** (confirmed via `git log`), structural (not text) in nature, and out of safe reach without the owning feature context; they are documented precisely below.

**The localisation lock was preserved throughout:** active runtime candidates **0**, EN/FR parity pass, all 12 localisation tests pass, `view:cache` compiles, `route:list` OK, `git diff --check` clean.

---

## 2. Test commands run

```text
php artisan test                                  → baseline & final full-suite
php artisan test tests/Feature/Localization       → 12 passed (lock check, before & after)
php scripts/localisation-audit.php                → Active runtime candidates: 0
php artisan logs:audit --json                     → drove the audit-gap fixes
php artisan route:list / view:cache / view:clear  → OK / compiles
php -l (changed controllers & lang)               → clean
git diff --check                                  → clean
```

## 3. Results

| | Tests | Passed | Failed |
|---|---:|---:|---:|
| **Initial full suite** | 540 | 525 | **15** |
| **After fixes** | 540 | **535** | **5** |

(1898 assertions baseline.) The 5 remaining are pre-existing **domain/feature** failures unrelated to localisation — see §6.

---

## 4. Failure classification (15)

| # | Test | Category | Root cause |
|---|---|---|---|
| 1 | `UnifiedInventoryWorkflowTest` (×2) | **C** seeder | Inventory receiving/returns post to accounting; chart of accounts + `inventory_account_id` not seeded |
| 2 | `Stage2NeedsReviewLogTest` › purchase-return | **C** seeder | Same accounting-config gap |
| 3 | `Stage2NeedsReviewLogTest` › zero needs-review/missing | **A** real gap | `logs:audit` flagged 1 MISSING_LOG + 1 NEEDS_REVIEW |
| 4 | `RolePermissionSecurityLogTest` › no missing logs | **A** real gap | Same audit gap |
| 5 | `UserRoleAssignmentSecurityLogTest` › zero missing | **A** real gap | Same audit gap |
| 6 | `BillingEnhancementsTest` › aging report | **D** perm | Route gained `can:reports.ar_aging.view`; test role lacked it |
| 7 | `ConsultationRouteSessionWorkflowTest` › transition section | **B** outdated test | `'Services to add'` label removed (commented) in localisation refactor |
| 8 | `ConsultationClinicalSectionsTest` › clinical order | **I** fragile test | Workspace section-nav now repeats section labels earlier; `strpos` from 0 broke the order check |
| 9 | `InertiaBridgeLeakGuardTest` › unguarded reload | **E** moved file | Guarded fallback moved from `lab/process` into `lab/partials/accept-bill`; allowlist not updated |
| 10 | `LabWorkflowTest` › billed request opens | **A** domain | `assertDontSee('Bill Selected')` fails — billing action shown for an already-billed request |
| 11–13 | `MedicationAdministrationWorkflowTest` (×3) | **A** domain | MAR dose-cell modal markup, PRN section, and `time_columns` not produced (text matches lang exactly) |
| 14 | `VisitStatusPatientPathwayWorkflowTest` › emergency bed billing | **A** domain | State machine rejects `Admit Patient → Admitted` |

---

## 5. Fixes applied (10)

### Test infrastructure / seeders (C) — 3 failures
- `tests/Feature/UnifiedInventoryWorkflowTest.php`, `tests/Feature/Stage2NeedsReviewLogTest.php`: seed `Database\Seeders\AccountingChartSeeder` in `setUp()` so inventory/accounting postings find the chart of accounts + inventory control account. No production change; no totals altered.

### Real audit-logging gap (A) — 4 failures
- `app/Http/Controllers/Admin/BloodStorageLocationController.php`: wired `ActivityLogService` (`logCreated`/`logUpdated`) into `store`/`update`/`toggle` — these CRUD actions emitted **no** audit log (genuine gap; `logs:audit` MISSING_LOG).
- `app/Http/Controllers/Admin/EmergencyTaskController.php`: wired `logClinicalAction` (CREATED/COMPLETED/REOPENED) for emergency task store/complete (delegated to a non-logging service → NEEDS_REVIEW).
- Result: `logs:audit` → MISSING_LOG 0, NEEDS_REVIEW 0. Audit coverage **added**, not bypassed (honours "do not remove audit events").

### Permission setup (D) — 1 failure
- `tests/Feature/BillingEnhancementsTest.php`: granted `reports.ar_aging.view` + `reports.statements.view` to the test role (route gained the gate). Production `@can`/gate unchanged.

### Localisation-refactor test expectations (B / I) — 2 failures
- `ConsultationRouteSessionWorkflowTest`: the `'Services to add'` label was intentionally removed in the refactor; assert the still-present `visitRouteServiceSelect` field instead.
- `ConsultationClinicalSectionsTest`: made the section-order check robust to the new workspace nav by searching each label **after** the previous match (`strpos($content, $label, $last + 1)`) — preserves the order intent, tolerates duplicate nav labels.

### Inertia guard allowlist (E) — 1 failure
- `InertiaBridgeLeakGuardTest`: added `lab/partials/accept-bill.blade.php` to `ALLOWED_FALLBACKS` (the guarded `if (window.UhmsInertia && data.redirect) {…} else {window.location.href = target}` bridge-aware fallback) — the code moved out of `lab/process.blade.php`; the allowlist mechanism is exactly for these intentional fallbacks. No production JS change; the SPA bridge is preserved.

---

## 6. Remaining failures (5) — pre-existing domain/feature, documented

All 5 were last touched by **non-localisation feature commits** (`git log`): `342e142 feat: outpatient session auto-close & visit pathway`, `5185200 feat(logging): MAR dual-write`, lab refactor. They are **structural/data** failures (verified: the relevant MAR labels match `lang/*/medication_administration.php` exactly, so they are not text/localisation regressions). Fixing them correctly needs the owning feature context; guessing risks real clinical/billing bugs (the phase warns against this).

| Test | Precise root cause | Recommendation |
|---|---|---|
| `LabWorkflowTest › billed request opens from results route` | Results-show page renders the **'Bill Selected'** action for an already-billed request (`assertDontSee` fails). Display-gating regression in the lab results view/controller. | Gate the bill action on un-billed items only. |
| `MedicationAdministrationWorkflowTest › admission mar chart …` | Dose-cell modal trigger `data-bs-target="#mar-dose-{id}"` not rendered (patient/drug/DUE render fine). | Review MAR grid dose-cell markup after the dual-write logging change. |
| `MedicationAdministrationWorkflowTest › prn medications …` | PRN/SOS section (`'PRN / SOS Medications'`) not rendered for PRN schedules. | Review PRN branch of the MAR chart. |
| `MedicationAdministrationWorkflowTest › mar chart service …` | `MarChartService` returns empty `time_columns` for the test's schedule window. | Review the time-window/normalisation logic. |
| `VisitStatusPatientPathwayWorkflowTest › emergency bed billing …` | `VisitStatusService` rejects `Admit Patient → Admitted` transition. | Reconcile the visit-status state machine's allowed transitions with the emergency-bed-billing pathway. |

These are **non-localisation, non-blocking-to-localisation** debt. They predate/parallel this phase's localisation work and do not affect active-runtime candidates (still 0).

---

## 7. Files changed

**Production (2):** `app/Http/Controllers/Admin/BloodStorageLocationController.php`, `app/Http/Controllers/Admin/EmergencyTaskController.php` — both **add** audit logging only (no logic/permission change).

**Tests (6):** `UnifiedInventoryWorkflowTest`, `Stage2NeedsReviewLogTest` (seeders); `BillingEnhancementsTest` (permission); `ConsultationRouteSessionWorkflowTest`, `ConsultationClinicalSectionsTest` (assertion robustness); `InertiaBridgeLeakGuardTest` (allowlist).

**Seeders/factories/migrations:** none changed (existing `AccountingChartSeeder` reused). **npm build:** not run (no frontend asset change; SPA bridge untouched).

---

## 8. Localisation gate result (preserved)

- **Active runtime candidates: 0.**
- `php artisan test tests/Feature/Localization` → **12 passed** (parity, audit-zero, FR route smoke, validation FR, JS bridge).
- EN/FR parity pass; `view:cache` compiles; `route:list` OK; `git diff --check` clean.

## 9. Safety confirmations

No `@can`/policy/gate/middleware weakened. No invoice totals/journal/payment/accounting semantics changed (only test seeding + audit logging added). Products vs services unchanged. No NHIS-only logic. No stock-cost permission weakened. No clinical/financial data exposure. No business logic moved into Blade. Activity-log coverage **increased** (2 controllers).

## 10. Known risks & next phase

- **Risk:** the 5 documented domain failures indicate real (pre-existing) regressions in MAR rendering, lab billing-action gating, and the visit-status state machine from recent feature work — they should be triaged by the owning feature work, not patched blind here.
- **Next phase:** a domain-focused stabilisation pass for MAR / visit-pathway / lab billing display (with the feature authors), then wire `tests/Feature/Localization` + the full suite into CI as required gates.

> Localisation lock preserved (active runtime candidates 0; 12 localisation tests green). Full suite improved **525 → 535 passing**; remaining 5 are documented non-localisation domain failures.
