# Phase 2F reconciliation, acceptance, privacy and dry-run analysis draft

## Purpose and authority

This is a documentation-only specialist draft for Phase 2F consolidation. It defines the proposed composed interfaces and exact acceptance gates for a future bounded patient pilot. It creates no importer, table, migration, state store, key, runtime, database connection or database write.

The policy authority is `APPROVED_DECISION_SPECIFICATIONS.md` and `DECISIONS.md`. The normative Phase 2A-2E specifications remain authoritative. This draft links and composes those rules; it does not replace them.

Evidence classification used below:

- **Confirmed upstream contract** means a rule already present in a normative Phase 2A-2E machine-readable specification.
- **Approved policy** means an accepted owner decision, principally D-001, D-002, D-004 through D-008, D-101, D-102, D-104 through D-107 and D-201 through D-214.
- **Phase 2F technical specification** means a proposed interface or gate for the composed patient-pilot contract. It is not an implementation.
- **Phase 3 prerequisite** means a capability that must exist and be independently reviewed before any commit-mode pilot.

No database was queried for this workstream. All counts and rule IDs referenced here are inherited from the Phase 2A-2E evidence baseline.

## Upstream contracts consumed without reopening

| Concern | Authoritative upstream rules | Composed consequence |
|---|---|---|
| Reference parents | `NK-004`, `EXTRACT-SETT_PRIVATE`, `RECON-SETT_PRIVATE`, Phase 2A reference exception and relationship contracts | A patient dependency can consume only a versioned, approved crosswalk result. It cannot create an artificial provider, department or other parent. |
| Actor safety | Phase 2B security exclusion contract; `STAFF-REC-002` through `STAFF-REC-007`; `STAFF-REC-095`; D-202 and D-008 | `patients.registered_by` remains null with protected absence provenance. No password, permission, role, login, notification or fallback registrar is projected. |
| Patient roots and privacy | `PATIENT-PRIV-001` through `PATIENT-PRIV-012`; `PATIENT-CHAIN-001` through `PATIENT-CHAIN-028`; `PATIENT-REC-ENTITY-001`, `PATIENT-REC-ALIAS-002`, `PATIENT-REC-CHAIN-100`, `PATIENT-REC-PRIVACY-050` | The `legacy-patient-chain-v1` token is the patient quarantine root; orphan attendance and insurance roots remain separately typed. Parent identity is resolved before any child. |
| Required patient facts and identity | `PATIENT-REC-REQ-001` through `PATIENT-REC-REQ-011`; `PATIENT-REC-DUPLICATE-010`; `PATIENT-REC-NUMBER-020`; `PATIENT-REC-EXISTING-030`; Phase 2C exception catalogue | Missing independent first/last name evidence or unresolved required/state fields blocks new-patient commit eligibility. No automatic match, merge or default is permitted. |
| Patient children | `PATIENT-CHILD-PRIV-001` through `PATIENT-CHILD-PRIV-012`; `PATIENT-CHILD-REC-001` through `PATIENT-CHILD-REC-010` | Optional invalid child data is withheld and reconciled independently; it cannot repair or redirect identity. Existing-target children remain immutable. |
| Insurance history and grouping | `INS-HISTORY-001` through `INS-HISTORY-006`; `INS-PRIV-001` through `INS-PRIV-009`; `INS-REC-001` through `INS-REC-015` | Every source insurance row has protected provenance. At most one current representation is selected for a resolved new-patient/provider group; existing-target memberships remain immutable and eligibility/verification is never fabricated. |

## A. Protected interface specifications

All interfaces in this section are logical message contracts. They deliberately avoid physical table names, storage engines and implementation configuration. A Phase 3 design must choose protected storage, transactional boundaries, encryption, access control, retention and audit implementation.

### A1. Common envelope

Every protected interface record must contain:

| Field | Contract |
|---|---|
| `interface_version` | Immutable semantic version of the interface. |
| `pilot_contract_version` | Exact Phase 2F contract version. |
| `contract_bundle_hash` | SHA-256 over the ordered nonsecret specification bundle, with algorithm and canonicalization version recorded. |
| `run_token` | Opaque, high-entropy run identity; never a source/target key. |
| `cohort_token` | Opaque pilot-cohort identity. |
| `source_snapshot_token` | Protected identity of the coordinated `uuhms` snapshot. |
| `target_snapshot_token` | Protected identity of the read-only target-collision snapshot. |
| `source_schema_fingerprint` | Approved nonsecret fingerprint; must match D-102 baseline. |
| `target_schema_fingerprint` | Approved nonsecret non-production target fingerprint. |
| `hmac_key_version` | Key identifier only, never key material. |
| `canonicalization_version` | Exact typed serialization version. |
| `created_at_utc` | Migration-system fact, never substituted for a Classic historical date. |
| `record_checksum` | Keyed integrity checksum over the protected canonical record. A plain hash of patient data is prohibited. |
| `retention_class` | Approved records/privacy class. |
| `access_class` | Least-privilege access policy label. |

Envelope invariants:

1. The source connection is exactly `legacy_uhms`, the Classic database is exactly `uuhms`, and source access is read-only.
2. Domain-separated HMAC-SHA-256 uses environment-protected secret material, a pinned key version and typed length-prefixed UTF-8 input with explicit null/type markers, as required by `PATIENT-PRIV-*`, `PATIENT-CHILD-PRIV-*` and `INS-PRIV-*`.
3. Tokens from different domain, environment, key or canonicalization versions are never compared or joined.
4. A missing or unverifiable envelope is not reconstructed from target data; it is a privacy/provenance stop.
5. Repository output contains only aggregate counts, rule IDs, nonsecret specification hashes and coarse pass/fail states. No row token is repository-safe.

### A2. Patient crosswalk interface (`PILOT-XW-PATIENT-V1`)

| Field | Required rule |
|---|---|
| `source_patient_token` | `PATIENT-PRIV-001` domain; required and unique within the pinned source snapshot. |
| `patient_root_token` | `PATIENT-PRIV-007` domain; required and stable for the chain. |
| `target_patient_ref` | Protected target reference; nullable before an approved mapping. Never published. |
| `branch` | Exactly one of `new_target`, `explicit_existing_target`, `quarantined`, `not_commit_eligible`. |
| `mapping_state` | State-machine value; a terminal success requires target, provenance and reconciliation evidence. |
| `patient_number_action` | `allocate_on_commit`, `reuse_verified_prior_migration_allocation`, `existing_target_unchanged`, or `none`. |
| `patient_number_result_ref` | Protected allocator result reference; absent in a dry-run unless explicitly labelled nonbinding projection. |
| `authoritative_rule_ids` | Exact Phase 2C rule IDs and Phase 2F composition IDs. |
| `transformation_versions` | Name, required-field, state, number and patient mapping versions. |
| `existing_target_evidence_ref` | Protected collision/crosswalk evidence; required for existing-target branch. |
| `idempotency_key_ref` | Protected key reference, not raw key material. |
| `mapping_checksum` | Keyed record checksum. |
| `revocation_state` | `active`, `invalid`, `revoked`, or `superseded_by_review`; never silently deleted. |
| `run_provenance_ref` | Link to separate migration audit. |

Cardinality and immutability:

- One source patient token has no more than one active target mapping at a contract/snapshot coordinate.
- One approved mapping resolves exactly one target patient.
- Existing-target links do not authorize mutation, enrichment or merge-pointer traversal.
- A prior successful mapping is resolved before any patient-number allocation.
- The Classic primary key is never a renewed primary key and never appears outside protected runtime.

### A3. Reference crosswalk interface (`PILOT-XW-REFERENCE-V1`)

| Field | Required rule |
|---|---|
| `source_reference_token` | Domain-separated protected source identity. |
| `reference_domain` | Allow-listed domain such as insurance provider or department; free-form domain switching is prohibited. |
| `target_reference_ref` | Protected target reference. |
| `phase2a_crosswalk_version` | Exact version; required. |
| `phase2a_rule_id` | Exact owner rule such as `NK-004`. |
| `match_authority` | Explicit approved crosswalk, never an ad hoc Phase 2F name match. |
| `status` | `resolved_unique`, `unresolved`, `ambiguous`, `conflict`, `inactive_target`, `drifted`. |
| `conflict_state` | Classified exception and protected evidence reference. |
| `snapshot_token` | Coordinate under which the target reference was verified. |

Only `resolved_unique` under matching rule/canonicalization/crosswalk/snapshot versions releases a dependent patient field or insurance group.

### A4. Provenance/history interface (`PILOT-PROV-V1`)

| Field | Required rule |
|---|---|
| `source_row_token` | Domain-specific HMAC identity; required for every source patient row and every source insurance row. |
| `patient_root_token` | Patient root or the documented orphan root. |
| `subchain_token` | Optional typed attendance/insurance/child subchain. |
| `raw_protected_payload_ref` | Access-controlled payload location; never the raw payload or repository path. |
| `source_query_id` / `query_hash` | Reviewed extraction identity. |
| `source_snapshot_token` | Exact coordinated snapshot. |
| `field_disposition_set` | One primary disposition per field plus ordered secondary exceptions. |
| `transformation_version` | Exact rule version for every projection. |
| `target_outcome` | `none`, `candidate`, `created`, `linked_existing`, `withheld`, `historical_only`, `quarantined`, or `failed`; dry-run uses projected variants. |
| `target_outcome_ref` | Protected mapping/group/child reference, nullable. |
| `exception_set` | Stable catalogue codes, ordered by Phase 2F precedence. |
| `reconciliation_refs` | All equations to which this row contributes. |
| `retention_class` / `access_class` | Inherited from approved records governance. |

`INS-HISTORY-001` requires a valid provenance/history record for all 31,307 baseline Classic insurance rows. Exact duplicates remain separate provenance rows under `INS-HISTORY-004`. No row is collapsed merely because one target membership represents its group.

### A5. Quarantine interface (`PILOT-QUARANTINE-V1`)

| Field | Required rule |
|---|---|
| `root_token` | Exactly one valid typed root. Patient rows use `PATIENT-PRIV-007`; orphan attendance and insurance use `PATIENT-PRIV-008` and `PATIENT-PRIV-009` respectively. |
| `subchain_token` | Typed child/group token; must link to but never replace the authoritative root. |
| `primary_exception_code` | Highest-precedence applicable code. |
| `secondary_exception_codes` | Stable ordered set; may not replace the primary. |
| `blocking_scope` | `run`, `snapshot`, `patient_root`, `subchain`, `field`, `alias`, `contact`, `insurance_row`, `insurance_group`, or `reference_parent`. |
| `manual_review_owner` | Exact owner inherited from the normative exception catalogue. |
| `manual_review_sla` | Exact inherited SLA. |
| `release_conditions` | Evidence, authority, refresh and zero-difference rerun requirements. |
| `dependency_edges` | Typed rule IDs and root/subchain references; no raw keys. |
| `current_disposition` | `held`, `review_pending`, `remediated_pending_recheck`, `released`, `permanently_withheld`, `superseded`, or `failed_closed`. |
| `release_approval_ref` | Protected approval/audit reference. |

Quarantine invariants:

- Every quarantined outcome has exactly one root.
- A child never releases before the valid parent is mapped or explicitly linked.
- Release is topological: environment/snapshot -> reference/actor -> patient identity/state -> patient core -> alias/contact/insurance row -> insurance group -> reconciliation.
- Releasing a root does not silently release descendants: each child reruns its own current rules, target-collision checks and reconciliation.
- No orphan is assigned to a similar, first, current, administrator, importer or generic patient.
- An optional child exception cannot replace or downgrade a patient-root exception.

### A6. Reconciliation interface (`PILOT-RECON-V1`)

| Field | Required rule |
|---|---|
| `run_token` / `cohort_token` | Exact protected run/cohort coordinate. |
| `domain` | Allow-listed reconciliation domain. |
| `contract_id` | Phase 2A-2F reconciliation ID. |
| `contract_version` | Exact version. |
| `population_definition` | Stable definition and snapshot coordinate. |
| `expected_equation` | Canonical equation or safety-zero assertion. |
| `measured_values` | Integer or exact decimal values; aggregates only in repository. |
| `difference` | Exact calculated difference. |
| `tolerance` | Zero for all Phase 2F count/partition equations and all safety zeroes. |
| `acceptance_result` | `passed`, `failed`, `blocked_not_measured`, or `not_applicable_with_rule`. |
| `evidence_hashes` | Source, target, contract, query and result identities. |
| `exception_refs` | Stable classified differences. A classified source-quality exception is a bucket, not an equation difference. |
| `approval_ref` | Protected readiness/go-no-go evidence. |

Rules:

- Every defined population equals the sum of exactly one ordered primary bucket per member. Secondary diagnostics never add to the partition.
- Every equation difference is exactly zero. A nonzero classified exception count is allowed only as an explicit bucket; it cannot be used to excuse a nonzero equation difference.
- Absence of a mandatory measurement is `blocked_not_measured`, never pass.
- A changed source, target, query, HMAC, canonicalization or contract version invalidates the result and requires reclassification.
- Phase 2F specification and future dry-run target/source write deltas are both exactly zero.

## B. Cross-phase exception precedence and release

### B1. Precedence

Apply the following order before assigning a primary disposition. Within a level, the most specific normative Phase 2A-2E code is primary; other applicable codes are secondary. Severity never permits a lower-level child issue to displace a higher-level root issue.

| Order | Precedence class | Representative authoritative codes | Required disposition |
|---:|---|---|---|
| 1 | Environment, schema, fingerprint or privacy stop | `LEGACY-REF-COMMON-006`, `LEGACY-STAFF-SCHEMA-020`, `LEGACY-PATIENT-DRIFT-033`, `LEGACY-PATIENT-PRIVACY-036`, `LEGACY-PATIENT-CHILD-PRIVACY-034`, `LEGACY-INSURANCE-SOURCE-034`, `LEGACY-INSURANCE-PRIVACY-036` | Stop affected run/snapshot; contain privacy event; no row release. |
| 2 | Extraction, stable-key, query, snapshot or checkpoint failure | `LEGACY-REF-COMMON-001`, `LEGACY-REF-COMMON-007`, `LEGACY-STAFF-EXTRACTION-021`, `LEGACY-PATIENT-KEY-001/002`, `LEGACY-PATIENT-EXTRACTION-034`, `LEGACY-PATIENT-CHILD-EXTRACT-033`, `LEGACY-INSURANCE-KEY-001/002`, `LEGACY-INSURANCE-EXTRACT-035` | Stop affected extraction and all descendants; retry only at unchanged verified coordinate. |
| 3 | Required reference-parent or actor failure | Phase 2A parent codes; `LEGACY-STAFF-MATCH-003`, `LEGACY-STAFF-ACTOR-018/019`; `LEGACY-INSURANCE-PROVIDER-006` through `-009`, `-039` | Hold dependent field/subchain; never create an artificial parent or fallback actor. |
| 4 | Existing-target ambiguity, drift or immutable conflict | `LEGACY-PATIENT-TARGET-023` through `-026`, `-035`; `LEGACY-PATIENT-CHILD-TARGET-031/036`; `LEGACY-INSURANCE-TARGET-030` through `-032`, `-040`, `-041` | Stop relevant root/group; retain comparison evidence only; refresh target snapshot. |
| 5 | Required patient identity or remediation failure | `LEGACY-PATIENT-NAME-008` through `-012`, `DOB-013` through `-016`, `GENDER-017/018`, `PHONE-019/020`, `REMEDIATION-040`, `DUPLICATE-028` | Quarantine patient root and dependants; do not infer, split, match or merge. |
| 6 | Patient target-state or number-allocation failure | `LEGACY-PATIENT-STATUS-044` through `-049`, `NUMBER-037` through `-039` | Patient is not commit-eligible; no default or allocation side effect. |
| 7 | Patient-core eligibility/result | `LEGACY-PATIENT-CHAIN-030/042/043`, patient entity and required-field classes | Core is eligible, explicit-existing immutable, remediation-pending or quarantined. This result cannot be changed by children. |
| 8 | Alias-only outcome | `LEGACY-PATIENT-ALIAS-003` through `-007`, `-041`; Phase 2D alias integration codes | Withhold or reconcile alias independently; never turn alias duplication into patient duplication. |
| 9 | Optional demographic/contact outcome | Phase 2D contact, occupation, address, religion and marital codes | Withhold field/child or record successful absence; do not invalidate an otherwise eligible patient unless parent/target/privacy stop applies. |
| 10 | Insurance row/provider/group outcome | Phase 2E patient, provider, type, member, date, consolidation and history codes | Historical provenance always retained; unresolved row/group cannot create current membership and cannot change patient identity. |
| 11 | Side-effect violation | `LEGACY-PATIENT-ACTOR-032`, Phase 2B security codes, `LEGACY-INSURANCE-ELIGIBILITY-029`, safety-zero failures | Stop run and prove zero delta; isolation review required. |
| 12 | Reconciliation failure | `LEGACY-STAFF-RECON-026`, `LEGACY-PATIENT-NUMBER-039`, normative reconciliation failure codes | No acceptance until every difference is zero at the same coordinate. |
| 13 | Commit, lineage or idempotency failure | `LEGACY-PATIENT-NUMBER-038`, `LEGACY-PATIENT-DELETE-043`, `LEGACY-INSURANCE-TARGET-041` | Stop; never interpret partial state as success or delete/recreate by default. Reconstruct durable facts and compensate under reviewed design. |

### B2. Root-selection rules

1. Environment/privacy/snapshot failure uses the run or snapshot as root.
2. A valid patient source row uses its `legacy-patient-chain-v1` root, even when an optional alias, contact or insurance outcome is withheld.
3. An insurance row whose nonzero `PAT_ID` has no patient uses `PATIENT-PRIV-009 / legacy-insurance-chain-v1`. The Phase 2E `patient-insurance-orphan-v1` token is secondary correlation only.
4. An orphan attendance uses `PATIENT-PRIV-008 / legacy-attendance-chain-v1`; its clinical, billing, claim, admission and related descendants remain in that chain.
5. Reference-parent failures retain the patient root plus a typed reference subchain; they never generate a replacement provider/department.
6. A single source event cannot be rooted under two patient chains. Conflicting paths use `LEGACY-PATIENT-CHAIN-042` and remain held.

### B3. Topological release proof obligation

For every release, the future foundation must persist evidence that:

`environment_valid AND source_snapshot_unchanged AND target_snapshot_refreshed AND source_key_valid AND parent_crosswalks_resolved AND actor_policy_valid AND patient_identity_resolved AND patient_state_approved AND target_branch_deterministic AND parent_reconciliation_difference_zero AND child_specific_rules_passed AND child_collision_result_deterministic AND child_reconciliation_difference_zero`.

If any term is false or unmeasured, the child stays held. A later stage is prohibited from modifying an earlier identity or branch result.

## C. Exact reconciliation and acceptance contract

### C1. Mandatory zero-difference equations

The future dry-run must measure these equations for the bounded cohort, not reuse whole-database Phase 2C/2E totals as pilot results:

1. `selected cohort roots = included + excluded_before_classification + failed_selection`, difference 0.
2. `included roots = exactly one patient entity outcome`, difference 0 (`PATIENT-REC-ENTITY-001` semantics).
3. `pilot source fields = exactly one authoritative field disposition`, difference 0.
4. `patient roots = exactly one existing-target/new-target/quarantined/not-commit-eligible branch`, difference 0.
5. `alias candidates = unique_create_candidate + duplicate_withheld + blank + invalid + target_collision + not_applicable + failed`, difference 0 (`PATIENT-REC-ALIAS-002`).
6. `NOK source tuples = complete_valid + partial + all_blank + invalid + parent_quarantined + existing_target_evidence_only + failed`, difference 0 (`PATIENT-CHILD-REC-001`).
7. Each occupation/address/religion/marital population equals exactly one Phase 2D bucket, difference 0 (`PATIENT-CHILD-REC-003` through `-006`).
8. `registered child outcomes = released + held_parent + held_domain + sentinel_or_optional_absence + existing_target_evidence_only + failed`, difference 0 (`PATIENT-CHILD-REC-010`).
9. `pilot insurance source rows = exactly one terminal row outcome`, difference 0 (`INS-REC-001`).
10. Every insurance patient relationship, provider outcome, member outcome, date outcome, type, Scheme and Plan projection has exactly one primary bucket, difference 0 (`INS-REC-002` through `-006`, `-008`, `-009-*`).
11. `mapped patient/provider groups = existing_target_immutable + invalid_or_unresolved + conflicting_candidates + history_only + one_current_selected + failed`, difference 0; no group has more than one current selection (`INS-REC-007-GROUP`).
12. Source rows within mapped groups have exactly one `INS-REC-007-ROW` bucket, difference 0.
13. Every insurance row has complete protected provenance, difference 0 and zero missing/silently discarded (`INS-REC-014`).
14. Every exception has a valid catalogue code, owner, SLA, root, blocking scope and release condition; invalid/unclassified count 0.
15. Every target collision has exactly one deterministic branch; unclassified collision count 0.
16. Every checkpoint maps to one durable stage outcome and every successful stage outcome has one checkpoint; unexplained difference 0.
17. Every idempotency key resolves to zero or one compatible durable result; incompatible/multiple result count 0.
18. Dry-run source writes = 0; dry-run target writes = 0; specification source writes = 0; specification target writes = 0.

All tolerances are integer zero. There is no rounding allowance for these patient-pilot counts. Financial 0.01 policy is out of Phase 2F scope and cannot be imported as a patient-count tolerance.

### C2. Mandatory safety zeroes

The following measurements must exist and equal zero. A missing measurement fails closed.

#### Identity and target integrity

- source writes;
- target writes during Phase 2F specification and future dry-run;
- automatic patient merges;
- name-only matches;
- phone-only matches;
- OPD-only target matches;
- fuzzy or weighted matches;
- Classic primary-key reuse as renewed key;
- duplicate renewed patient numbers;
- replacement patient numbers on rerun;
- unexplained consumed patient-number sequence values;
- automatically assigned duplicate OPD aliases;
- alias ownership influenced by child similarity;
- existing-target patient identity changes;
- existing-target alias or demographic changes;
- existing-target child enrichment;
- soft-deleted/merged target mutation or automatic merge-pointer following;
- invented patient values or name-order guesses.

#### Actor, reference and insurance safety

- invented actors or registrar fallbacks;
- mapped Classic passwords, roles, permissions or login access;
- invented providers or reference parents;
- invented member/policy/CCC numbers;
- coerced, guessed, replaced, truncated or swapped dates;
- fabricated eligibility or verification;
- verification rows or verification actors;
- duplicate emergency contacts;
- multiple migration-created primary contacts;
- duplicate patient/provider memberships;
- patient/provider groups with more than one current selection;
- mutation of existing-target membership;
- silent source insurance-row loss;
- insurance source rows without protected provenance;
- claims, billing, payment, receivable, allocation, journal or accounting projection.

#### Runtime and audit safety

- operational patient, registration, contact, insurance or eligibility services called;
- prohibited queue, scheduler, notification, SMS or email activity;
- contact-search side effects;
- billing, accounting, stock, pathway or bed-state side effects;
- operational activity/history fabricated for Classic facts;
- use of current, first, administrator, importer, migration executor or `Legacy Actor Unknown` as patient registrar;
- non-`uuhms` Classic access.

#### Privacy and provenance safety

- raw PHI in repository artifacts, prompts, logs, fixtures, screenshots or reports;
- raw source patient/insurance keys or raw target IDs in repository artifacts;
- row-level tokens or row-level dates in repository artifacts;
- raw remediation, member, policy, contact, address, provider/company or eligibility values in repository artifacts;
- plain hashes of low-entropy patient data;
- cross-domain/environment/key/canonicalization HMAC comparisons;
- missing root tokens;
- wrong-domain/version tokens;
- cross-chain union or patient reassignment;
- child released before parent;
- unresolved reconciliation differences.

### C3. Allowed nonzero classified outcomes

The following may be nonzero without failing the cohort solely because they are nonzero:

- expected classified exceptions;
- quarantined or remediation-pending patients;
- blank, invalid, duplicate-withheld or collision-withheld aliases;
- withheld optional demographic fields and contacts;
- successful optional absence;
- suspected-duplicate review flags without automatic match/merge;
- historical-only insurance rows;
- provider-unresolved/ambiguous insurance rows;
- unknown-date, expired-looking or chronology-exception insurance rows;
- exact duplicate insurance provenance rows;
- conflicting insurance groups withheld from current representation;
- existing-target patient/child/membership immutable evidence;
- target collisions with a deterministic fail-closed branch.

Each allowed nonzero outcome is acceptable only if all of these are true:

1. It is required or permitted by the scenario/stratum contract.
2. It belongs to exactly one primary partition bucket.
3. It carries every applicable stable code, owner, SLA, root and release condition.
4. The source row remains accounted for in protected provenance.
5. It projects no unsafe target change.
6. Its enclosing equation difference and all safety-zero assertions are zero.

The pilot acceptance objective is zero unexplained or unsafe outcomes, not zero data-quality exceptions.

### C4. Verdict hierarchy

| Verdict | Meaning |
|---|---|
| `FAIL_CLOSED` | Any privacy leak, write, fingerprint drift, nonzero safety assertion, nonzero equation difference, unclassified row/collision/exception, missing provenance, or prohibited side effect. |
| `BLOCKED_PREREQUISITE` | Equations/safety pass but a mandatory Phase 3 or owner-approved target-representation prerequisite is absent. This is not commit-ready. |
| `DRY_RUN_ACCEPTED_NOT_COMMIT_AUTHORIZED` | Dry-run evidence is complete and exact, expected exceptions are classified, and all safety gates pass. It still authorizes no write. |
| `COMMIT_PILOT_ELIGIBLE_AFTER_PHASE3_REVIEW` | May be assigned only after the Phase 3 foundation, state matrices, persistence isolation, target refresh and commit-specific independent review exist. Phase 2F alone cannot emit it. |

## D. Future zero-write dry-run contract

### D1. Required inputs

| Input | Required validation |
|---|---|
| Environment allow-list | Exact Classic connection/database/read-only guard and exact approved non-production target; production target fails closed. |
| Source guard | D-101 account evidence; D-102 exact database, version, 55-table/479-column shape and approved fingerprint. |
| Target guard | Installed schema/constraint fingerprint and service-invariant version; drift is classified before any cohort evaluation. |
| Snapshot coordinates | One coordinated source snapshot and one target collision snapshot, both bound to run and query identities. |
| Contract bundle | Ordered versions/hashes for every consumed Phase 2A-2F normative file. |
| Cohort manifest | Bounded deterministic scenarios/strata, selection version, protected tokens, expected outcomes and approval state. |
| Protected remediation input | Approved, current, nonconflicting field evidence with reviewer/issuer/validity/revocation metadata; no values in report. |
| Reference crosswalk input | Exact Phase 2A versions and protected resolved/ambiguous/conflict outcomes. |
| Existing-target crosswalk input | Explicit approved source-target links and immutability state. |
| Patient state matrix | Every installed non-null state field has approved initialization or an explicit commit blocker. No DB/model default is silently accepted. |
| Insurance initialization matrix | Explicit outcomes for member type, active/primary flags, tier, identifiers, dates, verification/eligibility and timestamps. |
| HMAC metadata | Algorithm/domain/key/canonicalization versions and key-access preflight; never secret material. |
| Configuration fingerprints | Patient-number allocator, validation, isolation and runtime configuration identities; dry-run projections remain nonbinding. |
| Evaluation coordinate | Pinned UTC date/time and timezone, applied only where the upstream rule explicitly uses it. |
| Expected scenario matrix | Exact synthetic outcomes and source/target stratum expectations. |

Any missing input gives `BLOCKED_PREREQUISITE` or `FAIL_CLOSED` according to safety impact; it is never silently defaulted.

### D2. Evaluation order

The dry-run follows the parent-specified stages 0 through 23. The stages relevant to this draft are mandatory gates:

1. Validate environment, accounts, exact schemas, fingerprints, contract bundle and coordinated snapshots.
2. Resolve reference and actor outcomes; unresolved required parent is classified before patient identity.
3. Validate remediation and state matrices; classify patient entity and immutable/new branch.
4. Classify number action, alias, optional demographic/contact children and every insurance source row.
5. Resolve provider mappings and at-most-one current representation per patient/provider group.
6. Build one-root quarantine outcomes under the cross-phase precedence contract.
7. Project all future atomic units without allocating numbers, reserving values or writing any record.
8. Reconcile every source row, field, relationship, child, insurance row/group, collision, exception and checkpoint projection.
9. Run artifact privacy and side-effect safety scans.
10. Emit exactly one readiness verdict.

No later step changes an earlier patient identity, target branch or root assignment.

### D3. Required outputs

Repository-safe outputs are aggregate-only. Protected row-level outputs remain in the future protected runtime/store.

| Output family | Minimum aggregate output |
|---|---|
| Preflight | Pass/fail/block status for every environment, source, target, contract, snapshot, key-version and configuration guard. |
| Cohort | Included/excluded counts by cohort type, scenario and stratum; quota shortfalls and deterministic fallback outcome. |
| Patient entity | Counts by explicit-existing/new/remediation-pending/quarantined/not-eligible/failed branch. |
| Number | Allocate-on-commit, verified-prior, existing unchanged, none and collision counts; all projections labelled nonbinding. |
| Alias | Unique candidate, duplicate-withheld, blank, invalid, collision, not-applicable and failed counts. |
| Demographics/contact | Each Phase 2D partition plus contact primary-invariant/collision outcomes. |
| Insurance | All `INS-REC-001` through `INS-REC-015` populations, buckets, differences and safety zeroes. |
| Exceptions/quarantine | Counts by stable code, severity, owner, SLA and blocking scope; roots/subchains only as protected runtime references. |
| Target collision | Counts by collision type and deterministic branch; snapshot-age/drift state. |
| Reconciliation | Contract IDs, expected/measured aggregates, difference, tolerance, evidence hashes and verdict. |
| Privacy | Scan coverage, tool/rule/version/hash, finding counts and containment status; never matched raw content. |
| Side effects | Named zero assertions with observed count zero and evidence source. |
| Idempotency | Projected first-run/rerun equivalence by atomic unit and incompatible lineage count. |
| Resume/rollback | Crash-boundary outcome counts and missing durable-fact/compensation requirements. |
| Verdict | One of the hierarchy in C4, blockers and exact required next gate. |

Dry-run output must never contain a patient number that could be mistaken for reserved. If an allocator simulation is possible in Phase 3, it must emit only a protected/nonbinding action token and aggregate collision result unless a separate reviewed reservation design exists.

## E. Privacy-safe evidence and artifact-wide regression contract

### E1. Prohibited repository content

Across all generated Phase 1-2 evidence, specifications, tests, fixtures, drafts, logs, screenshots and reports, prohibit:

- patient or NOK names;
- OPD values;
- phone numbers;
- addresses or contact values;
- identifying/free-text occupation values;
- insurance member, policy or CCC numbers;
- high-cardinality provider/company/scheme values derived from Classic patient rows;
- raw patient/attendance/insurance keys or target IDs;
- row-level dates;
- row-level HMACs, tokens or token prefixes;
- remediation values, reviewer notes containing identity facts, or eligibility facts;
- credentials, connection secrets or HMAC key material.

Obviously fictitious synthetic scenario labels are permitted only when they are generated independently and cannot be confused with copied or lightly modified Classic data. Prefer scenario IDs over realistic identity text.

### E2. Allowed repository evidence

- aggregate counts with small-cell policy where applicable;
- stable rule, exception, reconciliation, scenario and stratum IDs;
- schema/table/column names and nonsecret types/constraints;
- nonsecret query/specification/tool/result hashes;
- approved allow-list categorical values already recorded in normative crosswalks;
- pass/fail/block states and coarse owner/SLA categories;
- synthetic values that are overtly fictitious and independently generated.

### E3. Artifact-wide scan interface (`PILOT-PRIV-SCAN-V1`)

The future focused test must enumerate all repository files under `docs/legacy-migration/` plus Phase 2F focused tests. It must not inspect only newly generated files. The scan contract records:

| Field | Requirement |
|---|---|
| `scan_scope_manifest_hash` | Hash of the sorted relative file list. |
| `scanner_version` | Exact detector/rule-set version. |
| `allowlist_version` | Narrow reviewed false-positive allow-list; no raw value allow-list. |
| `file_count` | Count of scanned files. |
| `coverage_difference` | Expected files minus scanned files; must be zero. |
| `pattern_classes` | Credential, raw key/token, phone, OPD/member/policy, address/contact, row-date and accidental dump/log patterns. |
| `structured_checks` | JSON parse, prohibited property names/values, database/source guards, `implementation_authorized=false` and repository-output policy. |
| `finding_count` | Must be zero for unallowlisted potential PHI/secret/raw identifiers. |
| `finding_handling` | Stop, contain, purge, rotate secrets/keys if exposure could reveal protected data, rescan entire scope. |

The scanner must not print matched sensitive content. Diagnostics use file path, detector ID and line/field location only, with content redacted. Generated coverage manifests must contain paths and nonsecret hashes only.

### E4. Runtime protected-token rules

- Domain separation is mandatory for patient source, patient content, alias comparison, patient root, attendance root, insurance root, child contact, child demographic, insurance row, insurance member, insurance group and target comparison.
- Canonical inputs are typed and length-prefixed; concatenation with an ambiguous separator is prohibited.
- Plain SHA-256 of PAT_ID, OPD, phone, member number or other low-entropy data is prohibited.
- Token comparison is limited to the same declared purpose, environment, key and canonicalization version.
- A key rotation changes comparison coordinate and requires an explicit protected re-tokenization lineage process; tokens from old/new versions are not silently mixed.
- Repository artifacts include key version identifiers only if they are nonsecret; they never include tokens or lookup material.

## F. Idempotency, checkpoint and reconciliation contract

### F1. Key construction

Every stable idempotency key uses a distinct fixed domain plus typed, length-prefixed protected references. The logical inputs are:

| Unit | Required logical key inputs |
|---|---|
| Patient core | domain, source patient token, patient transformation/state versions, canonicalization version, new/existing branch. |
| Existing-target link | domain, source patient token, protected target mapping reference, crosswalk version. |
| Patient-number allocation | domain, source patient token, allocator configuration/version and prior mapping reference. |
| OPD alias | domain, source patient token, `legacy_opd` alias type, alias canonicalization version, protected alias comparison token. |
| Emergency contact | domain, patient mapping reference, `PATIENT-CHILD-PRIV-005` child token, transformation version. |
| Inline demographic projection | domain, source patient token, typed field discriminator, child token/version. |
| Insurance source history | domain, `INS-PRIV-001` source-row token, history transformation version. |
| Insurance patient/provider group | domain, resolved patient mapping reference, resolved provider crosswalk reference, `INS-PRIV-003` group token and consolidation version. |
| Exception | domain, root/subchain token, catalogue code, trigger rule/version and evidence coordinate. |
| Quarantine chain | domain, root token, root-domain version and source snapshot. |
| Reconciliation result | domain, run/cohort, reconciliation contract/version and population/snapshot identity. |
| Checkpoint | domain, run, cohort, atomic unit, partition/chunk identity and contract/snapshot versions. |

No key contains raw PHI, source keys, target IDs or a plain hash of low-entropy data.

### F2. Rerun invariants

A rerun at the same exact coordinate must:

- resolve the prior successful patient mapping before allocation;
- return the same target patient and same committed patient number;
- create no duplicate alias, contact, history row or patient/provider membership;
- retain the same primary/secondary exceptions and quarantine root when evidence and versions are unchanged;
- return the same reconciliation populations/buckets/differences;
- recognize only exact compatible prior migration lineage, never a merely similar target row;
- stop on missing, stale, conflicting or partially unexplained lineage;
- invalidate and reclassify rather than silently reuse results when contract, snapshot, target collision, key or canonicalization version changes.

### F3. Checkpoint evidence

A checkpoint must be written only by a future authorized foundation and must durably bind:

- atomic unit and state-machine transition;
- input/output idempotency keys;
- source/target snapshot and contract bundle;
- patient/root/group protected references;
- prior and new stage state;
- target write-set evidence or explicit zero-write outcome;
- mapping/provenance/quarantine/reconciliation record references;
- transaction/compensation state;
- keyed checksum and migration audit reference.

An unexplained checkpoint without matching durable facts, or durable target facts without matching crosswalk/checkpoint evidence, is not success.

## G. Rollback, compensation and resume reconciliation

This section specifies expected behavior for the future foundation; it authorizes no implementation. Hard deletion is never the universal rollback. An existing-target link is compensated only in migration metadata and is never rolled back by mutating the target patient.

| Crash boundary | Durable facts required before retry | Safe retry and duplicate prevention | Rollback/compensation expectation | Reconciliation/checkpoint proof | Operator action |
|---|---|---|---|---|---|
| Before number allocation | Classification, branch, idempotency input, zero target delta | Restart preflight/classification at pinned snapshots | None | Prior state remains `classified`; no allocation fact | Refresh if coordinate changed |
| After number allocation, before patient commit | Durable allocator outcome tied to patient-core idempotency key | Reuse exact allocation; never allocate replacement | Release only if allocator has reviewed transactional release; otherwise preserve explained consumption | Sequence equation records committed/available/explained outcome | Investigate any unexplained consumption |
| After patient commit, before crosswalk commit | Patient write-set evidence and transaction identity | Must not infer ownership from patient likeness; stop unless core transaction proves atomic rollback | Prefer transactional rollback of Unit A; if already durable, compensation workflow and protected orphan-migration record | Patient, mapping, allocation, provenance and core recon must commit together or be explicitly inconsistent | Migration operator plus architect |
| After patient/crosswalk commit, before checkpoint | Durable patient/crosswalk/provenance/core recon bundle | Resolve by idempotency key and repair checkpoint only under verified lineage; no second patient | No patient deletion merely to recreate checkpoint | Target/mapping checksum and core equation prove exact success | Controlled checkpoint recovery |
| During alias creation | Core mapping, alias key, source duplicate class and target collision coordinate | Recheck all; upsert/return exact prior migration alias only | Unit B transaction rolls back independently; patient core remains | Alias mapping/provenance/recon atomically agree | Withhold on collision/change |
| During contact creation | Core mapping, contact key, tuple class, primary-contact snapshot | Recheck parent and one-primary invariant; exact prior migration contact only | Unit C rollback/compensation; never edit existing-target contacts | Contact mapping/provenance/recon and primary count | Withhold optional child if safe |
| During insurance-history processing | One protected history key per source row | Resume missing source-row history by exact key | Roll back incomplete row transaction or complete metadata; source row never disappears | `INS-REC-014` remains exact; incomplete row is failed/held | Reprocess affected protected row set |
| During current-membership processing | All history rows, group key, provider/patient mappings, target collision coordinate | Recheck unique group and exact prior migration membership | Unit D group transaction rolls back; history remains preserved; no existing membership mutation | At most one current representation; all group rows accounted | Hold group and refresh collision state |
| After target writes, before reconciliation | Exact write set, atomic-unit transaction outcome, mappings and provenance | Stop further stages; reconstruct reconciliation from durable facts | Compensate by unit-specific reviewed action; no blanket hard delete | No `completed` state until all equations pass | Mandatory operator review |
| After reconciliation, before run completion | Signed/hash-bound reconciliation bundle and all checkpoints | Recover terminal checkpoint from exact bundle | No data rollback merely for missing run-complete flag | All differences and safety zeroes remain zero at same coordinate | Controlled terminal-state repair |

State-machine rules:

- `not_started -> extracted -> classified -> dry_run_accepted` is zero-write.
- Commit substates remain blocked until Phase 3 and later pilot authorization.
- `core_committed` is required before alias/contact/insurance target operations; history preservation may be prepared but cannot be reported committed without authorized protected persistence.
- Alias, contact and insurance current representation have independent pending/committed/withheld outcomes.
- `completed` requires reconciliation passed, privacy passed, side effects zero and no unexplained compensation.
- `quarantined`, `rollback_required` and `compensation_required` are explicit durable states, never aliases for failed or completed.

## H. Phase 3 foundation capability handoff

Each capability below remains an implementation prerequisite. Phase 3 must trace its tests to the Phase 2F interface IDs proposed here and the upstream IDs shown.

| Capability | Phase 2F interface/gate | Minimum focused tests before patient commit authorization |
|---|---|---|
| Environment/schema guards | Common envelope, D1 | Reject wrong connection/database, production target, fingerprint/count/version drift and non-read-only source. |
| Least-privilege source connection | D1, D-101 | Prove only SELECT/metadata on exactly `uuhms`; prohibited statement tests fail closed. |
| Source snapshot manager | Common envelope, provenance | Repeatable coordinated patient/child/insurance snapshot; drift invalidates descendants. |
| Target collision snapshot manager | Common envelope, reconciliation, D outputs | Detect row/schema/config changes between dry-run and commit; no target writes during capture. |
| Run/cohort manifest | Common envelope | Version/hash completeness, bounded cohort, deterministic replay and zero raw identifiers. |
| Protected patient/reference crosswalks | A2/A3 | Cardinality, immutability, exact lineage, target-missing/drift branches and access controls. |
| Protected remediation store | D1 | Approval/validity/revocation/conflict behavior; never expose values or use remediation as general matching. |
| Provenance/history store | A4 | One patient source record and one record for every insurance source row; exact duplicates remain separate; tamper detection. |
| Quarantine/exception ledger | A5/B | One root, ordered exception set, owner/SLA/release, topological release and no cross-chain union. |
| Reconciliation store | A6/C | Exact equations, missing measurement fails, immutable evidence hashes and zero unexplained difference. |
| HMAC/key management | A1/E4/F1 | Domain/key/version isolation, typed length-prefixing, rotation lineage and no secret/token logging. |
| Migration audit | A4/A6 | Separate migration activity only; no false operational audit events. |
| Patient-number allocator | F/G | Crosswalk-first, concurrent uniqueness, same allocation on rerun, explained sequence consumption and collision fail-close. |
| Migration-specific patient persistence | A2/F/G | Unit A atomicity, approved state only, `registered_by=null`, no operational services/side effects. |
| Alias persistence | F/G | Unit B atomicity, exact canonicalizer, target recheck, duplicate-withheld zero create, null creator/source target fields as specified upstream. |
| Contact persistence | F/G | Unit C atomicity, complete tuple, no duplicate/extra primary, parent-first, optional withholding and existing-target immutability. |
| Insurance history/membership persistence | A4/F/G | All source rows preserved; at most one per patient/provider; no default member/active state; no verification/eligibility; existing immutable. |
| Side-effect isolation | C2/D | Named services/integrations all observed zero; fail closed if isolation cannot be proven. |
| Idempotency | F | Same target/number/outcomes on rerun; concurrent duplicate prevention; drift and partial-lineage stops. |
| Checkpoint/resume | F3/G | Crash injection at every listed boundary; no duplicate and no unexplained success. |
| Rollback/compensation | G | Unit-specific rollback, compensation ledger, no universal deletion, no existing-target mutation. |
| Target-state validators | C/D | Every patient and insurance state field explicitly initialized/withheld; unsafe defaults rejected. |
| Privacy scanning | E | Full artifact coverage, zero unallowlisted findings, redacted diagnostics, containment/re-scan path. |
| Dry-run reporting | D | Complete inputs/outputs, aggregate only, nonbinding number action, source and target write deltas zero. |

## I. Recommended focused specification tests

The consolidated Phase 2F package should add documentation-focused tests for at least:

1. Every required Phase 2F JSON file parses and declares a version, exact `uuhms`, read-only source and `implementation_authorized=false` or equivalent nonimplementation boundary.
2. Every referenced Phase 2A-2E rule and exception ID exists in exactly one normative owner file.
3. Every pilot mapping field has exactly one authoritative upstream transformation owner.
4. Every reconciliation contract has a population, ordered mutually exclusive primary buckets, exact equation, tolerance 0 and missing-measurement failure rule.
5. Every upstream count partition still sums exactly, including all Phase 2C patient relationship equations and Phase 2E 31,307/16,950 partitions.
6. Every mandatory safety-zero name in C2 appears exactly once in the machine acceptance specification and is required to equal integer zero.
7. Allowed-nonzero outcomes are disjoint from safety-zero measures and each requires code/root/owner/SLA/provenance.
8. Every exception output resolves to an existing Phase 2A-2E catalogue code or a separately declared Phase 2F runtime/foundation code; no free-text-only exception.
9. Every quarantine outcome names one valid root domain, and no release can precede parent success.
10. Existing-target patient, child and membership mutations remain zero in all branches.
11. Every insurance source row has a provenance outcome and each patient/provider group selects at most one current representation.
12. Every dry-run input and output listed in D exists, and source/target writes both equal zero.
13. Every atomic unit has an idempotency key, checkpoint rule and rollback/compensation expectation.
14. Every crash boundary in G has durable facts, retry, duplicate prevention, rollback/compensation, checkpoint/reconciliation proof and operator action.
15. Full artifact-tree privacy scan coverage difference is zero and findings are reported without matched content.
16. No raw source/target keys, row tokens, OPD/member/phone/address/remediation values, database credentials, importer settings, persistence DDL or production-writing configuration appear.
17. Phase 3 capability list is complete and each capability maps to an interface and focused test.

Run only focused specification, evidence, privacy and cross-reference tests. Do not run the broad application suite for Phase 2F.

## J. Phase 2F exit recommendation for this workstream

This workstream can support a Phase 2F pass only if the consolidated package preserves all rules above and the independent reviewer finds no Critical or High issue. In particular:

- the zero-write dry-run may be specified but cannot be executed as a writing simulation;
- classified nonzero data-quality outcomes are not failures when fully accounted and safe;
- every equation difference and every safety assertion remains exactly zero;
- privacy scan coverage applies to the complete migration artifact package, not just Phase 2F;
- commit-mode patient persistence remains blocked by the owner-approved patient-state matrix, insurance initialization representation, D-101 account, protected stores, key management, safe allocator, isolated persistence, collision refresh, idempotency, checkpoint, resume and compensation foundation;
- Phase 2F documentation completion does not authorize the patient pilot or importer implementation.

