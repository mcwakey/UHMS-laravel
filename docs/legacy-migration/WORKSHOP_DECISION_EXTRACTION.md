# Workshop decision extraction

## Authority and interpretation

All rows below were accepted by direct project-owner decision on 2026-07-21. Approver: `mcwakey` (established repository identity), acting as Project Owner and Final Decision Authority. Reference: project-owner directive recorded in the migration decision register. No workshop was conducted and no other approver is required.

| Pack | Controlled outcomes | Status | Approved policy result | Phase 2 implementation item |
|---|---|---|---|---|
| Patient identity | D-201/Q-001 | Accepted | Renewed numbers; unique valid OPD aliases; protected Classic-PK crosswalk | Number/alias mapping contract |
| Patient identity | D-211/Q-008 | Accepted | No duplicate alias assignment; one exception per affected patient | Duplicate-alias review contract |
| Patient identity | Q-002 | Accepted | No automatic patient merge; separate patient chains | Post-migration identity-review handoff |
| Patient identity | D-213/Q-007 | Accepted | Evidenced remediation or chain quarantine; no invented identity values | Required-field validity and quarantine codes |
| Patient identity | Q-006 | Accepted | Orphan encounter and all dependants quarantined as one chain | Chain traversal and release contract |
| Staff attribution | D-202/Q-003 | Accepted | Disabled historical identities; constrained unknown actor; no current-user attribution | Staff/actor crosswalk and unknown-actor rule |
| Staff attribution | Q-304 | Accepted | Verified employment identity; credentials and permissions excluded | HR-verifiable matching specification |
| Visit/workflow | D-205/Q-004 | Accepted | Explicit value crosswalks; no defaults | Domain value maps and exception codes |
| Visit/workflow | D-206/Q-301 | Accepted | Preserve valid dates; field-specific unknowns; quarantine invalid chronology | Per-field date rules |
| Visit/workflow | Q-302 | Accepted | Equal dates require corroborating admission evidence | Admission corroboration predicates |
| Visit/workflow | D-207/Q-005 | Accepted | Per-relationship sentinel rules; no artificial parents | Join/sentinel contracts |
| Visit/workflow | Q-010 | Accepted | Evidence-backed appointments; renewed numbers; quarantine ambiguity | Appointment mapping contract |
| Visit/workflow | D-214/Q-203 | Accepted | Validated migration-specific persistence with side-effect suppression | Persistence-boundary design and tests |
| Clinical history | D-214/Q-011 | Accepted | Evidence-backed grouping/clinician; ambiguity quarantined | Grouping and logical-uniqueness contract |
| Clinical history | D-208/Q-201 | Accepted | Preserve representations; sanitize RTF; protected provenance | Field display/sanitization mappings |
| Clinical history | Q-202 | Accepted | Preserve valid order; result not evidenced; no synthesis | Result-state/exception design |
| Clinical history | Q-204 | Accepted | Evidenced facts only; separate migration audit | Migration-audit contract |
| Insurance/claims | D-212/Q-009 | Accepted | Deterministic current membership; all rows retained in provenance | Membership consolidation contract |
| Insurance/claims | D-206/Q-301 | Accepted | Field-specific unknown date representation; invalid chronology quarantined | Insurance/claim date mappings |
| Insurance/claims | D-205/Q-004 | Accepted | Explicit payer/claim status maps; no assumption for `ARCHIVES` | Payer/status crosswalks |
| Insurance/claims | Q-102 | Accepted | Reported `ClaimTotal` retained; component check; differences over 0.01 fail closed | Decimal claim reconciliation contract |
| Finance | D-203/Q-103 | Accepted | Evidenced facts plus approved labelled openings; unsupported histories empty | Opening-balance and anti-double-count contract |
| Finance | D-107/Q-101 | Accepted | Preserve four source amounts; decimal equation; mismatches fail closed | Billing reconciliation contract |
| Finance | Q-102 | Accepted | Apply one claim contract and fixed 0.01 tolerance consistently in claims and finance | Shared claim reconciliation implementation spec |
| Stock | D-204/Q-104 | Accepted | Preserve snapshots; labelled openings only; negatives excluded pending review | Product/location/opening-stock contract |
| Stock | Q-105 | Accepted | Expiry gate; expired stock quarantined; pharmacist disposition | Batch identity and disposition contract |
| Privacy/retention | D-209/Q-303 | Accepted | No operational notification content; aggregate-only evidence | Zero-import/zero-trigger control |
| Privacy/retention | Q-305 | Accepted | Empty maternity/occupation excluded; changed fingerprint stops scope | Pre-cutover scope guard |
| Privacy/retention | D-106/Q-204 | Accepted | Separate labelled migration audit; no false operational events | Provenance/audit design |
| Privacy/retention | D-008 | Accepted | Classic credentials and permission flags excluded | Security assertions and tests |
| Incremental/cutover | D-210/Q-401 | Accepted | Snapshot/hash for untimestamped tables; approved strategy for high-risk tables | Per-table extraction design |
| Incremental/cutover | Q-402 | Accepted | Reliable composite watermarks, overlap/reconcile, or snapshot/hash | Per-table watermark contract |
| Incremental/cutover | Q-403 | Accepted | Staged freeze/final delta; explicit thresholds; owner go/no-go | Cutover runbook and thresholds |
| Incremental/cutover | Q-404 | Accepted | Environment isolation and complete integration pause/restore | Isolation inventory and verification |
| Incremental/cutover | D-101 | Accepted | Dedicated SELECT/metadata-only account on `uuhms` | Provision and verify restricted account |
| Incremental/cutover | D-102 | Accepted | Exact schema/connection/version/fingerprint guards | Versioned fail-closed guard contract |

The complete policy wording and limitations are authoritative in [APPROVED_DECISION_SPECIFICATIONS.md](APPROVED_DECISION_SPECIFICATIONS.md) and the controlled outcome record in each pack.
