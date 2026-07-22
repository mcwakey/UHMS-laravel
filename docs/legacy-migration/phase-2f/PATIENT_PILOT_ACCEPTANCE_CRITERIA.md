# Patient pilot acceptance criteria

## Exact threshold

Normative machine contract: `specifications/patient_pilot_acceptance_thresholds.json` version 2F.1.0. Every count/partition equation and every safety assertion has tolerance zero. Missing mandatory evidence is failure or a visible prerequisite blocker, never acceptance.

## Mandatory complete accounting

Require 100% classification of cohort rows, consumed fields, direct relationships, children, insurance rows, exceptions, quarantine roots and target collisions. Checkpoints and durable outcomes must agree, and each idempotency key may resolve to at most one compatible result.

## Safety zeroes

The full machine contract requires zero source/target writes during specification and dry-run; automatic/fuzzy/single-field identity actions; Classic-key reuse; number/alias/contact/membership duplicates; existing-target mutation/enrichment; invented patient, actor, provider, member or date facts; eligibility/verification fabrication; operational service/queue/notification/audit effects; source-row loss; missing provenance; PHI/raw identifiers/tokens in artifacts; unsafe hashes/HMAC comparisons; cross-chain release/reassignment; and unresolved reconciliation differences.

## Allowed nonzero outcomes

Classified expected exceptions, quarantined/remediation-pending patients, withheld aliases/optional children, successful absence, suspected-duplicate review flags, historical-only/provider-unresolved/unknown-date/conflicting insurance rows and immutable-target evidence may be nonzero.

They are acceptable only when expected by the scenario/stratum, assigned one primary bucket, fully coded and owned, rooted with an SLA/release condition, preserved in provenance, safe for the target and surrounded by zero-difference/zero-safety results.

The pilot requires zero unexplained or unsafe outcomes, not zero data-quality exceptions.

## Future pilot entry criteria

Phase 2F itself authorizes no execution. A future protected dry-run may enter only after independent acceptance of this contract; a bounded, versioned cohort manifest; the dedicated D-101 SELECT/metadata-only account on exact `uuhms`; exact source and non-production target guards/fingerprints; coordinated read-only snapshots; approved Phase 2A reference and Phase 2B actor inputs; protected HMAC, crosswalk, remediation, provenance, quarantine, reconciliation and audit capabilities; privacy-safe aggregate reporting; a fresh target-collision snapshot; and a runtime proven to write zero rows.

A future commit pilot additionally requires the implemented, tested and independently reviewed Phase 3 foundation; protected accepted required-field/name remediation for each candidate; an approved coherent patient-state/timestamp tuple; approved insurance `member_type`, `is_active`, `is_primary` and timestamp representations; the migration-safe patient-number allocator; migration-specific atomic persistence and complete side-effect isolation; tested idempotency/checkpoint/resume/compensation behavior; fresh precommit drift/collision checks; no Critical or High review finding; and separate explicit commit-pilot authorization.

## Future pilot exit criteria

Every selected root must have one terminal primary disposition; children, aliases and insurance rows must reconcile independently; every source row must retain provenance; every quarantine must have one valid root, owner, SLA and release condition; every equation difference and safety count must be zero; expected nonzero exceptions must be classified and never reported as success; existing-target mutation must be zero; rerun/resume must be duplicate-free; rollback or compensation evidence must be complete; the privacy scan must have zero unallowlisted findings; and independent review must report no Critical or High finding.

A passing dry-run exits only as `DRY_RUN_ACCEPTED_NOT_COMMIT_AUTHORIZED`. A commit exit verdict cannot exist until Phase 3 and separate commit-pilot authorization.

## Verdicts

- `FAIL_CLOSED`: leak, write, drift, safety/equation difference, unclassified outcome or missing provenance.
- `BLOCKED_PREREQUISITE`: safety/accounting pass but target representation or Phase 3 capability is absent.
- `DRY_RUN_ACCEPTED_NOT_COMMIT_AUTHORIZED`: complete exact dry-run evidence; still no write authority.
- `COMMIT_PILOT_ELIGIBLE_AFTER_PHASE3_REVIEW`: reserved for later independently reviewed foundation and commit authorization; Phase 2F cannot emit it.
