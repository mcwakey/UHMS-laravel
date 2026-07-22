# Alias, chain, and existing-target immutability analysis draft

Status: read-only repository analysis for Phase 2D consolidation. This draft authorizes no source or target database operation, importer, persistence service, crosswalk/quarantine table, alias/contact row, demographic update, or release action.

## Evidence and authority boundary

This analysis consumes, without altering or reopening:

- D-201, D-207, D-211 and D-213, plus Q-001, Q-002, Q-005, Q-006, Q-007 and Q-008;
- Phase 2C `PATIENT-COL-001` (`patients.PAT_ID`), `PATIENT-COL-003` (`patients.OpdNo`) and the seven Phase 2D-owned records `PATIENT-COL-007`, `009`, `010`, `011`, `012`, `013`, and `014`;
- Phase 2C alias rules `PATIENT-ALIAS-001..017`, canonicalizer `legacy-opd-comparison-v1`, exception codes `LEGACY-PATIENT-ALIAS-003..007` and `LEGACY-PATIENT-ALIAS-041`, and reconciliation `PATIENT-REC-ALIAS-002`;
- Phase 2C existing-target rules `PATIENT-TARGET-001..014`, class `PATIENT_EXISTING_TARGET_EXPLICIT_CROSSWALK`, reconciliation `PATIENT-REC-EXISTING-030`, and immutability HMAC domain `PATIENT-PRIV-006` / `target-patient-immutability-v1`;
- Phase 2C patient-root chain token `PATIENT-PRIV-007` / `legacy-patient-chain-v1`, optional locally held child-subchain token `PATIENT-PRIV-012` / `legacy-patient-child-subchain-v1`, dependency rules `PATIENT-CHAIN-001..028`, and reconciliation `PATIENT-REC-CHAIN-100`;
- Phase 2C extraction `PATIENT-EXT-001` and protected patient-row fingerprint domain `PATIENT-PRIV-002` / `patient-row-content-v1`;
- Phase 2B `TARGET-ACTOR-054`: patient registration has no approved Classic actor, `registered_by` is null with protected absence provenance, and `Legacy Actor Unknown` is prohibited. Phase 2C applies the same absence rule to `patient_aliases.created_by` through `PATIENT-ALIAS-012`.

`APPROVED_DECISION_SPECIFICATIONS.md` and `DECISIONS.md` are policy authority. Confirmed source/target facts cited here are the Phase 1 and Phase 2C captured baselines, not a fresh database inspection. Classic scope is exactly `uuhms`; no database was accessed for this draft.

## Non-negotiable integration invariants

1. `patients.PAT_ID` is a protected source identity only. A valid protected patient mapping must commit before any Phase 2D child can be released; it is never a renewed ID, number, alias value, or `patient_aliases.source_patient_id`.
2. Every Phase 2D candidate inherits its source patient's `PATIENT-PRIV-007` root token, key version, canonicalization version, source snapshot identity and protected mapping outcome. A child-local hold may additionally use `PATIENT-PRIV-012`, but it never changes or replaces the patient root.
3. A quarantined or unresolved patient holds the OPD alias outcome, NOK tuple outcome and all four demographic-field outcomes. Nothing may attach to another patient through name, phone, address, occupation, religion, marital status, contact similarity, OPD similarity, target search, or a suspected-duplicate signal.
4. Parent success is necessary but not sufficient for child release. Alias, emergency contact and each optional demographic field retain separate validation, exception and reconciliation outcomes.
5. A child failure may remain locally quarantined after a successful patient mapping. It does not invalidate or roll back the patient merely because the child is absent or invalid. Conversely, no child may release before the patient mapping.
6. Duplicate or withheld OPD aliases do not block an otherwise valid contact or demographic outcome. Contact or demographic evidence never influences alias ownership or source-wide duplicate classification.
7. Existing-target ambiguity blocks the entire source chain. A valid explicit existing-target link permits the source chain to reference that mapped patient but authorizes no child enrichment. The sole permitted later delta is one independently eligible, collision-free `legacy_opd` alias through the separate Phase 2C alias stage.
8. Release is crosswalk-first, idempotent and topological. A checkpoint advances only after the parent map, child outcome, protected provenance and applicable partition reconcile on the same coordinated source/target state.

## Alias integration by reference only

Phase 2D must not define an OPD canonicalizer, grammar, uniqueness grouping, ownership ladder, collision namespace, decoration rule, or release workflow. It must point to `LEGACY_OPD_ALIAS_CONTRACT.md`, `patient_alias_rules.json` and the IDs below.

| Integration subject | Authoritative Phase 2C reference | Required Phase 2D behavior |
|---|---|---|
| Source and type | `PATIENT-COL-003`, `PATIENT-ALIAS-001..002` | Source remains only `uuhms.patients.OpdNo`; type is exactly `legacy_opd`. |
| Canonical comparison | `PATIENT-ALIAS-003`, `legacy-opd-comparison-v1` | Reuse the exact version; an alternative or locally modified canonicalizer is prohibited. |
| Blank/invalid/duplicate | `PATIENT-ALIAS-004..007`; `LEGACY-PATIENT-ALIAS-003..005` | Retain the Phase 2C outcomes and exception identities; do not reinterpret them as child-contact outcomes. |
| Target collisions | `PATIENT-ALIAS-008..009`, `014..016`; `LEGACY-PATIENT-ALIAS-006..007` | Check live, soft-deleted, merged, archived patient-number and alias ownership; do not transfer, recycle, decorate or choose an owner. |
| Same-owner idempotency | `PATIENT-ALIAS-010`, `LEGACY-PATIENT-ALIAS-041` | Zero write only when mapped target, protected source token, normalized alias and rule/provenance versions all agree. |
| Target fields | `PATIENT-ALIAS-011..012` | `source_patient_id = null`; `created_by = null` plus protected absence provenance. Classic `PAT_ID` and actor fallbacks are prohibited. |
| Parent sequencing | `PATIENT-ALIAS-013`, `PATIENT-REC-CHAIN-100` | Alias persistence waits for a committed protected patient mapping. Entity quarantine leaves the alias classified and pending, never discarded. |
| Reconciliation | `PATIENT-ALIAS-017`, `PATIENT-REC-ALIAS-002` | One alias outcome per source patient; duplicate-withheld rows equal duplicate exceptions. Keep alias and patient-entity sums separate. |

`OriginalName` (`PATIENT-COL-023`) and `OriginalOpd` (`PATIENT-COL-024`) remain protected provenance only and were blank for all 16,950 captured rows. They create zero aliases. Any future nonblank value is source-fingerprint drift under `LEGACY-PATIENT-DRIFT-033` plus a Phase 2D-specific drift guard; it stops alias processing for semantic review rather than becoming a new alias source.

Suggested Phase 2D integration IDs, all additive guards rather than replacement alias rules:

| Proposed ID | Trigger and disposition | Required Phase 2C cross-reference |
|---|---|---|
| `PATIENT-CHILD-ALIAS-001` | Parent mapping unavailable: retain alias outcome as parent-held; create no alias. | `PATIENT-ALIAS-013`, `PATIENT-REC-CHAIN-100`, `LEGACY-PATIENT-CHAIN-030` |
| `PATIENT-CHILD-ALIAS-002` | Any Phase 2D attempt to redefine canonicalization, duplicate grouping or ownership: stop specification/runtime. | `PATIENT-ALIAS-003..016`, `legacy-opd-comparison-v1`, alias exception codes unchanged |
| `PATIENT-CHILD-ALIAS-003` | Nonblank `OriginalName` drift: stop and re-evidence semantics; create zero aliases. | `PATIENT-COL-023`, `LEGACY-PATIENT-DRIFT-033` |
| `PATIENT-CHILD-ALIAS-004` | Nonblank `OriginalOpd` drift: stop and re-evidence semantics; create zero aliases. | `PATIENT-COL-024`, `LEGACY-PATIENT-DRIFT-033` |
| `PATIENT-CHILD-ALIAS-005` | Existing target alias, number, owner or provenance differs: withhold under Phase 2C collision outcome; modify nothing. | `PATIENT-TARGET-009..011`, `PATIENT-ALIAS-008..010`, `LEGACY-PATIENT-ALIAS-006..007`, `041` |

## Exact Phase 2D relationship register requirements

The consolidated machine relationship registry should define exactly the following five Phase 2D relationship IDs. Each has one and only one companion sentinel-rule ID. Same-row projections are logical migration relationships, not asserted Classic foreign keys.

| Proposed relationship ID | Source / exact predicate | Target projection | Parent-map prerequisite | Blocking and existing-target behavior | Reconciliation / extraction |
|---|---|---|---|---|---|
| `PATIENT-CHILD-REL-001` | One captured `uuhms.patients` row, identified only by protected `PAT_ID`; same-row fields `Work`, `Address`, `Religion`, `MaritalStatus`. No join or inferred parent. | `patients.occupation`, evidenced unstructured `patients.address`, `patients.religion`, `patients.marital_status`; no inferred city/region/postal/digital fields. | Successful Phase 2C protected patient mapping and same `PATIENT-EXT-001` snapshot coordinate. | Patient quarantine holds all four field outcomes. Each optional field may remain locally withheld after parent success. Existing-target link yields comparison/provenance only and zero target mutation. | `PATIENT-CHILD-REC-OCCUPATION`, `-ADDRESS`, `-RELIGION`, `-MARITAL`; shared Phase 2D projection of `PATIENT-EXT-001`. |
| `PATIENT-CHILD-REL-002` | One same-row tuple `(NOK, NOKPhoneNo, NOKRel)` from the captured patient row. Tuple membership is exact; the fields cannot produce three independent contacts. | One emergency-contact candidate outcome, not dormant inline patient emergency fields. | Protected patient source identity exists for classification; successful target mapping is required for any later child release. | Parent quarantine holds the tuple. All-blank is optional absence; partial/invalid is a local child outcome. Existing target is evidence-only and creates/changes no contact. | `PATIENT-CHILD-REC-NOK-SOURCE`; shared Phase 2D projection of `PATIENT-EXT-001`. |
| `PATIENT-CHILD-REL-003` | Mapped source patient token resolves through the protected crosswalk to exactly one live target `patients.id`; no value-based contact join. | At most one migration contact candidate owned by the mapped patient, subject to the independently validated contact tuple and target primary-contact invariant. | Committed parent crosswalk plus tuple validation, target schema/state guard and migration-specific side-effect isolation. | No valid map means child held, never reassigned. Existing-target patient always takes the immutable/evidence-only branch. Multiple target primary contacts or target conflict withhold the child and change nothing. | `PATIENT-CHILD-REC-CONTACT-RUNTIME`, `PATIENT-REC-EXISTING-030`; future protected child idempotency key. |
| `PATIENT-CHILD-REL-004` | `uuhms.patients.OpdNo` owned by the same source row; exact owner is the protected Phase 2C patient mapping. | `patient_aliases(alias_type=legacy_opd)` only. | `PATIENT-ALIAS-013`; parent mapping has committed. | All sentinel, canonicalization, source-duplicate, target-collision, idempotency and existing-target behavior is delegated unchanged to Phase 2C. Duplicate withholding does not block REL-001..003. | Reference `PATIENT-REC-ALIAS-002`; do not create a Phase 2D alias partition or extraction snapshot. |
| `PATIENT-CHILD-REL-005` | `PATIENT-PRIV-007` token for the source `patients.PAT_ID`, inherited by the alias outcome, NOK tuple, emergency-contact candidate and four demographic outcomes. | Protected quarantine/provenance/release state only; no operational patient field. | Same domain, HMAC key version, canonicalization version, source snapshot and protected patient map must verify. | Missing/mismatched root token stops every child. A local `PATIENT-PRIV-012` subchain may hold one child but cannot change the root. Existing-target ambiguity holds all; valid existing target retains evidence-only child outcomes except the separate alias route. | `PATIENT-REC-CHAIN-100` plus every Phase 2D field/tuple partition; same snapshot dependency as `PATIENT-EXT-001`. |

Every machine relationship record must include source identity, target structure, parent-map prerequisite, null, blank, sentinel and invalid behavior, blocking/quarantine scope, existing-target disposition, exception references, reconciliation ID, extraction dependency and protected-provenance requirement. No rule may create a synthetic contact, demographic reference parent or fallback patient.

## One-to-one sentinel registry requirements

| Proposed sentinel ID | Relationship | Exact field-specific rule |
|---|---|---|
| `PATIENT-CHILD-SENT-001` | `PATIENT-CHILD-REL-001` | There is no relationship sentinel in `Work`, `Address`, `Religion` or `MaritalStatus`. The installed Classic fields are non-null; blank after approved decode/trim is optional absence and enters that field's blank/null bucket, not an error and not a shared zero-is-null rule. Nonblank invalid/overlength/unrepresentable content is withheld at field level. Missing/invalid `PAT_ID` remains a Phase 2C key/extraction stop, never a sentinel. |
| `PATIENT-CHILD-SENT-002` | `PATIENT-CHILD-REL-002` | There is no tuple-parent sentinel. All three fields blank after approved normalization means `CONTACT_ALL_FIELDS_BLANK`; one/two populated fields means a partial tuple, not a missing patient. Empty numeric-looking or conventional placeholder contact content is not globally approved as null. No name, phone or relationship may be synthesized from another tuple member or patient field. |
| `PATIENT-CHILD-SENT-003` | `PATIENT-CHILD-REL-003` | No mapped target patient is not a nullable target FK outcome: it is `parent_not_released`/parent quarantine. Classic `PAT_ID = 0`, null, guessed IDs, first/current/admin/importer IDs and synthetic parents are prohibited. Target FK nullability cannot waive the required mapped-parent relationship. |
| `PATIENT-CHILD-SENT-004` | `PATIENT-CHILD-REL-004` | Delegate completely to `PATIENT-ALIAS-004..009` and `PATIENT-REC-ALIAS-002`. Blank OPD is an alias-only outcome, source duplicates are withheld, and no Phase 2D zero/blank/default/canonicalization rule may be added. |
| `PATIENT-CHILD-SENT-005` | `PATIENT-CHILD-REL-005` | A chain token has no sentinel or substitute value. Missing key/version, invalid domain, ambiguous serialization, token collision or cross-environment/domain comparison is a privacy/provenance failure under `LEGACY-PATIENT-PRIVACY-036`; all affected children remain held. |

Per D-207/Q-005, these rules are relationship-specific. A blank field in one optional demographic cannot establish a global blank/null rule, and no numeric/text placeholder has sentinel meaning unless a later field-specific evidence contract explicitly approves it.

## Parent quarantine and topological release

Phase 2D specializes, but does not replace, Phase 2C's release order:

1. Verify exact `uuhms`, read-only/fingerprint guards, the coordinated `PATIENT-EXT-001` snapshot, `PATIENT-PRIV-002` row fingerprint and the `PATIENT-PRIV-007` root token/version.
2. Complete the Phase 2C entity outcome and commit/resolve the protected patient crosswalk. Patient identity, target ambiguity, provenance/privacy, extraction, state or schema failure leaves every Phase 2D outcome held.
3. Reconcile the source-level alias partition, NOK tuple partition and all four demographic partitions. Classification may occur before target persistence, but creates no target child.
4. Release the alias only through `PATIENT-ALIAS-013`, the full `PATIENT-ALIAS-001..017` contract and `PATIENT-REC-ALIAS-002`.
5. Release an emergency contact only when the whole tuple and contact-specific validation pass, the mapped patient is newly created/eligible for enrichment under the later persistence contract, the child idempotency key is exact, and target contact/primary invariants pass. An existing-target mapping always takes the immutable/evidence-only branch.
6. Release each valid occupation, address, religion and marital-status candidate independently after parent success. Invalid/blank/withheld sibling fields do not block one another. Existing-target mappings remain comparison-only.
7. Reconcile the parent token, each child outcome and all zero assertions before advancing the checkpoint. A local child failure stays attached to its original root and may be retried only after its release condition is evidenced.

A released parent with a locally held child is valid. A released child with an unresolved parent, a child moved to a different parent, or a child whose source snapshot differs from its parent is prohibited and stops the affected run.

## Reconciliation requirements

All primary buckets below are mutually exclusive and use explicit precedence: extraction/privacy failure, parent state, existing-target state, child validity/absence, then eligible/released outcome. Secondary diagnostic flags never double-count a primary partition.

### Source patient entity and alias references

- Reuse `PATIENT-REC-ENTITY-001`; Phase 2D adds no patient-entity term.
- Reuse `PATIENT-REC-ALIAS-002` unchanged. Phase 2D created alias rows = 0, alternative canonicalizers = 0, `OriginalName` aliases = 0, `OriginalOpd` aliases = 0, duplicate aliases automatically assigned = 0, and alias ownership influenced by contact/demographic similarity = 0.
- A duplicate-withheld alias contributes once to the alias partition and does not alter any Phase 2D NOK/demographic partition.

### NOK source tuple

`Classic patient rows = complete_valid + partial + all_blank + invalid + parent_quarantined + existing_target_evidence_only + failed`

`partial` covers nonempty but incomplete tuples after field normalization; `invalid` covers a tuple with required contact validity failure after completeness classification. If the final contact contract subdivides either bucket, the sub-buckets must sum exactly to it. Difference = 0. Every source patient receives exactly one source-tuple outcome even when no child is created.

### Emergency-contact runtime

`eligible_contact_candidates = created + exact_idempotent_prior_migration_contact + withheld_partial_or_invalid + parent_not_released + existing_target_immutable + commit_failed`

Difference = 0. `created` is possible only in a future authorized persistence phase and must be zero during Phase 2D. Prove at most one migration-created primary contact per newly created patient; partial contacts marked primary = 0; changes to an existing primary flag = 0; contacts attached to a different/guessed patient = 0.

### Demographic fields

For each `f` in occupation, address, religion and marital status:

`Classic patient rows = valid_f + blank_or_null_f + invalid_f + overlength_f + unrepresentable_f + parent_quarantined_f + existing_target_immutable_f + failed_f`

Difference = 0 for every field. Optional blank maps to null/absence provenance and is not an exception where the target representation permits null. A nonblank invalid value is not silently counted as blank/null. The four equations are independent and do not double-count the Phase 2C patient entity partition.

### Parent-chain release

Reference `PATIENT-REC-CHAIN-100`:

`registered child outcomes = released + held_parent + held_domain + sentinel_or_optional_absence + existing_target_evidence_only + failed`

Difference = 0 per child type. Required zero assertions are: child released before parent, guessed/reassigned patient, artificial parent, cross-chain union, missing root token, unclassified omitted child and snapshot-coordinate mismatch.

### Existing-target immutability

Reference `PATIENT-REC-EXISTING-030` and `PATIENT-PRIV-006`. For explicitly linked target patients, migration-caused changes must equal zero for:

- patient occupation, address, city, region, postal code, digital address, religion and marital status;
- every emergency-contact row, soft-delete state and primary-contact flag;
- target phone/secondary-phone and dormant inline emergency-contact fields;
- all pre-existing aliases, alias ownership and alias content;
- target demographic/contact timestamps, patient timestamps and activity/audit history.

Do not fill target blanks, compare fields for automatic identity, reconcile conflicts by overwriting, create a contact, change a primary flag, or delete/merge contacts. Store source evidence and comparison outcomes only in protected provenance. The only permitted later delta is a separately staged collision-free `legacy_opd` alias under `PATIENT-REC-ALIAS-002`; it must be excluded from the immutable pre-existing-alias set and reconciled independently.

### Privacy

Reference `PATIENT-REC-PRIVACY-050`. Required zeros include raw NOK/contact/address values in repository artifacts, raw occupation values unless explicitly allow-listed and nonidentifying, raw OPD/source keys, row-level plain hashes, cross-domain/environment HMAC comparison, contact notifications/SMS, patient activity events and target contact-search side effects.

## Exception cross-reference requirements

The consolidated Phase 2D catalogue should use its own stable `LEGACY-PATIENT-CHILD-*` codes for child-only conditions while referencing, not copying, these Phase 2C exceptions:

| Condition | Mandatory Phase 2C code/reference | Phase 2D blocking scope |
|---|---|---|
| Parent unresolved or quarantined | `LEGACY-PATIENT-RELATIONSHIP-029`, `LEGACY-PATIENT-CHAIN-030` | Every Phase 2D child under that root |
| Existing-target ambiguity/conflict/state | `LEGACY-PATIENT-TARGET-023..026` | Patient and entire Phase 2D chain |
| Existing-target immutable delta | `LEGACY-PATIENT-TARGET-024`, `PATIENT-TARGET-009`, `PATIENT-REC-EXISTING-030` | Stop the target mapping/run; modify nothing |
| Alias blank/duplicate/invalid/collision | `LEGACY-PATIENT-ALIAS-003..007`, `041` | Alias only unless independent target-identity conflict exists |
| Source/schema/fingerprint drift | `LEGACY-PATIENT-DRIFT-033` | Stop shared patient snapshot and all children |
| Extraction/snapshot failure | `LEGACY-PATIENT-EXTRACTION-034` | Hold the affected root and all projected children |
| Privacy/provenance/token failure | `LEGACY-PATIENT-PRIVACY-036` | Immediate containment; no child release |
| Conflicting roots | `LEGACY-PATIENT-CHAIN-042` | Affected subchain; never union or reassign |

Phase 2D-specific codes are appropriate for prohibited alias redefinition, `OriginalName`/`OriginalOpd` drift guards, child-parent unavailable, existing-contact conflicts and prohibited existing-target mutation, but their records must cross-reference the above authority. They must not change Phase 2C trigger, owner, SLA, release or reconciliation semantics. SLA expiry never assigns an alias, defaults a field, chooses a contact owner, enriches an existing target or releases a chain.

## Extraction coupling and stop conditions

Phase 2D must define one projection over the same coordinated Phase 2C patient snapshot, not a second independent snapshot. Its selected protected columns are `PAT_ID`, `Work`, `Address`, `NOK`, `NOKPhoneNo`, `NOKRel`, `Religion`, `MaritalStatus`, plus references to the Phase 2C parent/alias outcomes. Ordering is `PAT_ID ASC`; initial chunk recommendation and resume coordinate inherit `PATIENT-EXT-001` (1,000 rows; last completely emitted `PAT_ID` plus immutable snapshot identity).

The projection must record its own query ID/version/hash and field canonicalization version, but it must share the Phase 2C snapshot coordinate and `PATIENT-PRIV-002` row fingerprint dependency. It emits aggregate-only repository evidence. Any mismatch in patient key set, row count, snapshot identity, schema fingerprint, parent outcome, row fingerprint or preflight/freeze comparison stops both reconciliations; it cannot be handled as an incremental delta between the two phases.

Stop additionally on any attempt to:

- connect to a Classic schema other than exact `uuhms`, use a write-capable execution account as final authority, or write either database;
- create a second OPD comparison rule, alter Phase 2C exception identities or infer alias ownership from Phase 2D values;
- release a child before its protected patient mapping or move it to a different patient/root;
- mutate an explicitly linked existing target outside the separately authorized alias route;
- treat a blank/invalid optional child as patient identity failure without an independent patient-level privacy/provenance cause;
- place raw patient/contact/address/OPD/source-key values or row-level tokens in source control or ordinary logs;
- persist, notify, audit operationally, enqueue, send SMS/email, search for a target match, or invoke normal patient/contact services.

## Consolidation checklist

- [ ] Five Phase 2D relationships exist exactly once and resolve to five sentinel rules.
- [ ] `PATIENT-CHILD-REL-004` references `PATIENT-ALIAS-001..017` and `PATIENT-REC-ALIAS-002`; no alternate canonicalizer exists.
- [ ] Every child outcome carries the original `PATIENT-PRIV-007` root; local holds cannot alter it.
- [ ] Parent quarantine and existing-target ambiguity hold all children.
- [ ] Duplicate alias withholding leaves eligible contact/demographic outcomes independent.
- [ ] Existing-target contacts/demographics/pre-existing aliases are immutable; only the separate D-201 alias delta is permitted.
- [ ] NOK, contact runtime, four demographics, chain, immutability and privacy equations each have zero difference/zero assertions.
- [ ] Phase 2D projection shares `PATIENT-EXT-001` snapshot identity and `PATIENT-PRIV-002`; no second patient snapshot exists.
- [ ] All exception references resolve without changing Phase 2C codes or SLAs.
- [ ] No raw PHI, importer configuration, target identifier, persistence authorization or database write appears in the consolidated package.

