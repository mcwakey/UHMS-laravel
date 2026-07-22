# Existing target patient contract

Status: Phase 2C specification only. It authorizes no target match, crosswalk row, patient update, restoration, merge, alias transfer, schema change or database write.

## Authority and evidence

Q-002 permits a Classic patient to link to an existing renewed patient only through an explicit protected pre-approved crosswalk or a separately reviewed deterministic identity determination. D-201 keeps Classic keys in protected mappings only. D-211 prevents alias uniqueness from driving a match/merge. D-213/Q-007 prevent target blanks or another patient's data from becoming permission to invent or reassign identity.

Confirmed target evidence:

- `patients.id` is target-generated; `patient_number` is required and unique under `utf8mb4_unicode_ci`.
- `Patient` uses soft deletes. Default queries hide deleted patients, while unique patient numbers and Ghana Card values remain reserved.
- Merge state is represented by `merge_status` and nullable `merged_to_patient_id`; application helper `getFinalPatient()` can follow a merge pointer. Migration must not use that behavior to redirect mappings.
- `patient_aliases` is not soft-deletable and globally reserves `(alias_type, normalized_alias_value)`, including aliases whose owner is soft-deleted. Force deletion can cascade aliases and a large target child graph.
- `archived_patients` retains a patient-number snapshot and protected payload; its `patient_number` is indexed but not unique and can remain after parent removal.
- Phase 2B's existing-user contract establishes the accepted immutability pattern: protected pre/post domain-separated HMAC comparisons with zero migration-caused target changes.
- The sanitized target discovery baseline had 100 patients, no soft-deleted patients and no aliases at inspection time. This mutable aggregate is not a collision guarantee and must be recaptured at preflight/freeze.

## Only permitted link authorities

1. **Pre-approved protected crosswalk**: one exact typed `uuhms.patients.PAT_ID` source identity and one target `patients.id`, with approver, evidence class, scope, validity/revocation state, rule version and approval time.
2. **Separately reviewed deterministic identity decision**: the controlled outcome of `PATIENT_DUPLICATE_REVIEW_CONTRACT.md`, recorded with equivalent protected authority before migration consumes it.

No source-to-target link may be generated from name, normalized name, OPD/alias, phone, email, DOB, gender, address, Ghana Card/national identifier alone, similarity/fuzzy/phonetic search, weighted score, target patient search, target merge-confidence output or Classic key similarity. Those facts may support a human review but do not authorize a link.

The crosswalk is protected operational data and is not stored in this repository. A raw Classic key or patient identifier must never appear in logs/specifications, and Classic `PAT_ID` must never be written to `patients.id`, `patient_number` or `patient_aliases.source_patient_id`.

## Deterministic crosswalk validation

Evaluate every candidate in this order:

1. Validate source and target schema fingerprints, crosswalk signature/version/approval scope and protected token canonicalization.
2. Require exactly one crosswalk record for exactly one source token and one target ID.
3. Resolve the target by primary key using an unscoped/with-trashed query; ordinary Eloquent scope is insufficient.
4. Reject missing targets, multiple crosswalk candidates, revoked/expired approvals and contradictory source/target ownership.
5. Require a live, unmerged target: `deleted_at IS NULL`, active merge ownership as defined by the reviewed state matrix, and no unresolved `merged_to_patient_id`. Do not restore, follow a pointer or substitute another patient.
6. Compare all verified evidence covered by the approval. A new conflict quarantines unless the recorded approval explicitly and validly covers that conflict.
7. Lock or otherwise pin the target state for the atomic mapping outcome. If target state changes between reviewed snapshot, preflight and commit, stop and re-review.
8. Persist only the protected link/audit in the existing-target link transaction. Perform no patient, alias, child, archive or operational workflow mutation in that link path. After the link commits, D-201's separate alias stage may create one new collision-free `legacy_opd` alias owned by the mapped target under `LEGACY_OPD_ALIAS_CONTRACT.md`; it may not alter any pre-existing alias.

### Cardinality

- One source token maps to at most one successful target ID.
- One target ID receives at most one Classic source token unless a separate reviewed decision explicitly authorizes the otherwise merge-like many-source-to-one outcome and all source chains are accounted for.
- Multiple source rows may never collapse merely because they share a name, phone, OPD or other weak evidence.
- A missing/invalid crosswalk does not fall through to automatic new-patient creation in the same run. It enters existing-target ambiguity quarantine until reviewed, preventing duplicate creation after an intended but defective link.

## Outcome table

| Condition | Patient outcome | Number outcome | Alias outcome | Release condition |
|---|---|---|---|---|
| Unique approved link to live/unmerged target; evidence and state agree | `PATIENT_EXISTING_TARGET_EXPLICIT_CROSSWALK` | Keep existing number; allocate zero | Independently apply alias contract; no reassignment | Atomic protected mapping/audit, immutability proof and reconciliation pass. |
| Crosswalk target missing | Existing-target ambiguity quarantine | Allocate zero | Hold | Correct/revoke reviewed crosswalk; do not silently create replacement. |
| Multiple target candidates or crosswalk records | Existing-target ambiguity quarantine | Allocate zero | Hold | Controlled identity determination produces one approved result or explicit no-link decision. |
| Conflicting verified evidence | Existing-target conflict quarantine | Allocate zero | Hold | Reviewer explicitly resolves the conflict with authoritative evidence. |
| Soft-deleted target | Existing-target state quarantine | Number remains reserved; allocate zero | Ownership remains reserved | Controlled identity review; migration does not restore or replace. |
| Merged/redirected target | Existing-target state quarantine | Number remains historical; allocate zero | No automatic transfer/release | Controlled identity review; migration does not follow merge pointer. |
| Target alias/patient-number/Ghana Card uniqueness collision without approved link | Target uniqueness/entity quarantine as applicable | No match inferred | Withhold conflicting alias | Resolve collision through controlled review. |
| Separately reviewed no-link decision | Re-enter new-patient eligibility from the beginning | Target-generated at commit only if eligible | Independently classified | Reviewed decision plus all new-patient prerequisites. |

## Target immutability

A valid existing-target link is crosswalk-only. It must preserve every target fact and relationship, including:

- `id`, patient number and all names/demographics/contact/national-identifier fields;
- `registered_by`, created/updated/deleted timestamps and registration provenance;
- status, active/temporary/deceased/identity-confirmed fields;
- merge status, pointer, dates, actors, requests and logs;
- all pre-existing aliases and their ownership;
- archive rows/payloads;
- emergency contacts, insurance memberships, visits, appointments and every clinical, admission, billing, claim, pharmacy, privacy and other child;
- activity/audit records, files/avatar and operational access/search state.

Do not backfill even a blank target field from Classic data. Do not update timestamps, normalize a stored value, set `registered_by`, restore a soft deletion, change status or create a general child merely because the link exists. A unique, valid, collision-free Classic OPD is the sole child exception required by D-201: only the separate alias stage may create a new `legacy_opd` row after the owner mapping exists. Blank, invalid, duplicated or target-conflicting OPDs create no alias, and no pre-existing alias may be updated, reassigned or released.

At reviewed preflight and after the mapping transaction, compare versioned domain-separated HMACs of canonical field groups. The comparison must include row existence, every patient scalar, registration/status/delete/merge state, every pre-existing alias/owner and child cardinality/content groups. Any migration-caused delta stops the run. The separately authorized alias stage records and reconciles its one permitted new alias delta under `PATIENT-REC-ALIAS-002`; it never changes the immutable pre-existing set. A concurrent non-migration target change invalidates the reviewed snapshot and requires revalidation; it is not silently attributed to migration or ignored.

## Soft deletion, merge and force-delete rules

- All target existence/collision checks are unscoped/with-trashed.
- Soft-deleted patients are not available link targets without a new controlled review; their identifiers and aliases remain reserved.
- Merged-away patients are not redirected through `getFinalPatient()` or equivalent. The approved target ID must itself be eligible and consistent.
- Archive snapshots are identity history, not alternate match targets. Do not create, update or delete them during linking.
- No force delete is permitted. Installed FKs can cascade/null a large patient graph and aliases; delete-and-recreate is prohibited.
- No identifier/alias ownership is released automatically after delete or merge. Release is a separate controlled post-migration identity decision.

## Existing-target concurrency

The future mapping boundary must either hold a suitable target row/state lock for the short atomic crosswalk commit or use an equivalent optimistic version/fingerprint check. It must not lock or mutate the patient during long manual review.

Required sequence:

1. review against a named sanitized target snapshot;
2. at commit, re-resolve unscoped and compare the approved target-state fingerprint/version;
3. persist the mapping/audit atomically with its unique source/target cardinality constraints;
4. reprove no migration-caused patient, pre-existing-alias or other-child delta; any later approved new legacy alias is handled and reconciled separately;
5. advance checkpoint only after mapping/target reconciliation.

If the row becomes deleted, merged, changed, missing or differently owned at any point, stop and re-review. A concurrent target change is never permission to switch candidates.

## Reconciliation

Contract ID: `PATIENT-REC-EXISTING-030`. Valid links also contribute exactly once to the existing-target term of `PATIENT-REC-ENTITY-001`.

For existing-target candidate decisions:

`candidate decisions = valid explicit links + missing target + multiple/ambiguous target + conflicting verified evidence + soft-deleted/merged target + revoked/invalid approval + failed validation`

Outcomes are mutually exclusive and the difference must be zero.

For valid links:

`valid explicit links = distinct source tokens = distinct approved crosswalk records = successful protected mappings`

Unless an explicit reviewed many-source-to-one decision exists, these also equal distinct target IDs.

Zero assertions:

- patient rows created by existing-link path = 0;
- patient rows updated/restored/deleted by link path = 0;
- numbers allocated or changed by link path = 0;
- target aliases created/reassigned/released by the existing-link path = 0; a separately staged D-201 `legacy_opd` creation is counted only in `PATIENT-REC-ALIAS-002`;
- target child/archive/merge/privacy/audit rows changed by link path = 0;
- automatic existing-target matches = 0;
- source tokens linked to more than one target = 0;
- target IDs linked from multiple sources without explicit reviewed authority = 0;
- links to missing, soft-deleted or merged-inconsistent targets = 0;
- mappings whose stored target number differs from the patient row = 0;
- pre/post target immutability HMAC differences caused by migration = 0.

Existing target links remain one mutually exclusive term in the global patient-entity equation. Collision and suspected-duplicate flags are secondary and cannot double-count the patient.

## Classified exceptions, ownership and SLA

| Code | Use in this contract |
|---|---|
| `LEGACY-PATIENT-TARGET-023` | Multiple/ambiguous candidates, malformed/revoked crosswalk, crosswalk cardinality breach or unresolved reviewed identity determination. |
| `LEGACY-PATIENT-TARGET-024` | Conflicting verified evidence, existing target field/uniqueness conflict, immutable-target delta or concurrent target-state drift. |
| `LEGACY-PATIENT-TARGET-025` | Approved target ID does not exist. |
| `LEGACY-PATIENT-TARGET-026` | Approved target is soft-deleted, merged, redirected or otherwise reserved historical identity state. |
| `LEGACY-PATIENT-ALIAS-006` | Existing target alias ownership conflict. |
| `LEGACY-PATIENT-ALIAS-007` | Existing/archived patient-number namespace conflict. |
| `LEGACY-PATIENT-DUPLICATE-028` | Any automatic existing-target match, merge-pointer following or unapproved many-source-to-one collapse. |
| `LEGACY-PATIENT-PRIVACY-036` | Missing protected crosswalk/immutability provenance or disclosure of raw identity data. |

Identity Governance owns ambiguity/conflict review with a two-business-day objective for active identity-safety conflicts and five business days for nonurgent reviewed crosswalk work. The Migration Technical Owner handles schema/state/atomicity failures immediately; Privacy/Security handles protected-data failures immediately. SLA expiry never defaults to an existing patient, creates a new replacement, restores a target, overwrites a field, follows a merge or releases an alias.

## Privacy

Crosswalk contents, patient identifiers, target state and conflict evidence are protected data.

- Represent source identity with domain-separated HMAC-SHA-256 `patient-source-key-v1` and target pre/post groups with `target-patient-immutability-v1`.
- Use distinct domains for names, phones, OPDs, emails and national identifiers. Plain SHA-256 is prohibited for these low-entropy values.
- Canonical payloads use explicit field/type/null markers and length-prefixed UTF-8; record algorithm, purpose, key and canonicalization versions.
- Keep raw crosswalk values and row-level HMACs in the protected operational ledger only. Repository artifacts and ordinary logs contain aggregate counts, contract IDs and nonidentifying schema/configuration fingerprints.
- HMAC evidence is environment-bound and cannot substitute for the operational crosswalk. Missing keys/version history, cross-environment comparison or raw-PHI disclosure stops the process.

## Stop conditions and Phase 3 prerequisites

Stop the affected run/link when:

- exact source/target fingerprints and non-production target identity are not proven;
- protected pre-approved crosswalk/review authority, unique cardinality constraints, signatures/version or provenance is absent;
- target resolution uses a scoped query or excludes soft-deleted/merged/archive ownership;
- target is missing, duplicated, soft-deleted, merged, changed since review or conflicts with verified evidence;
- automatic name/phone/OPD/email/DOB/gender/address/national-ID/fuzzy/scored matching is attempted;
- the existing-link path would allocate a number, mutate/backfill the target, attach/release an alias, restore/follow a merge, create children or cause side effects; the separate D-201 alias stage remains governed by its own stricter contract;
- pre/post immutable target fingerprints differ, checkpoint/mapping/target disagree, or any reconciliation difference is nonzero;
- crosswalk/HMAC key versions are missing or protected data enters documentation/logs/source control.

Before Phase 3, implement and independently review the protected signed crosswalk, deterministic review-authority format, unique source/target ownership constraints, unscoped state validator, atomic crosswalk/audit transaction, target-state snapshot/version checks, immutability HMAC comparison, checkpoint behavior, review workflow and privacy key management. This contract defines the prerequisite behavior only.
