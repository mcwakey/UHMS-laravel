# Phase 2D privacy, normalization, reconciliation, and extraction analysis draft

## Status and ownership

This is a read-only analytical draft for lead-agent consolidation. It is not an approved mapping, executable query package, importer configuration, schema design, target-write authorization, or replacement for any Phase 2C contract. It was prepared without connecting to either database. All confirmed statements below come from repository evidence captured for exact Classic schema `uuhms` and the installed non-production target.

Evidence labels used here:

- **Confirmed evidence**: installed-schema/repository evidence or previously captured sanitized aggregate evidence.
- **Approved policy**: owner-approved decisions in `APPROVED_DECISION_SPECIFICATIONS.md` and `DECISIONS.md`.
- **Consumed contract**: an authoritative Phase 2A, 2B, or 2C specification that Phase 2D must reference without redefining.
- **Phase 2D technical recommendation**: a deterministic rule proposed for the consolidated Phase 2D specification.
- **Open evidence item**: a fact that must remain unresolved until sanitized evidence or target verification closes it.

## Boundaries consumed without reopening

Phase 2D owns exactly `patients.Work`, `Address`, `NOK`, `NOKPhoneNo`, `NOKRel`, `Religion`, and `MaritalStatus`. `PAT_ID` is consumed only as protected parent identity under `PATIENT-PRIV-001`; it is not a Phase 2D target value. `OpdNo` remains wholly governed by `LEGACY_OPD_ALIAS_CONTRACT`, `PATIENT-ALIAS-001` through `PATIENT-ALIAS-017`, and `PATIENT-REC-ALIAS-002`. Phase 2D must not implement a second OPD canonicalizer.

`Company` and `BillStatus` remain Phase 2E. `Allergies`, `Medication`, and `History` remain clinical-history scope. `LastVisit` remains visit scope. `Refill` has no approved destination. `OriginalName` and `OriginalOpd` remain protected provenance with a captured blank baseline; any later nonblank drift stops Phase 2D pending semantic review. `PhoneNo` and all core identity fields remain Phase 2C.

The following consumed contracts are mandatory dependencies:

| Concern | Authoritative dependency |
|---|---|
| Parent source identity and row snapshot | `PATIENT-EXT-001`, `PATIENT-PRIV-001`, `PATIENT-PRIV-002` |
| Parent quarantine/root token | `PATIENT-PRIV-007`, `PATIENT-PRIV-012`, `patient_dependency_chain_rules.json` |
| Existing-target immutability | `PATIENT-TARGET-001` through `PATIENT-TARGET-014`, `PATIENT-REC-EXISTING-030`, `PATIENT-PRIV-006` |
| OPD alias | `PATIENT-ALIAS-001` through `PATIENT-ALIAS-017`, `PATIENT-REC-ALIAS-002` |
| Registrar attribution | Phase 2B `TARGET-ACTOR-054`: target `patients.registered_by = null` plus protected absence provenance |
| Empty occupation catalogue | D-209/Q-305: `sett_ocuupation` remains excluded at the zero-row baseline; changed fingerprint or nonzero rows stop scope |
| Source and runtime guards | D-101, D-102, D-210, Q-401 through Q-404 |

## Sanitized baseline relevant to this stream

**Confirmed evidence:** `uuhms.patients` has 16,950 rows, 24 columns, primary key `PAT_ID`, no secondary index, `latin1_swedish_ci`, and structural fingerprint `150fcf4783fcb8bdc25f7e17fe0ece5050955f0c68e7dd03651ee8bd58498977`. The seven fields are non-nullable Classic `varchar` columns; empty strings carry absence semantics.

| Field | Declared length | Blank after trim | Nonblank | Observed maximum | At source limit | Outer whitespace | Control rows |
|---|---:|---:|---:|---:|---:|---:|---:|
| `Work` | 15 | 6,440 | 10,510 | 15 | 589 | 260 | 0 |
| `Address` | 25 | 982 | 15,968 | 25 | 25 | 719 | 0 |
| `NOK` | 100 | 1,505 | 15,445 | 31 | 0 | 296 | 0 |
| `NOKPhoneNo` | 10 | 2,094 | 14,856 | 10 | 14,676 | 11 | 0 |
| `NOKRel` | 15 | 1,668 | 15,282 | 15 | 11 | 46 | 0 |
| `Religion` | 15 | 1,651 | 15,299 | 12 | 0 | 0 | 0 |
| `MaritalStatus` | 15 | 1,381 | 15,569 | 9 | 0 | 0 | 0 |

Additional confirmed aggregate evidence:

- all seven fields had zero non-ASCII-byte rows in the earlier ASCII round-trip diagnostic; this does **not** waive deliberate `latin1` decoding or prove that future drift is valid;
- `NOKPhoneNo` has 14,594 exactly-ten-digit rows, 14,592 ten-digit rows beginning with zero, 98 populated nondigit rows, and 190 populated rows whose trimmed length is not ten; these defect signals overlap and therefore are not a partition;
- `NOKRel` has 435 distinct nonblank trimmed values. Raw relationship values were correctly not emitted;
- safe religion aggregates are `CHRISTIANITY` 12,438, `MUSLIM` 2,749, explicit source `OTHER` 112, and blank 1,651;
- safe marital aggregates are `SINGLE` 8,240, `MARRIED` 6,976, `WIDOW(ER)` 353, and blank 1,381;
- the source-controlled baseline does not yet contain a mutually exclusive three-field NOK tuple partition or the complete Phase 2D duplicate/length distribution requested by the directive. Those counts must be supplied only by the sanitized Classic profiling stream.

The repository target evidence records operational maxima of 100 for occupation/religion, 500 for address, 100 for emergency-contact name, 20 for emergency-contact phone, and 50 for relationship. Therefore no captured source value is over those operational maxima. This is a baseline statement, not permission to bypass future drift checks or semantic validation.

## Privacy classification and artifact boundary

### Required classification

All seven values are protected patient-linked data. The target privacy configuration treats occupation, religion, and marital status as level 1 demographic data; address as level 2 hidden address data; and emergency-contact phone as level 2 restricted contact data. For migration evidence, use the stricter classification **restricted patient-child migration data** because every value is linkable through the protected patient map. NOK name and high-cardinality relationship text remain restricted even where the current target field registry does not explicitly enumerate them.

### Permitted and prohibited repository evidence

Permitted source-controlled evidence is limited to:

- schema/table/column metadata and nonsecret schema/query/specification hashes;
- aggregate row/null/blank/nonblank/length/control/encoding/quality counts;
- allow-listed, nonidentifying categorical values for religion and marital status with aggregate counts;
- contract, class, exception, relationship, sentinel, reconciliation, extraction, query, and version identifiers;
- zero assertions for prohibited behavior.

Prohibited in documentation, source control, prompts, ordinary logs, fixtures, screenshots, test failure messages, and aggregate reports:

- raw or masked-reversible `PAT_ID` and OPD values;
- NOK names, phones, and high-cardinality relationship values;
- patient addresses;
- raw occupation values, including examples, unless explicitly allow-listed as nonidentifying by governance;
- row-level religion or marital values;
- row-level plain hashes or HMACs;
- protected source-to-target crosswalk values;
- low-cardinality result hashes that permit confirmation of a specific patient value;
- exact patient-derived temporal extrema.

Synthetic examples may be used in future tests only when they are clearly synthetic and cannot be mistaken for captured source values.

### Protected operational provenance

Within the future protected migration boundary, every field outcome must retain:

- opaque Phase 2C patient-root token, not raw `PAT_ID`;
- source table and column/tuple identifier;
- snapshot identity and Phase 2C parent outcome/crosswalk version;
- strict decoding result and canonicalization version;
- outcome class, exception codes, rule version, target map/version if applicable;
- protected source representation only when required for remediation or legal provenance;
- run, query, tool-code, approval, and reconciliation identifiers.

The migration audit is separate from operational activity history. It records facts about migration execution without fabricating historical view, notification, contact, or patient-update events.

## Text normalization boundary

### Common pipeline `PATIENT-CHILD-TEXT-v1`

The recommended canonical pipeline is deterministic and field-specific after a shared prefix:

1. Decode bytes deliberately as Classic `latin1`; do not rely on connection coercion.
2. Fail the affected field/tuple on undecodable or ambiguous input. Never insert replacement characters silently.
3. Convert to UTF-8 and normalize Unicode to NFC.
4. Preserve the exact decoded source representation only in protected provenance.
5. For the display candidate, remove outer Unicode whitespace only. Do not collapse internal whitespace unless the field rule expressly permits it.
6. For comparison only, collapse each internal Unicode whitespace run to one ASCII space where the field-specific rule permits comparison normalization. Comparison form is never automatically written as display.
7. Reject C0/C1 controls other than an explicitly permitted, field-specific whitespace code, Unicode noncharacters, embedded NUL, and invalid scalar sequences. No stripping is permitted.
8. Measure the normalized display candidate using the target application's Unicode string-length semantics and enforce the stronger operational maximum, not merely physical `varchar(191)`/`text` capacity.
9. Never transliterate, spell-correct, title-case, infer, truncate, geocode, split, concatenate unrelated fields, or default a value.

Field rules:

| Field | Display rule | Comparison rule | Operational maximum | Prohibited semantic use |
|---|---|---|---:|---|
| `Work` | NFC + outer trim; preserve internal form | NFC + outer trim + comparison whitespace collapse; casefold only for protected diagnostics | 100 | identity, matching, payer inference, fabricated occupation catalogue |
| `Address` | NFC + outer trim; preserve punctuation/internal spacing | comparison normalization only in protected same-field diagnostics | 500 | geocoding, locality parsing, identity/matching, organisation-address fallback |
| `NOK` | NFC + outer trim; preserve punctuation and component order | comparison normalization only for same-source idempotency; never patient matching | 100 | person matching, name splitting, patient merge/linkage |
| `NOKPhoneNo` | field-specific phone contract below | field-specific phone canonical form | 20 | identity/matching, patient-phone fallback |
| `NOKRel` | NFC + outer trim; preserve approved free text | controlled-category canonical form only where explicitly approved | 50 | default to `other`, identity/matching |
| `Religion` | approved display crosswalk only | trim + Unicode casefold under versioned allow-list | 100 | inference from name/address/contact; identity |
| `MaritalStatus` | target enum value only | trim + Unicode casefold under explicit crosswalk | target enum | default/inference; identity |

Blank-after-trim optional demographic values map to null plus protected absence provenance for newly created patients. They are not errors and do not become strings such as `unknown`, `unemployed`, `none`, or `other`. A nonblank unrecognized value must not be made null as if it were blank.

## NOK phone normalization boundary

`NOKPhoneNo` is separate from required patient `PhoneNo`. No patient phone may be copied into a NOK field. A contact phone is never identity evidence.

The installed application has inconsistent operational paths: inline patient registration applies the configured Ghana regex `^(?:\+233|0)[235][0-9]{8}$` with maximum 20, while the separate emergency-contact controller enforces only string/maximum 20. The migration contract must not select the weaker controller behavior. A migration-specific validator must be frozen and reviewed before Phase 3.

Recommended fail-closed normalization for the captured Classic field:

- strict decode, NFC, and outer trim;
- accept an exact local form `^0[235][0-9]{8}$` once the sanitized Phase 2D profiler supplies the corresponding mutually exclusive count;
- retain an exact `+233` form only if future evidence actually contains it and the frozen target validator accepts it. The captured `varchar(10)` shape does not support assuming such values exist;
- do not change a valid leading-zero form to `+233`, because both are target-compatible and the source representation should not be silently rewritten;
- do not remove punctuation, extensions, alphabetic content, or multiple-number separators without a separately evidenced and versioned rule;
- whitespace inside the value, multiple numbers, extensions, invalid characters, or invalid length produce a contact-field exception and withhold the tuple; they never trigger a guessed repair;
- keep the raw decoded representation only in protected provenance.

The already captured nondigit/length diagnostics overlap. They must not be added to produce an invalid count. The Classic profiling stream must return a mutually exclusive exact phone partition under the selected regex before the consolidated mapping claims completeness.

## Contact tuple, deduplication, and idempotency boundary

`NOK`, `NOKPhoneNo`, and `NOKRel` form one source tuple with one candidate slot per Classic patient row. The tuple identity is the Phase 2C protected patient source token plus a typed constant such as `classic-patients-nok-slot-v1`; it is not a hash of the contact values.

Recommended tuple classification precedence, producing exactly one primary outcome per Classic patient row:

1. extraction/privacy/provenance failure;
2. parent unresolved or quarantined;
3. explicitly linked existing target, evidence-only and immutable;
4. all three fields blank after canonical trim;
5. required contact core complete and valid: valid nonblank name plus valid nonblank phone; relationship may be valid or blank because the installed target relationship is nullable;
6. partial tuple: one of required name/phone is blank while any tuple field is nonblank;
7. invalid tuple: any required value is invalid/overlength or any nonblank relationship is unsupported/invalid.

Relationship blank may be recorded as an informational secondary outcome while the tuple remains a valid contact candidate. A nonblank unknown relationship must not be dropped while a name/phone contact is created unless the final contract explicitly authorizes preserving the protected source relationship and withholding only the display field. The safest baseline is to withhold the tuple pending semantic review.

Deduplication rules:

- never deduplicate contacts across different patients;
- never compare NOK/contact/demographic values to identify, link, merge, or reassign a patient;
- never infer that a same-value target contact is the migrated source tuple;
- treat only an exact prior migration child with the same protected source-slot token, mapped patient, canonicalization version, and content fingerprint as an idempotent prior outcome;
- a conflicting prior token/owner/content version is a protected target conflict, not a value-based merge;
- because Classic has one tuple per source patient row, duplicate contact rows from the same extraction indicate a pipeline defect and stop that child outcome;
- explicit existing-target patients receive no contact create/update/delete/primary change. Similarity does not authorize enrichment.

## Domain-separated HMAC design

Phase 2D must reuse the Phase 2C parent key and row-content contracts and add only child-purpose domains. Recommended protected domains are:

| Recommended contract ID | HMAC domain | Purpose |
|---|---|---|
| `PATIENT-CHILD-PRIV-001` | `patient-child-projection-v1` | protected seven-field projection fingerprint bound to `PATIENT-PRIV-001` and `PATIENT-EXT-001` snapshot |
| `PATIENT-CHILD-PRIV-002` | `patient-nok-slot-v1` | deterministic one-tuple-per-source-parent idempotency token |
| `PATIENT-CHILD-PRIV-003` | `patient-nok-content-v1` | same-environment protected tuple content comparison |
| `PATIENT-CHILD-PRIV-004` | `patient-occupation-value-v1` | protected occupation field comparison |
| `PATIENT-CHILD-PRIV-005` | `patient-address-value-v1` | protected address field comparison |
| `PATIENT-CHILD-PRIV-006` | `patient-religion-value-v1` | protected religion field comparison |
| `PATIENT-CHILD-PRIV-007` | `patient-marital-value-v1` | protected marital field comparison |
| `PATIENT-CHILD-PRIV-008` | `target-patient-child-immutability-v1` | same-environment pre/post child/demographic field-group comparison; subordinate to `PATIENT-PRIV-006` |

Every message is length-prefixed UTF-8 with explicit field name, semantic type, null marker, canonicalization version, source snapshot identity, and purpose-domain marker. Keys are environment-controlled secrets; key version is mandatory; keys and row HMACs never enter source control. HMACs cannot be compared across domains, keys, environments, or canonicalization versions and do not replace the protected crosswalk. Loss of a required key/version is a stop condition.

## Mutually exclusive child partitions

Quality diagnostics may overlap, but each primary reconciliation partition must not. Apply precedence `failed -> parent quarantined/unresolved -> existing-target immutable -> blank -> invalid encoding/control -> overlength -> domain unknown/unrepresentable -> valid`.

### Source projection

`phase_2c_patient_rows = phase_2d_projection_rows + projection_failed`, with expected difference zero. Before any child classification, `projection_failed` must be zero and the ordered protected `PAT_ID` set digest, row count, schema fingerprint, and snapshot coordinate must equal `PATIENT-EXT-001`.

### NOK source tuple

`classic_patient_rows = contact_complete_valid + contact_partial + contact_all_fields_blank + contact_invalid + contact_parent_quarantined_or_unresolved + contact_existing_target_evidence_only + contact_failed`

The difference must be zero. `contact_complete_valid` means the target-required core name and phone are both valid; relationship is either valid or blank. Relationship-blank is a nonexclusive informational counter and must not be added again to the primary equation.

### Contact runtime

For all source contact candidates reaching the Phase 2D child stage:

`contact_stage_candidates = contact_created + exact_idempotent_prior_contact + withheld_partial_or_invalid + parent_not_released + existing_target_immutable + contact_commit_failed`

The difference must be zero. At most one migration-created primary contact may exist per newly created mapped patient. A source candidate must never appear in both created and idempotent-prior buckets.

### Optional demographic fields

Create one independent partition for each of occupation, address, religion, and marital status:

`classic_patient_rows = value_valid + value_blank_null + value_invalid_withheld + value_overlength_withheld + value_unrepresentable + value_parent_quarantined_or_unresolved + value_existing_target_immutable + value_failed`

Each difference must be zero. The field partitions are independent of one another and do not add to the Phase 2C patient entity partition. A field-level failure does not automatically invalidate an otherwise eligible patient; a patient privacy/provenance failure or parent quarantine still holds the complete chain.

## Recommended reconciliation contracts

The consolidator should assign final IDs consistently across Markdown and JSON. The following IDs and assertions are recommended:

| ID | Scope | Required assertion |
|---|---|---|
| `PATIENT-CHILD-REC-001` | shared projection | row count, ordered protected parent-key-set digest, snapshot identity, source fingerprint, and Phase 2C coordinate match; difference 0 |
| `PATIENT-CHILD-REC-002` | NOK tuple | every Classic patient row has exactly one primary tuple outcome; difference 0 |
| `PATIENT-CHILD-REC-003` | contact runtime | every staged tuple has exactly one runtime outcome; difference 0 |
| `PATIENT-CHILD-REC-004` | occupation | every source patient row has one occupation outcome; difference 0 |
| `PATIENT-CHILD-REC-005` | address | every source patient row has one address outcome; difference 0 |
| `PATIENT-CHILD-REC-006` | religion | every source patient row has one religion outcome; difference 0; allow-listed category counts plus blank/unknown/invalid reconcile |
| `PATIENT-CHILD-REC-007` | marital status | every source patient row has one marital outcome; explicit enum crosswalk counts plus blank/unknown/invalid reconcile |
| `PATIENT-CHILD-REC-008` | Phase 2C alias integration | reference `PATIENT-REC-ALIAS-002`; Phase 2D-created aliases, alternate canonicalizers, `OriginalName` aliases, `OriginalOpd` aliases, automatic duplicate ownership, and similarity-influenced ownership each equal 0 |
| `PATIENT-CHILD-REC-009` | existing target | migration-caused changes to all listed patient demographics, contacts, primary flags, aliases, timestamps, and activity history each equal 0 |
| `PATIENT-CHILD-REC-010` | parent release | persisted child without committed protected parent map = 0; child attached to non-source parent = 0; released child under quarantined token = 0 |
| `PATIENT-CHILD-REC-011` | privacy | prohibited raw artifacts, row-level plain hashes, cross-domain HMAC comparisons, and ordinary operational audit payloads each equal 0 |
| `PATIENT-CHILD-REC-012` | side effects | SMS, email, notifications, contact messages, queues, activity logs, archive writes, merge calls, and target searches caused by Phase 2D persistence each equal 0 |

Every contract records numerator/denominator definitions, precedence, query/specification version, snapshot identity, count type, difference, tolerance exactly zero, failure disposition, and exception code. SLA expiry never changes a reconciliation bucket or releases a record.

## Existing-target immutability detail

For every explicitly linked existing target patient, perform same-environment protected pre/post comparisons under `PATIENT-PRIV-006` plus the Phase 2D child field group. Migration-caused changes must be zero for occupation, address, city, town, region, postal code if present, digital address, religion, marital status, all emergency-contact rows and values, primary flags, existing aliases, patient/child timestamps, and patient activity history.

Do not fill a target blank from Classic. Do not create an emergency contact even when no target contact exists. Do not update a same-value contact, resolve conflicting primaries, or normalize an existing value. Record only protected comparison outcomes. Any attempted mutation is Critical, stops the affected commit/run, and requires target-state revalidation; it is not converted into a child exception that allows the mutation to stand.

## Read-only extraction specification

### Shared-snapshot rule

Phase 2D must not create an independent patient snapshot. It consumes `PATIENT-EXT-001`, whose required mode is the full ordered 24-column `uuhms.patients` snapshot under one verified `REPEATABLE READ`, `READ ONLY` coordinate, ordered by `PAT_ID ASC`, with chunk recommendation 1,000 and protected row domain `PATIENT-PRIV-002`.

The preferred implementation is a logical eight-column projection materialized from the already captured protected Phase 2C row frame. If a database projection query is used, it must execute inside the same verified transaction/snapshot and reconcile to the identical protected parent key set before use. It is never a separately timed snapshot.

Recommended extraction record:

| Item | Required value |
|---|---|
| Strategy ID | `PATIENT-CHILD-EXT-001` |
| Source | exact `uuhms.patients` only |
| Consumed parent strategy | `PATIENT-EXT-001` |
| Projection | `PAT_ID`, `Work`, `Address`, `NOK`, `NOKPhoneNo`, `NOKRel`, `Religion`, `MaritalStatus` |
| Query ID/version | `phase2d.patients.child_projection` / `1` |
| Normalized SQL | `SELECT PAT_ID, Work, Address, NOK, NOKPhoneNo, NOKRel, Religion, MaritalStatus FROM uuhms.patients ORDER BY PAT_ID ASC` |
| Normalized-SQL SHA-256 | `f0109fd00b6ef07aa0487f94d2a94123b35363c14b709221a663a47b8e4f3732` |
| Ordering/chunk | `PAT_ID ASC`; 1,000 |
| Snapshot | exactly the `PATIENT-EXT-001` coordinated snapshot identity |
| Resume | last completely emitted `PAT_ID` plus immutable Phase 2C snapshot identity; raw key remains protected |
| Update detection | Phase 2C full 24-column protected row comparison; optional `(EditDate,PAT_ID)` overlap is advisory only |
| Delete detection | full ordered protected parent-key-set comparison only |
| Projection canonicalization | `PATIENT-CHILD-TEXT-v1` plus field-specific versions |
| Output | aggregate counts/classes/contract IDs only; no raw values or row HMACs |

Any change to the normalized SQL requires a new query version and hash. Parent outcome references are joined inside the protected migration boundary through the Phase 2C crosswalk/token; they are not source columns and must not be emitted in query manifests as raw identifiers.

Preflight/freeze comparison must verify database name, database/version compatibility, 55-table/479-column structural coordinate, patient 24-column shape, fingerprint, D-101 least privilege, session read-only state, query/tool/specification hashes, HMAC key/version, ordered key-set equality, row count equality, and shared snapshot identity. A parent snapshot change invalidates every Phase 2D field and tuple reconciliation. PK watermarks can discover inserts only; they cannot prove updates or deletes.

Stop on any non-`uuhms` source, write-capable source account, non-read-only transaction, shape/fingerprint/query/tool drift, missing selected column, null/duplicate/unstable parent key, changed snapshot during resume, Phase 2C/2D key-set or row-count difference, lost HMAC version, invalid decoding, unclassified outcome, nonzero reconciliation difference, raw-data artifact, nonblank `OriginalName`/`OriginalOpd` drift, or attempted target persistence during this phase.

## Exception and SLA structure

Every final exception JSON record should include exactly the Phase 2C-compatible fields: `code`, category, severity, trigger, source scope, primary disposition, secondary flags, retryability, manual-review flag, owner role, SLA, release condition, reconciliation treatment, parent/child blocking scope, chain behavior, privacy classification, and provenance requirement. Optional blank demographics remain accepted null outcomes, not errors; their requested catalogue entries should be informational classifications.

Recommended code allocation and minimum operational treatment:

| Code | Severity / owner / SLA | Disposition and blocking scope |
|---|---|---|
| `LEGACY-PATIENT-CHILD-PARENT-001` unresolved parent | High / Patient Identity Lead / before child release | hold every Phase 2D child; release only after protected one-to-one parent map |
| `LEGACY-PATIENT-CHILD-PARENT-002` quarantined parent | High / Patient Identity Lead / inherited Phase 2C SLA | hold all children under unchanged patient-root token |
| `LEGACY-PATIENT-CHILD-ALIAS-003` alias parent unavailable | High / Patient Identity Lead / before alias release | alias only; reference Phase 2C, no redefinition |
| `LEGACY-PATIENT-CHILD-ALIAS-004` prohibited alias redefinition | Critical / Migration Technical Lead / immediate stop | stop Phase 2D alias integration |
| `LEGACY-PATIENT-CHILD-DRIFT-005` `OriginalName` drift | Critical / Migration Technical Lead / immediate stop | stop scope for semantic review |
| `LEGACY-PATIENT-CHILD-DRIFT-006` `OriginalOpd` drift | Critical / Migration Technical Lead / immediate stop | stop scope for semantic review |
| `LEGACY-PATIENT-CHILD-CONTACT-007` all blank | Information / Health Records Officer / none | accepted no-contact outcome; patient unaffected |
| `LEGACY-PATIENT-CHILD-CONTACT-008` name missing | Medium / Health Records Officer / 2 business days for pilot cohort | withhold tuple only |
| `LEGACY-PATIENT-CHILD-CONTACT-009` name invalid | High / Health Records Officer / 2 business days | withhold tuple only |
| `LEGACY-PATIENT-CHILD-CONTACT-010` name overlength | High / Health Records Officer / 2 business days | withhold tuple only; no truncation |
| `LEGACY-PATIENT-CHILD-CONTACT-011` phone missing | Medium / Health Records Officer / 2 business days for pilot cohort | withhold tuple only; no patient-phone fallback |
| `LEGACY-PATIENT-CHILD-CONTACT-012` phone invalid | High / Health Records Officer / 2 business days | withhold tuple only; no guessed normalization |
| `LEGACY-PATIENT-CHILD-CONTACT-013` phone overlength | High / Health Records Officer / 2 business days | withhold tuple only |
| `LEGACY-PATIENT-CHILD-CONTACT-014` relationship blank | Information / Health Records Officer / none | null relationship is allowed when required contact core is valid |
| `LEGACY-PATIENT-CHILD-CONTACT-015` relationship unknown | Medium / Health Records Officer / 2 business days | protected review; never default to `other` |
| `LEGACY-PATIENT-CHILD-CONTACT-016` relationship invalid | Medium / Health Records Officer / 2 business days | withhold according to final tuple rule |
| `LEGACY-PATIENT-CHILD-CONTACT-017` partial tuple | Medium / Health Records Officer / 2 business days for pilot cohort | withhold tuple only |
| `LEGACY-PATIENT-CHILD-CONTACT-018` multiple target primaries | Critical / Target Application Architect / before persistence | stop contact stage; do not repair target operational data |
| `LEGACY-PATIENT-CHILD-CONTACT-019` target contact conflict | High / Patient Identity Lead / 1 business day | evidence only; no merge/update |
| `LEGACY-PATIENT-CHILD-TARGET-020` prohibited existing-target mutation | Critical / Migration Technical Lead / immediate containment | rollback/stop; revalidate target state |
| `LEGACY-PATIENT-CHILD-OCCUPATION-021` blank | Information / Health Records Officer / none | accepted null field outcome |
| `LEGACY-PATIENT-CHILD-OCCUPATION-022` invalid | Medium / Health Records Officer / 5 business days before optional release | withhold occupation only |
| `LEGACY-PATIENT-CHILD-OCCUPATION-023` overlength | Medium / Health Records Officer / 5 business days | withhold; no truncation |
| `LEGACY-PATIENT-CHILD-ADDRESS-024` blank | Information / Health Records Officer / none | accepted null field outcome |
| `LEGACY-PATIENT-CHILD-ADDRESS-025` invalid | Medium / Health Records Officer / 5 business days | withhold address only |
| `LEGACY-PATIENT-CHILD-ADDRESS-026` overlength | Medium / Health Records Officer / 5 business days | withhold; no truncation/parsing |
| `LEGACY-PATIENT-CHILD-RELIGION-027` blank | Information / Health Records Officer / none | accepted null field outcome |
| `LEGACY-PATIENT-CHILD-RELIGION-028` unknown | Medium / Health Records Officer / 5 business days | withhold religion only; no default |
| `LEGACY-PATIENT-CHILD-RELIGION-029` invalid | Medium / Health Records Officer / 5 business days | withhold religion only |
| `LEGACY-PATIENT-CHILD-RELIGION-030` overlength | Medium / Health Records Officer / 5 business days | withhold; no truncation |
| `LEGACY-PATIENT-CHILD-MARITAL-031` blank | Information / Health Records Officer / none | accepted null field outcome |
| `LEGACY-PATIENT-CHILD-MARITAL-032` unknown | Medium / Health Records Officer / 5 business days | withhold status only; no default |
| `LEGACY-PATIENT-CHILD-MARITAL-033` invalid | Medium / Health Records Officer / 5 business days | withhold status only |
| `LEGACY-PATIENT-CHILD-TARGET-034` target enum mismatch | Critical / Target Application Architect / immediate stop | stop affected domain until enum/model contract agrees |
| `LEGACY-PATIENT-CHILD-TEXT-035` invalid encoding | High / Migration Data Steward / 1 business day | affected field/tuple; patient chain if provenance cannot be trusted |
| `LEGACY-PATIENT-CHILD-TEXT-036` control/noncharacter content | High / Migration Data Steward / 1 business day | affected field/tuple; no stripping |
| `LEGACY-PATIENT-CHILD-DRIFT-037` target constraint drift | Critical / Target Application Architect / immediate stop | entire Phase 2D target stage |
| `LEGACY-PATIENT-CHILD-DRIFT-038` source fingerprint drift | Critical / Migration Technical Lead / immediate stop | entire Phase 2D extraction |
| `LEGACY-PATIENT-CHILD-EXTRACTION-039` extraction failure | High / Migration Technical Lead / immediate retry after cause | failed bucket; never omitted |
| `LEGACY-PATIENT-CHILD-PRIVACY-040` privacy/provenance failure | Critical / Privacy and Records Officer / immediate containment | hold affected chain; stop artifact publication |

Every manual release requires new evidence and a recorded protected decision. SLA expiry never authorizes defaulting, omission, alias ownership, patient linking, contact reassignment, value repair, or release.

## Targeted documentation/evidence tests

No database-backed, broad application, feature, or production test is warranted for this specification-only phase. Add one targeted, filesystem-only contract test (recommended path `tests/Unit/LegacyMigration/Evidence/Phase2DDocumentationContractTest.php`) with the following assertions:

1. Parse all 16 required Phase 2D JSON specifications with `JSON_THROW_ON_ERROR`; assert specification version, phase `2D`, exact approved source `uuhms`, privacy-safe marker, and `implementation_authorized = false`.
2. Assert the source-column mapping contains exactly and only the seven Phase 2D columns once each. Assert `Company`, `BillStatus`, clinical fields, `LastVisit`, `Refill`, `OriginalName`, `OriginalOpd`, and Phase 2C core fields have no Phase 2D mapping record.
3. Assert alias integration references `PATIENT-ALIAS-*`/`PATIENT-REC-ALIAS-002`, introduces no alias canonicalization algorithm/version, and has zero authorized Phase 2D alias creates.
4. Assert every exception reference resolves to exactly one exception record and every reconciliation/relationship/sentinel reference resolves.
5. Assert each relationship has exactly one sentinel rule and all relationship rules require a committed protected Phase 2C patient map.
6. Assert NOK primary classes are mutually exclusive and exhaustive and the tuple contract treats the three fields as one tuple.
7. Assert occupation, address, religion, and marital status each define one mutually exclusive field outcome per source patient row.
8. Assert all existing-target mutation expected counts are numeric zero and the immutability field list includes contacts, primary flags, aliases, timestamps, and activity history.
9. Assert extraction strategy `PATIENT-CHILD-EXT-001` references `PATIENT-EXT-001`, selects only the eight projection columns, orders `PAT_ID ASC`, uses the same snapshot coordinate, and carries the exact normalized-SQL hash/version.
10. Assert every machine-readable contract prohibits raw values, row-level repository hashes/HMACs, cross-domain comparison, notifications/messages, target writes, and importer configuration.
11. Retain the existing `GeneratedEvidenceConsistencyTest` assertions that all temporal extrema are null/count-only and historical temporal result hashes cannot confirm redacted extrema.
12. Scan the machine-readable package for prohibited property names such as `raw_value`, `patient_name`, `phone_value`, `address_value`, `source_example`, `target_write`, and `importer`, allowing only explicit negative-control fields. Never print serialized offending values in the failure message; report file/path only.

Run only the focused test file plus the existing generated-evidence consistency test. The Phase 2C reviewer Low recommendation for a future generator-level service regression test belongs in the Phase 3/evidence-hardening backlog. Phase 2D should not modify the shared evidence generator merely to satisfy documentation tests.

## Open evidence items and handoff

The consolidator must not claim closure until the independent streams provide:

- a sanitized mutually exclusive NOK tuple partition, including exact target-regex-compatible phone counts;
- distinct-normalized/duplicate-group/affected-row and observed-length distributions for all seven fields;
- safe allow-listed relationship categories or an explicit decision that nonblank free text is withheld;
- installed-target and repository confirmation of emergency-contact invariants, privacy/search/display paths, and primary-contact behavior;
- a final resolution of the emergency-contact phone-validator inconsistency;
- target-collision and pre/post immutability preflight design, without target writes;
- JSON cross-reference tests passing against the final consolidated IDs.

No unresolved item authorizes a guessed value or importer. This draft recommends that the Phase 2D exit remain fail-closed if the Phase 2C snapshot cannot be shared exactly, if any aggregate partition differs from zero, or if raw patient-child data appears in an artifact.
