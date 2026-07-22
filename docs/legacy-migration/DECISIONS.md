# Migration decision register

## Authority and states

- **Accepted**: explicitly mandated by repository safety rules or approved by the project owner.
- **Proposed**: a technical recommendation outside the direct-owner workshop directive; it is not implementation authority.
- **Phase 2 task**: implementation detail required to realize an accepted policy; it is not an unsigned governance decision.

The 2026-07-21 approvals use this authority record:

| Field | Record |
|---|---|
| Approval method | Direct project-owner decision |
| Approving authority | Project Owner and Final Decision Authority |
| Approving stakeholder name | mcwakey (established repository identity; no legal name is asserted) |
| Approval date | 2026-07-21 |
| Signature/reference | Project-owner directive recorded in the migration decision register |
| Additional approvers | None required |
| Workshop status | Superseded by final-authority decision; no workshop occurred |

## Accepted baseline decisions

| ID | Decision | Evidence/authority | Consequence |
|---|---|---|---|
| D-001 | `uuhms` is the only authoritative Classic schema. | Owner instruction, 2026-07-20 | Reject `uhms`, `uuhmss`, `uhms_clean`, or any other Classic-shaped schema. |
| D-002 | Classic remains strictly read-only. | Repository `AGENTS.md` | No source DDL/DML, migrations, or write-capable migration execution. |
| D-003 | Discovery/decision work does not implement importers, state tables, UI, or orchestration. | Phase scope | Documentation and read-only evidence only. |
| D-004 | Unknown or malformed values are never silently defaulted or discarded. | Repository `AGENTS.md` | Explicit transformation or classified exception. |
| D-005 | Names alone never justify a patient merge. | Repository `AGENTS.md` and owner directive | No automatic patient merge during migration. |
| D-006 | Classic primary keys are not renewed primary keys. | Repository `AGENTS.md` and owner directive | Use protected cross-system mappings. |
| D-007 | Historical imports do not trigger operational side effects. | Repository safety rules and target evidence | Migration-specific isolated persistence is mandatory. |
| D-008 | Classic passwords and permission flags are excluded. | Owner directive, 2026-07-21 | Map approved identity/attribution only; provision renewed access independently. |

## Accepted architecture and execution-control decisions

| ID | Accepted decision | Authority | Phase 2 consequence |
|---|---|---|---|
| D-101 | Use a dedicated `SELECT`/metadata-only Classic account scoped to `uuhms`; reject the current broad account for execution. | Owner directive, 2026-07-21 | Provision and verify least privilege before connectivity tests. |
| D-102 | Verify connection, exact schema, fingerprint, 55-table/479-column shape, and compatible database version on every run; fail closed. | Owner directive, 2026-07-21 | Create a versioned schema-guard contract. |
| D-104 | Use a migration-specific validated persistence boundary for historical rows. | D-214/Q-203 owner directive, 2026-07-21 | Design and review persistence/invariant contracts before importers. |
| D-105 | Isolate queues, schedulers, notifications, integrations, and domain side effects during commit windows. | Q-404 owner directive, 2026-07-21 | Produce and test pause/restore and fail-closed isolation controls. |
| D-106 | Import only source-evidenced facts and use a separately labelled migration audit. | Q-204 owner directive, 2026-07-21 | Define provenance/audit schema and retention. |
| D-107 | Preserve reported financial fields and use decimal equation reconciliation with a fixed 0.01 billing and claim exception tolerance. | Q-101/Q-102 owner directive, 2026-07-21 | Define billing/claim reconciliation contracts and posting gates without changing the tolerance. |

## Accepted domain decisions

| ID | Accepted policy | Resolved questions | Consequence |
|---|---|---|---|
| D-201 | Generate renewed patient numbers; retain only unique valid OPD values as typed aliases; keep Classic PKs in the protected crosswalk. | Q-001 | Patient number/alias specification in Phase 2. |
| D-202 | Use verified disabled historical staff identities and a constrained Legacy Actor Unknown identity; never attribute history to current/importer users. | Q-003, Q-304 | Verified staff/actor crosswalk required. |
| D-203 | Preserve approved billing/claim facts and only labelled approved openings; unsupported allocation/refund/credit/GL history starts empty. | Q-103 | Finance opening/anti-double-count contract required. |
| D-204 | Preserve stock snapshots as evidence; create only approved labelled openings; never reconstruct movements or load negatives as available stock. | Q-104, Q-105 | Stock opening and expiry/negative exception contracts required. |
| D-205 | Use separate explicit domain value crosswalks; unknowns are exceptions and never defaults. | Q-004 | Per-field value maps required. |
| D-206 | Use field-specific date rules; preserve valid dates, support unknown only where designed, and quarantine invalid chronology. | Q-301, Q-302 | Per-field date and admission-evidence rules required. |
| D-207 | Approve sentinels per relationship; no global zero rule and no artificial parents. | Q-005, Q-006 | Relationship/sentinel and chain-quarantine contracts required. |
| D-208 | Preserve clinical representations, sanitize RTF, never synthesize results, and report conflicts. | Q-201, Q-202 | Clinical content/result specifications required. |
| D-209 | Exclude operational notification content; exclude empty maternity/occupation baseline with fingerprint stop; retain aggregate evidence only. | Q-303, Q-305 | Privacy and scope guards required. |
| D-210 | Choose deterministic extraction per table: reliable composite watermarks, overlap/reconciliation, or snapshot/hash/freeze/CDC as evidenced. | Q-401, Q-402, Q-403, Q-404 | Per-table extraction and cutover/isolation design required. |
| D-211 | Do not assign duplicate OPD aliases automatically; protect provenance and create one exception per affected patient. | Q-008 | Controlled identity review only. |
| D-212 | Create a current patient/provider membership only when deterministic; preserve all insurance source rows and quarantine conflicts. | Q-009 | Membership consolidation contract required. |
| D-213 | Never invent required identity fields; use evidenced remediation or quarantine the complete patient source chain. | Q-007 | Required-field and chain exception specification required. |
| D-214 | Use migration-specific validated persistence and only evidence-backed consultation grouping/clinician attribution; quarantine ambiguity. | Q-010, Q-011, Q-203 | Appointment and clinical grouping contracts plus persistence design required. |

Full authoritative wording is in [APPROVED_DECISION_SPECIFICATIONS.md](APPROVED_DECISION_SPECIFICATIONS.md).

## Remaining proposed technical decisions

| ID | Proposal | Current position |
|---|---|---|
| D-103 | Treat repository migrations plus domain code as the proposed target contract and verify installed non-production schema before mapping approval. | Actual target evidence is captured; the formal source-of-truth hierarchy should be documented in Phase 2. |
| D-108 | Never delete/recreate mapped parents to achieve idempotency. | Consistent with safety architecture but outside the direct workshop directive; decide in migration-foundation design review. |

Neither proposal blocks Phase 2 detailed mapping. Both remain blockers to relevant importer-foundation authorization until reviewed.

## Decision effect

All D-201 through D-214 are **Accepted**. Their business-policy questions are closed. Column-level mappings, transformation details, exception codes, reconciliation contracts, manual-review SLAs, extraction details, and runtime designs remain Phase 2 tasks—not implicit defaults and not importer authorization.
