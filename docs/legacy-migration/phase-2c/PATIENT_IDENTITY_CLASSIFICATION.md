# Patient identity classification

## Classification model

Phase 2C separates three dimensions so that review flags cannot corrupt entity reconciliation:

1. one mutually exclusive **patient entity outcome** per Classic patient row;
2. one mutually exclusive **legacy OPD alias outcome** per Classic patient row; and
3. zero or more **secondary review/chain states**.

**Confirmed evidence** describes source/target facts. **Policy** comes from D-201, D-211, D-213, Q-001, Q-002, Q-006, Q-007 and Q-008. **Technical specification** below defines deterministic precedence and outcomes. The source baseline is 16,950 patient rows; final class counts cannot be asserted until protected name remediation and target collision preflight exist.

## Primary entity precedence and reconciliation

Evaluate each source row exactly once, in this order:

1. `PATIENT_EXTRACTION_FAILURE`
2. `PATIENT_EXISTING_TARGET_EXPLICIT_CROSSWALK`
3. `PATIENT_TARGET_UNIQUENESS_CONFLICT`
4. `PATIENT_SOURCE_REMEDIATION_PENDING`
5. `PATIENT_MISSING_REQUIRED_IDENTITY`
6. `PATIENT_INVALID_REQUIRED_IDENTITY`
7. `PATIENT_QUARANTINED` for another classified blocking reason, including unresolved existing-target ambiguity
8. `PATIENT_ELIGIBLE`

Missing takes precedence over invalid when both occur; invalid includes unrepresentable target identity. Target uniqueness conflict applies only to a new-patient path. An explicit crosswalk is valid only when exactly one protected source maps to exactly one live, unmerged, non-soft-deleted target and the approval covers any verified evidence conflict. Otherwise it is ambiguous.

Exact equation:

`Classic patient rows = explicit existing-target links + eligible new-patient candidates + source-remediation pending + quarantined missing identity + quarantined invalid/unrepresentable identity + quarantined existing-target ambiguity + quarantined target uniqueness conflict + failed extraction/constraint + excluded`

The difference must equal zero. Duplicate OPD and suspected-duplicate flags do not enter this equation.

## Class outcomes

Privacy for every row-level class is **Restricted patient migration data**: raw source keys/identity values reside only in protected ledgers; routine output uses aggregates or domain-separated HMAC tokens. “Null actor” means target `registered_by = null` plus Phase 2B protected absence provenance.

| Class | Eligibility and target representation | Patient-number / alias outcome | Actor and dependency outcome | Review, exception, reconciliation, and release |
|---|---|---|---|---|
| `PATIENT_ELIGIBLE` | Eligible new-patient candidate only after every required identity field and the coherent target-state matrix are valid and no blocking target conflict exists. Persist one new target patient through the future migration boundary. | Allocate one target number only at commit after crosswalk lookup. Alias is classified independently. | Null actor. Parent becomes eligible; children release only in topological order after the patient mapping and their own domain gates. | No primary exception; reconciliation `PATIENT-REC-ENTITY-001`. Release at successful atomic patient/map commit. |
| `PATIENT_ELIGIBLE_WITH_UNIQUE_LEGACY_ALIAS` | Secondary eligible subtype; same entity eligibility as `PATIENT_ELIGIBLE`. | One unique, valid, source- and target-collision-free `legacy_opd` alias is pending/created after patient mapping. Never use it as patient number. | Null actor; alias state does not alter child eligibility. | No primary exception; reconciliation `PATIENT-REC-ALIAS-002`. Release alias only after owner mapping and commit-time uniqueness recheck. |
| `PATIENT_ELIGIBLE_ALIAS_WITHHELD_DUPLICATE` | Secondary eligible subtype; duplicate OPD does not block the patient entity. | Allocate/retain patient number normally; create no alias. Preserve raw OPD only in protected provenance. | Null actor; children remain eligible subject to their own rules. | One `LEGACY-PATIENT-ALIAS-004` per affected source patient; reconciliation `PATIENT-REC-ALIAS-002`; manual alias-ownership review required. Release only through controlled post-migration review; never decorate or select a best owner. |
| `PATIENT_EXISTING_TARGET_EXPLICIT_CROSSWALK` | Eligible link path only for a protected pre-approved crosswalk resolving uniquely to an acceptable existing target. Create/update zero target patient identity rows. | Retain target ID and existing patient number; allocate zero. Alias handling is independent: the later alias stage may create one new collision-free `legacy_opd` under D-201, but may not mutate/reassign any pre-existing alias. | Preserve existing registrar and all target fields/pre-existing children unchanged. Source dependents may use the protected mapping after relationship checks. | No primary exception; reconciliation `PATIENT-REC-EXISTING-030`; any new alias reconciles only under `PATIENT-REC-ALIAS-002`. Crosswalk approval and immutability proof are the release condition. |
| `PATIENT_MISSING_REQUIRED_IDENTITY` | Ineligible because one or more source-backed required fields are absent. No placeholders. | Allocate zero; alias may remain classified but cannot persist until it has a valid target owner. | No actor persistence; patient and complete dependent chain held. | `LEGACY-PATIENT-NAME-009`, `LEGACY-PATIENT-NAME-010`, `LEGACY-PATIENT-DOB-016`, `LEGACY-PATIENT-GENDER-017`, and/or `LEGACY-PATIENT-PHONE-019`; reconciliation `PATIENT-REC-ENTITY-001`. Release only after evidenced remediation validates every required field. |
| `PATIENT_INVALID_REQUIRED_IDENTITY` | Ineligible because a required value is malformed, chronologically invalid, overlength, unsupported or unrepresentable. | Allocate zero; alias classification remains separate. | No actor persistence; patient and complete dependent chain held. | Applicable codes are `LEGACY-PATIENT-NAME-011`, `LEGACY-PATIENT-NAME-012`, `LEGACY-PATIENT-DOB-013` through `-016`, `LEGACY-PATIENT-GENDER-018`, or `LEGACY-PATIENT-PHONE-020`; reconciliation `PATIENT-REC-ENTITY-001`. Release only after versioned evidence-backed remediation and revalidation. |
| `PATIENT_TARGET_UNIQUENESS_CONFLICT` | Ineligible new-patient path when a required unique target invariant conflicts, including generated-number race/drift or Ghana Card if later supplied. Conflict is not identity proof. | Allocate/commit zero successful numbers; alias collision uses alias partition instead. | No actor persistence; full chain held. | `LEGACY-PATIENT-TARGET-024` or `LEGACY-PATIENT-NUMBER-037`; reconciliation `PATIENT-REC-EXISTING-030`; target owner review. Release only after a reviewed deterministic decision and clean re-preflight. |
| `PATIENT_SUSPECTED_DUPLICATE_REVIEW` | Secondary flag only. The underlying row stays in its primary entity class; no automatic merge, match or block follows merely from similarity. | Number and alias follow their independent outcomes. | Never coalesce patient or child chains. | `LEGACY-PATIENT-DUPLICATE-027`; reconciliation `PATIENT-REC-DUPLICATE-010`. Release is not required for entity import; post-migration identity review may resolve the flag. |
| `PATIENT_SOURCE_REMEDIATION_PENDING` | Temporarily ineligible while a controlled remediation case is open and reliable evidence is expected. | Allocate zero; persist no alias until a patient owner exists. | Full patient-root chain held. | `LEGACY-PATIENT-REMEDIATION-040`; reconciliation `PATIENT-REC-ENTITY-001`. Release only on approved evidence plus reclassification; SLA expiry never defaults a value. |
| `PATIENT_QUARANTINED` | Blocking entity state for unresolved existing-target ambiguity, unresolved target-state policy or another classified reason not represented by a more specific primary class. It is also the state envelope for every blocked patient. | No new target number or patient-owned alias while blocked. | Patient and all matched descendants held as one traceable chain. | `LEGACY-PATIENT-CHAIN-030` plus the specific cause, including `LEGACY-PATIENT-STATUS-044..049`; reconciliation `PATIENT-REC-ENTITY-001`. Release requires every blocking reason resolved and full chain reconciliation. |
| `PATIENT_EXTRACTION_FAILURE` | Ineligible when decoding, PK, snapshot, source fingerprint, protected HMAC, query, checkpoint, or commit-time constraint integrity fails. | Allocate/commit zero successful number or alias. Any partial transaction rolls back. | Full affected chain held; checkpoint cannot advance. | `LEGACY-PATIENT-EXTRACTION-034` (plus `LEGACY-PATIENT-DRIFT-033` or `LEGACY-PATIENT-PRIVACY-036` where applicable); reconciliation `PATIENT-REC-ENTITY-001`. Release after evidence is restored and extraction reruns from a valid snapshot. |
| `PATIENT_DEPENDENCY_CHAIN_QUARANTINED` | Derived child-chain state, not a patient entity count. Applies when the patient is blocked or an attendance/insurance relationship lacks a valid patient parent. | Does not allocate identity. | No child may migrate ahead of its unresolved patient/attendance/claim parent; never reassign or create a generic patient. | `LEGACY-PATIENT-RELATIONSHIP-029` and `LEGACY-PATIENT-CHAIN-030`; reconciliation `PATIENT-REC-CHAIN-100`. Release topologically after exact parent repair and every domain gate. |

`PATIENT_ELIGIBLE_WITH_UNIQUE_LEGACY_ALIAS` and `PATIENT_ELIGIBLE_ALIAS_WITHHELD_DUPLICATE` are secondary refinements of `PATIENT_ELIGIBLE`; they must not add patient rows to the primary equation. `PATIENT_DEPENDENCY_CHAIN_QUARANTINED` is a chain state, not an extra primary patient count. `PATIENT_QUARANTINED` is counted once only when it is the row's primary unresolved blocking outcome; its envelope use does not add a second count. There is no inferred exclusion class in the baseline; the entity reconciliation `excluded` term is zero unless a separate approved scope rule is added.

## Alias outcome partition

Each source row receives exactly one alias outcome in this precedence:

1. `failed`
2. `blank`
3. `invalid`
4. `duplicate_withheld`
5. `target_alias_conflict`
6. `target_patient_number_conflict`
7. `alias_not_applicable`
8. `unique_valid_alias_candidate`

Exception alignment is: blank `LEGACY-PATIENT-ALIAS-003`; duplicate-withheld `LEGACY-PATIENT-ALIAS-004`; invalid `LEGACY-PATIENT-ALIAS-005`; target-alias conflict `LEGACY-PATIENT-ALIAS-006`; and target patient-number conflict `LEGACY-PATIENT-ALIAS-007`. Failed decoding/extraction/privacy outcomes use `LEGACY-PATIENT-TEXT-031`, `LEGACY-PATIENT-EXTRACTION-034` or `LEGACY-PATIENT-PRIVACY-036` as applicable.

Exact equation:

`16,950 = unique valid alias candidates + duplicate aliases withheld + blank aliases + invalid aliases + target alias conflicts + target patient-number conflicts + alias not applicable + failed`

The captured source canonical comparison gives 15,550 unique nonblank candidates, 1,259 duplicate-withheld candidates and 141 blank, difference zero. It removes whitespace and uppercases, but does not yet supply semantic-invalid, Unicode/NFC runtime-parity or target-collision counts. Phase 3 must refresh and complete that partition privately before persistence.

For every final duplicate group, every affected row receives one exception and zero aliases are assigned. Duplicate group ownership is never resolved by merge, score, name, phone, smallest PK, earliest date, or decorated value.

## Existing-target decision rules

Only two paths may resolve to an existing target:

1. an explicit protected, pre-approved source-to-target crosswalk; or
2. a separately reviewed deterministic identity determination which is then recorded as such a crosswalk.

Automatic matching is prohibited for names, normalized names, OPD/alias, phone, email, DOB, gender, address, Ghana Card alone, similarity/fuzzy search and weighted scoring. A successful link is immutable: target ID, patient number, identity, registration actor, timestamps, status, merge/deleted state, every pre-existing alias and every pre-existing child are changed zero times. The separate D-201 alias stage may add only one new eligible `legacy_opd`, reconciled independently. A soft-deleted or merged target is blocking, not automatically restored or followed.

## Quarantine and controlled release

Each quarantined patient receives a protected source token, source-row fingerprint, primary class, all field-specific reason codes, snapshot/rule versions, affected relationship counts and review history. Raw identifiers stay in the protected ledger. Dependent chains use separately domain-separated HMAC chain tokens.

Release is fail-closed and topological:

1. approve remediation or explicit target identity decision;
2. rerun every required-field, target-collision, fingerprint and privacy rule;
3. atomically persist/map the patient or validate the existing link;
4. reconcile the patient mapping and number;
5. release the direct attendance/insurance/occupancy edge;
6. release encounter, claim or consultation parents;
7. release descendants only after their own actor, reference, chronology, clinical and financial gates.

Partial child release, reassignment, cross-chain union and generic/artificial patients must each reconcile to zero. Repairing a patient link never waives a child-domain exception. A missed review SLA keeps the same state; it never creates an unknown value, merge, alias owner or exclusion.

## Registration and privacy invariants

Classic contains no verified registration actor. Every newly created eligible patient has null `registered_by` and protected absence provenance under `TARGET-ACTOR-054`. Existing links retain their registrar unchanged. Current-user, importer, first-user, administrator, supervisor and unauthorized Legacy Actor Unknown counts must all be zero.

Routine audit/reconciliation output contains class IDs and aggregates only. Domain-separated HMAC-SHA-256 must be used for protected row-level correlation with key and canonicalization versions. Patient names, OPDs, phones, addresses, emails, national identifiers, raw source/target crosswalk values and clinical text must never appear in documentation or ordinary migration logs.
