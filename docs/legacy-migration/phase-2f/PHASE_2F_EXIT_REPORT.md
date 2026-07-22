# Phase 2F exit report

Status: **PASSED 2026-07-22. Specification only; no pilot execution or persistence is authorized.**

## Exit assessment

| # | Criterion | Current result |
|---:|---|---|
| 1 | Pilot scope bounded and explicit | Pass: three cohort types; source cohort maximum 148 protected roots |
| 2 | Synthetic and protected source-derived cohorts defined | Pass |
| 3 | Every mandatory scenario has an expected outcome | Pass: 66 scenarios |
| 4 | Phase 2A–2E contracts composed without contradiction | Pass: independent review confirmed all prior semantic findings closed |
| 5 | Every pilot source field has one authoritative mapping | Pass: 33 fields; 15 Phase 2C + 7 Phase 2D + 11 Phase 2E |
| 6 | Protected remediation inputs specified | Pass |
| 7 | Patient and insurance target-state matrices complete or commit-blocking | Pass; commit mode blocked where representation/approval is absent |
| 8 | Dependency sequencing deterministic | Pass: stages 0–23; identity sealed before child stages |
| 9 | Future atomic boundaries defined | Pass: Units A–D |
| 10 | Protected interfaces defined | Pass: crosswalk, provenance, quarantine and reconciliation |
| 11 | Dry-run inputs/outputs complete | Pass; zero-write only |
| 12 | Idempotency and rerun outcomes deterministic | Pass for specification |
| 13 | Target collision handling complete | Pass for specification; refresh remains mandatory |
| 14 | Rollback/resume expectations complete | Pass; no universal hard-delete claim |
| 15 | Reconciliation thresholds exact and fail-closed | Pass: unexplained difference tolerance zero |
| 16 | Expected exceptions distinct from failures | Pass |
| 17 | Privacy-safe evidence and artifact scan specified | Pass |
| 18 | Phase 3 foundation requirements complete | Pass |
| 19 | Machine specifications parse/cross-reference | Pass: 21 specifications; focused validation passes |
| 20 | Independent review has no Critical/High finding | Pass: Critical 0, High 0, Medium 0, Low 0 |
| 21 | No prohibited implementation or write introduced | Pass |

## Mandatory blockers carried forward

Frozen/hash-bound Cohort B predicates and capacity queries; protected name and required-field remediation; approved coherent patient-state/timestamp tuple; approved insurance `member_type`, `is_active`, `is_primary` and timestamp initialization; approved provider-crosswalk runtime; D-101 least-privilege `uuhms` account; protected crosswalk/remediation/provenance/quarantine/reconciliation/audit storage; HMAC key management; migration-safe patient-number allocation; migration-specific persistence; complete side-effect isolation; fresh target collision checks; and tested idempotency/checkpoint/resume/compensation infrastructure.

These block commit-mode execution, not completion of a sound Phase 2F specification.

## Independent review

**PASS.** The final independent reviewer reports Critical 0, High 0, Medium 0 and Low 0. All six prior High and four prior Medium findings are independently verified closed. The reviewer reran the focused Phase 2F suite: 15 tests and 18,449 assertions passed.

Phase 2F is complete and the restricted Phase 3 migration-foundation work below may begin. This does not make Cohort B dry-run ready, authorize a patient pilot or authorize any importer.

## Exact recommended Phase 3 prompt

> Act as the Lead Architect and Orchestrator for Phase 3 — Migration Foundation and Safety Infrastructure Implementation. Read AGENTS.md and all files under docs/legacy-migration/, including the independently approved Phase 2A–2F packages. Treat APPROVED_DECISION_SPECIFICATIONS.md, DECISIONS.md and the Phase 2F machine-readable interfaces as authoritative. Implement only the shared migration foundation required before any domain importer: exact environment/schema/version guards; verification of a dedicated SELECT/metadata-only `legacy_uhms` account scoped to `uuhms`; coordinated source and target snapshot/run manifests; protected crosswalk, remediation, provenance/history, quarantine/exception, reconciliation and migration-audit storage; domain-separated HMAC/key management; target-collision snapshots; idempotency keys; checkpoints; resume and rollback/compensation state; migration-safe patient-number allocation/reservation; complete queue, scheduler, notification, integration, activity, billing, stock and audit side-effect isolation; and privacy-safe dry-run reporting. Use separate implementation, security/privacy, database-integrity and independent-review subagents with non-overlapping ownership. Do not implement patient, staff, reference or insurance importers; do not execute the patient pilot; do not access any Classic schema except read-only `uuhms`; do not use the broad-privilege Classic account for execution; do not write to production. Add focused foundation safety tests and obtain independent migration review before authorizing any pilot importer work.
