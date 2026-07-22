# Phase 1B exit report

## Current determination

**Status: complete.** Phase 1B evidence closure and governance-pack preparation passed independent review on 2026-07-21. Later that day, the project owner directly accepted all 36 controlled outcomes and resolved all 29 business-policy questions. The completed decision-authority package then passed independent migration review with no remaining evidence-backed defects. No workshop occurred; the governance packs are superseded by final-authority decision.

Phase 2 detailed mapping may begin. This does not authorize importer implementation or database writes.

## Scope and safety attestation

- Approved Classic schema inspected: `uuhms` only, using SELECT-only command code and aggregate/metadata queries.
- Actual target inspected: local non-production `uhms_clean`, using SELECT-only metadata queries.
- Neither database was modified. `DatabaseSeeder` was not run.
- No importer, migration-state table, Migration Center UI, incremental-sync implementation, orchestration, or production-writing path was added.
- The existing Classic account is write-capable at schema level and is rejected for migration execution; D-101 requires a dedicated SELECT/metadata-only account.
- Evidence outputs contain schema metadata, aggregate counts, safe allow-listed categories and normalized query text; they are designed to exclude patient-identifying values and clinical free text.

## Exit-criteria assessment

| Criterion | Status | Evidence / remaining action |
|---|---|---|
| Actual non-production target captured and fingerprinted | Met | 335 tables, 5,347 columns; structural/ledger fingerprint `12e3a4c6ca80a0c1a54ea9267d9c4935453b7a36d27c7f8ad900062c1c12bff0` |
| Classic evidence regenerates without PHI | Met; independently verified | `legacy-migration:capture-classic-evidence`; privacy/query manifests under `evidence/` |
| Every critical source relationship has predicate and sentinel evidence | Met; independently verified | Relationship manifest contains predicate, null/zero/match/orphan counts, including zero-orphan outcomes; relationships remain inferred because Classic has no FKs |
| Target constraints and service-only invariants documented | Met | Installed constraint manifest, drift report, target rules and side-effects documents |
| Controlled decision packs ready | Met; independently verified | Nine packs preserve evidence, counts, options, risks, recommendations, owners, blocked domains, and controlled outcomes |
| Every business-policy blocker resolved | Met by owner directive | 36/36 controlled outcomes Accepted; 29/29 Phase 1B questions resolved |
| No importer or production-writing code introduced | Met | Governance changes are documentation only |
| Independent reviewer confirms evidence and decision-authority package fitness | Met | Phase 1B reviewer passed all eight evidence-closure criteria; the final authority update passed a separate independent migration review with no remaining defects |

## Confirmed Phase 1B findings

- Classic remains 55 tables, 479 columns and 1,548,058 exact rows on MariaDB 10.4.32, with no declared foreign keys.
- Target `uhms_clean` contains 335 base tables, 5,347 columns, 335 primary keys, 162 unique constraints, 1,202 foreign keys, 63 JSON validity checks, 2,150 indexes, one generated column, zero triggers, zero scheduled events and zero stored routines.
- Target migration ledger is 305/306 repository files. The absent file is a permission-data migration, not schema DDL.
- The checked-in schema dump is stale: 106 tables/133 ledger rows versus 335/305 installed.
- `invoices.active_visit_id` exists, but intended unique index `uq_invoices_active_visit` is absent; only the non-unique fallback exists.
- Route/medical-record link integrity, active-admission uniqueness, same-day visit rules, journal balancing, payment allocation and stock-balance rules rely partly or entirely on service validation.
- Classic finance and stock evidence supports classified exceptions and approved labelled openings, not reconstruction of allocations, GL entries, or stock movement history.

## Accepted policy and retained technical work

The canonical policies are in [APPROVED_DECISION_SPECIFICATIONS.md](APPROVED_DECISION_SPECIFICATIONS.md). Business policy for identity, actors, statuses, dates, sentinels/orphans, clinical history, insurance, finance, stock, privacy/audit, extraction, isolation, and cutover is resolved.

Phase 2 must still define column maps, transformation rules, target representations, exception codes/SLAs, reconciliation contracts, per-table extraction strategies, the migration audit, and isolated persistence/runtime controls. Those tasks are not unsigned governance blockers, but importer implementation remains blocked until they are complete and independently reviewed.

## Exact next action

Begin **Phase 2A — Organisation and Reference Crosswalk Specifications** under the sequence in [PHASE_2_ENTRY_REPORT.md](PHASE_2_ENTRY_REPORT.md). Do not implement importers or write either database.
