# Phase 2D Classic demographic and NOK discovery draft

Status: **read-only discovery handoff; not an importer or persistence authority**

## Scope and evidence classifications

This draft profiles only the approved Classic schema `uuhms` and only:

- `patients.Work`
- `patients.Address`
- `patients.NOK`
- `patients.NOKPhoneNo`
- `patients.NOKRel`
- `patients.Religion`
- `patients.MaritalStatus`

It consumes `patients.PAT_ID` only as the protected Phase 2C parent/crosswalk key and consumes `patients.OpdNo` only by reference to `LEGACY_OPD_ALIAS_CONTRACT`. It does not redefine patient identity, patient numbering, alias canonicalization, duplicate ownership, existing-target linking, parent quarantine, or chain release.

The following are expressly outside this profile: `Company`, `BillStatus`, `PhoneNo`, `Allergies`, `Medication`, `History`, `LastVisit`, `Refill`, `OriginalName`, `OriginalOpd`, the patient name/DOB/gender contract, and every table other than `patients` except the count-only zero-row guard on `sett_ocuupation`.

- **Confirmed** means live `uuhms` metadata or aggregate-only `SELECT` evidence captured in a guarded read-only transaction.
- **Inferred** means likely business meaning based on source naming and established Phase 2C ownership; it is not mapping authority.
- **Unresolved** means the Classic database cannot establish a safe semantic or target transformation.

No raw patient key, OPD value, NOK name, NOK phone, NOK relationship, address, occupation, row-level hash, patient date, or unapproved category was returned or written. Counts are deliberately aggregate-only.

## Read-only execution guard

The live profile was executed on 2026-07-21 UTC through configured connection `legacy_uhms`. Before any profile query, the command required the configured database name to equal case-sensitive `uuhms`, then set `REPEATABLE READ` and session `READ ONLY`, opened a consistent-snapshot transaction, ran only `SELECT` and metadata statements, and rolled the transaction back.

The live guard returned:

| Guard | Confirmed value |
|---|---:|
| `DATABASE()` | `uuhms` |
| Database version | MariaDB 10.4.32 |
| Session read-only | 1 |
| Isolation | `REPEATABLE-READ` |
| Base tables | 55 |
| Columns | 479 |
| Structural fingerprint baseline | `150fcf4783fcb8bdc25f7e17fe0ece5050955f0c68e7dd03651ee8bd58498977` |

The configured Classic account still has broader privileges than the final migration account may have. D-101 remains a Phase 3 prerequisite: migration execution must use a dedicated account limited to `SELECT` and metadata on exact `uuhms`.

## Installed source structure

**Confirmed:** `uuhms.patients` is InnoDB, contains 16,950 rows, has 24 columns and has only `PRIMARY(PAT_ID)`. There is no secondary index on any Phase 2D field. Classic declares no foreign keys. Every in-scope field is `NOT NULL` with no declared default and uses `latin1/latin1_swedish_ci`.

| Source column | Ordinal | Installed type | Declared length | Nullability | Phase 2C ownership consumed |
|---|---:|---|---:|---|---|
| `Work` | 7 | `varchar(15)` | 15 | NOT NULL | `PATIENT-COL-007`; Phase 2D |
| `Address` | 9 | `varchar(25)` | 25 | NOT NULL | `PATIENT-COL-009`; Phase 2D |
| `NOK` | 10 | `varchar(100)` | 100 | NOT NULL | `PATIENT-COL-010`; Phase 2D |
| `NOKPhoneNo` | 11 | `varchar(10)` | 10 | NOT NULL | `PATIENT-COL-011`; Phase 2D |
| `NOKRel` | 12 | `varchar(15)` | 15 | NOT NULL | `PATIENT-COL-012`; Phase 2D |
| `Religion` | 13 | `varchar(15)` | 15 | NOT NULL | `PATIENT-COL-013`; Phase 2D |
| `MaritalStatus` | 14 | `varchar(15)` | 15 | NOT NULL | `PATIENT-COL-014`; Phase 2D |

`PAT_ID` is a positive, unique source primary key under the Phase 2C baseline. It is not a target key, patient number, alias source-patient FK, or document-safe identifier.

## Sanitized field profile

Comparison profiling used deliberate declared-Latin-1 interpretation, outer trim and case-insensitive `UPPER(TRIM(value))` aggregation. This comparison is evidence only. It is not a final Unicode canonicalizer and must not be reused for patient identity or OPD alias ownership.

| Field | Rows | Null | Exact empty | Whitespace-only | Blank after trim | Nonblank | Distinct normalized nonblank | Duplicate groups | Rows in duplicate groups |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| `Work` | 16,950 | 0 | 6,440 | 0 | 6,440 | 10,510 | 1,387 | 380 | 9,503 |
| `Address` | 16,950 | 0 | 982 | 0 | 982 | 15,968 | 2,198 | 465 | 14,235 |
| `NOK` | 16,950 | 0 | 1,505 | 0 | 1,505 | 15,445 | 13,112 | 1,632 | 3,965 |
| `NOKPhoneNo` | 16,950 | 0 | 2,094 | 0 | 2,094 | 14,856 | 11,501 | 2,187 | 5,542 |
| `NOKRel` | 16,950 | 0 | 1,668 | 0 | 1,668 | 15,282 | 435 | 139 | 14,986 |
| `Religion` | 16,950 | 0 | 1,651 | 0 | 1,651 | 15,299 | 3 | 3 | 15,299 |
| `MaritalStatus` | 16,950 | 0 | 1,381 | 0 | 1,381 | 15,569 | 3 | 3 | 15,569 |

Duplicate counts describe repeated source text only. They are not duplicate patients, target matches, contact matches, merge evidence, alias ownership evidence, or authority to deduplicate across patient parents.

### Length and text-quality profile

| Field | Observed maximum | At source declared limit | Over source limit | Outer-whitespace rows | Control-character rows | Non-ASCII rows | Declared-Latin-1 round-trip failures |
|---|---:|---:|---:|---:|---:|---:|---:|
| `Work` | 15 | 589 | 0 | 260 | 0 | 0 | 0 |
| `Address` | 25 | 25 | 0 | 719 | 0 | 0 | 0 |
| `NOK` | 31 | 0 | 0 | 296 | 0 | 0 | 0 |
| `NOKPhoneNo` | 10 | 14,676 | 0 | 11 | 0 | 0 | 0 |
| `NOKRel` | 15 | 11 | 0 | 46 | 0 | 0 | 0 |
| `Religion` | 12 | 0 | 0 | 0 | 0 | 0 | 0 |
| `MaritalStatus` | 9 | 0 | 0 | 0 | 0 | 0 | 0 |

Every stored byte in the seven fields is ASCII at this capture, and every value round-trips under the declared Latin-1 encoding. This does not authorize implicit connection transcoding: the future extractor still must decode Latin-1 deliberately, normalize Unicode NFC, and fail closed on a later decode or encoding drift.

The Phase 2C target baseline reports operational maxima of 100 for `patients.occupation`, 500 for `patients.address`, and 100 for `patients.religion`; source maxima produce zero overlength rows against those known limits. Source maxima for NOK name (31), phone (10) and relationship (15) are below the installed `emergency_contacts` physical `varchar(191)` capacity. The target specialist must independently confirm the current operational contact validation limits; physical capacity alone is not a persistence rule. `MaritalStatus` requires enum-semantic validation rather than a length-only test.

### Observed length distribution

| Field | 1–5 | 6–10 | 11–15 | 16–25 | 26–50 | 51–100 | >100 |
|---|---:|---:|---:|---:|---:|---:|---:|
| `Work` | 538 | 8,260 | 1,712 | 0 | 0 | 0 | 0 |
| `Address` | 6,449 | 7,490 | 1,588 | 441 | 0 | 0 | 0 |
| `NOK` | 112 | 1,687 | 10,465 | 3,161 | 20 | 0 | 0 |
| `NOKPhoneNo` | 10 | 14,846 | 0 | 0 | 0 | 0 | 0 |
| `NOKRel` | 1,537 | 13,553 | 192 | 0 | 0 | 0 | 0 |
| `Religion` | 112 | 2,749 | 12,438 | 0 | 0 | 0 | 0 |
| `MaritalStatus` | 0 | 15,569 | 0 | 0 | 0 | 0 | 0 |

### Shape diagnostics

| Field | Digits only | Contains digit | Contains ASCII alpha | Contains punctuation | Evidence implication |
|---|---:|---:|---:|---:|---|
| `Work` | 4 | 9 | 10,505 | 149 | Free text is not a clean catalogue key. |
| `Address` | 2 | 8,630 | 15,966 | 5,476 | Mixed unstructured address text; no parsing or geocoding is evidenced. |
| `NOK` | 24 | 38 | 15,421 | 215 | Twenty-four nonblank values contain no ASCII alphabetic character; name validity review is required. |
| `NOKPhoneNo` | 14,758 | 14,770 | 87 | 11 | Ninety-eight populated values are not digits-only; phone transformation cannot default. |
| `NOKRel` | 45 | 53 | 15,237 | 125 | High cardinality and non-relationship-shaped content prohibit a clean-enum assumption. |
| `Religion` | 0 | 0 | 15,299 | 0 | Exactly three safe categorical values exist at the baseline. |
| `MaritalStatus` | 0 | 0 | 15,569 | 353 | Parentheses in the observed widow category explain the punctuation count. |

Shape predicates overlap and therefore are not reconciliation partitions.

## NOK candidate tuple completeness

**Confirmed:** `NOK`, `NOKPhoneNo` and `NOKRel` must be profiled as one optional candidate contact tuple on each patient row. The mutually exclusive presence partition is:

| Tuple presence class | Rows |
|---|---:|
| Name + phone + relationship | 14,578 |
| Name + phone only | 213 |
| Name + relationship only | 586 |
| Phone + relationship only | 49 |
| Name only | 68 |
| Phone only | 16 |
| Relationship only | 69 |
| All three blank | 1,371 |
| **Total** | **16,950** |
| **Difference** | **0** |

There are 1,001 partial tuples. Missing-field views are overlapping diagnostics, not partitions: 134 partial tuples lack a name, 723 lack a phone, and 297 lack a relationship.

### NOK phone shape evidence

| Diagnostic | Rows |
|---|---:|
| Populated NOK phones | 14,856 |
| Exactly ten ASCII digits | 14,594 |
| Exactly ten digits with leading zero | 14,592 |
| Conservative Ghana local shape `^0[235][0-9]{8}$` | 14,577 |
| Populated non-digit shape | 98 |
| Populated trimmed length other than ten | 190 |
| Multiple-number separator shape (`/`, `,`, or `;`) | 2 |
| Plus-prefix shape | 2 |
| Complete tuple with conservative Ghana local phone shape | 14,348 |

These are validation inputs, not approved normalization outcomes. The Classic field cannot store a full `+233` representation and is not evidence that every ten-digit value is a Ghana number. A future rule may transform only an evidence-compatible leading-zero local number; it must preserve protected provenance, reject ambiguous multiple-number/extension content, and never copy `patients.PhoneNo` into a missing NOK phone.

Twenty-four populated NOK-name values contain no ASCII alphabetic character. No raw names were inspected or returned. The source cannot establish whether those values are names, placeholders, numbers, or data-entry mistakes; they require a deterministic invalid/withheld or manual-review outcome in the consolidated contract.

## Safe category evidence

Only the project-reviewed, non-identifying allow-list was emitted. No unapproved category literal was returned.

### Religion

| Safe normalized source category | Rows | Evidence state |
|---|---:|---|
| `CHRISTIANITY` | 12,438 | Confirmed exact source category |
| `MUSLIM` | 2,749 | Confirmed exact source category |
| `OTHER` | 112 | Confirmed exact source category; semantics must not be broadened |
| Blank | 1,651 | Confirmed absence |
| Unrecognized nonblank outside allow-list | 0 | Confirmed for this snapshot |
| **Total** | **16,950** | Difference zero |

**Unresolved:** the Classic schema has no religion catalogue or semantic metadata. A target free-text representation and display normalization belong to the consolidated Phase 2D contract. Blank must remain absence, not a default religion. Religion is not identity evidence.

### Marital status

| Safe normalized source category | Rows | Evidence state |
|---|---:|---|
| `SINGLE` | 8,240 | Confirmed exact source category |
| `MARRIED` | 6,976 | Confirmed exact source category |
| `WIDOW(ER)` | 353 | Confirmed source category requiring explicit target semantic crosswalk |
| Blank | 1,381 | Confirmed absence |
| `DIVORCED`, `WIDOWED`, `SEPARATED`, `COHABITING` combined | 0 | Confirmed absent under the safe named set |
| Other unrecognized nonblank | 0 | Confirmed for this snapshot |
| **Total** | **16,950** | Difference zero |

The target enum/cast compatibility and the precise treatment of `WIDOW(ER)` must be stated explicitly by the consolidated technical specification. No convenient default is supported. A future nonblank out-of-domain value is a stop/exception input, not null-equivalent absence.

## Occupation evidence and dormant catalogue

**Confirmed:** `sett_ocuupation` contains zero rows, while `patients.Work` contains 10,510 nonblank values across 1,387 normalized groups. The source has no populated reference catalogue, no foreign key and no stable occupation code connecting `Work` to a master row.

**Inferred:** `Work` is occupation-like short free text based on the column name and Phase 2C ownership assignment. That inference is sufficient only to nominate `patients.occupation` as a candidate destination for independently valid text.

The evidence does not support creating or populating `sett_ocuupation`, generating a renewed occupation catalogue, turning repeated values into natural keys, deriving insurance or socioeconomic status, or using occupation for identity/matching/deduplication. D-209/Q-305 makes a nonzero `sett_ocuupation` count or fingerprint change a preflight stop requiring scope review.

## Address evidence

**Confirmed:** `Address` is one `varchar(25)` unstructured text field; there are no companion patient city, town, region, postal-code, or digital-address columns in Classic. Its 15,968 nonblank rows include mixed alphabetic, numeric and punctuation content.

The source does not evidence geocoding, GhanaPost GPS derivation, locality parsing, punctuation splitting, organisation-address substitution, or patient matching. The only candidate destination supported by source shape is an unstructured target patient-address field. The final mapping must preserve the protected source representation and must leave target city, town, region, postal code and digital address unpopulated unless another approved source exists.

## Relationship and sentinel inputs

| Relationship input | Source predicate | Sentinel/absence evidence | Blocking scope |
|---|---|---|---|
| Patient row to demographic value | Same `uuhms.patients` row identified by protected `PAT_ID` | Exact empty string is the observed absence representation; null and whitespace-only counts are zero. No numeric/text sentinel is approved. | Field outcome only after parent success; parent quarantine holds it. |
| Patient row to NOK tuple | `patients.(NOK,NOKPhoneNo,NOKRel)` on the same protected parent row | All three empty means no contact candidate; partial presence is not a sentinel and requires a child outcome. | Optional child only; invalid contact does not establish a different parent. |
| Protected source parent to mapped target patient | Phase 2C crosswalk, not a demographic join | `PAT_ID` is never null/zero in the patient master baseline; no synthetic target parent is permitted. | Missing/ambiguous/quarantined parent holds every Phase 2D child. |
| Parent to legacy OPD alias | Reference `PATIENT-ALIAS-001` through `PATIENT-ALIAS-017` | Reference Phase 2C blank, invalid, duplicate and collision rules; no Phase 2D sentinel or canonicalizer. | Alias outcome independent after patient map; duplicate-withheld alias does not invalidate other children. |

Digit-only `NOKRel` values are not a proved zero/null sentinel or relationship code. Empty strings have field-specific absence meaning only; this evidence does not establish a global blank-is-null or zero-is-null policy.

## Phase 2C parent and alias contracts consumed

- `PATIENT-COL-001` / protected crosswalk: Classic `PAT_ID` is only source identity and ordering provenance.
- `PATIENT-EXT-001`: the authoritative patient extraction is one ordered full 24-column snapshot with protected fingerprinting and optional overlap assistance.
- `PATIENT-TARGET-001` through `PATIENT-TARGET-014`: only explicit protected links may identify an existing target; existing target identity, contact, child and demographic data remain immutable.
- `PATIENT-ALIAS-001` through `PATIENT-ALIAS-017`: OPD aliases retain the Phase 2C display/comparison, blank, duplicate-withholding, collision, idempotency, creator, source-patient-FK and reconciliation rules.
- `PATIENT-PRIV-007` and the Phase 2C chain contract: a patient-root token holds all Phase 2D child candidates while the patient is unresolved or quarantined.

No contact, phone, address, occupation, religion, marital status, text duplicate, or similarity result may establish a patient link, reassign a child, choose an alias owner, merge patients, or release a quarantine.

## Extraction handoff

Phase 2D must be a projection of the same coordinated `PATIENT-EXT-001` patient snapshot, not a second independently timed patient extraction.

Proposed projection query identity for the lead specification:

- Query ID: `PHASE2D-EXTRACT-PATIENT-CHILD-PROJECTION-001`
- Version: `phase2d-patient-child-projection/1.0.0`
- Exact normalized SQL (single line when hashed):

```sql
SELECT `PAT_ID`, `Work`, `Address`, `NOK`, `NOKPhoneNo`, `NOKRel`, `Religion`, `MaritalStatus` FROM `uuhms`.`patients` ORDER BY `PAT_ID` ASC
```

- UTF-8 SHA-256 of that exact single-line normalized SQL: `db20d1995d2c525e8f3efc90a8170581a2ed0832e122623e893467e0dd3722bc`
- Ordering/resume coordinate: protected `PAT_ID ASC`
- Recommended chunk: inherit Phase 2C `PATIENT-EXT-001` value of 1,000
- Update detection: inherit protected full-row fingerprint comparison; `EditDate` overlap is assistance only
- Delete detection: inherit complete protected source-key comparison; never infer deletion from absence in a partial chunk
- Snapshot dependency: exact Phase 2C snapshot coordinate and protected patient-row fingerprint
- Output: row-level data only inside the protected migration runtime; repository evidence remains aggregate-only

The projection hash is a specification input, not proof that an extractor exists. Any timing, row-count, parent-key-set, schema, collation, source fingerprint, query-version, category, or dormant-table difference between the Phase 2C patient snapshot and this projection must stop reconciliation. The current evidence run did not create a row-level extract or fingerprint.

## Confirmed findings

1. Exactly seven Phase 2D source columns exist on one 16,950-row `patients` table and are classified above.
2. All seven are non-null Latin-1 text with empty string as the observed absence representation and zero whitespace-only values.
3. The NOK presence partition reconciles all 16,950 patient rows exactly once; 14,578 are complete, 1,001 partial and 1,371 all blank.
4. NOK phone quality is mostly ten-digit local-shaped but includes invalid/ambiguous characters, lengths, plus-prefixes and multiple-number separators.
5. `NOKRel` has 435 normalized nonblank values plus numeric/punctuation shapes and cannot be treated as a clean enum.
6. Religion has exactly three safely allow-listed source categories and marital status has exactly three safely allow-listed source categories at the captured baseline.
7. `sett_ocuupation` remains empty; `Work` is unlinked free text.
8. All seven fields are protected patient information even where category literals are allow-listed.

## Inferred findings

1. `Work` is occupation-like, `Address` is an unstructured patient address, and `NOK*` is one next-of-kin/emergency-contact candidate tuple. These meanings follow source naming and Phase 2C ownership but do not prove every value is semantically valid.
2. A valid optional field can be treated independently after parent success; a child-quality failure need not become a patient-identity failure. Final blocking scope belongs to the consolidated contract.
3. Repeated values are expected in demographic/contact text and are not useful natural keys.

## Unresolved inputs for consolidation

1. The target specialist must confirm exact current operational emergency-contact name, phone and relationship validation, not only installed physical lengths.
2. `NOKRel` safe literals and typographical variants were deliberately not emitted. Any literal relationship crosswalk needs separately privacy-reviewed, aggregate-safe evidence or must remain protected/manual review.
3. The final Ghana NOK-phone normalization must define local-to-`+233` behavior, invalid characters, extensions and multiple-number inputs without assuming every ten-digit value is valid.
4. The final contact tuple rules must decide whether complete source presence plus validated values is sufficient for child creation and initial-primary status on a newly created patient; Classic contains no primary flag.
5. The target crosswalk for `WIDOW(ER)` needs explicit semantic approval in the Phase 2D technical specification.
6. Target-state comparisons and existing-target immutability require protected runtime evidence that this Classic-only profile cannot obtain.

## Handoff constraints

- Do not store or log raw values from any of the seven fields.
- Do not use raw, unsalted, or cross-domain-comparable row hashes. Future protected diagnostics require environment-keyed, domain-separated HMAC with typed length-prefixed canonical messages and explicit key/canonicalization versions.
- Do not create child rows before a successful protected patient mapping exists.
- Do not mutate or enrich an explicitly linked existing target patient or any existing target emergency contact.
- Do not make a partial or invalid NOK tuple block an otherwise valid patient solely because the tuple exists; retain a classified child outcome.
- Do not create aliases, contacts, occupation catalogues, demographic reference rows, importers, migration tables, seeders, schema changes, or database writes from this draft.
