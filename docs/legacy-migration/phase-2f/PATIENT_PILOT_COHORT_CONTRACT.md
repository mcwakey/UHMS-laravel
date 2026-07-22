# Patient pilot cohort contract

Version: `2F.1.0`. Status: specification only; `implementation_authorized=false`.

## Boundary and authority

The only Classic connection/schema is read-only `legacy_uhms` / exact `uuhms`. Cohort C uses only the approved non-production target read-only. Production access, database writes, importers, migration/state/schema/UI/seeder/synchronization implementation and operational eligibility verification are prohibited.

This contract composes D-101, D-102, D-201, D-202, D-205, D-206, D-207, D-211, D-212, D-213 and D-214 plus Phase 2A `NK-004`, Phase 2B `TARGET-ACTOR-054`, Phase 2C `PATIENT-EXT-001`/identity/alias/chain/privacy contracts, Phase 2D `PATIENT-CHILD-EXT-001` and Phase 2E `INS-EXT-001`/history/consolidation contracts. It does not reopen them.

## Three pilot inputs

| Cohort | Contract | Size | Phase 2F result |
|---|---|---:|---|
| A — synthetic fixtures | Independently generated `SYNTHETIC-ONLY-P2F` records; never copied or perturbed Classic values | Exactly 66 scenarios, A-001–A-066 | Complete expected patient, alias, child, insurance, exception, provenance and reconciliation outcomes; zero writes/external actions. |
| B — protected source-derived dry run | One pinned read-only `uuhms` snapshot; domain-separated HMAC ordering; 24 mutually exclusive primary strata with retained secondary flags | `N_B = Σ min(q_s,A_s_after_prior_assignment)`, maximum 148 | Mapping/remediation/quarantine coverage only. Source rows labelled future commit-ready = **0**. |
| C — protected target collision/immutability | Fresh protected non-production target snapshot; unscoped live/soft-delete/merge/archive/alias/contact/insurance checks | One protected case for every available required branch; no unversioned sampling | Comparison and collision classification only; target rows created/updated/deleted = 0. |

Normative machine contracts are `patient_pilot_cohort_rules.json`, `patient_pilot_scenarios.json`, `patient_pilot_source_selection_rules.json` and `patient_pilot_remediation_input_rules.json`.

## Readiness classes

`MAPPING_CLASSIFIABLE` means fields can be assigned deterministic dry-run outcomes. `REMEDIATION_REQUIRED` means valid independent protected evidence is missing. `REMEDIATION_ACCEPTED_CANDIDATE` means the remediation interface passes but target/foundation gates remain. `FUTURE_COMMIT_CANDIDATE` means all mapping and representation gates project cleanly. `FUTURE_COMMIT_READY` additionally requires the reviewed Phase 3 foundation and fresh preflight. Phase 2F assigns `FUTURE_COMMIT_READY` to zero real source rows.

`EXISTING_TARGET_LINK_CANDIDATE` is crosswalk/link projection only and permits zero patient or child mutation. `QUARANTINED` holds the exact root/dependency chain. `RUN_STOPPED` invalidates all success claims for the run.

## Protected cohort manifest

The operational manifest must contain: contract/run identity; source and target snapshot identities; cohort and scenario/stratum; protected source and dependency-chain tokens; mapping/remediation classes; exact expected exception/reconciliation/collision results; inclusion/exclusion reasons; selection/HMAC/canonicalization versions; approval state; retention class; upstream bundle hashes; and keyed record integrity.

It contains one primary Cohort B record per protected root. Secondary flags never duplicate quota arithmetic. Raw source/target IDs, patient values, HMACs, rank tokens and remediation values remain outside repository artifacts and ordinary logs. Repository output is limited to scenario IDs, aggregate counts and nonsecret contract hashes.

## Cohort C exact branches

| ID | Condition | Required outcome |
|---|---|---|
| C-001 | Valid explicit crosswalk to live, unmerged target | Link projection only; preserve every target scalar/alias/child/membership. |
| C-002 | Approved target missing | Quarantine; allocate/create nothing; `LEGACY-PATIENT-TARGET-025`. |
| C-003 | Target soft-deleted | Quarantine; never restore/replace; `LEGACY-PATIENT-TARGET-026`. |
| C-004 | Target merged/redirected | Quarantine; never follow pointer; `LEGACY-PATIENT-TARGET-026`. |
| C-005 | Patient-number collision/configuration race | Stop Unit A; no suffix/reallocation/recycle; `LEGACY-PATIENT-NUMBER-037`. |
| C-006 | Target alias/number namespace collision | Withhold alias only; no identity match; `LEGACY-PATIENT-ALIAS-006/007`. |
| C-007 | Existing-target contact difference | Comparison evidence only; never mutate existing contacts; `LEGACY-PATIENT-CHILD-CONTACT-012`. |
| C-008 | Existing patient/provider membership | Same protected prior lineage is no-op; otherwise immutable conflict; `LEGACY-INSURANCE-TARGET-030/041`. |
| C-009 | Target member-number collision | History-only/conflict; no overwrite/reassignment; `LEGACY-INSURANCE-MEMBER-019`. |
| C-010 | Schema/constraint/configuration/fingerprint drift | Stop whole run and recapture; target drift exceptions. |
| C-011 | Multiple target primary contacts | Stop Unit C; never clear/reselect an existing primary; `LEGACY-PATIENT-CHILD-CONTACT-013`. |

## Acceptance and blockers

Every outcome must reconcile to one terminal primary disposition; expected classified exceptions may be nonzero, while every unexplained difference, existing-target mutation, database write and prohibited side-effect count equals zero.

Commit mode remains blocked by the D-101 account, protected remediation, approved patient-state tuple, approved insurance `member_type`/active/primary representation, provider crosswalk runtime inputs, protected stores/HMAC management, safe allocator, migration persistence/isolation, collision refresh, and reviewed idempotency/resume/compensation foundation.
