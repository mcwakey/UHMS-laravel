# Master migration plan

## Objective

Deliver a safe, repeatable, resumable, auditable migration from stakeholder-approved data in Classic `uuhms` to renewed UHMS. A one-click experience may later invoke the pipeline, but cannot replace explicit preflight, mapping, transformation, chunking, checkpoints, reconciliation, and classified failure handling.

## Non-negotiable controls

- Treat Classic as read-only and use a dedicated account limited to `SELECT`/metadata on `uuhms`.
- Refuse any source other than the allow-listed schema and verify a schema fingerprint before every run.
- Never run migration tests against renewed production.
- Never expose identifying data in logs, fixtures, screenshots, documents, or source control.
- Maintain explicit legacy-record-to-target-record mappings; do not reuse source primary keys as target keys.
- Support dry-run, idempotency, resumability, bounded chunks, checkpoints, reconciliation, and classified failures.
- Preserve valid historical timestamps and actors. Never manufacture contemporaneous-looking history.
- Suppress operational side effects and schedulers while temporary/incomplete imported state exists.
- Reconcile money exactly with decimal arithmetic, or fail into explicit exceptions.

## Discovery baseline (confirmed)

- Source: stakeholder-confirmed `uuhms`, MariaDB 10.4.32, 55 tables, 1,548,058 rows, no declared foreign keys.
- Target repository: 306 migrations, approximately 322 statically created table names, 315 models, 467 services, 75 form requests, and 156 enum classes at discovery time. The installed non-production target capture contains 335 tables, 5,347 columns, and 1,202 foreign keys.
- Target schema dump is incomplete/stale; conditional/raw migrations mean installed schemas can diverge.
- Source relationships are logical/inferred and contain many unmatched identifiers and sentinel zeros.
- Target operational pathways have material billing, inventory, accounting, notification, queue, bed, activity, and current-context effects.

## Programme phases and gates

| Phase | Deliverable | Exit gate |
|---|---|---|
| 1A. Initial discovery | This reviewed discovery package | All known gaps recorded without guessed mappings |
| 1B. Evidence closure | Actual non-production target catalogue; complete reproducible source schema/edge/query manifests; controlled governance packs | Installed target constraints verified; direct owner decision closes all business-policy questions |
| 2. Detailed mapping | Column-level maps, transformations, value crosswalks, exception codes, reconciliation and extraction contracts | Every field has an owner-approved/evidence-backed rule; no unknown defaulting; independent review complete |
| 3. Foundation | Separate connections, read-only enforcement, run/checkpoint/map/failure/audit model, dry-run framework | Safety tests prove Classic writes impossible and target production rejected |
| 4. Patient pilot | Synthetic/anonymised pilot for patients and child records | Counts, uniqueness, required fields, identifiers, rerun, resume, and rollback strategy verified |
| 5. Reference and clinical migration | Importers by dependency order | Per-domain reconciliation and exception acceptance |
| 6. Financial and stock migration | Approved source-evidenced facts/opening-position strategies and exact reconciliation | No invented history; supported invoices/claims/openings exact; stock opening/source facts exact |
| 7. Incremental sync | Watermarks or approved snapshots/change capture | No gaps/duplicates; mutable/untimestamped tables handled |
| 8. Cutover | Staged freeze, final read-only delta, reconcile, owner go/no-go, activate | Project-owner go/no-go recorded; thresholds pass; operational integrations restored safely |

## Proposed migration architecture

The later implementation should contain:

1. **Preflight**: validate environment, source name/fingerprint, privileges, target non-production status for testing, migrations/schema contract, connectivity, disk space, and side-effect switches.
2. **Consistent extraction coordinate**: an approved snapshot/change-capture boundary for a live Classic source, including keyless and untimestamped tables; a plain last-ID checkpoint is not sufficient.
3. **Run manifest**: immutable run identity, mode, scope, source fingerprint, target fingerprint, code version, approvals, timestamps, extraction coordinate, and result. Enforce one active compatible run.
4. **Record map**: domain, tokenized/HMACed source reference where feasible, target table/key, source checksum, transformation version, state, and run provenance. Apply least privilege, encryption, retention, deletion, and export controls because maps remain patient-linkable.
5. **Importer contract**: dry-run and commit modes; deterministic extraction order; chunk checkpoint; validation; transformation; persistence; reconciliation; classified failure.
6. **Atomic chunk/graph boundary**: target business writes, maps, failures, reconciliation outcome, and checkpoint advance commit together, or an explicit roll-forward/compensation record is created. Define recovery for crashes before/after each boundary and for detected prohibited effects.
7. **Migration persistence boundary**: bypass operational creation services and Eloquent events where history would otherwise trigger runtime actions, while still applying target FK, enum, uniqueness, precision, and aggregate invariants.
8. **Failure ledger**: never discard. Record a minimized/tokenized source reference, classification, rule/version, retryability, and approved disposition under access, encryption, retention, deletion, and export controls.
9. **Reconciliation**: counts and checksums per chunk/domain; financial and stock equations; relationship/orphan totals; sequence and active-state checks.
10. **Shared orchestration services**: future web and Artisan entry points call the same services.

## Approved Phase 2 detailed-mapping sequence

1. Organisation and reference crosswalks.
2. Staff identity and historical attribution.
3. Patient identity and registration.
4. Patient aliases, contacts and demographic children.
5. Patient insurance.
6. Patient pilot mapping contract.
7. Visits and workflow.
8. Clinical history.
9. Admissions.
10. Billing and claims.
11. Pharmacy and stock.
12. Incremental synchronization and cutover.

This sequence authorizes specifications only. The later implementation dependency sequence below is not activated until the relevant Phase 2 maps, transformations, exceptions, reconciliations, and extraction contracts are complete and the migration foundation is reviewed.

## Preliminary implementation domain sequence

1. Explicitly allow-listed system/reference seeders and mapped actors. Never run `DatabaseSeeder` for a migration target because it also invokes demo-user, patient, and appointment seeders.
2. Departments, clinical catalogues, insurance, suppliers, products/drugs, locations, wards/beds, accounting references.
3. Patients and patient children/aliases/insurance.
4. Appointments without forward links.
5. Visits and encounter histories.
6. Consultation routes and medical records.
7. Clinical children, orders, investigations, results, treatments, medications.
8. Admissions/nursing/discharge where approved.
9. Approved source-evidenced billing/claim/settlement facts and/or opening positions; renewed allocations/refunds/credits start empty unless directly evidenced.
10. Source-evidenced procurement/stock facts and/or an explicitly approved derived opening position; do not claim a complete Classic movement ledger.
11. Source-evidenced financial facts and/or an explicitly approved derived opening position; do not invent allocations, refunds, credits, journals, or subledgers.
12. Deferred links and polymorphic references.
13. Rebuild/reconcile identifiers, active state, finance, stock, claims, queues, beds, and schedulable records.

Detailed constraints are in `MIGRATION_DEPENDENCIES.md`.

## Discovery contradictions and resolutions

| Topic | Evidence tension | Resolution/status |
|---|---|---|
| Classic database identity | Three Classic-shaped schemas exist | `uuhms` stakeholder-confirmed; preflight must reject the others |
| Source row total | An early estimate was about 1.68M | Replaced by fresh exact sum: 1,548,058 |
| Legacy foreign keys | Columns look relational | Confirmed zero declared FKs; all source relationships documented as inferred |
| Target schema baseline | Dump contains 106 tables; migrations define roughly 322 | Actual installed target must be preflighted; neither artifact alone is sufficient |
| Billing equation | Candidate source equation has mismatches | Preserve all source amounts, calculate with decimal arithmetic, and quarantine differences over 0.01 from opening AR/GL |
| Claim equation | Reported `ClaimTotal` can differ from component sum | Preserve reported total and components independently; calculate separately and quarantine differences over the fixed 0.01 tolerance from posting |
| Clinical empty fields | Plain and RTF/catalogue alternatives coexist | Preserve representations, sanitize RTF, derive safe display text, report conflicts, and never synthesize empty results |
| Shared status/date rules | One global map/coercion could erase domain semantics | Separate status crosswalks and field-specific date rules; unknown/invalid values fail closed |
| Historical persistence | Normal services enforce current-time workflows and side effects | Use validated migration-specific persistence in an isolated runtime; no global weakening of production validation |
| Active target invariants | Some are service-only, not DB-enforced | Import validators must explicitly reproduce approved invariants |

## Critical risks

- Wrong-source selection or accidental Classic writes due to broad account privileges.
- Patient collision/incorrect merge because source OPD numbers are incomplete and duplicated.
- Orphaned logical relationships caused by missing/sentinel/stale IDs.
- Historical inaccuracy from current dates, generated numbers, current users, or regenerated logs.
- Live notification/SMS/audit/queue/billing/stock/accounting/bed side effects.
- Double-counting if operational financial/stock history and opening balances are both imported.
- Non-reproducible target behavior if actual schema differs from conditional migration expectations.
- Cascading target FKs if idempotency is attempted by deleting/recreating parent aggregates.
- Incremental gaps across 23 source tables with no timestamps and mutable `ON UPDATE` timestamps.
- Resume corruption for keyless source tables unless snapshot ordering and row identity are explicitly designed.
- Synthetic demo/patient/appointment contamination if the repository-wide `DatabaseSeeder` is run.
- Duplicate normalized OPD aliases and multiple Classic insurance rows colliding with target uniqueness constraints.

## Required future safety tests

- Database-enforced Classic write denial; wrong-source and target-production rejection.
- Actual-target schema/constraint drift rejection and prohibition of `DatabaseSeeder`.
- Crash immediately before/after business writes, map/failure writes, reconciliation, and checkpoint advance.
- Parallel-run exclusion, idempotent rerun conflicts, and keyless-table resume from an approved snapshot.
- No SMS, email, notification, queue, provider, audit-forwarding, billing, GL, stock, bed, or scheduler effects.
- Failure/map redaction, authorization, encryption, export restriction, retention, and deletion.
- Decimal negative/overflow/rounding cases and roll-forward/compensation of partial financial or clinical graphs.

## Current written handoff

Phase 1B generated the source/target catalogues, query manifest, installed non-production target inspection, and nine controlled governance packs. The project owner accepted all 36 controlled outcomes directly on 2026-07-21; no workshop occurred and no additional approval is required. All 29 business-policy questions are resolved and every domain may enter Phase 2 detailed mapping in the sequence above.

No implementation is authorized. Importers, migration-state tables, UI/orchestration, synchronization runtime, target writes, production tests, and cutover remain blocked until column maps, transformation specifications, exception codes/SLAs, reconciliation contracts, per-table extraction rules, and the reviewed migration foundation exist.
