# Phase 2F composed mapping and dependency-sequence draft

Status: **specialist draft for lead-agent consolidation; documentation only; implementation is not authorized.**

This draft composes the authoritative Phase 2A-2E machine-readable contracts for the bounded patient pilot. It introduces no replacement transformation. Every raw field has one owner; linked IDs remain authoritative. The only approved Classic database is `uuhms`, read-only. No source or target query was executed for this workstream.

## Authority and composition boundary

- Policy: `APPROVED_DECISION_SPECIFICATIONS.md`, `DECISIONS.md`, D-201, D-202, D-205-D-208, D-211-D-214, D-008, Q-001-Q-009, Q-301 and Q-304.
- Phase 2A supplies a **resolved reference-crosswalk input**, especially `NK-004`, `EXTRACT-SETT_PRIVATE`, `RECON-SETT_PRIVATE` and the source-row-to-target-provider mapping. Phase 2F must not re-extract or reinterpret `sett_private.PINS_ID`, `PrivateName`, `PrivateShort` or `PrivateType`.
- Phase 2B supplies `TARGET-ACTOR-054`, `STAFF-SECURITY-001` and the zero-access/security exclusions. Classic `patients` has no registration actor. `PATIENT-ACTOR-003` fixes `patients.registered_by = null` plus protected absence provenance; `Legacy Actor Unknown` and every current/admin/importer fallback remain prohibited.
- Phase 2C owns patient identity, patient numbering, OPD alias, patient-root quarantine, extraction and protected identity provenance.
- Phase 2D owns the seven demographic/contact fields deferred by Phase 2C. It consumes the identical Phase 2C patient snapshot and adds no second patient extraction or alias canonicalizer.
- Phase 2E owns `patients.Company`, `patients.BillStatus` and all nine `insurance` fields. It consumes, but cannot create, the Phase 2A provider map and the Phase 2C parent map.

The raw pilot projection therefore contains exactly **33 unique fields**: all 24 `uuhms.patients` columns under `PATIENT-EXT-001`, plus all nine `uuhms.insurance` columns under the Phase 2E extraction contract. The ownership partition is 15 Phase 2C fields + 7 Phase 2D fields + 11 Phase 2E fields = 33. Phase 2A reference fields are upstream inputs, not a second raw projection; Phase 2B contributes no patient source field.

## Shared coordinates and link bundles

These abbreviations are only traceability shorthand. They do not define new rules.

| Key | Exact authoritative links |
|---|---|
| `S-P` | `PATIENT-EXT-001`; full ordered 24-column `patients` snapshot; content identity `PATIENT-PRIV-002`; patient root `PATIENT-PRIV-007`. |
| `S-D` | `PATIENT-CHILD-EXT-001`; projection over the **same** `S-P` coordinate, never a second snapshot. |
| `S-I` | `INS-EXT-001`; ordered full `insurance` snapshot coordinated with `S-P`; `INS-EXT-002` patient projection, `INS-EXT-003` provider resolution projection and `INS-EXT-004` target collision snapshot are joined only at their pinned coordinates. |
| `A0` | `TARGET-ACTOR-054`, `PATIENT-ACTOR-001..008`, `STAFF-SECURITY-001`: null registrar/creator where specified, protected absence provenance, zero fallback/security access. |
| `C-X` | `LEGACY-PATIENT-EXTRACTION-034`, `LEGACY-PATIENT-TARGET-035`, `LEGACY-PATIENT-PRIVACY-036`, `LEGACY-PATIENT-CHAIN-030`, `LEGACY-PATIENT-CHAIN-042`. |
| `C-R` | `PATIENT-REC-ENTITY-001`, `PATIENT-REC-CHAIN-100`, `PATIENT-REC-PRIVACY-050`. |
| `D-X` | `LEGACY-PATIENT-CHILD-EXTRACT-033`, `LEGACY-PATIENT-CHILD-PARENT-001`, `LEGACY-PATIENT-CHILD-PARENT-002`, `LEGACY-PATIENT-CHILD-PRIVACY-034`, `LEGACY-PATIENT-CHILD-PRIVACY-035`, `LEGACY-PATIENT-CHILD-PRIVACY-041`. |
| `D-R` | `PATIENT-CHILD-REC-008`, `PATIENT-CHILD-REC-009`, `PATIENT-CHILD-REC-010`. |
| `E-X` | `LEGACY-INSURANCE-EXTRACT-035`, `LEGACY-INSURANCE-SOURCE-034`, `LEGACY-INSURANCE-PRIVACY-036`, `LEGACY-INSURANCE-PRIVACY-037`, `LEGACY-INSURANCE-CONSOLIDATION-026`, `LEGACY-INSURANCE-CONSOLIDATION-027`, `LEGACY-INSURANCE-TARGET-030`, `LEGACY-INSURANCE-TARGET-031`, `LEGACY-INSURANCE-TARGET-032`, `LEGACY-INSURANCE-TARGET-040`, `LEGACY-INSURANCE-TARGET-041`. |
| `E-R` | `INS-REC-001`, `INS-REC-007-GROUP`, `INS-REC-007-ROW`, `INS-REC-012`, `INS-REC-013`, `INS-REC-014`, `INS-REC-015`; all equations require difference zero. |
| `E-P` | `INS-PRIV-001..009`; `PATIENT-PRIV-009` remains the authoritative orphan-insurance root. |

Commit-blocker shorthand:

- `CB0`: environment, D-101 account, exact `uuhms`, fingerprints, contract bundle, snapshots, protected storage, HMAC/key management and side-effect isolation are not all valid.
- `CB1`: required identity or protected remediation fails; target patient-state matrix, migration-safe allocator or patient persistence boundary remains unapproved/unavailable.
- `CB2`: existing-target branch is missing, ambiguous, soft-deleted, merged, conflicting or changed since snapshot.
- `CB3`: alias validity/source uniqueness/target namespace/owner/idempotency is unresolved. This blocks Unit B only, not eligible Unit A.
- `CB4`: optional demographic/contact validity, parent, primary-contact or child idempotency fails. It withholds the field/Unit C unless the failure is a root privacy/extraction stop.
- `CB5`: insurance parent/provider/member/date/consolidation/target collision is unresolved, or `member_type`/active/current initialization lacks an approved target representation. It blocks the affected Unit D row/group, never patient identity.
- `CB6`: protected provenance/history, reconciliation, checkpoint, resume or rollback/compensation infrastructure is incomplete. It blocks any future commit.
- `NT`: no operational destination in this pilot; preserve/classify only under the owner contract. No target-column commit is permitted.

Future atomic-unit shorthand: `A` patient core/crosswalk/number/provenance/core reconciliation; `B` OPD alias; `C` emergency contact; `D` insurance source history and at-most-one current patient/provider representation; `P` protected provenance only. Existing-target branches allow crosswalk/provenance outcomes only and never mutate the target graph.

## Composed column map: ownership and projection

Each row has one and only one entry in **Owner/transformation**. Rule ranges mean all named records in the existing normative JSON, not a new combined rule.

| Key | Source field and installed type | Snapshot | Owner / transformation contract | Target and installed/logical constraint | Parent, remediation and other dependency | Future unit / commit eligibility / blocker |
|---|---|---|---|---|---|---|
| P01 | `patients.PAT_ID`, `int(11)` | `S-P` | `PATIENT-COL-001` | Protected patient crosswalk only; positive unique source identity; never `patients.id`, number or alias source FK | Valid source key; patient-root token | `A/P`; mapping prerequisite; `CB0,CB6` |
| P02 | `patients.PatientName`, `varchar(100) latin1` | `S-P` | `PATIENT-COL-002`; `PATIENT-REQ-002..003` | `patients.first_name`, `last_name` NOT NULL; operational max 100; optional `other_names` max 100 | Independently evidenced protected remediation; no automatic split/copy/guess | `A`; required; `CB0,CB1,CB2,CB6` |
| P03 | `patients.OpdNo`, `varchar(15) latin1` | `S-P` | `PATIENT-COL-003`; `PATIENT-ALIAS-001..017` | `patient_aliases`: required mapped owner/type/value/normalized value; global unique `(alias_type,normalized_alias_value)`; `source_patient_id=null`, `created_by=null` | Successful patient map; final canonicalizer; source duplicate and target namespace checks | `B`; independent alias eligibility; `CB0,CB3,CB6` |
| P04 | `patients.Sex`, `varchar(10) latin1` | `S-P` | `PATIENT-COL-004`; `PATIENT-REQ-005` | `patients.gender` NOT NULL; application values `male|female`, no DB check | Exact approved crosswalk or protected correction | `A`; required; `CB0,CB1,CB2,CB6` |
| P05 | `patients.DOB`, `date` | `S-P` | `PATIENT-COL-005`; `PATIENT-REQ-004` | `patients.date_of_birth` NOT NULL; valid approved chronology | Valid source or protected correction; no coercion/current date | `A`; required; `CB0,CB1,CB2,CB6` |
| P06 | `patients.PhoneNo`, `varchar(10) latin1` | `S-P` | `PATIENT-COL-006`; `PATIENT-REQ-006` | `patients.phone` NOT NULL, indexed not unique; operational max 20 and configured Ghana rule | Valid source or protected correction; never identity matching evidence | `A`; required; `CB0,CB1,CB2,CB6` |
| P07 | `patients.Work`, `varchar(15) latin1` | `S-D` | `PATIENT-CHILD-COL-001`; `PATIENT-CHILD-OCC-001..010` | `patients.occupation` nullable; operational max 100 | Successful new-patient branch; blank is null; invalid withholds field | `A` optional inline field; `CB0,CB2,CB4,CB6` |
| P08 | `patients.Company`, `varchar(25) latin1` | `S-P` + `INS-EXT-002` | `INS-COL-010`; `INS-COMPANY-001..005` | Corroborating payer/provider evidence only; no direct target column or membership creation | Same source patient and separately resolved provider outcome | `D/P`; evidence only; `CB0,CB5,CB6` |
| P09 | `patients.Address`, `varchar(25) latin1` | `S-D` | `PATIENT-CHILD-COL-002`; `PATIENT-CHILD-ADDR-001..011` | `patients.address` nullable text, operational max 500; never city/town/region/postal/digital address | Successful new-patient branch; blank null; invalid field withheld | `A` optional inline field; `CB0,CB2,CB4,CB6` |
| P10 | `patients.NOK`, `varchar(100) latin1` | `S-D` | `PATIENT-CHILD-COL-003`; tuple `PATIENT-CHILD-NOK-001..012` | `emergency_contacts.name` NOT NULL, operational max 100 | Joint tuple P10-P12; successful new parent; no cross-patient match | `C`; optional child; `CB0,CB2,CB4,CB6` |
| P11 | `patients.NOKPhoneNo`, `varchar(10) latin1` | `S-D` | `PATIENT-CHILD-COL-004`; tuple `PATIENT-CHILD-NOK-001..012` | `emergency_contacts.phone` NOT NULL, operational max 20/Ghana rule | Joint tuple P10-P12; valid parent and phone | `C`; optional child; `CB0,CB2,CB4,CB6` |
| P12 | `patients.NOKRel`, `varchar(15) latin1` | `S-D` | `PATIENT-CHILD-COL-005`; tuple `PATIENT-CHILD-NOK-001..012` | `emergency_contacts.relationship` nullable, operational max 50; no enum | Joint tuple P10-P12; structurally valid free text or null | `C`; optional child; `CB0,CB2,CB4,CB6` |
| P13 | `patients.Religion`, `varchar(15) latin1` | `S-D` | `PATIENT-CHILD-COL-006`; `PATIENT-CHILD-RELIGION-XW-001..004` | `patients.religion` nullable, operational max 100, no enum/check | Successful new-patient branch; explicit crosswalk/blank only | `A` optional inline field; `CB0,CB2,CB4,CB6` |
| P14 | `patients.MaritalStatus`, `varchar(15) latin1` | `S-D` | `PATIENT-CHILD-COL-007`; `PATIENT-CHILD-MARITAL-XW-001..004` | `patients.marital_status` nullable; application `single|married|divorced|widowed` | Successful new-patient branch; explicit crosswalk/blank only | `A` optional inline field; `CB0,CB2,CB4,CB6` |
| P15 | `patients.BillStatus`, `varchar(25) latin1` | `S-P` + `INS-EXT-002` | `INS-COL-011`; `INS-BILL-001..006` | Payer-category evidence only; never patient status or provider-specific membership | Exact payer crosswalk; blank/unknown separately classified | `D/P`; evidence only; `CB0,CB5,CB6` |
| P16 | `patients.Refill`, `int(11)` | `S-P` | `PATIENT-COL-016` | No approved target destination | No identifier relationship may be inferred | `P/NT`; `CB0,CB6` |
| P17 | `patients.Allergies`, `varchar(500) latin1` | `S-P` | `PATIENT-COL-017` | No patient-pilot destination; later clinical scope only | Protected provenance; never identity content | `P/NT`; `CB0,CB6` |
| P18 | `patients.Medication`, `varchar(500) latin1` | `S-P` | `PATIENT-COL-018` | No patient-pilot destination; later clinical scope only | Protected provenance; never synthesize medication history | `P/NT`; `CB0,CB6` |
| P19 | `patients.History`, `varchar(500) latin1` | `S-P` | `PATIENT-COL-019` | No patient-pilot destination; later clinical scope only | Protected content and later sanitization contract | `P/NT`; `CB0,CB6` |
| P20 | `patients.LastVisit`, `datetime` | `S-P` | `PATIENT-COL-020` | No target creation/update timestamp and no inferred visit | Preserve source fact only in protected provenance | `P/NT`; `CB0,CB6` |
| P21 | `patients.EditDate`, `timestamp ON UPDATE` | `S-P` | `PATIENT-COL-021` | Extraction/change evidence only; never `patients.updated_at` | Overlap assistance; full snapshot/hash remains authoritative | `P/NT`; `CB0,CB6` |
| P22 | `patients.RegDate`, `timestamp` | `S-P` | `PATIENT-COL-022` | `patients.created_at` candidate (DB nullable); valid source time only, never migration time | Source chronology valid; actor remains null by `A0` | `A`; conditional core value; `CB0,CB1,CB2,CB6` |
| P23 | `patients.OriginalName`, `varchar(250) latin1` | `S-P` | `PATIENT-COL-023` | Protected provenance only; never overwrites/splits `PatientName` | Baseline drift review; cannot remediate automatically | `P`; no target column; `CB0,CB6` |
| P24 | `patients.OriginalOpd`, `varchar(250) latin1` | `S-P` | `PATIENT-COL-024` | Protected provenance only; never direct alias | Baseline drift review; P03 remains sole alias owner | `P`; no target column; `CB0,CB6` |
| I01 | `insurance.INS_ID`, `int`, auto-increment PK | `S-I` | `INS-COL-001`; `INS-HISTORY-001..006` | Protected insurance row identity/history only; never target PK | Unique positive key and complete protected provenance | `D/P`; every row represented; `CB0,CB6` |
| I02 | `insurance.PAT_ID`, `int`, required | `S-I` | `INS-COL-002`; `INS-REL-001`, `INS-SENT-001` | Resolve to required `patient_insurances.patient_id` FK only through protected patient map | Exact `insurance.PAT_ID = patients.PAT_ID`; no null/zero sentinel, guessing or reassignment | `D`; parent mandatory; `CB0,CB1,CB2,CB5,CB6` |
| I03 | `insurance.InsType`, `varchar(25)`, required | `S-I` | `INS-COL-003`; `INS-TYPE-001..004`, `INS-REL-003`, `INS-SENT-003` | Type/payer/provider-compatibility evidence; never target `member_type` | Exact approved crosswalk and compatible provider | `D/P`; group input; `CB0,CB5,CB6` |
| I04 | `insurance.Scheme`, `varchar(200)`, required, default hyphen | `S-I` | `INS-COL-004`; `INS-SCHEME-001..006`, `INS-SENT-004` | No installed destination; protected history only | Field-specific blank/hyphen absence; no plan/tier invention | `D/P/NT`; every row represented; `CB0,CB5,CB6` |
| I05 | `insurance.MemberNo`, `varchar(15)`, required | `S-I` | `INS-COL-005`; `INS-MEMBER-001..010`, `INS-SENT-011` | Nullable `patient_insurances.membership_number` candidate; exact text semantics | Patient/provider group; duplicate and target-collision classification | `D`; conditional; `CB0,CB5,CB6` |
| I06 | `insurance.Company`, `varchar(25)`, required | `S-I` + `INS-EXT-003` | `INS-COL-006`; `INS-PROVIDER-001..008`, `INS-REL-002`, `INS-SENT-002` | Required `patient_insurances.insurance_provider_id` FK candidate | Unique approved Phase 2A source-row-to-target-provider crosswalk and compatible type; source name candidate alone insufficient | `D`; group parent mandatory; `CB0,CB5,CB6` |
| I07 | `insurance.IssueDate`, `date`, required | `S-I` | `INS-COL-007`; `INS-DATE-001..013`, `INS-SENT-012` | Nullable `patient_insurances.start_date` candidate | Field-specific valid/unknown/future/chronology class; no coercion | `D`; conditional/history; `CB0,CB5,CB6` |
| I08 | `insurance.ExpiryDate`, `date`, required | `S-I` | `INS-COL-008`; `INS-DATE-001..013`, `INS-SENT-012` | Nullable `patient_insurances.expiry_date` candidate | Field-specific valid/unknown/future/chronology class; no swap/coercion | `D`; conditional/history; `CB0,CB5,CB6` |
| I09 | `insurance.Plan`, `varchar(255)`, required | `S-I` | `INS-COL-009`; `INS-SCHEME-001..006`, `INS-SENT-013` | No installed destination; protected history only | Only blank is absence; hyphen is substantive if encountered | `D/P/NT`; every row represented; `CB0,CB5,CB6` |

### Target fields with no Classic field owner

The mapping must not invent a source owner for `patients.patient_number`, `status`, `is_active`, `is_temporary`, `merge_status`, `is_deceased`, `registered_by`, or the target-insurance fields `member_type`, `is_active`, `is_primary`, tier, policy number, CCC code, verification, eligibility and timestamps. Patient number is target-generated by `PATIENT-NUM-001..018`; registrar is fixed by `A0`; patient state and insurance initialization remain explicit Phase 2F matrices and future commit blockers where no owner-approved representation exists. Target defaults are not evidence.

## Per-field control trace

All rows also inherit `CB0` safety stops. `C-X/C-R`, `D-X/D-R` and `E-X/E-R` expand to the exact IDs listed above.

| Field key(s) | Field-specific exception IDs (plus shared bundle) | Reconciliation IDs | Privacy and extraction IDs | Actor / alias / child / insurance link | Rollback scope |
|---|---|---|---|---|---|
| P01 | `LEGACY-PATIENT-KEY-001`, `LEGACY-PATIENT-KEY-002`; `C-X` | `C-R` | `PATIENT-PRIV-001`, `PATIENT-PRIV-007`; `PATIENT-EXT-001` | `A0`; patient root and crosswalk owner | Unit A transaction or existing-link mapping revocation; never mutate linked target |
| P02 | `LEGACY-PATIENT-NAME-008..012`, `LEGACY-PATIENT-REMEDIATION-040`; `C-X` | `PATIENT-REC-REQ-002`, `PATIENT-REC-REQ-003`, `C-R` | `PATIENT-PRIV-002`, `PATIENT-PRIV-004`, `PATIENT-PRIV-007`; `PATIENT-EXT-001` | `A0`; required identity; remediation version pinned | Unit A as a whole; remediation input is separately revocable, never overwritten in repo |
| P03 | `LEGACY-PATIENT-ALIAS-003..007`, `LEGACY-PATIENT-ALIAS-041`; `C-X` | `PATIENT-REC-ALIAS-002`, `PATIENT-CHILD-REC-007`, `C-R` | `PATIENT-PRIV-003`, `PATIENT-PRIV-007`; `PATIENT-EXT-001` | `PATIENT-ALIAS-001..017`, `PATIENT-CHILD-ALIAS-001..010`, `A0` | Unit B only; duplicate-withheld creates zero alias; committed alias requires compensation, not patient rollback |
| P04 | `LEGACY-PATIENT-GENDER-017`, `LEGACY-PATIENT-GENDER-018`; `C-X` | `PATIENT-REC-REQ-005`, `C-R` | `PATIENT-PRIV-002`, `PATIENT-PRIV-007`; `PATIENT-EXT-001` | `A0`; required identity | Unit A |
| P05 | `LEGACY-PATIENT-DOB-013..016`; `C-X` | `PATIENT-REC-REQ-004`, `C-R` | `PATIENT-PRIV-002`, `PATIENT-PRIV-007`; `PATIENT-EXT-001` | `A0`; required identity | Unit A |
| P06 | `LEGACY-PATIENT-PHONE-019`, `LEGACY-PATIENT-PHONE-020`; `C-X` | `PATIENT-REC-REQ-006`, `C-R` | `PATIENT-PRIV-002`, `PATIENT-PRIV-005`, `PATIENT-PRIV-007`; `PATIENT-EXT-001` | `A0`; required identity, never matching key | Unit A |
| P07 | `LEGACY-PATIENT-CHILD-OCC-015..017`, `LEGACY-PATIENT-CHILD-TEXT-029`, `LEGACY-PATIENT-CHILD-TEXT-030`, `LEGACY-PATIENT-CHILD-TARGET-036`; `D-X` | `PATIENT-CHILD-REC-003`, `D-R` | `PATIENT-CHILD-PRIV-001..004`, `PATIENT-CHILD-PRIV-007`, `PATIENT-CHILD-PRIV-010`; `PATIENT-CHILD-EXT-001` | `PATIENT-CHILD-REL-001`, `PATIENT-CHILD-SENT-001`; no actor | Unit A optional field; invalid field withheld before commit; existing target unchanged |
| P08 | `LEGACY-INSURANCE-PROVIDER-007`, `LEGACY-INSURANCE-PROVIDER-010`; `E-X` | `INS-REC-010`, `E-R` | `PATIENT-PRIV-007`, `INS-PRIV-003`; `INS-EXT-002`, `INS-EXT-003` | `INS-REL-006`, `INS-SENT-006`; insurance evidence cannot alter identity | Unit D/provenance only |
| P09 | `LEGACY-PATIENT-CHILD-ADDR-018..020`, `LEGACY-PATIENT-CHILD-TEXT-029`, `LEGACY-PATIENT-CHILD-TEXT-030`, `LEGACY-PATIENT-CHILD-TARGET-036`; `D-X` | `PATIENT-CHILD-REC-004`, `D-R` | `PATIENT-CHILD-PRIV-001..004`, `PATIENT-CHILD-PRIV-006`, `PATIENT-CHILD-PRIV-010`; `PATIENT-CHILD-EXT-001` | `PATIENT-CHILD-REL-001`, `PATIENT-CHILD-SENT-001`; no actor | Unit A optional field; existing target unchanged |
| P10-P12 | `LEGACY-PATIENT-CHILD-CONTACT-001..014`, `LEGACY-PATIENT-CHILD-TEXT-029`, `LEGACY-PATIENT-CHILD-TEXT-030`; `D-X` | `PATIENT-CHILD-REC-001`, `PATIENT-CHILD-REC-002`, `D-R` | `PATIENT-CHILD-PRIV-001..005`, `PATIENT-CHILD-PRIV-010`; `PATIENT-CHILD-EXT-001` | `PATIENT-CHILD-REL-002..003`, `PATIENT-CHILD-SENT-002..003`, `PATIENT-CHILD-CONTACT-001..014`; no creator field | Unit C; child failure never rewrites Unit A; compensation/retry local to contact |
| P13 | `LEGACY-PATIENT-CHILD-RELIGION-021..024`, `LEGACY-PATIENT-CHILD-TARGET-036`; `D-X` | `PATIENT-CHILD-REC-005`, `D-R` | `PATIENT-CHILD-PRIV-001..004`, `PATIENT-CHILD-PRIV-008`, `PATIENT-CHILD-PRIV-010`; `PATIENT-CHILD-EXT-001` | `PATIENT-CHILD-REL-001`, `PATIENT-CHILD-SENT-001`; no actor | Unit A optional field; existing target unchanged |
| P14 | `LEGACY-PATIENT-CHILD-MARITAL-025..027`, `LEGACY-PATIENT-CHILD-TARGET-036`; `D-X` | `PATIENT-CHILD-REC-006`, `D-R` | `PATIENT-CHILD-PRIV-001..004`, `PATIENT-CHILD-PRIV-008`, `PATIENT-CHILD-PRIV-010`; `PATIENT-CHILD-EXT-001` | `PATIENT-CHILD-REL-001`, `PATIENT-CHILD-SENT-001`; no actor | Unit A optional field; existing target unchanged |
| P15 | `LEGACY-INSURANCE-TYPE-013`; `E-X` | `INS-REC-011`, `E-R` | `PATIENT-PRIV-007`, `INS-PRIV-003`; `INS-EXT-002` | `INS-REL-007`, `INS-SENT-007`; never patient state | Unit D/provenance only |
| P16 | `LEGACY-PATIENT-REMEDIATION-040`; `C-X` | `C-R` | `PATIENT-PRIV-002`, `PATIENT-PRIV-007`; `PATIENT-EXT-001` | No relationship may be inferred | Protected provenance disposition only |
| P17-P18 | `C-X` | `C-R` | `PATIENT-PRIV-002`, `PATIENT-PRIV-007`; `PATIENT-EXT-001` | Clinical data excluded from pilot projection | Protected provenance only; no patient-pilot target rollback |
| P19 | `LEGACY-PATIENT-TEXT-031`; `C-X` | `C-R` | `PATIENT-PRIV-002`, `PATIENT-PRIV-007`; `PATIENT-EXT-001` | Clinical text excluded; no rendering/execution | Protected provenance only |
| P20 | `C-X` | `C-R` | `PATIENT-PRIV-002`, `PATIENT-PRIV-007`; `PATIENT-EXT-001` | No visit inference | Protected provenance only |
| P21 | `LEGACY-PATIENT-DRIFT-033`; `C-X` | `C-R` | `PATIENT-PRIV-002`; `PATIENT-EXT-001` | Extraction advisory only | Snapshot invalidation/re-extraction; no target rollback |
| P22 | `LEGACY-PATIENT-REMEDIATION-040`; `C-X` | `C-R` | `PATIENT-PRIV-002`, `PATIENT-PRIV-007`; `PATIENT-EXT-001` | `A0`; no actor/timestamp fabrication | Unit A |
| P23-P24 | `LEGACY-PATIENT-DRIFT-033`; `C-X` | `C-R` | `PATIENT-PRIV-002`, `PATIENT-PRIV-007`; `PATIENT-EXT-001` | P23 cannot rewrite P02; P24 cannot rewrite P03 | Protected provenance only |
| I01 | `LEGACY-INSURANCE-KEY-001`, `LEGACY-INSURANCE-KEY-002`; `E-X` | `INS-REC-001`, `INS-REC-014`, `E-R` | `INS-PRIV-001`, `E-P`; `INS-EXT-001` | `INS-HISTORY-001..006`; every row has an outcome | Unit D history atom; no silent loss |
| I02 | `LEGACY-INSURANCE-PATIENT-003..005`; `E-X` | `INS-REC-002`, `INS-REC-012`, `E-R` | `PATIENT-PRIV-009`, `E-P`; `INS-EXT-001` | `PATIENT-REL-003`, `INS-REL-001`, `INS-SENT-001`; no reassignment | Unit D row/group held under exact root; never rollback patient identity |
| I03 | `LEGACY-INSURANCE-TYPE-011..013`; `E-X` | `INS-REC-008`, `E-R` | `INS-PRIV-001`, `E-P`; `INS-EXT-001` | `INS-TYPE-001..004`; does not own `member_type` | Unit D group/history |
| I04 | `LEGACY-INSURANCE-SCHEME-014`, `LEGACY-INSURANCE-SCHEME-038`; `E-X` | `INS-REC-009-SCHEME`, `E-R` | `INS-PRIV-001`, `E-P`; `INS-EXT-001` | `INS-SCHEME-001..006`, `INS-SENT-004` | Unit D protected history only |
| I05 | `LEGACY-INSURANCE-MEMBER-015..019`; `E-X` | `INS-REC-004`, `E-R` | `INS-PRIV-002`, `E-P`; `INS-EXT-001` | `INS-MEMBER-001..010`, `INS-SENT-011`; no numeric cast/invention | Unit D; group-local retry/compensation, existing membership immutable |
| I06 | `LEGACY-INSURANCE-PROVIDER-006..010`, `LEGACY-REF-INSURANCE-001`, `LEGACY-REF-INSURANCE-003`; `E-X` | `INS-REC-003`, `RECON-SETT_PRIVATE`, `E-R` | `INS-PRIV-003`, `E-P`; `INS-EXT-003`, `EXTRACT-SETT_PRIVATE` | `NK-004`, `INS-PROVIDER-001..008`, `INS-REL-002`, `INS-SENT-002`; no artificial provider | Unit D group held until exact provider crosswalk resolves |
| I07 | `LEGACY-INSURANCE-DATE-020..022`; `E-X` | `INS-REC-005-ISSUE`, `INS-REC-006`, `E-R` | `INS-PRIV-001`, `E-P`; `INS-EXT-001` | `INS-DATE-001..013`, `INS-SENT-012`; not eligibility | Unit D history/current candidate; invalid chronology held |
| I08 | `LEGACY-INSURANCE-DATE-023..025`; `E-X` | `INS-REC-005-EXPIRY`, `INS-REC-006`, `E-R` | `INS-PRIV-001`, `E-P`; `INS-EXT-001` | `INS-DATE-001..013`, `INS-SENT-012`; not eligibility | Unit D history/current candidate; invalid chronology held |
| I09 | `LEGACY-INSURANCE-SCHEME-014`, `LEGACY-INSURANCE-SCHEME-038`; `E-X` | `INS-REC-009-PLAN`, `E-R` | `INS-PRIV-001`, `E-P`; `INS-EXT-001` | `INS-SCHEME-001..006`, `INS-SENT-013` | Unit D protected history only |

## Exact 24-stage dependency sequence

Every stage consumes only immutable prior-stage outputs and appends a new classification. A stage may stop, hold or quarantine a descendant; it may not mutate an earlier result.

| Stage | Required operation and authoritative inputs | Output / fail-closed gate | Decision immutability |
|---:|---|---|---|
| 0 | Validate migration environment and exact approved non-production target. | Environment coordinate or global stop. | Target environment cannot change within run. |
| 1 | Validate D-101 least-privilege source account, connection and exact `uuhms`; metadata/SELECT only. | Source authority coordinate or global stop. | No alternate Classic schema/fallback account. |
| 2 | Validate approved source and installed-target fingerprints, table/column counts, DB versions and constraints. | Fingerprint bundle or drift stop. | Fingerprints pinned. |
| 3 | Pin every Phase 2A-2F specification, policy, canonicalization, HMAC-key metadata and configuration version. | Nonsecret contract-bundle hash. | No mid-run rule replacement. |
| 4 | Capture/pin coordinated immutable `S-P` and `S-I` source snapshot coordinates and deterministic cohort order. | Source snapshot/candidate manifest; zero raw identifiers in repository. | Later stages cannot refresh a field selectively. |
| 5 | Capture/pin target collision snapshot covering unscoped patients, aliases, contacts, insurance, archived/merged/soft-deleted state, sequence and constraints. | Target comparison coordinate. | Later target change causes stop/refresh, not repair. |
| 6 | Resolve required Phase 2A reference inputs, especially exact versioned provider crosswalk (`NK-004`, `EXTRACT-SETT_PRIVATE`, `RECON-SETT_PRIVATE`). | Crosswalk outcome per reference token; unresolved is held. | No downstream invention or alternate matching. |
| 7 | Validate Phase 2B actor outcomes (`TARGET-ACTOR-054`, `A0`) and zero security/permission/credential/fallback assertions. | Null-attribution/absence-provenance decision. | No later actor substitution. |
| 8 | Validate protected remediation input against source token, evidence, reviewer/approval/revocation, validity and rule versions. | Approved per-field remediation or unresolved. | Downstream cannot derive remediation from children, insurance or duplicate candidates. |
| 9 | Validate explicit patient-state and insurance-initialization matrices; target defaults are rejected as evidence. | Matrix outcomes or commit-mode blocker. | No later convenient initialization. |
| 10 | Classify each patient entity using P01-P06, P22, remediation, required-field and duplicate-review rules. Duplicate signals remain review flags only. | Source identity class and required-field vector. | **Identity classification closes here; later fields cannot recalculate it.** |
| 11 | Resolve explicit existing-target branch versus eligible new-patient branch using protected crosswalk and target snapshot. | Branch, target mapping token/null and immutable `identity_decision_hash`. | **Identity envelope seals here.** Later stages may only preserve, hold or stop it; never relink, merge or rewrite. |
| 12 | Resolve patient-number action under `PATIENT-NUM-001..018`: retain existing, symbolic `TARGET_GENERATED_AT_COMMIT`, or none. Dry-run reserves nothing. | Number action/idempotency outcome. | Cannot change branch; rerun cannot allocate replacement. |
| 13 | Classify P03 independently: blank, invalid, duplicate-withheld, collision-held, same-owner satisfied or creatable after parent. | Alias outcome and exceptions. | Cannot change identity/branch/number; duplicate alias is never duplicate-patient evidence. |
| 14 | Classify P07, P09, P13, P14 under Phase 2D. | Per-field direct/null/withheld/existing-target-evidence outcome. | Optional fields cannot rewrite identity; existing target immutable. |
| 15 | Classify joint P10-P12 tuple and target primary-contact state. | Contact candidate/absence/withheld/evidence-only outcome. | Contact cannot repair identity or alter branch. |
| 16 | Classify every I01-I09 row under source key, parent, type, member, dates, scheme/plan and history rules. | One primary row class plus diagnostics; every row retained. | Insurance cannot modify patient identity or remediation. |
| 17 | Resolve provider mapping using Stage 6 crosswalk and I06/I03 compatibility. | Unique provider token, blank/unresolved/ambiguous/conflict outcome. | No provider invention; patient identity unchanged. |
| 18 | Group only resolved patient/provider tokens; apply `INS-CONS-001..012`, exact duplicates/history/conflicts and target initialization. | At most one current candidate per group plus history outcome for every row. | Grouping cannot merge patients/providers or change earlier row facts. |
| 19 | Build patient-root, child and orphan dependency chains; apply exception precedence and topological hold/release. | Exactly one root/disposition for every dependent outcome. | Children cannot release before parent; aggregation cannot overwrite primary exceptions or identity. |
| 20 | Produce complete zero-write dry-run projection for Units A-D and protected interfaces. | Aggregate/opaque projection; symbolic number only. | Projection has no reservation or persistence authority. |
| 21 | Reconcile every cohort, field, relationship, child, insurance row/group, collision, exception, quarantine and side-effect equation at difference zero. | Passed/failed contract results; no unexplained difference. | Reconciliation failure stops; it cannot repair data or reclassify silently. |
| 22 | Apply artifact-wide privacy scan and prove all prohibited source/target writes, actor fallbacks, services, events, queues, notifications and operational audit effects equal zero. | Privacy/side-effect verdict. | Any violation is a run stop, not a suppressible child exception. |
| 23 | Produce pilot readiness verdict against entry/exit criteria and mandatory Phase 3 blockers. | `READY_FOR_PHASE_3_FOUNDATION_HANDOFF`, `NOT_READY`, or `BLOCKED`; never importer authorization. | Verdict references sealed evidence; no post-hoc mutation. |

## Identity non-rewrite invariant

At the end of Stage 11 the protected runtime creates an `identity_decision_hash` over typed, length-prefixed canonical values for: run/snapshot/contract versions, patient source token, remediation-record version(s), required-field outcomes, Stage 10 entity class, duplicate-review flags, Stage 11 branch and target mapping token (if explicit). The hash is domain-separated and keyed; neither it nor row-level tokens may enter repository artifacts.

Stages 12-23 must present the same identity hash. A mismatch is drift/idempotency failure and stops the affected root. Alias, optional-child, provider, insurance, collision or reconciliation outcomes may add a hold to their own scope or the existing patient root, but may not:

- select a different target patient;
- convert a new-target branch to an inferred existing target or vice versa;
- merge or split source patients;
- use OPD, phone, NOK, address, payer, provider or member number as patient identity evidence;
- revise first/last-name remediation;
- release a quarantined patient merely because a child is valid.

If evidence legitimately changes, the prior candidate/run is invalidated and a new run coordinate is created. It is not an in-place later-stage rewrite.

## Topological future atomic boundaries

1. **Unit A - patient core:** requires Stages 0-12 and approved Stage 14 inline demographic outcomes. Crosswalk-first idempotency, number allocation, new patient, required fields, explicit state, permitted optional inline values, `registered_by=null`, absence provenance, mapping, core reconciliation and checkpoint succeed atomically or none succeeds. Existing-target branch records only the protected link/provenance outcome.
2. **Unit B - OPD alias:** depends on committed Unit A or a valid explicit existing-target link. It rechecks P03 canonicalization, source duplicate class, owner, target collision and idempotency. Duplicate-withheld/blank/invalid creates zero alias rows and remains reconciled.
3. **Unit C - emergency contact:** depends on committed Unit A new-patient branch only. It rechecks the complete tuple, parent, token and one-primary invariant. Its failure does not erase or rewrite Unit A; retry/compensation is child-local.
4. **Unit D - insurance history/current representation:** depends on valid patient and provider outcomes. Protected history and row reconciliation cover every source insurance row. At most one current representation per patient/provider is possible only after the insurance initialization matrix is approved. Existing-target memberships remain immutable; absence of a target membership never permits history loss.

Later units never reopen earlier ownership. Hard deletion is not the universal rollback. A committed patient number is never rewound, recycled or replaced; existing-target links are never rolled back by mutating the target patient.

## Dependency graph

```text
Stages 0-5: run and snapshot guards
        |
        +--> Stage 6 Phase 2A reference crosswalks
        +--> Stage 7 Phase 2B null actor/security outcome
        +--> Stage 8 protected remediation
        +--> Stage 9 explicit target-state matrices
                    |
S-P P01-P06,P22 --> Stage 10 entity classification
                    |
                    v
                Stage 11 sealed patient branch
                    |
        +-----------+-----------+------------------+
        |                       |                  |
 Stage 12 number          Stage 13 alias     Stages 14-15 children
        |                       |                  |
        +----------- Unit A ---+--> Units B/C     |
                    |                             |
S-I I01-I09 --> Stage 16 rows --> Stage 17 provider (Stage 6)
                                      |
                                 Stage 18 groups --> Unit D
                                      |
                                      v
                          Stage 19 rooted quarantine
                                      |
                       Stages 20-22 dry-run/reconcile/safety
                                      |
                              Stage 23 readiness only
```

## Consolidation checks for the lead agent

- Generate the normative Phase 2F field mapping with exactly 33 unique raw source-field keys and the 15/7/11 owner partition above.
- Treat Phase 2A provider crosswalk and Phase 2B actor decision as interface inputs, not additional raw-pilot transformation owners.
- Cross-reference every linked ID against its existing JSON before publication; ranges in this draft should be expanded to arrays in machine-readable output.
- Preserve all 24 stages with integer IDs 0-23 and exactly one predecessor/topological dependency definition per stage.
- Encode the Stage 10 close and Stage 11 sealed identity hash as invariants; require every later stage to carry it unchanged.
- Keep state/insurance initialization fields ownerless by Classic evidence and block commit mode rather than assigning defaults.
- Keep Phase 2F specification and dry-run writes at zero. Units A-D describe future Phase 3 interfaces only.

