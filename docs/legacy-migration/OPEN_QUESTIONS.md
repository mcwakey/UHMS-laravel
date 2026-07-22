# Open-question resolution register

## Status

All 29 Phase 1B business-policy questions were resolved by direct project-owner decision on 2026-07-21. Approver: `mcwakey` (established repository identity), Project Owner and Final Decision Authority. No workshop, committee, quorum, additional signature, or meeting minutes were required or held.

A “Phase 2 task” below is a technical specification needed to implement the accepted policy. It is not an open governance approval and does not authorize an importer.

## Patient, staff, and mapping policy

| ID | Status | Approved answer | Retained Phase 2 task |
|---|---|---|---|
| Q-001 | Resolved | Generate renewed patient numbers; retain unique valid OPDs as typed aliases; Classic PK only in protected crosswalk. | Specify eligibility, normalization, sequence, alias, and reconciliation rules. |
| Q-002 | Resolved | Never merge automatically; import eligible Classic patient chains separately; send candidates to post-migration identity review; names alone are insufficient. | Define candidate-review handoff and evidence without an auto-merge rule. |
| Q-003 | Resolved | Verified disabled historical identities; constrained Legacy Actor Unknown only where no valid actor is determinable; never current/importer attribution. | Define staff crosswalk, unknown-actor eligibility, and actor exception codes. |
| Q-004 | Resolved | Separate explicit domain value crosswalks; unknown/blank/out-of-domain values are exceptions, never defaults. | Enumerate every source value and target semantic per field/domain. |
| Q-005 | Resolved | Decide sentinels per relationship; no global zero rule; never fabricate parents. | Approve each join predicate, sentinel outcome, and relation exception code. |
| Q-006 | Resolved | Quarantine an orphan encounter and its complete dependent chain; never attach it to a guessed/generic patient. | Define chain traversal, quarantine, release, and reconciliation. |
| Q-007 | Resolved | Use evidenced remediation or quarantine; never invent identity values or reassign dependent records. | Specify field validity, reason codes, and any separately controlled unknown representation. |
| Q-008 | Resolved | Do not assign duplicate OPD aliases automatically; protect provenance and create one exception per affected patient; manual identity review only. | Define duplicate-group review and alias-release controls. |
| Q-009 | Resolved | Create a current patient/provider membership only when deterministic; preserve every source row; quarantine conflicting candidates. | Define deterministic selection and protected history representation. |
| Q-010 | Resolved | Import only evidence-complete appointments, generate renewed numbers, preserve Classic keys only in crosswalk, and quarantine ambiguity. | Define field derivations, historical validation, exception codes, and counts. |
| Q-011 | Resolved | One route/medical record per attendance only with one evidenced clinician and compatible context; quarantine ambiguity and validate logical uniqueness. | Define grouping, context, clinician predicates, and validation. |

## Finance and stock policy

| ID | Status | Approved answer | Retained Phase 2 task |
|---|---|---|---|
| Q-101 | Resolved | Preserve Bill/Discount/Paid/Balance; calculate the decimal equation; differences over 0.01 fail closed from opening AR/GL. | Define fields, signs, precision, mismatch codes, aggregates, and reviewed disposition. |
| Q-102 | Resolved | Preserve reported ClaimTotal and all components; calculate components in decimal; differences over 0.01 fail closed from receivable/opening/GL; invent no payments. | Define one shared claims/finance reconciliation contract using the fixed 0.01 tolerance. |
| Q-103 | Resolved | Preserve approved facts and only labelled approved openings; unsupported allocation/refund/credit/receivable/GL/journal histories start empty; prevent double count. | Define source-fact/opening contracts and posting gates. |
| Q-104 | Resolved | Preserve snapshots as evidence; create only mapped, approved labelled openings; exclude negatives until reviewed; invent no history or valuation. | Define product/unit/location maps, opening equations, deduplication, and negative exceptions. |
| Q-105 | Resolved | Expiry dates are safety gates; expired quantities are unavailable and quarantined pending pharmacist disposition; batch number alone is not identity. | Define batch identity, expiry coordinate, representation, disposition, and reconciliation. |

## Clinical transformation

| ID | Status | Approved answer | Retained Phase 2 task |
|---|---|---|---|
| Q-201 | Resolved | Preserve source representations, sanitize RTF, derive safe display, protect provenance, report conflicts, and never concatenate blindly. | Define field-by-field display/sanitization/retention rules. |
| Q-202 | Resolved | Preserve a valid order/request with result not evidenced; never synthesize or default clinical meaning. | Define target state/exception and independent order/result reconciliation. |
| Q-203 | Resolved | Use migration-specific validated persistence with complete side-effect suppression; never use normal operational services or weaken global validation. | Design persistence boundary, invariant checks, and isolation tests. |
| Q-204 | Resolved | Import source-evidenced facts/timestamps only and use a separate labelled migration audit; do not simulate workflow/activity. | Define migration-audit records, access, retention, and reconciliation. |

## Dates, retention, and staff security

| ID | Status | Approved answer | Retained Phase 2 task |
|---|---|---|---|
| Q-301 | Resolved | Preserve valid dates; field-specific unknown representation only where supported; quarantine impossible/reversed/implausible chronology; never coerce. | Define rule and target representation for each date field. |
| Q-302 | Resolved | Equal admission/discharge dates alone do not prove admission; require corroborating evidence and quarantine ambiguity. | Specify corroborating inpatient/bed/ward predicates. |
| Q-303 | Resolved | Exclude Classic notification content from operational tables; aggregate-only non-identifying evidence unless a new legal decision is made; trigger nothing. | Define zero-import/zero-trigger checks and aggregate reconciliation. |
| Q-304 | Resolved | Match staff through verified employment identity, not usernames; credentials/permissions are excluded and renewed access is separate. | Define verifiable attributes and duplicate/unverified review. |
| Q-305 | Resolved | Exclude zero-row maternity/occupation baseline; fingerprint/row-count change is a stop requiring scope review. | Implement the pre-cutover scope guard after foundation approval. |

## Incremental synchronization and cutover

| ID | Status | Approved answer | Retained Phase 2 task |
|---|---|---|---|
| Q-401 | Resolved | Snapshot/hash small/master untimestamped tables; use approved freeze/snapshot/CDC where needed for high-risk mutable tables; infer no deletions. | Select and test extraction per table. |
| Q-402 | Resolved | Use reliable `(timestamp, primary key)` watermarks, overlap/reconcile mutable timestamps, and snapshot/hash where no watermark exists. | Specify ordering, overlap, deletion evidence, indexes, and reconciliation per table. |
| Q-403 | Resolved | Staged freeze plus final read-only delta; explicit thresholds; no activation with critical differences; project owner is final go/no-go authority. | Define stages, dates, thresholds, rollback, and future go/no-go record. |
| Q-404 | Resolved | Environment-isolated runtime with full queue/scheduler/integration/domain-side-effect pause/restore; model event suppression alone is insufficient. | Produce and test the isolation inventory, preflight, and restoration runbook. |

## Closure effect

There are **zero unsigned Phase 1B business-policy questions**. Phase 2 must not reinterpret a policy while filling technical detail. New evidence that genuinely conflicts with an approved policy must be escalated as a new controlled decision rather than silently changing this register.
