# Patient pilot execution sequence

Status: **specification-only, zero-write sequence.**

This sequence is normative with [`specifications/patient_pilot_dependency_sequence.json`](specifications/patient_pilot_dependency_sequence.json). Stages are exact and ordered 0-23. A later stage may append an outcome, hold or stop; it cannot silently repair data or modify an earlier decision.

| Stage | Required action | Principal contract inputs | Output and stop rule | Identity rule |
|---:|---|---|---|---|
| 0 | Validate environment and exact approved non-production target. | D-214/Q-404 isolation boundary | Pinned environment coordinate; stop on production suspicion or write-capable path. | Not created |
| 1 | Validate D-101 source account, connection `legacy_uhms` and exact schema `uuhms`. | D-101 | Read-only source authority; stop on privilege/schema/guard mismatch. | Not created |
| 2 | Validate source and target fingerprints, counts, versions and constraints. | `PATIENT-EXT-001`, `INS-EXT-001/004` | Pinned fingerprint bundle; any drift stops. | Not created |
| 3 | Pin Phase 2A-2F contracts, policies, canonicalizers, HMAC metadata and configuration fingerprints. | Patient/insurance privacy versions | Nonsecret contract-bundle hash; missing/unapproved version stops. | Not created |
| 4 | Capture coordinated immutable patient/insurance source snapshot and deterministic cohort order. | `PATIENT-EXT-001`, `PATIENT-CHILD-EXT-001`, `INS-EXT-001/002` | Source coordinate and candidate manifest; second snapshot/raw output/unversioned randomness stops. | Not created |
| 5 | Capture unscoped target collision snapshot. | `PATIENT-TARGET-001..014`, `INS-TARGET-001..010`, `INS-EXT-004` | Target coordinate covering live/deleted/merged/archive/number/alias/contact/insurance/sequence/constraint state. | Not created |
| 6 | Resolve required Phase 2A reference-crosswalk outcomes. | `NK-004`, `EXTRACT-SETT_PRIVATE`, `RECON-SETT_PRIVATE`, `INS-PROVIDER-001..008` | One classified reference outcome; missing/ambiguous/invented parent stops the affected dependency. | Not created |
| 7 | Validate Phase 2B actor/security outcomes. | `TARGET-ACTOR-054`, `STAFF-SECURITY-001`, `PATIENT-ACTOR-001..008` | Null registrar/creator where specified, absence provenance and security zeroes; fallback actor stops. | Not created |
| 8 | Validate protected patient remediation inputs. | `PATIENT-REQ-002..006`, patient privacy contracts | Approved per-field remediation or unresolved class; guessing/cross-patient/child/insurance repair stops. | Inputs prepared only |
| 9 | Validate explicit patient-state and insurance-initialization matrices. | `PATIENT-REQ-007..011`, `INS-ELIG-001..006` | Explicit value or classified commit blocker. A blocker continues; silent defaults, invention or fabricated state stop. | Inputs prepared only |
| 10 | Classify patient entity and required-field vector; retain duplicate-review signals only. | Core Phase 2C mappings, required rules, `PATIENT-DUP-*` | Eligible entity class or classified patient-and-chain quarantine. Unresolved identity/state is non-stopping; guessing, mutation or an unclassified outcome stops. | **Classification closes.** |
| 11 | Resolve explicit existing-target versus new-patient branch. | Stage 10 plus `PATIENT-TARGET-001..014` and Stage 5 snapshot | Branch, protected mapping token/null and identity seal; missing/ambiguous/deleted/merged/conflicting/inferred target stops. | **Identity envelope seals.** |
| 12 | Resolve patient-number action. | `PATIENT-NUM-001..018` | Retain existing, symbolic `TARGET_GENERATED_AT_COMMIT`, or none. Dry-run allocates/reserves zero. | Seal unchanged |
| 13 | Classify OPD alias independently. | `PATIENT-ALIAS-001..017` | Blank/invalid/duplicate-withheld/collision-held/satisfied/creatable outcome. | Seal unchanged; alias never changes identity |
| 14 | Classify optional inline demographics. | Phase 2D column/crosswalk/relationship rules | Direct/null/withheld/existing-target-evidence outcome per field. | Seal unchanged; optional values never identity evidence |
| 15 | Classify NOK/contact tuple and target primary-contact state. | `PATIENT-CHILD-NOK-001..012`, contact/relationship rules | Candidate/absence/withheld/parent-held/existing-target-evidence outcome. | Seal unchanged; contact cannot repair identity |
| 16 | Classify every insurance row. | `INS-COL-001..009`, row/history/member/date/type rules | One primary row class and protected-history outcome per source row. | Seal unchanged; no reassignment/default/invention |
| 17 | Resolve provider mappings. | Stage 6 crosswalk plus `INS-PROVIDER-001..008`, `INS-REL-002` | Unique provider token or blank/unresolved/ambiguous/conflict class. | Seal unchanged; no artificial provider |
| 18 | Consolidate resolved patient/provider groups. | `INS-CONS-001..012`, target insurance rules and initialization matrix | History for every row. Emit at most one current candidate or a classified current-withheld result for unresolved initialization, conflicts, unknown dates, unresolved provider/root or existing-target immutability. Only invention, mutation, omission or an unclassified outcome stops. | Seal unchanged; no patient/provider merge |
| 19 | Build rooted dependency-chain and quarantine outcomes. | Patient/child/insurance root, sentinel and exception-precedence contracts | Exactly one valid root and topological disposition for every dependent. | Seal unchanged; no child before parent |
| 20 | Produce complete zero-write dry-run projection. | Classified cohort and future Unit A-D interfaces | Aggregate/opaque outputs, symbolic numbers and idempotency/resume/rollback projection; any write/service/raw value stops. | Seal unchanged |
| 21 | Reconcile every partition at difference zero. | All patient, alias, child, insurance and safety reconciliation IDs | Exact reconciliation plus expected classified nonzero exceptions; unexplained difference stops. | Seal unchanged; reconciliation cannot repair |
| 22 | Scan complete artifacts for PHI and prove safety zeroes. | Patient/child/insurance privacy and security contracts | Privacy and zero-side-effect verdict; leak, plain low-entropy hash, cross-domain HMAC or effect stops. | Seal unchanged |
| 23 | Produce readiness verdict. | All stage verdicts, mandatory blockers and independent review | `FAIL_CLOSED`, `BLOCKED_PREREQUISITE` or `DRY_RUN_ACCEPTED_NOT_COMMIT_AUTHORIZED`; foundation handoff is documentation-only. | Seal unchanged; never importer authorization |

## Mandatory sequence invariants

- Source and target writes during Phase 2F and dry-run equal zero.
- Each of the 33 composed fields retains its single Phase 2C, 2D or 2E owner.
- Stage 11's protected identity seal is unchanged through Stages 12-23.
- A changed source, target, contract, remediation or configuration coordinate invalidates the candidate/run and requires a new run; it is not an in-place rewrite.
- Identity resolves before alias, demographic, contact and insurance release.
- Existing-target patients, contacts, aliases and memberships remain immutable except that the separately approved collision-free `legacy_opd` alias path may later create one new alias without altering the existing set.
- Every Classic insurance row receives a protected history outcome; at most one current representation is possible per resolved patient/provider group.
- Expected classified exceptions may be nonzero, but unexplained differences and unsafe outcomes must be zero.
- A required patient identity/state blocker becomes a classified patient-root quarantine. Its dependants remain parent-held, but Stages 11-23 still account for them.
- An insurance initialization/consolidation blocker withholds only the current representation; every source row still receives history/provenance and reaches dependency rooting, dry-run projection and reconciliation.
- Run stopping is reserved for safety failures such as invention, prohibited mutation/reassignment, silent default/loss, privacy/side-effect violations or any unclassified outcome.

## Phase 3 gate

Stage 23 may hand the contracts to Phase 3 only. Future commit execution remains blocked until the reviewed foundation provides schema/environment guards, least-privilege source access, coordinated snapshots, protected stores/interfaces, HMAC keys, safe patient numbering, isolated migration persistence, target-state validators, side-effect controls, idempotency, checkpoints, resume and rollback/compensation.
