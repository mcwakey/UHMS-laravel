# Phase 14R.6 — Advisory Readiness, Summary Projection, Immutable Completion Snapshot, Reconciliation Dry Run & Billing De-duplication Policy (Report)

**Status:** ✅ Implemented. **R6 is CLOSED.** Dark by default (all six new flags `false`).
**Update — Phase 14R.6.1:** **K1 is CLOSED** — the summary, snapshot, history, current-record, readiness and print surfaces are authored and wired. See `OBGYN_MATERNITY_SUMMARY_SNAPSHOT_UI_PHASE_14R_6_1_REPORT.md`.
**Companion:** `OBGYN_MATERNITY_HANDOFF_UI_PHASE_14R_5_1_REPORT.md`

---

## 1. Architecture audited before any code (step 1)

**Consultation completion.** `ConsultationSessionWorkflowService::completeRoute()` asserts the editable-route guard and completion readiness, then delegates to **`ConsultationRouteService::completeRoute()`**, which owns the `DB::transaction`. That method **early-returns when the route is already `COMPLETED` or `CANCELLED`** — completion is therefore already idempotent, and re-completion after a reopen stamps a **fresh `completed_at`**. `ConsultationAutoCompletionService::evaluate()` runs afterwards, outside the route transaction. Reopening is owned by `ConsultationSessionWorkflowService::reopenRoute()` via `ConsultationReopenEligibilityService`.

→ **The completion identity already exists**: `route id + completed_at`. No token was invented.

**Consultation summary.** Summaries are **generated live and never persisted**. `ConsultationSpecialtySummaryBuilder::build()` assembles sections from `ConsultationSpecialtySummaryTemplateRegistry` using sources from `ConsultationSpecialtySummarySourceCollector::collect()`. The only stored text is `medical_records.final_note`, which is clinician-authored and untouched here. This is precisely why an immutable snapshot was needed: without one, a completed consultation's summary would silently re-render from *current* maternity data.

**Billing.** `MaternityBillingPostingService::previewForSource()` builds an advisory preview; `postForSource()` returns `posting_not_implemented` and creates nothing. `mappingKeysForSource()` defines the maternity mapping keys. Crucially, **`consultation_specialty_service_mappings` has no maternity key column** — its columns are `section_key`, `mapping_context`, `billing_trigger`, `department_type`. The overlap is therefore declared explicitly against `section_key` rather than inventing a column.

**Historical entries.** `consultation_specialty_entries` has **no `patient_id`** — the patient is reached through the consultation route — and its profile relation is `profile`, not `specialtyProfile`. Both facts shaped the reconciliation queries.

## 2. Feature flags (all default false)

```
CONSULTATION_MATERNITY_READINESS_ENABLED=false
CONSULTATION_MATERNITY_SUMMARY_ENABLED=false
CONSULTATION_MATERNITY_COMPLETION_SNAPSHOT_ENABLED=false
MATERNITY_BILLING_DEDUPLICATION_POLICY_ENABLED=false
MATERNITY_BILLING_ALLOW_BOTH_WHEN_CONFIGURED=false
MATERNITY_BILLING_ALLOW_MANUAL_SELECTION=false
```

The snapshot flag is **deliberately independent** of the workspace and summary flags: enabling Obstetrics must not silently start writing medico-legal history, and disabling the summary must not stop it.

## 3. Explicit-context rule

Only an **EXPLICIT** link produces clinical values.

| Context | Live projection | Snapshot | Readiness |
|---|---|---|---|
| Explicit valid | ✅ full curated projection | ✅ captured | ✅ stage-aware |
| Suggested (inferable) | advisory status only, **no clinical values** | ❌ none | advisory warning |
| Ambiguous | advisory status only | ❌ none | advisory warning |
| Invalid | advisory status only | ❌ none | advisory warning |
| None | no section | ❌ none | no warning (general Obstetrics unchanged) |

Completion always continues. Nothing here persists a link.

## 4. Advisory readiness

`ConsultationMaternityReadinessService` → `ConsultationMaternityReadinessResult` with the closed status set **ready / warning / unavailable**. There is deliberately **no blocked state**, and `blocksCompletion()` returns `false` unconditionally.

Modes are derived **only from explicit links** — never from specialty, diagnosis, pregnancy test, complaint or gestational age. Most-specific stage wins: postnatal → labor → antenatal → general.

Warnings: `context_confirmation_required`, `context_ambiguous`, `context_invalid`, `pregnancy_profile_missing`, `anc_visit_not_recorded`, `labor_episode_missing`, `labor_episode_profile_mismatch`, `postnatal_case_missing`, `postnatal_referral_required`, `postnatal_readiness_unavailable`.

Gynaecology returns `unavailable` and renders nothing. General Obstetrics readiness is unchanged. No admission discharge rule was copied into Consultation. **Tested: a warning does not stop completion.**

## 5. Live summary projection

`ConsultationMaternitySummaryService` → `ConsultationMaternitySummaryProjection`, registered as an additional generated source named **`maternity_context`** on the existing collector. It creates **no specialty entry**, overwrites no field, and returns `[]` with zero queries while the flag is off.

Included: pregnancy (status, gravida/para/abortions/living children, previous caesarean, LMP, EDD, GA, dating method = GA source, risk codes), ANC (id, date, GA, BP, weight, fundal height, FHR, presentation, danger-sign/risk codes, next visit), labor (stage, status, latest observation time, dilation, FHR, BP, escalation flags), delivery (mode, outcome, maternal condition, EBL, newborn count), newborns (order, sex, weight, Apgar 1/5/10, resuscitation, outcome), postnatal (status, readiness timestamps, referral, follow-up, observation **times only**), admission (id, status, ward, bed — labelled Admission-owned).

Excluded and tested: full ANC assessment/plan, labor notes, delivery notes, postnatal observation values, STI/sexual history, protected fields, long narrative, billing, pharmacy, stock.

Values are **typed codes and scalars**; translated labels are never stored.

## 6. Immutable snapshot

`consultation_maternity_snapshots` — append-only by design: **no `updated_at`, no soft deletes**. Unique on `(consultation_route_id, snapshot_version)` and `(consultation_route_id, completion_reference)`; indexed on profile, captured_at and previous_snapshot_id. `previous_snapshot_id` chains versions.

`ImmutableClinicalSnapshot` rejects `update()`, `delete()`, `forceDelete()` and any dirty `save()` on an existing row — schema alone would not stop application code. All three are tested.

**Canonical payload:** recursive key sort (lists keep order), ISO-8601 dates, numerics kept numeric, enums kept as codes, deterministic newborn order (birth_order, then id), no locale strings, no URLs, no model objects. `payload_hash` is SHA-256 over that canonical JSON, with one shared hashing routine used by both capture and `verifyPayloadHash()`.

**Hash caveat, stated plainly:** the hash is **tamper evidence**, not encryption. It shows the row no longer matches what was captured. It does **not**, on its own, make the record cryptographically non-repudiable — anyone able to rewrite the row could recompute it.

## 7. Capture inside the completion transaction

Order: existing readiness/guard validation → transaction opens → completion state written → **lock the route, resolve the completion reference, capture** → existing logs → commit.

- Completion fails → snapshot rolls back (tested by rolling back an outer transaction).
- Snapshot capture fails → the transaction aborts, so there is never a completed consultation with a half-written snapshot.
- Repeated completion → same snapshot, no second version (the service early-returns on the reference; the unique index is the final guard against a concurrent double-capture).
- Reopen + recomplete → version 2, with version 1 byte-identical and its hash unchanged.
- No explicit context → completion continues, no snapshot.

**Bug caught during testing, not by inspection:** the reopen fixture called `forceFill()` on a stale in-memory model, which marked nothing dirty and silently saved nothing — the second completion then early-returned. Fixed in the test by refreshing first; the product behaviour was correct.

## 8. Reconciliation dry run

`php artisan maternity:reconcile-obgyn-entries` — **dry-run only**. `--apply` prints that apply mode is unavailable in 14R.6 and **exits non-zero with zero writes**. Options: `--patient --consultation --profile --format=table|json|csv --output --include-historical --limit --detailed`.

Scope (approved matrix): Obstetrics `obstetric_history`, `lmp_edd_gestational_age`, `current_pregnancy`, `antenatal_vitals`, `fetal_assessment`, `risk_assessment`, `birth_plan`, plus hidden legacy `lab_screening` and `ultrasound_findings`; Gynaecology `obstetric_history` **only**. Consultation-owned sections are excluded and tested — `menstrual_history`, `current_complaints`, `action_plan`, `planned_place`, `previous_complications`, `high_risk_notes`, `booking_status`, `delivery_plan`.

Classifications: `safe_to_link` · `safe_to_migrate` · `conflict_requires_review` · `historical_only` · `insufficient_context`.

Parsers (`ObgynEntryValueParser`) are pure and never write. Each returns value + confidence (`exact` / `safe_normalised` / `unparseable`) + warnings. `120/80` and `120 / 80` parse; `high`, `120/80 sitting`, `120-80` do not. `32`, `32 cm`, `32.5 cm` parse; `32 weeks size` does not. Locale-ambiguous dates (`03/04/2026`) are **refused, never reinterpreted**, and a missing year is never inferred. **A scan-dated profile is never overridden by an LMP-derived GA** — it reports `scan_dated_profile_not_overridden`.

Console output shows aggregate counts by default; detail rows carry identifiers, section keys, classifications and a **stable SHA-256 of the original entry JSON**. Patient names and clinical narrative never appear (tested). Output is deterministic (tested).

**Environment row counts (this environment):** all five classifications **0** — there are no historical O&G entries here. Re-measure per environment before enabling any write guard there.

## 9. Billing de-duplication policy

`MaternityBillingDeduplicationPolicy` (closed enum) + `MaternityBillingPolicyDecision` (typed DTO) + `MaternityBillingDeduplicationPolicyService` (**read-only**).

The distinction the audit demanded:

| | Charge | Treatment |
|---|---|---|
| **A** | Base encounter/attendance fee | **Never** a duplicate of a maternity event. Not a specialty billing application, so it cannot even be matched. Tested. |
| **B** | Event-specific consultation specialty charge | The only thing that can conflict. Matched via declared `section_key` overlap. |
| **C** | Maternity event charge | Bound to the actual source record. |

Approved defaults: Obstetrics with no maternity event → `consultation_only`; ANC / labor / delivery / newborn / postnatal → `maternity_event_only`; genuinely separate services → `both_when_configured` (disabled by default); disputed → `manual_selection` (disabled by default). The newborn responsible-patient policy stays governed by the existing `MATERNITY_NEWBORN_BILLING_POLICY`.

**Duplicate identity** = `source_type + source_id + mapping_key`. Deliberately **not** patient + date, so two legitimate ANC visits on the same day are never collapsed (tested).

Preview integration adds `deduplication_policy` to `previewForSource()`. **`postForSource()` remains non-posting**, no invoice item, invoice, maternity billing event or consultation billing application is created, no invoice is recalculated, and no billing card was added to any clinical workspace. All tested.

## 10. Query counts (measured)

| Surface | Queries |
|---|---|
| Active consultation, **all flags off** — readiness | **0** |
| Active consultation, **all flags off** — summary | **0** |
| Readiness on, no context | 6 |
| Non-O&G specialty | 2 |
| Summary on, explicit profile | **7** |
| Gynaecology, explicit profile | **6** |
| Summary, unlinked (suggested) | 19 |
| Full chain: profile + ANC + labor + delivery + **3 newborns** + postnatal | **14** |
| Same chain with **5 newborns** | **14** (no N+1) |
| Completion, snapshot **off** | 13 |
| Completion, snapshot **on** | 27 |
| Completed summary **from snapshot** | **2** |
| Snapshot history, multiple versions | **3** |
| Billing policy **off** | **0** |
| Billing policy **on** | 2 |

Every flag-off surface costs **0**. Multiple newborns add nothing (one bounded ordered query). A completed summary loads from the snapshot in **2** queries and does **not** fan out into live maternity tables. Readiness and the summary each memoise per request. No persistent cross-request cache is used for clinical data.

The unlinked/suggested path (19) is the inferring resolver's fallback chain, unchanged from 14R.2 — it runs only when nothing explicit is linked and the summary flag is on.

## 11. Tests and baseline comparison

| Suite | Result | Baseline | Verdict |
|---|---|---|---|
| **ConsultationMaternityReadinessPhase14R6Test** (new) | **13 passed** | — | ✅ |
| **ConsultationMaternitySummarySnapshotPhase14R6Test** (new) | **20 passed** | — | ✅ |
| **ObgynMaternityReconciliationDryRunPhase14R6Test** (new) | **19 passed** | — | ✅ |
| **MaternityBillingDeduplicationPolicyPhase14R6Test** (new) | **13 passed** | — | ✅ |
| All 14R.2 → 14R.6 suites combined | **280 passed**, 1 skipped | — | ✅ |
| All Emergency suites | **91 passed** | — | ✅ |
| Admission + Maternity 8–14.1 | 84 passed, **2 failed** | 2 pre-existing | ✅ unchanged |
| `tests/Feature/Consultations` | 230 passed, **22 failed** | 22 pre-existing | ✅ **exactly unchanged** |

The two failures remain the documented `AdmissionNursingCarePhase6Test > admission show renders…` and `AntenatalCarePhase9Test > anc history detail…`. The one Consultations summary failure touched by this work (`ConsultationSpecialtySummaryBuilderTest`) was **verified pre-existing by stashing every 14R.6 change and re-running** — identical result.

**Phase 14R.6 introduced zero new failures.** `composer test:wide` was not run.

## 12. Files changed

**New**
```
app/Data/Consultation/Maternity/{ConsultationMaternitySummaryProjection,ConsultationMaternityReadinessResult}.php
app/Data/Maternity/Billing/MaternityBillingPolicyDecision.php
app/Enums/MaternityBillingDeduplicationPolicy.php
app/Models/ConsultationMaternitySnapshot.php
app/Models/Concerns/ImmutableClinicalSnapshot.php
app/Services/Consultation/Maternity/{ConsultationMaternitySummaryService,ConsultationMaternitySnapshotService,ConsultationMaternityReadinessService}.php
app/Services/Maternity/MaternityBillingDeduplicationPolicyService.php
app/Services/Maternity/Reconciliation/{ObgynEntryValueParser,ObgynEntryReconciliationService}.php
app/Console/Commands/ObgynEntryReconciliationCommand.php
database/migrations/2026_07_27_000001_create_consultation_maternity_snapshots_table.php
database/migrations/2026_07_27_000002_seed_consultation_maternity_summary_permissions.php
lang/{en,fr}/{consultation_maternity_summary,maternity_reconciliation,maternity_billing_policy}.php
tests/Feature/{ConsultationMaternityReadiness,ConsultationMaternitySummarySnapshot,ObgynMaternityReconciliationDryRun,MaternityBillingDeduplicationPolicy}Phase14R6Test.php
docs/maternity/OBGYN_MATERNITY_READINESS_SUMMARY_RECONCILIATION_PHASE_14R_6_REPORT.md
```

**Modified**
```
config/consultation.php                     — 3 traceability flags
config/billing.php                          — 3 billing-policy flags
app/Services/ConsultationRouteService.php   — snapshot capture inside the completion transaction
app/Services/Consultation/Specialty/ConsultationSpecialtySummarySourceCollector.php — maternity_context source
app/Services/Maternity/MaternityBillingPostingService.php — policy decision in the preview
```

## 13. Permissions and privacy

Added view-only: `consultation.maternity_context.summary.view`, `consultation.maternity_context.readiness.view`. **No mutation permission** — nothing in this phase is clinician-writable. Granted to Admin/Super Admin, and to clinical roles only where they already hold both consultation access and `consultation.maternity_context.view`, so a snapshot can never become a privilege-escalation path.

Activity logging is identifier-only: `CONSULTATION_MATERNITY_SNAPSHOT_CAPTURED` / `_RECAPTURED` carry route id, snapshot id, version, profile id, source-record ids, payload hash, actor, completion reference and schema version. **Payload contents are never logged.** Ordinary summary/readiness renders are not logged; the reconciliation dry run writes no activity row (tested); read-only policy evaluation is not logged.

## 14. Localisation

EN/FR in strict recursive parity, verified programmatically: `consultation_maternity_summary` **47/47**, `maternity_reconciliation` **18/18**, `maternity_billing_policy` **21/21**.

## 15. Existing workflows protected

Unchanged and verified: consultation completion, readiness and reopening; the whole Consultation suite at its exact baseline; Gynaecology completion and readiness; Emergency (91 tests); admission request/bed/nursing/discharge; Maternity phases 8–13; Phase 14.1 billing preview. No invoice item is created anywhere, maternity billing posting remains disabled, and no historical specialty entry was modified.

## 16. Known risks

| # | Risk |
|---|---|
| K1 | ✅ **CLOSED in 14R.6.1.** The audit found the root cause: the `maternity_context` source was collected but no summary template declared a section for it, so the builder never touched it. Rather than flatten the projection into the generated text, 14R.6.1 renders it as a structured card driven by a typed presentation service — live for active consultations, the stored snapshot for completed ones. |
| K2 | The completion reference is `route id + completed_at`. Two completions within the same second on the same route would share a reference and be treated as one occurrence. In practice the route early-returns after the first completion, so this needs a reopen plus a recompletion inside one second to occur. |
| K3 | Overlapping billing sections are a **declared map**, not a schema relationship, because no maternity key column exists on specialty mappings. If a site bills a maternity act through a section not in that map, the overlap will not be detected. Documented in the service. |
| K4 | Reconciliation reports **0 rows in this environment**. Counts must be re-measured per environment before any write guard is enabled there. |
| K5 | The snapshot hash is tamper evidence only (see §6). |

## 17. Rollout and rollback

**Rollout order:** run the reconciliation dry run per environment → review classifications and counts → enable advisory readiness → enable the live summary projection → verify with clinicians → enable completion snapshots → complete/reopen/recomplete controlled pilot consultations → enable the billing policy audit → review mapping conflicts before restarting Phase 14.2.

**Do not enable an O&G write guard in an environment before its reconciliation dry run has been reviewed.**

**Rollback:** disable each flag independently. Readiness and summary rendering return to exactly their previous behaviour. Disabling the snapshot flag stops new captures; **existing snapshots remain immutable and readable and are never deleted**. Disabling the billing policy returns Phase 14.1 preview behaviour unchanged — posting was never available. No destructive rollback is required.

## 18. R6 closure

**R6 is CLOSED.** Every criterion is met and test-enforced:

- snapshots are immutable (update/save/delete all rejected) and versioned;
- capture happens inside the completion transaction, and rolls back with it;
- a completed consultation binds to its completion-time snapshot;
- later maternity changes do not alter the historical snapshot (while the live projection does — proven separately in the same test);
- recompletion creates version 2 with version 1 byte-identical;
- no historical snapshot is fabricated retroactively, even after the flag is switched on later.

## 19. Next phase

**14R.7 — O&G/Maternity manual-test data, environment reconciliation review, pilot acceptance and wider regression.**
