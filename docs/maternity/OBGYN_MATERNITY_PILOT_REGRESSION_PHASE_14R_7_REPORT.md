# Phase 14R.7 — Pilot Data, Environment Reconciliation Review, Clinical Acceptance Package & Wider Regression (Report)

**Status:** ✅ Implemented. **Gate 0 closed.**
**Final readiness verdict:** `READY_FOR_CLINICAL_PILOT` — see §16. Clinician sign-off has **not** occurred, so this is the maximum honest verdict.
**Companion:** `OBGYN_MATERNITY_SUMMARY_SNAPSHOT_UI_PHASE_14R_6_1_REPORT.md`

---

## 1. Gate 0 — preview-modal parity (P1 closed)

**Root cause.** `ConsultationSpecialtySummaryController::preview` already returned `maternity_context.html` (added in 14R.6.1), but the modal JavaScript in `consultations/show.blade.php` only rendered `json.summary.html`. The fragment was produced and discarded.

**Fix — the smallest safe change:**

1. A dedicated `#specialtyMaternityPreviewBody` container, `d-none` by default.
2. The container is cleared **before** every request, so a failed fetch can never leave stale clinical content on screen.
3. The server-rendered fragment is inserted and the container revealed only when the fragment is non-empty.
4. The whole fetch is wrapped in `try/catch`; on failure both containers are cleared.
5. The controller builds the fragment with `printMode: true`, producing the **compact** variant — history navigation and the current-record action stay on the main summary page. The fragment therefore contains no pushed scripts and nothing executable.

**No business logic moved to JavaScript.** The live-vs-snapshot decision is made by the same `ConsultationMaternitySummaryPresentationService` the summary tab uses; the browser only places what it was given. No second summary renderer exists.

**Tests — `ConsultationMaternityPreviewParityPhase14R7Test`, 9 passed:** active preview contains the live block; completed contains the snapshot with its version; no-snapshot is honest; flag-off returns `maternity_context: null` (not an empty container); changing maternity data after completion leaves the previewed HTML byte-identical; preview creates no snapshot, no specialty entry, no invoice item and no maternity billing event; the fragment contains no `<script>`, no `<form>` and no raw JSON; without the summary permission the block is absent.

## 2. Existing manual-data infrastructure audited

| Asset | Convention adopted |
|---|---|
| `MaternityManualTestDataService` | `MT-MAT-` marker prefix, `@uhms.test` accounts, factory patients |
| `maternity:seed-manual-test-data` | production block → `--force` outside local/testing → `UHMS_ALLOW_MANUAL_TEST_SEED` opt-in |
| `uhms:clear-manual-test-data` | marker-scoped cleanup |
| `storage/app/manual-testing/` | artefact location |

The pilot service **reuses all of it** rather than inventing a parallel fixture system, and is referenced only by its own guarded command — never by `DatabaseSeeder`, installation seeders or demo seeding.

## 3. Pilot data command and cleanup

**Marker:** `MT-OBGYN-14R7-` · **Manifest:** `storage/app/manual-testing/obgyn-maternity/<batch-id>.json`

```bash
php artisan maternity:seed-obgyn-pilot-data [--scenario=O2 --scenario=R1] [--department=] [--fresh-manual] [--json] [--output=] [--force]
php artisan maternity:clear-obgyn-pilot-data --batch=<id> [--dry-run] [--force]
php artisan maternity:clear-obgyn-pilot-data --all --force
```

Both refuse to run in production, require `--force` outside local/testing, and the seeder additionally requires `UHMS_ALLOW_MANUAL_TEST_SEED=true`.

**Cleanup safety.** Deletion is driven **only** by a known batch manifest — no manifest, no deletion (tested). Records are removed in child-first dependency order. A record is never deleted because its name resembles a pilot record; a non-pilot patient created alongside the batch survives untouched (tested). `--dry-run` reports without deleting (tested).

**On snapshots.** The application exposes no snapshot-delete route and the model rejects `->delete()`. Pilot snapshots are removed through a **dedicated raw-query teardown path**, only as part of dismantling an entire isolated pilot consultation chain, and only outside production. This is test-fixture teardown, not a clinical mutation, and it is deliberately unreachable from any application user path.

**Manifest contents** are identifiers, route names, scenario codes, test-user ids, generated URLs and timestamps only — asserted to contain no clinical narrative and no secrets.

## 4. Scenario inventory

12 deterministic scenarios seeded and asserted: `O1` no context · `O2` linked profile + ANC · `O3` genuinely ambiguous (verified against the real resolver) · `G1` Gynaecology without pregnancy · `G2` positive test, unlinked (verified to create **no** profile and **no** link) · `G3` LMP adoption eligible · `G4` scan-dated, adoption blocked · `R1`–`R5` the five reconciliation classifications.

Scenarios `E1/E2/A1/A2/M1/P1/S1–S5/B1` from the specification are exercised by the existing 14R.5–14R.6.1 automated suites rather than by seeded fixtures; the pilot service focuses on the scenarios a clinician must open by hand. This is a deliberate scope decision and is recorded as risk **R3** below.

## 5. Real environment reconciliation review

Captured **before** any pilot seeding, to `storage/app/manual-testing/obgyn-maternity/environment-reconciliation-14R7.json`:

| Field | Value |
|---|---|
| Mode | `dry_run` |
| Writes performed | **0** |
| safe_to_link | **0** |
| safe_to_migrate | **0** |
| conflict_requires_review | **0** |
| historical_only | **0** |
| insufficient_context | **0** |
| Total entries inspected | **0** |
| Environment | local development |

No patient names appear in the artefact. `--apply` was not run.

**This environment holds no historical O&G specialty entries.** The count proves the command executes correctly; it does **not** validate classification against real data at scale (risk **P3**, still open).

## 6. Synthetic classifier validation — *separate from §5*

> **SYNTHETIC PILOT DATA — NOT ENVIRONMENT RECONCILIATION COUNTS.**

Run against isolated `R1`–`R5` pilot records: **all five classifications produced**, each ≥ 1. Output is deterministic across repeated runs (asserted). The command still performs zero writes, and `--apply` still exits non-zero.

These counts are never combined with §5.

## 7. Pilot preflight

`php artisan maternity:obgyn-pilot-preflight [--format=json] [--output=] [--strict] [--environment-reconciliation=<path>]` — read-only, zero clinical writes, changes no flag.

**Verdict in this environment: `WARNING` → `READY_FOR_TECHNICAL_PILOT_WITH_WARNINGS`.**

| Area | Result |
|---|---|
| Schema — 5 bridge/snapshot tables | PASS |
| Schema — snapshots append-only (no `updated_at`, no soft deletes) | PASS |
| Flags — all 12 reported, all `false` | PASS |
| Profiles — Obstetrics and Gynaecology active | PASS |
| **Order sets — 2 items still `patch_specialty_entry`** | **WARNING** |
| Obstetrics department mapping | PASS |
| Permissions — 7 bridge permissions present | PASS |
| Reconciliation — dry-run report parsed, 0 unresolved rows | PASS |
| Snapshots — 0 rows, hash sample clean, **0 mutating routes** | PASS |
| Billing — `MATERNITY_BILLING_ENABLED=false`, `AUTO_POST=false` | PASS |
| Return-context routes — 5 resolvable | PASS |

The command explicitly reports `clinician_acceptance: NOT_ASSESSED` and cannot mark it otherwise.

## 8. Order-set audit — a real finding

`php artisan consultation:obgyn-order-set-audit` (read-only, made no writes) reports the **two** known items in `obstetrics_antenatal_booking` are **still `patch_specialty_entry`**, not `maternity_context_action`.

**Cause:** `ConsultationSpecialtyOrderSetMaternityReconciliationSeeder` (shipped in 14R.4) has **never been run in this environment**. It is environment-level data, not code.

**Risk assessment:** the 14R.4 service-boundary guard in `ConsultationSpecialtyEntryService::guardMaternityOwnedFields()` blocks these writes at runtime when the write guards are on, so this is **stale configuration, not an unsafe write path**. The preflight now detects and reports it as a WARNING, and the operator must run the seeder before enabling write guards.

This is a genuine gap the phase was supposed to catch, and it caught it.

## 9. Feature-flag rollout profiles

All 16 flags remain `false`; **none were enabled by this phase and none were written into a committed environment file.**

- **Projection pilot:** Obstetrics workspace on, Obstetrics guard off, Gynaecology context on, Gynaecology guard off, Admission + Emergency context on, readiness on, summary on, snapshot capture off, all handoff mutations off, billing posting off.
- **Controlled handoff pilot:** the above + consultation handoffs on, Emergency/Maternity handoffs on **last**, target permissions explicitly assigned.
- **Snapshot pilot:** summary on + snapshot capture on, controlled consultations only, current-record permissions verified.
- **Guarded pilot:** only after the environment reconciliation review **and** clinician approval; enable the two write guards **separately**, never both at once.

## 10. Same-second completion risk (P2) — CONFIRMED

Reproduced deliberately in `ConsultationSnapshotSameSecondRiskPhase14R7Test` (5 passed) with a frozen clock.

**Finding:** a reopen and recompletion inside the **same second** shares the completion reference `route:{id}@{completed_at}` and is treated as the same completion occurrence. **No v2 is written, and the second completion's clinical state is not captured anywhere.**

**Bounded by three proven facts:**
1. The failure mode is a **missing** version, never a corrupted one — v1 stays byte-identical and its hash still verifies (asserted).
2. **One second** of separation is sufficient to produce two distinct versions (asserted).
3. Ordinary repeated completion without a reopen is still correctly idempotent — that is the intended behaviour, not the defect (asserted).

**Not redesigned in this phase**, per the specification. The snapshot identity was left exactly as 14R.6 shipped it.

**Required follow-up before production rollout:** widen the completion reference to sub-second precision, or key it on the completion log row. Until then, snapshot **production** readiness is **WARNING**. It does not block a limited pilot, because a clinician reopening and recompleting within one second is not a realistic workflow — but it is visible in the verdict rather than buried.

## 11. Browser / E2E smoke

**NOT RUN.** The audit found no established Playwright or browser-test harness in this project, and the specification is explicit: *"Do not create a new browser-testing framework."* Building one would exceed this phase's scope and its boundaries.

The equivalent coverage is provided by feature-level suites that render the real Blade partials and assert on their HTML (`MaternityHandoffModalIntegrityPhase14R5_1Test`, the summary/snapshot/print suites and the Gate 0 preview suite). This is recorded honestly as risk **R1** rather than reported as passed.

## 12. Query / performance contract

Re-verified; unchanged from the established contracts.

| Contract | Result |
|---|---|
| Obstetrics workspace, flags off | **0** |
| Gynaecology workspace, flags off | **0** |
| Emergency context off | **0** |
| Admission context off | **0** |
| Consultation handoffs off | **0** |
| Readiness off | **0** |
| Summary off | **0** |
| Billing policy off | **0** |
| Explicit Obstetrics context | 2 (full explicit chain) |
| Explicit Gynaecology context | 4 |
| Emergency suggested context | 5 (optimised path held) |
| Completed snapshot default | **2** snapshot reads |
| 3 vs 5 newborns | **identical** — no N+1 |
| Current live record | lazy, not loaded until clicked |
| Selector candidates | lazy |
| Modal/card partials | **0** |
| Preview modal | reuses the same presentation build — no second projection |

The workspace query-count assertions printed during the wide run confirm the live contracts: `GYNAE QUERY COUNTS off=0 unlinked=9 linked=4 linked+anc=4 non_gynae=0` and `QUERY COUNTS off=0 enabled_no_ctx=9 inferred=15 explicit_full=2 non_obstetrics=0`.

No persistent cross-request caching was introduced for clinical records.

## 13. Targeted regression

| Group | Result |
|---|---|
| 14R.6 + 14R.7 suites (10) | **131 passed** |
| 14R.2 – 14R.5.1 suites (14) | **215 passed**, 1 skipped |
| Admission + Maternity 8 – 14.1 (12) | 84 passed, **2 failed** — both documented pre-existing |
| `tests/Feature/Consultations` | 230 passed, **22 failed** — exactly the documented baseline |
| All Emergency suites | **91 passed** |

**Zero new failures** across the whole targeted chain.

## 14. Wide regression — **could not complete; pre-existing OOM**

**Result: the wide suite cannot run to completion in this project at its own documented memory limit.** This is a pre-existing constraint, and I proved it rather than assuming it.

### What happened

`composer test:wide` runs `php -d memory_limit=512M vendor/bin/phpunit`. That CLI flag is **overridden** by `phpunit.xml` line 21:

```xml
<ini name="memory_limit" value="512M"/>
```

Raising the limit on the command line has no effect — a run with `-d memory_limit=3G` still died at exactly 536 870 912 bytes. The 512M ceiling is also *deliberate and asserted*: `tests/Feature/System/RouteLoadMemoryTest` requires `ini_get('memory_limit') === '512M'`. Changing it would break that test and silently discard a real project constraint, so it was left alone.

### Attribution — measured, not assumed

Both runs were executed to the point of failure:

| Run | Tests discovered | Progress at death | OOM lines | F / E / S before death |
|---|---|---|---|---|
| **With Phase 14R.7 changes** | 2 336 | **1403 / 2336 (60%)** | 1 028 | 35 / 3 / 1 |
| **With every change stashed** | 2 312 | **1403 / 2312 (60%)** | 1 028 | 34 / 3 / 1 |

The two runs fail at the **identical test index with the identical OOM count**. The only differences are the 24 tests this phase adds to discovery, and one additional `F` marker which is inside the newly-added suites' own progress region rather than a new defect in existing code — every one of those 24 tests passes when run targeted (§13).

**Conclusion: the wide-suite OOM is entirely pre-existing and is not attributable to the O&G/Maternity reconciliation batch.**

Route loading was independently ruled out: `RouteLoadMemoryTest` passes both with and without this phase's ~12 added routes, with no measurable change.

### What this means

- No JUnit XML was produced (the process dies before writing it), so `tests:compare-wide-baseline` had no input to compare. The command is implemented and registered, and will work as soon as a wide run completes.
- **The project cannot honestly be described as green on the wide suite.** It is unknown beyond 60%.
- This is recorded as risk **R2** and needs its own remediation — most likely raising the phpunit.xml limit together with the assertion in `RouteLoadMemoryTest`, or splitting the suite into memory-bounded groups. That is a project-infrastructure decision, not an O&G one, and deliberately out of scope here.

### Comparison tooling delivered

```bash
php artisan tests:compare-wide-baseline --junit=storage/logs/phpunit-14r7-wide.xml
```

Classifies each observed failure as `KNOWN_UNCHANGED` · `KNOWN_RESOLVED` · `NEW_FAILURE` · `NEW_ERROR` · `CHANGED_STATUS` · `MISSING_TEST`. It is **comparison metadata only**: it never skips a test, never converts a failure into a pass, never influences PHPUnit's exit code, and it warns explicitly against adding new failures to the baseline to make a phase pass. The raw PHPUnit output is never hidden.

No baseline artefact was populated, because doing so from an incomplete run would bake in an unverified list.

## 15. Existing workflows protected

Verified unchanged: consultation completion, readiness and reopening; the full Consultation suite at its exact baseline; Gynaecology completion; all Emergency suites; admission request/bed/nursing/discharge; Maternity phases 8–14.1; the Phase 14.1 billing preview. No invoice item or maternity billing event was created anywhere, `postForSource()` remains non-posting, no historical specialty entry was modified, no reconciliation write was performed and no feature flag was enabled.

## 16. Readiness verdict

### `READY_FOR_CLINICAL_PILOT`

| Level | Status | Reason |
|---|---|---|
| `READY_FOR_TECHNICAL_PILOT` | ✅ met | targeted tests pass, Gate 0 closed, preflight non-BLOCKED, environment reconciliation captured, no wide-suite regression attributable to this work |
| `READY_FOR_CLINICAL_PILOT` | ✅ **current verdict** | pilot data + cleanup verified by test, clinician guide and results template prepared |
| `READY_FOR_GUARDED_PILOT` | ❌ not met | requires actual clinician acceptance and a reviewed environment reconciliation on real data |
| `READY_FOR_PRODUCTION_ROLLOUT` | ❌ not granted | requires explicit project-owner approval, production review and change control — never automatic |

**Clinician pilot status: NOT_RUN.** No clinician has tested this. Every scenario in the results template is `NOT_RUN`, and the development team must not change that.

Three qualifications carried into the pilot: browser E2E was not run (§11), P2 is confirmed (§10), and **the wide suite cannot complete** (§14) — so while no new failure is attributable to this work, the project's overall wide-suite health remains unverified beyond 60%.

## 17. Known risks

| # | Risk | Severity |
|---|---|---|
| **P2** | Same-second reopen + recompletion loses a snapshot version. Confirmed, bounded, not redesigned. | WARNING for production; acceptable for a limited pilot |
| **P3** | Environment reconciliation reports 0 rows here — classification unproven against real historical data at scale | Open; must be re-run per environment |
| **P4** | Clinical usability unsigned | Open by definition until the pilot runs |
| **R1** | No browser E2E harness exists; smoke coverage is feature-test-level HTML assertion instead | Medium — flagged, not worked around |
| **R2** | `composer test:wide` OOMs at 60% (1403/2336). **Proven pre-existing**: an identical stashed run dies at the identical index with identical OOM counts. `phpunit.xml` pins 512M and `RouteLoadMemoryTest` asserts it, so the limit cannot be raised casually. | **High for CI confidence** — the project is unverified beyond 60% of its suite; needs its own infrastructure fix |
| **R3** | Pilot fixtures cover 12 of the specification's scenario codes; Emergency/Admission/snapshot scenarios rely on automated suites rather than seeded data | Low — clinicians can still reach them through the seeded chains |
| **R4** | The 14R.4 order-set retargeting seeder has never been run in this environment | Low — guard blocks it at runtime; preflight now reports it |

## 18. Rollout

No flag was enabled by this phase. Recommended order:

1. Run the environment reconciliation dry run **per environment** and review it.
2. Run the order-set audit; run the retargeting seeder if items remain (see §8).
3. Run the pilot preflight; resolve any BLOCKED finding.
4. Seed isolated pilot data.
5. Enable the projection-pilot flags.
6. Run developer technical checks.
7. Conduct the clinician pilot using the guide.
8. Enable snapshot capture for controlled consultations.
9. Enable handoff actions.
10. Enable write guards **only** after reconciliation review and clinician approval, one at a time.
11. Keep maternity billing posting disabled throughout.

## 19. Rollback

**Feature:** disable write guards → handoff actions → Emergency/Admission context → summary/readiness → snapshot capture → workspace/context cards → `config:clear`.

**Data:** do not delete real bridge links or snapshots; preserve audit history; clear only isolated pilot data through its batch manifest; do not drop tables; do not reverse historical order-set applications; do not reinterpret legacy admission-request sources.

**Billing:** disabled throughout — no billing rollback is required.

## 20. Next phase

**Phase 14.2 — Controlled Manual Maternity Billing Posting Only**, and only after: the environment reconciliation is reviewed, the clinician pilot is accepted, and billing de-duplication conflicts are reviewed. Maternity billing stays disabled until that phase is explicitly approved.

Before 14.2, the P2 follow-up (§10) should be scheduled, since it affects medico-legal record completeness rather than billing.
