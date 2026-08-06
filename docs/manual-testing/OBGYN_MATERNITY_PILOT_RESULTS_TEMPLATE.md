# O&G / Maternity Pilot — Results Record

**Batch ID:** _________________  **Environment:** _________________  **Date:** _________________

**Statuses:** `NOT_RUN` · `PASS` · `PASS_WITH_OBSERVATION` · `FAIL` · `BLOCKED`

> Evidence classes are kept **separate on purpose**. Automated evidence proves the mechanism.
> It does not and cannot stand in for clinician evidence.

---

## 1. Automated evidence — filled by the development team

| Area | Suite | Status | Evidence |
|---|---|---|---|
| Preview parity (Gate 0) | `ConsultationMaternityPreviewParityPhase14R7Test` | PASS | 9 passed |
| Pilot data + synthetic classifier | `ObgynMaternityPilotDataPhase14R7Test` | PASS | 10 passed |
| Same-second risk (P2) | `ConsultationSnapshotSameSecondRiskPhase14R7Test` | PASS | 5 passed — assertions inverted in 14R.8; now the regression guard |
| **Completion identity (P2 closure)** | `ConsultationSnapshotCompletionIdentityPhase14R8Test` | PASS | 28 passed, 126 assertions — covers the 41 specified checks |
| Summary UI | `ConsultationMaternitySummaryUiPhase14R6_1Test` | PASS | 22 passed |
| Snapshot history UI | `ConsultationMaternitySnapshotHistoryUiPhase14R6_1Test` | PASS | 12 passed |
| Print | `ConsultationMaternitySummaryPrintPhase14R6_1Test` | PASS | 8 passed |
| Readiness | `ConsultationMaternityReadinessPhase14R6Test` | PASS | 13 passed |
| Snapshot capture | `ConsultationMaternitySummarySnapshotPhase14R6Test` | PASS | 20 passed |
| Reconciliation dry run | `ObgynMaternityReconciliationDryRunPhase14R6Test` | PASS | 19 passed |
| Billing policy | `MaternityBillingDeduplicationPolicyPhase14R6Test` | PASS | 13 passed |
| Handoff suites 14R.5 / 14R.5.1 | 7 suites | PASS | see report |
| Bridge suites 14R.2 – 14R.4.1 | 7 suites | PASS | see report |

---

## 2. Developer manual evidence

| # | Check | Status | Notes |
|---|---|---|---|
| D1 | Pilot seed command runs and writes a manifest | NOT_RUN | |
| D2 | Pilot cleanup removes only the manifest batch | NOT_RUN | |
| D3 | Preflight verdict reviewed | NOT_RUN | |
| D4 | Environment reconciliation report reviewed | NOT_RUN | |
| D5 | Order-set audit reviewed | NOT_RUN | |
| D6 | Feature-flag rollback exercised | NOT_RUN | |
| D7 | Print output reviewed on paper | NOT_RUN | |

---

## 3. Clinician evidence — **must not be filled in by the development team**

| # | Scenario | Status | Reviewer | Usability issue | Clinical-safety issue |
|---|---|---|---|---|---|
| A | Obstetrics, no context | NOT_RUN | | | |
| B | Obstetrics, linked profile | NOT_RUN | | | |
| C | Record ANC from consultation | NOT_RUN | | | |
| D | Ambiguous profiles | NOT_RUN | | | |
| E | Gynaecology without pregnancy | NOT_RUN | | | |
| F | Positive test, no auto transition | NOT_RUN | | | |
| G | Explicit Gynaecology link | NOT_RUN | | | |
| H | One-way LMP adoption | NOT_RUN | | | |
| I | Emergency pregnancy/labour/admission | NOT_RUN | | | |
| J | Admission context propagation | NOT_RUN | | | |
| K | Maternity → Emergency handoff | NOT_RUN | | | |
| L | Postnatal review | NOT_RUN | | | |
| M | Active live summary | NOT_RUN | | | |
| N | Completion snapshot | NOT_RUN | | | |
| O | Current vs historical separation | NOT_RUN | | | |
| P | Reopen / recomplete versioning | NOT_RUN | | | |
| Q | Completed with no snapshot | NOT_RUN | | | |
| R | Five-newborn print | NOT_RUN | | | |
| S | Billing de-duplication preview | NOT_RUN | | | |
| T | Feature-flag rollback | NOT_RUN | | | |

**Clinician sign-off:** name ______________ role ______________ date __________ outcome ☐ Accepted ☐ Rejected ☐ Needs changes

---

## 4. Environment reconciliation evidence

| Field | Value |
|---|---|
| Report path | |
| Mode | must be `dry_run` |
| Writes performed | must be `0` |
| safe_to_link | |
| safe_to_migrate | |
| conflict_requires_review | |
| historical_only | |
| insufficient_context | |
| Total inspected | |
| Reviewed by | |
| Unresolved rows accepted / excluded | |

> Write guards must **not** be enabled while unreviewed `conflict_requires_review` or
> `insufficient_context` rows remain.

---

## 5. Wide regression evidence

| Field | Value |
|---|---|
| Command | |
| Total / assertions | |
| Failures / errors / skipped | |
| Peak memory | |
| Known unchanged failures | |
| Newly resolved | |
| **New failures** | |
| Attributable to O&G/Maternity? | |

---

## 6. Open risks carried into the pilot

| # | Risk | Status |
|---|---|---|
| P2 | Same-second reopen + recompletion collapses into one snapshot version | **`CLOSED_BY_PHASE_14R_8`** — completion identity is now a durable occurrence ULID, not a timestamp |
| P3 | Environment reconciliation reports zero rows here; classification unproven at scale on real data | OPEN |
| P4 | Clinical usability not signed off | OPEN until §3 is completed |

---

## 7. Verdict

| Field | Value |
|---|---|
| Technical verdict | |
| Clinical verdict | |
| Final readiness | |
| Approved by | |
| Date | |

> `READY_FOR_PRODUCTION_ROLLOUT` may never be recorded automatically. It requires explicit
> project-owner approval, production environment review and change control.
