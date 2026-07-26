# Phase 14R.6.1 — Maternity Summary & Completion Snapshot UI (Report)

**Status:** ✅ Implemented. **K1 is CLOSED.** Dark by default.
**Companion:** `OBGYN_MATERNITY_READINESS_SUMMARY_RECONCILIATION_PHASE_14R_6_REPORT.md`

---

## 1. Architecture audited before any template change (step 1)

**Where `maternity_context` entered — and why nothing rendered.** The source *was* being collected by `ConsultationSpecialtySummarySourceCollector::collect()` since 14R.6. But `ConsultationSpecialtySummaryBuilder::build()` only emits sections declared in `ConsultationSpecialtySummaryTemplateRegistry::templateForProfile()`, and `buildSection()` reads `data_get($sources, $section['source'])`. **No template declares a `maternity_context` section**, so the collected data was never touched. That was the whole of K1.

**Decision — and why.** I did *not* register a template section. Doing so would have flattened the curated projection into the generated text blob, with no way to distinguish live from historical data, no version, no integrity state and no history. Instead the maternity context is a **separate, structured card beside** the existing summary, driven by a typed presentation service. The existing summary engine is untouched — there is no second summary engine.

**Surfaces found:**

| Surface | Reality |
|---|---|
| Consultation show | `summary-section` tab; `summary-sections` partial renders `$consultationSummary` |
| Summary preview | `ConsultationSpecialtySummaryController::preview` — a **JSON endpoint**, consumed by JS in `show.blade.php` |
| Print | `ReportController::printConsultation` → dompdf `Pdf::loadView('reports.print-consultation')`. **A PDF surface does exist**; no new dependency was added |
| Completed consultation | rendered by the same show view — it was calling **live** summary generation, which is exactly what made snapshots necessary |
| Persistence | summaries are generated live; only `medical_records.final_note` is stored |

## 2. Typed presentation service

`ConsultationMaternitySummaryPresentationService` → `ConsultationMaternitySummaryViewModel`.

Four closed modes: `live` · `completion_snapshot` · `no_snapshot` · `unavailable`. The view model carries consultation status, active/completed/reopened state, live payload, selected + latest snapshot, bounded history, version, schema version, captured at/by, profile id, integrity state, previous/next version, current-record availability, permission state, warnings, print mode and internal routes.

It consumes `ConsultationMaternitySummaryService` and `ConsultationMaternitySnapshotService` and **writes nothing** — no snapshot, link, maternity record, specialty entry or billing. Results are memoised per request per (consultation, version, print mode). **Blade issues zero queries.**

## 3. Flag behaviour

| Flags | Behaviour |
|---|---|
| Summary **off** | No section anywhere. No presentation call, no projection query, no snapshot/history query. Existing summary output unchanged. Existing snapshots stay stored and immutable. **0 queries** (asserted). |
| Summary **on**, capture **off** | Active shows the live projection. Completed shows an **existing** snapshot if one was captured earlier — disabling capture never hides or deletes history. Completed with none shows the honest no-snapshot state. |
| Both **on** | Active → live. Completed → latest snapshot. Reopened → live plus history. Recompleted → new latest by default. |

The capture flag governs **future capture only**; it is never treated as permission to rewrite or delete prior snapshots.

## 4. Live vs completed

**Active/reopened** — `summary-live.blade.php`, headed **Current Maternity Record** with *Source of truth: Maternity* / *Encounter source: Consultation*. Never labelled as completion-time data (asserted).

**Completed** — `summary-snapshot.blade.php` renders the **stored payload**. The completed path does not query `antenatal_visits`, `labor_episodes`, `delivery_records`, `newborn_records` or `postnatal_cases` (asserted table-by-table). Changing the Pregnancy Profile after completion leaves the rendered summary **byte-identical** (asserted by comparing full HTML before and after).

Both render through **one shared partial**, `summary-payload.blade.php`, so a historical view and a current view can never present the same data differently. Excluded narrative is structurally absent — the payload only ever contained curated fields — and a test writes `SECRETASSESSMENTNARRATIVE` into the ANC record and asserts it never appears.

## 5. History navigation

`snapshot-history.blade.php` — version, captured at/by, schema version, integrity, latest marker, and a GET link per version. `previous`/`next` are bounded by the actual version list.

Route: `GET admin/consultations/{visit}/maternity-summary/history?version=N`.

- Scoped server-side: `snapshotVersion()` filters by `forConsultation()`, so a version belonging to another consultation is never returned (asserted with a real second consultation).
- Unknown version → 404. Missing permission → 403. Flag off → 403.
- Selecting a version performs **zero writes** (asserted with a query listener) and does not verify against live maternity data.

**A permission bug caught here:** my first route placement inherited `can:consultations.create` from the enclosing group — a *write* permission gating read-only views. Moved beside the other read fragments so they inherit `can:consultations.view`.

## 6. Integrity display

Three closed states from `verifyPayloadHash()`: `verified` · `mismatch` · `unavailable`.

Mismatch shows a prominent warning, keeps the historical payload visible, and **does not repair, re-hash, regenerate or delete anything** (asserted: the stored hash is unchanged and the row count stays 1 after rendering a tampered snapshot).

Wording is deliberate: *"Verified against the hash stored at capture"*, plus an explicit statement that this is **tamper evidence only — not an external signature, and not by itself proof of non-repudiation.** Clinical users see the state and an abbreviated reference; **raw canonical JSON is never rendered** (asserted).

## 7. Current record — explicit, separate, lazy

`current-record.blade.php` offers **View Current Maternity Record** on a completed consultation. It is:

- an explicit clicked action — the normal completed page never fetches live data (asserted: no `antenatal_visits` query on load);
- a separate block, badged *"Not part of the completion-time snapshot"*;
- gated on summary access **plus** `consultation.maternity_context.view` **plus** `maternity.pregnancy.view` (asserted in both partial-permission directions);
- explicit-only — a suggested/ambiguous context returns null rather than being presented as current truth (asserted).

It never compares, overwrites or alters the snapshot.

## 8. No snapshot, reopened, Gynaecology

**No snapshot** — states it plainly, explains the consultation may predate capture, and says *no historical snapshot has been fabricated*. Enabling capture later still shows the no-snapshot state (asserted). Nothing is created on page load.

**Reopened** — live values, an explicit "consultation reopened / recompletion will create a new version" notice, and history remains reachable. Recompletion makes v2 the default while v1 stays readable (asserted).

**Gynaecology** — no explicit link → no maternity values. With an explicit link → the same curated projection plus *"This consultation remains Gynaecology"*. No ANC/labor controls; Gynaecology completion untouched.

## 9. Readiness UI

The audit found `ConsultationMaternityReadinessResult` was **not rendered anywhere** — 14R.6 built the service without a card. Added `readiness-card.blade.php`: review mode, ready/warning badge, warning list, a pointer to record the missing information in Maternity, and the statement that **completion is still allowed**. Flag- and permission-gated; no mutation, no blocking, no admission discharge rule copied in.

## 10. Print

`reports/print-consultation.blade.php` gains a maternity block using the same shared payload partial in compact mode:

- **Active** → live projection under *Current Maternity Record*.
- **Completed** → the snapshot, with version, captured-at, integrity state and a *Historical Consultation Summary* label.
- **Selected version** → prints that version (v1 and v2 verified to print their own captured values).
- **No snapshot** → the honest message; current values are never printed as historical (asserted by changing the profile post-completion and confirming the captured value prints, not the new one).
- Flag off → no block at all.

No PDF dependency was added — the existing dompdf path is reused.

## 11. Query counts

| Surface | Queries |
|---|---|
| Summary flag **off** (active/completed/reopened) | **0** |
| Completed default — snapshot table reads | **2** (`latestFor` + bounded history) |
| Completed with 3 newborns vs **5 newborns** | **identical** (asserted equal) |
| Snapshot partials (render only) | **0** |
| History with 2 versions — snapshot reads | **≤ 2** (no per-version fan-out) |
| Current live record on the completed page | **not loaded** until the action is clicked |
| Presentation service, second call | **0** (memoised) |

Newborn count cannot affect the completed page: the babies live inside the stored payload, which is a single row. No persistent cross-request cache was added.

## 12. Tests and baseline comparison

| Suite | Result | Baseline | Verdict |
|---|---|---|---|
| **ConsultationMaternitySummaryUiPhase14R6_1Test** (new) | **22 passed** | — | ✅ |
| **ConsultationMaternitySnapshotHistoryUiPhase14R6_1Test** (new) | **12 passed** | — | ✅ |
| **ConsultationMaternitySummaryPrintPhase14R6_1Test** (new) | **8 passed** | — | ✅ |
| All 14R.2 → 14R.6.1 O&G suites | **322 passed**, 1 skipped | — | ✅ |
| All Emergency suites | **91 passed** | — | ✅ |
| Admission + Maternity 8–14.1 | 72 passed, **2 failed** | 2 pre-existing | ✅ unchanged |
| `tests/Feature/Consultations` | 230 passed, **22 failed** | 22 pre-existing | ✅ **exactly unchanged** |

**Phase 14R.6.1 introduced zero new failures.** `composer test:wide` was not run.

## 13. Files changed

**New**
```
app/Data/Consultation/Maternity/ConsultationMaternitySummaryViewModel.php
app/Services/Consultation/Maternity/ConsultationMaternitySummaryPresentationService.php
app/Http/Controllers/Doctor/Consultations/ConsultationMaternitySummaryController.php
resources/views/consultations/partials/maternity/{summary-payload,summary-live,summary-snapshot,snapshot-history,snapshot-integrity,current-record,current-record-body,readiness-card}.blade.php
tests/Feature/ConsultationMaternity{SummaryUi,SnapshotHistoryUi,SummaryPrint}Phase14R6_1Test.php
docs/maternity/OBGYN_MATERNITY_SUMMARY_SNAPSHOT_UI_PHASE_14R_6_1_REPORT.md
```

**Modified**
```
routes/web.php                                             — 2 GET-only read routes
app/Http/Controllers/Doctor/Consultations/Concerns/HandlesConsultationWorkspace.php
app/Http/Controllers/Doctor/Consultations/ConsultationSpecialtySummaryController.php  — preview parity
app/Http/Controllers/Admin/Reporting/ReportController.php  — print payload
resources/views/consultations/show.blade.php               — summary tab + readiness card
resources/views/reports/print-consultation.blade.php       — maternity block
lang/{en,fr}/consultation_maternity_summary.php            — 47 → 101 keys
```

## 14. Immutability, permissions and privacy

**No mutation path was added.** Every new route is GET; there is no store/update/destroy action, no form, no `_method`, and the rendered snapshot markup contains no `<form>` at all (asserted). Nothing recalculates a hash, regenerates a version, "fixes" a mismatch or fabricates a snapshot for an old completion.

Snapshot visibility requires consultation access **and** `consultation.maternity_context.summary.view`. The live current record additionally requires `consultation.maternity_context.view` **and** `maternity.pregnancy.view`. The snapshot is a curated consultation artefact, not unrestricted maternity access: raw JSON, protected fields, STI/sexual history and billing are all absent.

## 15. Billing absence

No billing card, no posting button, no invoice item, no maternity billing event, no invoice recalculation. `postForSource()` remains non-posting. Billing-policy evaluation does **not** run because a summary is viewed — the summary path never calls it. The de-duplication audit stays administrative.

## 16. Manual acceptance

Scenarios A–H are covered by the automated suites above (A/B/C/D/E/F by the summary and history suites, G by the tamper test, H by the print suite). They remain to be exercised **by a clinician** in the pilot, which is the point of Phase 14R.7 — the automation proves the mechanism, not the clinical usability.

## 17. Known risks

| # | Risk |
|---|---|
| R1 | The preview endpoint returns the maternity block as pre-rendered HTML in its JSON payload. The existing preview JS shows `summary.plain_text`, so a small JS change is needed for the maternity block to appear **in the preview modal specifically**; the main summary tab, print and history surfaces are fully wired. |
| R2 | The current-record fetch uses a small inline `fetch()` in a `@once` push, matching the project's existing lightweight pattern. If the app later standardises on a partial-loader helper, this should move to it. |
| R3 | Print renders through dompdf; very long newborn tables could paginate awkwardly. Compact mode is used, but the layout has not been proven against a 5+ newborn delivery on paper. |
| R4 | Integrity verification runs per rendered snapshot row in history (in PHP, no queries). With the 20-version cap this is negligible, but it is CPU work proportional to payload size. |

## 18. K1 closure

**K1 is CLOSED.** Every criterion is met and test-enforced:

- the real summary surface renders the live projection for an active consultation;
- a completed consultation defaults to its completion snapshot, without loading live maternity data;
- snapshot history is visible, scoped and navigable;
- current live data is visually separate, explicitly requested and permission-controlled;
- print uses the snapshot for a completed consultation;
- a consultation with no snapshot states so and fabricates nothing;
- no snapshot mutation route, billing action, specialty entry or clinical mutation was introduced.

## 19. Rollout and rollback

**Rollout:** keep all flags false → enable advisory readiness → enable the live summary → review with Obstetrics clinicians → enable snapshot capture for a controlled pilot → complete one consultation → change maternity data and confirm the snapshot is unchanged → reopen/recomplete and confirm versioning → test print → test a completed consultation with no snapshot.

**Rollback:** disabling readiness removes the card; disabling the summary removes the maternity section and returns the previous rendering exactly; disabling capture stops new snapshots while existing rows remain immutable, readable and undeleted. No destructive migration rollback.

## 20. Next phase

**14R.7 — O&G/Maternity manual-test data, environment reconciliation review, pilot acceptance and wider regression.**
