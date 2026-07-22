# Patient pilot mapping readiness matrix

“Ready” below means contract-ready input to Phase 3 foundation work. It does not authorize pilot execution, importer implementation or database writes.

| Capability | Phase 2F specification | Future commit readiness | Remaining prerequisite |
|---|---|---|---|
| Phase 2A reference composition | Versioned upstream IDs; no invented parent | Blocked | Protected runtime crosswalks and target refresh |
| Phase 2B actor composition | `registered_by = null`; no fallback actor | Blocked | Migration audit and security-exclusion enforcement |
| Patient identity | Single Phase 2C owner per field | Blocked | Protected name/required-field remediation and state approval |
| Numbering and alias | Crosswalk-first; target-generated; duplicate aliases withheld | Blocked | Migration-safe allocator, collision refresh and atomic persistence |
| Optional demographics | Phase 2D field/tuple rules composed independently | Blocked | Parent commit, validators, protected provenance and child persistence |
| Insurance | Phase 2E history/current-selection rules composed | Blocked | Provider crosswalk, `member_type`/active policy and protected history store |
| Cohort A | 66 obviously fictitious scenarios with exact outcomes | Ready | Executable synthetic fixtures belong to Phase 3 tests |
| Cohort B | 24 ordered strata; maximum 148 protected roots; deterministic algorithm specified | Not ready for dry-run | Freeze/hash-bind all 24 predicates and incomplete capacity queries at the pinned snapshot, then prove disjoint precedence; D-101 account, snapshot manager, HMAC key and protected manifest |
| Cohort C | Protected target collision/immutability branches | Ready for future dry-run | Fresh target collision snapshot immediately before execution |
| State matrices | Explicit; unsafe defaults rejected | Contract complete, commit blocked | Owner-approved coherent patient tuple and insurance initialization |
| Dependency sequence | 24 stages, identity sealed before children | Ready | Phase 3 orchestration/checkpoint implementation |
| Atomicity | Four future units with independent child withholding | Ready | Transaction, ledger, staging/activation and compensation implementation |
| Protected interfaces | Crosswalk, provenance, quarantine and reconciliation specified | Ready | Encrypted stores, access control, retention and audit implementation |
| Dry-run | Complete zero-write inputs/outputs/verdict hierarchy | Ready | Runtime and privacy-safe reporter implementation |
| Idempotency/resume | Stable protected keys and crash branches specified | Ready | Durable mapping/checkpoint infrastructure |
| Reconciliation | Difference zero; safety zero; expected exceptions separate | Ready | Measured Phase 3 dry-run evidence |
| Privacy | Artifact-wide scan and domain-separated token rules | Ready | Secret/key management and CI enforcement |
| Independent review | PASS; Critical/High/Medium/Low all zero | Cleared for Phase 3 foundation handoff only | Preserve reviewer findings and rerun review after Phase 3 implementation |

Commit-mode patient creation remains prohibited until every blocking prerequisite is implemented, tested, approved and revalidated against fresh source and target coordinates.
