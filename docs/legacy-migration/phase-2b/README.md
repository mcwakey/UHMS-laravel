# Phase 2B — Staff identity and historical attribution mapping specifications

Status: **Specification package complete; implementation remains unauthorized.**

## Authority and boundary

D-202, D-008 and Q-304 in [APPROVED_DECISION_SPECIFICATIONS.md](../APPROVED_DECISION_SPECIFICATIONS.md) and [DECISIONS.md](../DECISIONS.md) are the policy ceiling. Only Classic `uuhms` was inspected, read-only. No importer, migration/state table, schema change, user/historical identity, role, permission, seeder, synchronization runtime, UI or database write was introduced.

Evidence labels:

- **Confirmed** — installed schema, repository code or sanitized aggregate evidence.
- **Approved policy** — direct project-owner decision.
- **Inference** — a plausible interpretation that is not mapping authority.
- **Technical specification** — a fail-closed Phase 2 contract within approved policy.
- **Phase 3 prerequisite** — required foundation design or control; not implemented here.

## Package summary

- 11/11 Classic `users` columns classified exactly once.
- 11 identity classes and six verified matching rungs.
- 28 actor/actor-like columns found by scanning all 55 Classic tables: nine numeric `USER_ID` relations, nine free-text `Doctor` fields and ten explicitly excluded/ambiguous actor-like fields.
- 28 relationship-specific sentinel/baseline rules; no global zero rule.
- 32 stable exception codes, 142 reconciliation contracts, 30 extraction strategies and 116 downstream records, including every one of the 53 installed non-nullable user FKs.
- Credentials, permission values, raw staff identity/contact values and PHI are absent from the package.

The canonical machine contracts are in [specifications](specifications/). The eleven required specifications are accompanied by `staff_evidence_trace.json`. Exact normalized SQL, bindings, execution timestamps, query hashes and sanitized result hashes are recorded in [PHASE_2B_STAFF_QUERY_MANIFEST.json](../evidence/PHASE_2B_STAFF_QUERY_MANIFEST.json) and [PHASE_2B_STAFF_AGGREGATE_RESULTS.json](../evidence/PHASE_2B_STAFF_AGGREGATE_RESULTS.json). Human-readable documents explain their use.

## Central conclusion

Classic contains no staff number, professional registration number, verified organization email, phone or specialty evidence. Consequently, Classic-only evidence verifies **zero** employment identities. All 86 source users require an approved protected HR crosswalk or manual HR verification before matching an existing renewed user or becoming a disabled historical-identity candidate.

The installed target has no dedicated historical identity marker. A normal `users` row is `Authenticatable` and `Notifiable`, requires unique email and password values, is audited by observers, and remains addressable by existing-session and password-reset paths. Although inactive users cannot complete normal login, inactive status alone is not the complete non-login guarantee required by D-202. [TARGET_STAFF_IDENTITY_CONTRACT.md](TARGET_STAFF_IDENTITY_CONTRACT.md) records the hardened historical-`users` Phase 3 prerequisite required by the 426 installed user FKs.

## Required use

1. Validate exact `uuhms` database, 55/479 shape and fingerprint.
2. Provision D-101 least-privilege source credentials before implementation.
3. Supply a protected HR crosswalk; never publish crosswalk values.
4. Resolve Phase 2A department crosswalk dependencies.
5. Complete the Phase 3 historical-identity/non-login foundation.
6. Reconcile every source staff row and actor reference to one mutually exclusive outcome.
7. Obtain implementation review before any persistence work.

Phase 2C patient identity mapping may consume this specification. It does not authorize a patient or staff importer.
