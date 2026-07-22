# Classic patient discovery draft

## Scope and safety

This is the read-only Phase 2C workstream-1 handoff for Classic patient identity. It contains schema metadata, aggregate counts and non-identifying categorical values only. It contains no patient names, OPD numbers, phone numbers, addresses, clinical text or row-level identifiers.

The live evidence run used only configured connection `legacy_uhms`, rejected a configured or live database other than exact `uuhms`, set `REPEATABLE READ` and session `READ ONLY`, opened one transaction, ran SELECT/metadata statements, and rolled it back. The reproducibility run was `2026-07-21T19:46:56+00:00` through `2026-07-21T19:46:59+00:00`; transaction level was 1 before rollback and 0 after rollback. The live guard returned `uuhms`, MariaDB `10.4.32-MariaDB`, `tx_read_only=1`, `REPEATABLE-READ`, 55 base tables and 479 columns.

The Phase 1 structural fingerprint remains `150fcf4783fcb8bdc25f7e17fe0ece5050955f0c68e7dd03651ee8bd58498977` (captured `2026-07-21T01:57:29+00:00`). This workstream confirmed the live shape and the exact installed `patients` columns/index against that manifest; it did not recompute the complete 55-table structural fingerprint.

Safety finding: Phase 1 evidence shows the configured account has `SELECT` globally but broad schema privileges on `uuhms`. The session read-only controls were observed for this discovery, but D-101 still blocks migration execution until a dedicated SELECT/metadata-only account is provisioned and verified.

## Classification convention

- **Confirmed**: established directly by live metadata or sanitized aggregate SELECT evidence.
- **Inferred**: likely domain meaning or future disposition supported by structure/aggregates, but not an approved mapping.
- **Unresolved**: source semantics or validation policy cannot safely be established from the database alone.

## Confirmed patient master and complete installed schema

`uuhms.patients` is the only table named `patients`, is an InnoDB base table using `latin1_swedish_ci`, and contains exactly 16,950 rows. It has 24 columns and one index: unique BTREE primary index `PRIMARY(PAT_ID)`. No secondary, unique business-key, OPD, name, phone, `EditDate`, `RegDate` or `LastVisit` index exists. `uuhms` declares zero foreign keys.

| # | Column | Installed definition | Null/default/extra | Confirmed evidence and likely ownership |
|---:|---|---|---|---|
| 1 | `PAT_ID` | `int(11)` | NOT NULL; auto-increment; PK | Stable source ordering/link key; protected migration provenance/crosswalk only. Never a target ID or patient number. |
| 2 | `PatientName` | `varchar(100)` latin1 | NOT NULL; no default | Only installed patient-name field; combined name. Patient identity. No separate first/last/other-name columns exist. |
| 3 | `OpdNo` | `varchar(15)` latin1 | NOT NULL; no default | Classic OPD identifier; typed legacy-alias candidate subject to validity and uniqueness. Not a target patient number. |
| 4 | `Sex` | `varchar(10)` latin1 | NOT NULL; no default | Patient identity/demographic category requiring explicit crosswalk. |
| 5 | `DOB` | `date` | NOT NULL; no default | Patient required identity date requiring chronology validation. |
| 6 | `PhoneNo` | `varchar(10)` latin1 | NOT NULL; no default | Patient contact/required identity input; normalization cannot establish identity. |
| 7 | `Work` | `varchar(15)` latin1 | NOT NULL; no default | Likely occupation/free-text work field; Phase 2D demographic ownership inferred, semantics unresolved. |
| 8 | `Company` | `varchar(25)` latin1 | NOT NULL; no default | Likely default payer/company context; Phase 2E insurance ownership inferred, semantics unresolved. |
| 9 | `Address` | `varchar(25)` latin1 | NOT NULL; no default | Patient contact/address; Phase 2D. |
| 10 | `NOK` | `varchar(100)` latin1 | NOT NULL; no default | Next-of-kin name; emergency contact/Phase 2D, protected PHI. |
| 11 | `NOKPhoneNo` | `varchar(10)` latin1 | NOT NULL; no default | Next-of-kin phone; emergency contact/Phase 2D, protected PHI. |
| 12 | `NOKRel` | `varchar(15)` latin1 | NOT NULL; no default | Next-of-kin relationship/free text; Phase 2D, semantics dirty/unresolved. |
| 13 | `Religion` | `varchar(15)` latin1 | NOT NULL; no default | Patient demographic; Phase 2D. |
| 14 | `MaritalStatus` | `varchar(15)` latin1 | NOT NULL; no default | Patient demographic; Phase 2D. |
| 15 | `BillStatus` | `varchar(25)` latin1 | NOT NULL; no default | Default payer/billing category; Phase 2E. It is not evidence of active/inactive patient status. |
| 16 | `Refill` | `int(11)` | NOT NULL; no default | Unclear relationship/operational pointer; no approved destination. See ambiguity evidence below. |
| 17 | `Allergies` | `varchar(500)` latin1 | NOT NULL; no default | Clinical-history field; empty baseline. Evidence-only/clinical phase. |
| 18 | `Medication` | `varchar(500)` latin1 | NOT NULL; no default | Clinical-history field; empty baseline. Evidence-only/clinical phase. |
| 19 | `History` | `varchar(500)` latin1 | NOT NULL; no default | Clinical-history/free-text field; protected PHI, later clinical phase. |
| 20 | `LastVisit` | `datetime(0)` | NOT NULL; no default | Derived/business visit timestamp candidate; not reliable creation/update evidence. |
| 21 | `EditDate` | `timestamp(0)` | NOT NULL; default current timestamp; `ON UPDATE CURRENT_TIMESTAMP` | Mutable row-change candidate with one-second precision; extraction evidence, not a creation date. |
| 22 | `RegDate` | `timestamp(0)` | NOT NULL; default current timestamp | Registration timestamp candidate. No source registration actor accompanies it. |
| 23 | `OriginalName` | `varchar(250)` latin1 | NOT NULL; no default | Previous/original-name provenance field; all rows blank. |
| 24 | `OriginalOpd` | `varchar(250)` latin1 | NOT NULL; no default | Previous/original-OPD provenance field; all rows blank. |

Confirmed absent from `patients`: separate given/first name, surname/last name, other names, email, Ghana Card/national ID, blood group, general patient status, patient notes distinct from clinical history, registration actor/user/creator, and any declared relationship constraint.

Suggested primary-disposition inputs for the lead specification, not approvals: `PAT_ID` protected provenance/crosswalk; `PatientName` controlled split/remediation with no automatic ambiguous split; `OpdNo` typed legacy-alias candidate; `Sex`, `DOB`, `PhoneNo` transform/validate; `RegDate` direct historical timestamp subject to validity; `EditDate` extraction/evidence-only; `OriginalName`/`OriginalOpd` protected provenance; `Work`, `Address`, `NOK*`, `Religion`, `MaritalStatus` defer to Phase 2D; `Company`, `BillStatus` defer to Phase 2E; `Allergies`, `Medication`, `History`, `LastVisit`, `Refill` evidence-only or later-domain pending an approved semantic destination.

## Primary-key quality

**Confirmed:** 16,950 rows, 16,950 distinct `PAT_ID`, zero null, zero nonpositive, zero duplicate groups. Range is 1 through 18,724, so `MAX-MIN+1-COUNT = 1,774` unused values. The observed next auto-increment value was 18,725. Gaps do not prove deletion. The PK is a safe stable ordering/resume coordinate, but only for inserts; D-201 prohibits reuse as any renewed identifier.

## Identity and required-field quality

### Combined name

Predicate `COALESCE(TRIM(PatientName),'')=''` finds 211 blank rows; 16,739 are populated. There are 507 rows whose binary stored value differs from `TRIM(PatientName)`, 134 populated single-token rows, one row containing a control character, one row at the declared 100-character limit, and a maximum length of 100. All populated names contain at least one alphabetic character.

Normalized `UPPER(TRIM(PatientName))` has 1,001 duplicate groups covering 2,278 rows. Stronger review signals remain non-deterministic: `(normalized name,DOB,normalized Sex)` has 230 groups/466 rows; adding `TRIM(PhoneNo)` has 190 groups/384 rows. These are review flags only under Q-002; they prove neither a duplicate patient nor a target match.

**Unresolved:** no source column reliably separates first and last name. Therefore no row can be confirmed from schema evidence alone to satisfy separate target first/last-name requirements. Automatic splitting, copying one component into both fields, or inventing a component is prohibited by D-213.

### OPD number and alias partition

The exact partition under comparison normalization `UPPER(TRIM(OpdNo))` is:

| Outcome | Rows/groups |
|---|---:|
| Classic patient rows | 16,950 |
| Blank after trim | 141 rows |
| Unique nonblank normalized values | 15,550 rows |
| Duplicated nonblank normalized values | 1,259 rows in 275 groups |
| Difference | 0 |

The 15,550 are uniqueness candidates, not yet semantically validated aliases. Two duplicate groups contain more than one binary stored representation that collapse under uppercase/trim comparison. Additional shape evidence: 1 binary outer-whitespace row, 5 values containing whitespace, 0 control-character rows, 0 digits-only rows, 696 alphanumeric-only rows, 16,113 rows containing punctuation, 1 leading-zero row, maximum length 15, and 3 rows at the declared limit. Punctuation dominates the installed format, so a digits-only validity rule would be unsupported. Leading zeros must be preserved. The semantic allowed-character grammar remains unresolved and requires an approved source-format rule.

Under D-201/D-211: blank rows receive no alias; all 1,259 duplicate-affected patients receive a duplicate-alias exception and no automatic alias; the 15,550 unique rows may proceed only after the approved validity predicate and target collision checks. Duplicate OPD status does not by itself block an otherwise valid patient entity.

### Gender/sex

Normalization is `UPPER(TRIM(Sex))`. Recognized source classes `MALE`, `FEMALE`, `M`, `F` cover 16,784 rows. Blank covers 151. Fifteen populated rows are outside that set and must not default:

| Normalized value | Count | State |
|---|---:|---|
| `FEMALE` | 9,080 | explicit crosswalk candidate |
| `MALE` | 7,282 | explicit crosswalk candidate |
| `F` | 224 | explicit crosswalk candidate |
| `M` | 198 | explicit crosswalk candidate |
| blank | 151 | missing/quarantine unless controlled representation approved |
| `EMALE`, `FEMLE`, `MAL` | 1 each | malformed/remediation or quarantine |
| `1`, `2`, `10`, `22`, `23`, `24`, `26`, `27`, `32`, `38`, `42`, `74` | 1 each | out-of-domain/remediation or quarantine |

### Date of birth

`DOB` is NOT NULL `date`. Zero dates: 0; partial dates cannot be represented by the installed type. There is 1 pre-1900 row, 0 future rows as of capture, 18 rows with `DOB > DATE(RegDate)`, and 646 with `DOB = DATE(RegDate)`. Patient-derived extrema are deliberately omitted. The equality class is a review signal, not automatically invalid. A conservative `DOB >= '1900-01-01' AND DOB <= UTC_DATE() AND DOB <= DATE(RegDate)` passes 16,931 rows, but the final valid minimum and same-day rule require approval. No invalid date may be replaced by registration/current/inferred dates.

### Patient phone

`PhoneNo` is NOT NULL `varchar(10)`: 5,050 blank and 11,900 populated. Exactly ten ASCII digits after trim: 11,751; exactly ten digits beginning `0`: 11,747; 153 populated rows fail the conservative `^0[0-9]{9}$` shape. There are 26 populated non-digit values after trim, 139 populated values whose trimmed length is not 10, 15 binary outer-whitespace rows, maximum length 10, and 11,771 stored values at the declared limit. Exact `TRIM(PhoneNo)` duplicates form 935 groups covering 2,075 rows.

The local-shape predicate is evidence only, not an approved Ghana normalization. Four ten-digit numeric values do not begin zero. The source length cannot retain a full `+233...` representation in the same field. Country-code semantics, extensions and any safe normalization remain unresolved. Phone duplicates or values must never establish patient identity or target matching.

### Source-required intersection

Using only the provisional checks `TRIM(PatientName)<>''`, recognized gender, conservative DOB chronology, and conservative phone shape, 11,732 rows pass all four. This is not a migration-eligible count because the Classic combined name does not establish separate required first/last names, and target collision/constraint checks are outside this workstream. Independent source counts are: combined name present 16,739; recognized gender 16,784; conservative DOB 16,931; conservative phone shape 11,747.

### Email and national identifier

**Confirmed absent:** no patient email column and no Ghana Card/national-ID-like column. Consequently presence, format, uniqueness and verified-state counts are not available from the patient master. No value may be inferred from another field.

## Other installed patient-column quality

All 18 character columns use `latin1/latin1_swedish_ci` although the database default is `utf8mb4_general_ci`. An ASCII round-trip comparison found zero non-ASCII-byte rows in each patient character column. This is aggregate evidence, not proof that historical application decoding was correct. UTF-8 output must still explicitly decode Classic latin1 and normalize comparison text to Unicode NFC. Control characters occur in 1 `PatientName` row and all 7 populated `History` rows.

| Column | Blank | Populated | Max length / limit | At limit | Binary outer whitespace | Control-char rows |
|---|---:|---:|---:|---:|---:|---:|
| `PatientName` | 211 | 16,739 | 100 / 100 | 1 | 507 | 1 |
| `OpdNo` | 141 | 16,809 | 15 / 15 | 3 | 1 | 0 |
| `Sex` | 151 | 16,799 | 6 / 10 | 0 | 1 | 0 |
| `PhoneNo` | 5,050 | 11,900 | 10 / 10 | 11,771 | 15 | 0 |
| `Work` | 6,440 | 10,510 | 15 / 15 | 589 | 260 | 0 |
| `Company` | 11,652 | 5,298 | 25 / 25 | 180 | 75 | 0 |
| `Address` | 982 | 15,968 | 25 / 25 | 25 | 719 | 0 |
| `NOK` | 1,505 | 15,445 | 31 / 100 | 0 | 296 | 0 |
| `NOKPhoneNo` | 2,094 | 14,856 | 10 / 10 | 14,676 | 11 | 0 |
| `NOKRel` | 1,668 | 15,282 | 15 / 15 | 11 | 46 | 0 |
| `Religion` | 1,651 | 15,299 | 12 / 15 | 0 | 0 | 0 |
| `MaritalStatus` | 1,381 | 15,569 | 9 / 15 | 0 | 0 | 0 |
| `BillStatus` | 40 | 16,910 | 24 / 25 | 0 | 5 | 0 |
| `Allergies` | 16,950 | 0 | 0 / 500 | 0 | 0 | 0 |
| `Medication` | 16,950 | 0 | 0 / 500 | 0 | 0 | 0 |
| `History` | 16,943 | 7 | 500 / 500 | 1 | 1 | 7 |
| `OriginalName` | 16,950 | 0 | 0 / 250 | 0 | 0 | 0 |
| `OriginalOpd` | 16,950 | 0 | 0 / 250 | 0 | 0 | 0 |

`NOKPhoneNo` has 14,594 exactly-ten-digit rows, 14,592 ten-digit-leading-zero rows, 98 populated non-digit rows and 190 populated length-not-ten rows. These are Phase 2D contact-quality inputs.

`NOKRel` has 435 distinct nonblank trimmed values, unexpectedly high for a relationship category; no values were emitted because the field may contain free text or identifying mistakes. Treat semantics as unresolved and protected.

`sett_ocuupation` contains 0 rows. Therefore none of 10,510 nonblank `Work` values can be validated against that catalogue; D-209/Q-305 makes any later nonzero occupation-catalogue fingerprint a stop condition. `Work` is inferred occupation-like only from naming/context.

`Allergies` and `Medication` are empty in all 16,950 rows. `History` has 7 populated rows, all with control characters, and one reaches 500 characters. No clinical content was read or emitted.

## Safe categorical values

`BillStatus` values are: NHIS 10,587; PRIVATE INSURANCE 3,416; CASH AND CARRY 2,518; PRIVATE INSURANCE + NHIS 384; blank 40; PRIVATE INSURANCE + 5. These are payer-source classes, not target mappings. Company presence by those classes is respectively 2,775; 1,783; 473; 262; 3; and 2. Company semantics and provider matching remain Phase 2E decisions.

`Religion`: CHRISTIANITY 12,438; MUSLIM 2,749; OTHER 112; blank 1,651. `MaritalStatus`: SINGLE 8,240; MARRIED 6,976; WIDOW(ER) 353; blank 1,381. These values require explicit Phase 2D target crosswalks.

## Registration timestamp and actor evidence

`RegDate`, `EditDate` and `LastVisit` each have null/zero count 0. Patient-derived extrema are deliberately omitted from the draft and represented only by protected evidence hashes.

No patient registration actor field exists. There is no declared patient-to-user relationship. Under the Phase 2B actor contract, no current user, importer, administrator, first user, supervisor or `Legacy Actor Unknown` may be invented for patient registration. The future target outcome must be null plus protected provenance if `patients.registered_by` permits null; otherwise this is a target prerequisite/blocker requiring a controlled field contract.

`LastVisit` is not a reliable derivation of the maximum matched `attendance.AttDate`: among 16,366 patients with at least one matched attendance, only 23 equal the maximum attendance timestamp, 12,158 are earlier and 4,185 are later. Another 584 patients have no matched attendance. Preserve it only as source evidence unless later semantics are established.

## Update detection and extraction implications

`PAT_ID` is the only indexed/stable ordering key. Its 1,774 gaps and 26 observed adjacent-PK registration-time regressions mean PK order is stable but not equivalent to registration chronology. A PK watermark detects later inserts only; it cannot detect updates or deletes.

`EditDate` is schema-managed `ON UPDATE CURRENT_TIMESTAMP`, has one-second precision and no index. It is later than `RegDate` for 9,678 rows, equal for 7,271 and earlier for 1. There are 16,825 distinct edit timestamps across 16,950 rows (125 collision-excess rows). One row has `EditDate` later than the maximum `RegDate`. It is a useful overlap candidate but cannot prove all semantic updates, gives no delete evidence and is expensive without an index. `RegDate` and `LastVisit` are unindexed business/event timestamps, not change watermarks.

**Required extraction contract input:** ordered full snapshot by `PAT_ID`, deterministic protected row fingerprint across all 24 installed columns using explicit latin1 decoding/canonicalization, and either full comparison or an approved `(EditDate,PAT_ID)` overlap scan plus complete snapshot/hash reconciliation. Resume coordinate is `PAT_ID`; updates/deletes require fingerprint/full-snapshot comparison. A schema/fingerprint change, missing column, changed type/collation/index, non-`uuhms` database, non-read-only session, or changed dormant occupation/maternity baseline must stop extraction.

## Direct patient relationships and chain evidence

The live catalogue contains exactly six `PAT_ID` columns: the patient PK plus direct child fields in `attendance`, `beds`, `insurance`, `mat_family` and `mat_obstetrics`. All are inferred relationships because no FK exists. Partition predicate is exact equality `child.PAT_ID = patients.PAT_ID`; zero is reported separately as a candidate sentinel only and is not approved globally.

| Relationship | Child rows | Null | Zero candidate | Matched nonzero | Orphan nonzero | Partition difference |
|---|---:|---:|---:|---:|---:|---:|
| `attendance.PAT_ID -> patients.PAT_ID` | 51,927 | 0 | 155 | 48,589 | 3,183 | 0 |
| `beds.PAT_ID -> patients.PAT_ID` | 18 | 0 | 15 | 3 | 0 | 0 |
| `insurance.PAT_ID -> patients.PAT_ID` | 31,307 | 0 | 0 | 30,991 | 316 | 0 |
| `mat_family.PAT_ID -> patients.PAT_ID` | 0 | 0 | 0 | 0 | 0 | 0 |
| `mat_obstetrics.PAT_ID -> patients.PAT_ID` | 0 | 0 | 0 | 0 | 0 | 0 |

Distinct matched patient parents are 16,366 for attendance, 3 for beds and 15,478 for insurance; respectively 584, 16,947 and 1,472 patient rows have no such child. Under Q-006/D-213, 3,183 nonzero attendance orphans and their downstream visit chains, plus 316 insurance orphans, cannot be guessed or reassigned. The 155 attendance and 15 bed zero values need separate field-specific sentinel decisions. Empty maternity tables remain excluded/dormant under D-209/Q-305 and any nonzero pre-cutover count is a scope-stop.

## Ambiguous `Refill` evidence

`Refill` ranges 0 to 54,301 with 203 distinct values: 16,748 zero and 202 positive. Of the positive rows, 182 happen to match an `attendance.ATT_ID`, 78 a `patients.PAT_ID`, 144 an `insurance.INS_ID`, and 159 a `billing.BILL_ID`. Because Classic identifier domains overlap and there is no FK or semantic constraint, these matches do not establish any relationship. `Refill` must remain unresolved/evidence-only until application/stakeholder semantics prove a field-specific meaning.

## Reproducible query evidence

Recorder version: `phase-2c-classic-patient-discovery/1.0.0`; query version is 1 for every entry. Empty-binding canonical SHA-256 is `4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945`.

| Query ID | Started/finished UTC | SQL SHA-256 | Sanitized result SHA-256 |
|---|---|---|---|
| `phase2c.classic.guard` | 19:46:56 / 19:46:56 | `36a099c30c2b1ea305a0050484a15472ab65c4a5c10eb9ad856691e4f8aad6cf` | `4b08bfba297e7c87fbef19604d820df2ecd35ad9508bad2cccdfbdaccc1c9a62` |
| `phase2c.patients.columns` | 19:46:56 / 19:46:56 | `615597205d1136d88551956b8372476a74908ff75f84e327487e91faaffb2ff3` | `2ba0e94e248450f45c2e7ffa500fa8f3da9ba31d492d81677015ca5e054f2ae3` |
| `phase2c.patients.indexes` | 19:46:56 / 19:46:56 | `91c48c3ebf70f5217f729045211ce38e8eeed91c3cd3b0ecbd5a91bdd0ab4dcf` | `e5c32d77f1b7cf9d529e8d9be9f4fc3e2236915fdd263f0aa060f45e3504236d` |
| `phase2c.patients.core_quality` | 19:46:56 / 19:46:56 | `6ad957c6e182dd9458ade6753b1f4970419588d45143aea40fcb77c5deee701a` | `474c967f0b650dac2e0cdb835ad63b8a94a422b2790d43953cb635d8f9984134` |
| `phase2c.patients.gender_values` | 19:46:56 / 19:46:56 | `af5abdadf572e0717747a6fedd3ac4884bb97487eba60efa08e8b93010f04d53` | `ad7e85988f077b6e4a5808e4713903eb441a5860a965e5ab278911db973891d4` |
| `phase2c.patients.bill_status_values` | 19:46:56 / 19:46:56 | `bd6787995d361cfd164bc8dc0fa0f9362382ef112b14b8c4e2dd858f855ad350` | `756ae44446691cc529e177e560aa0536652be86c03668a6e6b25147cc7e161ee` |
| `phase2c.patients.demographic_categories` | 19:46:56 / 19:46:56 | `0622ae5e0e556a667b502c21a5935534933c3bc0b50b2aa9e81defadd09bc49c` | `50ac4ccb3d2623cfc4cad66f95c7dd1e41e0da3d2e6883c744baffa2fd2ee7c0` |
| `phase2c.patients.opd_partition` | 19:46:56 / 19:46:56 | `77e509ba2651f023bf4acbad807bc855e832b0354acb7e9de2e0e1e8cc6b2612` | `e97c41d0845c613daf4098a0072b698e3fd68b817e380d959295dee84cd131c4` |
| `phase2c.patients.suspected_duplicates` | 19:46:56 / 19:46:56 | `61618bd7af5992a727640d8946f787c89dfe62dd86b5ba99348824433c31c36d` | `1467795831c5552c360c44b827aa14098c889123a6fedb12d6aaf0a57b0af032` |
| `phase2c.patients.relationships` | 19:46:56 / 19:46:59 | `43d19db4b4b9cce94a1d9db868475adfe772ad5e63a5df540fc6026568d0e5b6` | `a35641699d72552dcd3d071d68c29ec1d53d83c056c921979eea3712357dc005` |
| `phase2c.patients.incremental` | 19:46:59 / 19:46:59 | `1f138479d41fc3dd0f73125550f03b589b4762d6111d58a7c9a574a7b0eeab45` | `7a52fb9231c8cc7dd46f771f57e7c710a3d93e69f7c4d1e1ff194deb50808dd3` |
| `phase2c.patients.refill_candidates` | 19:46:59 / 19:46:59 | `87e541f00d36a1b3c47a83d64a2d676134a0dd03b17ec5177e26903862b6e272` | `3ba2099eac43b5455ada1da9282a12c240d3b3fe1bb9ece11b16320303edb89a` |

Exact normalized SQL for the highest-risk machine predicates follows. Schema/category query SQL is directly represented by the column/value tables above and hashes in the registry.

```sql
-- phase2c.classic.guard
SELECT DATABASE() AS database_name, VERSION() AS database_version, @@session.tx_read_only AS session_transaction_read_only, @@session.tx_isolation AS transaction_isolation, (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'uuhms' AND TABLE_TYPE = 'BASE TABLE') AS base_table_count, (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'uuhms') AS column_count
```

```sql
-- phase2c.patients.columns
SELECT COLUMN_NAME, ORDINAL_POSITION, COLUMN_DEFAULT, IS_NULLABLE, DATA_TYPE, COLUMN_TYPE, CHARACTER_MAXIMUM_LENGTH, NUMERIC_PRECISION, NUMERIC_SCALE, DATETIME_PRECISION, CHARACTER_SET_NAME, COLLATION_NAME, COLUMN_KEY, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'uuhms' AND TABLE_NAME = 'patients' ORDER BY ORDINAL_POSITION
```

```sql
-- phase2c.patients.indexes
SELECT INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME, COLLATION, CARDINALITY, SUB_PART, NULLABLE, INDEX_TYPE FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = 'uuhms' AND TABLE_NAME = 'patients' ORDER BY INDEX_NAME, SEQ_IN_INDEX
```

```sql
-- phase2c.patients.core_quality
SELECT COUNT(*) AS total_rows, COUNT(DISTINCT PAT_ID) AS distinct_pk_count, SUM(PAT_ID IS NULL) AS null_pk_count, SUM(PAT_ID <= 0) AS nonpositive_pk_count, MIN(PAT_ID) AS min_pk, MAX(PAT_ID) AS max_pk, SUM(COALESCE(TRIM(PatientName), '') = '') AS blank_name_count, SUM(BINARY PatientName <> BINARY TRIM(PatientName)) AS name_outer_whitespace_count, SUM(TRIM(PatientName) <> '' AND TRIM(PatientName) NOT REGEXP '[[:space:]]') AS single_token_name_count, MAX(CHAR_LENGTH(PatientName)) AS max_name_length, SUM(CHAR_LENGTH(PatientName) = 100) AS name_at_limit_count, SUM(PatientName REGEXP '[[:cntrl:]]') AS name_control_character_count, SUM(COALESCE(TRIM(OpdNo), '') = '') AS blank_opd_count, SUM(BINARY OpdNo <> BINARY TRIM(OpdNo)) AS opd_outer_whitespace_count, SUM(TRIM(OpdNo) <> '' AND TRIM(OpdNo) REGEXP '[[:space:]]') AS opd_contains_whitespace_count, SUM(OpdNo REGEXP '[[:cntrl:]]') AS opd_control_character_count, MAX(CHAR_LENGTH(OpdNo)) AS max_opd_length, SUM(CHAR_LENGTH(OpdNo) = 15) AS opd_at_limit_count, SUM(COALESCE(TRIM(Sex), '') = '') AS blank_gender_count, SUM(UPPER(TRIM(Sex)) IN ('MALE', 'FEMALE', 'M', 'F')) AS recognized_gender_count, SUM(TRIM(Sex) <> '' AND UPPER(TRIM(Sex)) NOT IN ('MALE', 'FEMALE', 'M', 'F')) AS malformed_gender_count, SUM(CAST(DOB AS CHAR) = '0000-00-00') AS zero_dob_count, MIN(CASE WHEN CAST(DOB AS CHAR) <> '0000-00-00' THEN DOB END) AS valid_dob_min, MAX(CASE WHEN CAST(DOB AS CHAR) <> '0000-00-00' THEN DOB END) AS valid_dob_max, SUM(DOB < '1900-01-01' AND CAST(DOB AS CHAR) <> '0000-00-00') AS pre_1900_dob_count, SUM(DOB > UTC_DATE()) AS future_dob_count, SUM(DOB > DATE(RegDate)) AS dob_after_registration_count, SUM(COALESCE(TRIM(PhoneNo), '') = '') AS blank_phone_count, SUM(TRIM(PhoneNo) REGEXP '^[0-9]{10}$') AS exactly_ten_digit_phone_count, SUM(TRIM(PhoneNo) REGEXP '^0[0-9]{9}$') AS conservative_phone_shape_count, SUM(TRIM(PhoneNo) <> '' AND TRIM(PhoneNo) NOT REGEXP '^0[0-9]{9}$') AS populated_nonconforming_phone_count, SUM(BINARY PhoneNo <> BINARY TRIM(PhoneNo)) AS phone_outer_whitespace_count, SUM(COALESCE(TRIM(OriginalName), '') = '') AS blank_original_name_count, SUM(COALESCE(TRIM(OriginalOpd), '') = '') AS blank_original_opd_count, MIN(RegDate) AS registration_min, MAX(RegDate) AS registration_max, MIN(EditDate) AS edit_min, MAX(EditDate) AS edit_max, MIN(LastVisit) AS last_visit_min, MAX(LastVisit) AS last_visit_max, SUM(EditDate = RegDate) AS edit_equals_registration_count, SUM(EditDate > RegDate) AS edit_after_registration_count, SUM(EditDate < RegDate) AS edit_before_registration_count FROM uuhms.patients
```

```sql
-- phase2c.patients.gender_values
SELECT COALESCE(NULLIF(UPPER(TRIM(Sex)), ''), '__BLANK__') AS normalized_value, COUNT(*) AS row_count FROM uuhms.patients GROUP BY normalized_value ORDER BY row_count DESC, normalized_value
```

```sql
-- phase2c.patients.bill_status_values
SELECT COALESCE(NULLIF(UPPER(TRIM(BillStatus)), ''), '__BLANK__') AS normalized_value, COUNT(*) AS row_count FROM uuhms.patients GROUP BY normalized_value ORDER BY row_count DESC, normalized_value
```

```sql
-- phase2c.patients.demographic_categories
SELECT 'Religion' AS source_column, COALESCE(NULLIF(UPPER(TRIM(Religion)), ''), '__BLANK__') AS normalized_value, COUNT(*) AS row_count FROM uuhms.patients GROUP BY normalized_value UNION ALL SELECT 'MaritalStatus' AS source_column, COALESCE(NULLIF(UPPER(TRIM(MaritalStatus)), ''), '__BLANK__') AS normalized_value, COUNT(*) AS row_count FROM uuhms.patients GROUP BY normalized_value ORDER BY source_column, row_count DESC, normalized_value
```

```sql
-- phase2c.patients.opd_partition
SELECT (SELECT COUNT(*) FROM uuhms.patients) AS total_rows, (SELECT COUNT(*) FROM uuhms.patients WHERE TRIM(OpdNo) = '') AS blank_rows, (SELECT COALESCE(SUM(group_rows), 0) FROM (SELECT COUNT(*) AS group_rows FROM uuhms.patients WHERE TRIM(OpdNo) <> '' GROUP BY UPPER(TRIM(OpdNo)) HAVING COUNT(*) = 1) unique_groups) AS unique_nonblank_rows, (SELECT COUNT(*) FROM (SELECT 1 FROM uuhms.patients WHERE TRIM(OpdNo) <> '' GROUP BY UPPER(TRIM(OpdNo)) HAVING COUNT(*) > 1) duplicate_groups) AS duplicate_group_count, (SELECT COALESCE(SUM(group_rows), 0) FROM (SELECT COUNT(*) AS group_rows FROM uuhms.patients WHERE TRIM(OpdNo) <> '' GROUP BY UPPER(TRIM(OpdNo)) HAVING COUNT(*) > 1) duplicate_groups) AS duplicate_row_count, (SELECT COUNT(*) FROM (SELECT 1 FROM uuhms.patients WHERE TRIM(OpdNo) <> '' GROUP BY UPPER(TRIM(OpdNo)) HAVING COUNT(DISTINCT BINARY OpdNo) > 1) normalization_collisions) AS normalization_collision_group_count
```

```sql
-- phase2c.patients.suspected_duplicates
SELECT 'normalized_name' AS profile, COUNT(*) AS duplicate_group_count, COALESCE(SUM(group_rows), 0) AS duplicate_row_count FROM (SELECT COUNT(*) AS group_rows FROM uuhms.patients WHERE TRIM(PatientName) <> '' GROUP BY UPPER(TRIM(PatientName)) HAVING COUNT(*) > 1) duplicate_groups UNION ALL SELECT 'name_dob_sex' AS profile, COUNT(*) AS duplicate_group_count, COALESCE(SUM(group_rows), 0) AS duplicate_row_count FROM (SELECT COUNT(*) AS group_rows FROM uuhms.patients WHERE TRIM(PatientName) <> '' GROUP BY UPPER(TRIM(PatientName)), DOB, UPPER(TRIM(Sex)) HAVING COUNT(*) > 1) duplicate_groups UNION ALL SELECT 'name_dob_sex_phone' AS profile, COUNT(*) AS duplicate_group_count, COALESCE(SUM(group_rows), 0) AS duplicate_row_count FROM (SELECT COUNT(*) AS group_rows FROM uuhms.patients WHERE TRIM(PatientName) <> '' GROUP BY UPPER(TRIM(PatientName)), DOB, UPPER(TRIM(Sex)), TRIM(PhoneNo) HAVING COUNT(*) > 1) duplicate_groups
```

```sql
-- phase2c.patients.relationships
SELECT 'attendance.PAT_ID' AS relationship_id, COUNT(*) AS child_rows, COALESCE(SUM(c.PAT_ID IS NULL), 0) AS null_count, COALESCE(SUM(c.PAT_ID = 0), 0) AS zero_count, COALESCE(SUM(c.PAT_ID IS NOT NULL AND c.PAT_ID <> 0 AND p.PAT_ID IS NOT NULL), 0) AS matched_nonzero_count, COALESCE(SUM(c.PAT_ID IS NOT NULL AND c.PAT_ID <> 0 AND p.PAT_ID IS NULL), 0) AS orphan_nonzero_count FROM uuhms.attendance c LEFT JOIN uuhms.patients p ON c.PAT_ID = p.PAT_ID UNION ALL SELECT 'beds.PAT_ID', COUNT(*), COALESCE(SUM(c.PAT_ID IS NULL), 0), COALESCE(SUM(c.PAT_ID = 0), 0), COALESCE(SUM(c.PAT_ID IS NOT NULL AND c.PAT_ID <> 0 AND p.PAT_ID IS NOT NULL), 0), COALESCE(SUM(c.PAT_ID IS NOT NULL AND c.PAT_ID <> 0 AND p.PAT_ID IS NULL), 0) FROM uuhms.beds c LEFT JOIN uuhms.patients p ON c.PAT_ID = p.PAT_ID UNION ALL SELECT 'insurance.PAT_ID', COUNT(*), COALESCE(SUM(c.PAT_ID IS NULL), 0), COALESCE(SUM(c.PAT_ID = 0), 0), COALESCE(SUM(c.PAT_ID IS NOT NULL AND c.PAT_ID <> 0 AND p.PAT_ID IS NOT NULL), 0), COALESCE(SUM(c.PAT_ID IS NOT NULL AND c.PAT_ID <> 0 AND p.PAT_ID IS NULL), 0) FROM uuhms.insurance c LEFT JOIN uuhms.patients p ON c.PAT_ID = p.PAT_ID UNION ALL SELECT 'mat_family.PAT_ID', COUNT(*), COALESCE(SUM(c.PAT_ID IS NULL), 0), COALESCE(SUM(c.PAT_ID = 0), 0), COALESCE(SUM(c.PAT_ID IS NOT NULL AND c.PAT_ID <> 0 AND p.PAT_ID IS NOT NULL), 0), COALESCE(SUM(c.PAT_ID IS NOT NULL AND c.PAT_ID <> 0 AND p.PAT_ID IS NULL), 0) FROM uuhms.mat_family c LEFT JOIN uuhms.patients p ON c.PAT_ID = p.PAT_ID UNION ALL SELECT 'mat_obstetrics.PAT_ID', COUNT(*), COALESCE(SUM(c.PAT_ID IS NULL), 0), COALESCE(SUM(c.PAT_ID = 0), 0), COALESCE(SUM(c.PAT_ID IS NOT NULL AND c.PAT_ID <> 0 AND p.PAT_ID IS NOT NULL), 0), COALESCE(SUM(c.PAT_ID IS NOT NULL AND c.PAT_ID <> 0 AND p.PAT_ID IS NULL), 0) FROM uuhms.mat_obstetrics c LEFT JOIN uuhms.patients p ON c.PAT_ID = p.PAT_ID
```

```sql
-- phase2c.patients.incremental
SELECT COUNT(*) AS total_rows, MIN(PAT_ID) AS min_pk, MAX(PAT_ID) AS max_pk, MIN(RegDate) AS reg_min, MAX(RegDate) AS reg_max, MIN(EditDate) AS edit_min, MAX(EditDate) AS edit_max, COUNT(DISTINCT EditDate) AS distinct_edit_timestamps, SUM(EditDate > RegDate) AS edit_after_reg_count, SUM(EditDate = RegDate) AS edit_equals_reg_count, SUM(EditDate < RegDate) AS edit_before_reg_count, SUM(EditDate > (SELECT MAX(RegDate) FROM uuhms.patients)) AS edit_after_latest_registration_count, (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = 'uuhms' AND TABLE_NAME = 'patients' AND COLUMN_NAME = 'EditDate') AS editdate_index_count, (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = 'uuhms' AND TABLE_NAME = 'patients' AND COLUMN_NAME = 'RegDate') AS regdate_index_count FROM uuhms.patients
```

```sql
-- phase2c.patients.refill_candidates
SELECT SUM(p.Refill = 0) AS zero_count, SUM(p.Refill > 0) AS positive_count, SUM(p.Refill > 0 AND EXISTS (SELECT 1 FROM uuhms.attendance a WHERE a.ATT_ID = p.Refill)) AS matches_attendance_att_id_count, SUM(p.Refill > 0 AND EXISTS (SELECT 1 FROM uuhms.patients q WHERE q.PAT_ID = p.Refill)) AS matches_patient_pat_id_count, SUM(p.Refill > 0 AND EXISTS (SELECT 1 FROM uuhms.insurance i WHERE i.INS_ID = p.Refill)) AS matches_insurance_ins_id_count, SUM(p.Refill > 0 AND EXISTS (SELECT 1 FROM uuhms.billing b WHERE b.BILL_ID = p.Refill)) AS matches_billing_bill_id_count FROM uuhms.patients p
```

## Gaps and stop conditions

### Confirmed blockers

- Classic connection privileges are too broad for migration execution despite the observed read-only discovery transaction.
- Patient master has no separate first/last names and 211 blank combined names.
- Target-required phone evidence is blank for 5,050 rows; 153 populated rows fail the conservative local shape.
- Gender is blank for 151 rows and malformed/out-of-domain for 15.
- DOB has 1 pre-1900 and 18 post-registration rows under the stated predicates.
- OPD has 141 blanks and 1,259 duplicate-affected rows; validity grammar and target collision evidence remain outstanding.
- No registration actor exists.
- Direct orphan relationships require traceable chain quarantine.

### Unresolved decisions/evidence

- Reliable remediation/splitting evidence for `PatientName` and target overlength limits.
- Approved OPD allowed-character/Unicode/case/whitespace semantics; whether the two normalization-collision groups should use exactly this comparison rule.
- Phone country-code, leading-zero and validity rules; no email/national-ID source exists.
- Whether DOB equal to registration date is acceptable or a placeholder signal.
- Exact semantics/destination of `Work`, `Company`, `Refill`, `LastVisit` and the 7 populated `History` rows.
- Field-specific decisions for zero `attendance.PAT_ID` and `beds.PAT_ID`.
- Target nullability/contract for registration actor and missing required fields.
- Existing-target patient, alias and patient-number collision evidence is outside Classic discovery and must not be inferred here.

## Handoff classification summary

**Confirmed:** exact 24-column patient master, PK/index quality, all aggregate counts above, complete direct `PAT_ID` relationship set, absent identity/actor columns, source date ranges, categorical values, encoding/length profiles, and update-detection limitations.

**Inferred:** `Work` is occupation-like, `Company`/`BillStatus` are payer context, `NOK*`/address/demographics belong to Phase 2D, and clinical/history fields belong to later clinical mapping. These are not approved mappings.

**Unresolved:** combined-name remediation, OPD semantic validity, phone normalization, several source-field semantics, target collision handling, target required-field representation, and field-specific sentinel approvals. No automatic merge, guessed match, invented identity fact, raw identifier exposure or source write is authorized.

## Evidence addendum: canonical phone and OPD partitions

### Addendum safety and schema guard

This addendum supersedes only the earlier provisional phone-shape and OPD comparison predicates; it does not change any source values or other findings. Run `phase-2c-classic-patient-addendum/1.0.0` executed from `2026-07-21T20:16:53+00:00` through `2026-07-21T20:16:55+00:00` against exact configured/live schema `uuhms`. The guard observed MariaDB `10.4.32-MariaDB`, session `tx_read_only=1`, `REPEATABLE-READ`, 55 base tables, 479 columns, and a successful non-PHI `REGEXP_REPLACE` capability probe.

The full live structural fingerprint was recomputed from the ordered tables, columns, indexes without cardinality, declared constraints, foreign-key columns and referential constraints using the Phase 1 canonical JSON algorithm. Expected and observed fingerprint both equal `150fcf4783fcb8bdc25f7e17fe0ece5050955f0c68e7dd03651ee8bd58498977`; mismatch would have stopped before patient aggregate queries. Transaction level was 1 before rollback, rollback executed, and level was 0 afterward.

### Target-validator-compatible patient phone partition

The exact installed target-validator-compatible local Ghana predicate is `TRIM(PhoneNo) REGEXP '^0[235][0-9]{8}$'`. Blank is `COALESCE(TRIM(PhoneNo),'')=''`; invalid nonblank is the exact complement among nonblank rows.

| Mutually exclusive outcome | Rows |
|---|---:|
| Total Classic patients | 16,950 |
| Blank after trim | 5,050 |
| Valid nonblank | 11,737 |
| Invalid nonblank | 163 |
| `total - blank - valid - invalid` | 0 |

The earlier 11,747/153 figures used the broader diagnostic shape `^0[0-9]{9}$`. The canonical target-compatible pattern rejects 10 additional populated rows because the second digit is outside `2`, `3` or `5`. No phone value was emitted, and this classification must not be used for identity matching.

### Phase 2C canonical OPD alias partition

The canonical comparison expression is exactly `UPPER(REGEXP_REPLACE(TRIM(OpdNo), '[[:space:]]+', ''))`: trim, remove every POSIX-whitespace run, then uppercase. MariaDB capability was confirmed; no fallback or guessed whitespace implementation was used.

| Mutually exclusive outcome | Rows/groups |
|---|---:|
| Total Classic patients | 16,950 rows |
| Blank canonical value | 141 rows |
| Unique nonblank canonical value | 15,550 rows |
| Duplicate canonical value withheld | 1,259 rows |
| Duplicate canonical groups | 275 groups |
| `total - blank - unique - duplicate` | 0 |

Binary raw evidence, still aggregate-only: 15,827 raw distinct nonblank values become 15,825 distinct canonical values, a reduction of 2. Nine raw distinct nonblank values differ from their canonical form; 2 canonical groups contain multiple binary raw values. These counts show canonicalization effects only. They do not authorize changing display/provenance values, selecting alias ownership, or assigning any duplicated alias.

### Addendum reproducibility registry

Every query has version 1 and empty-binding canonical SHA-256 `4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945`.

| Query ID | Started / finished UTC | SQL SHA-256 | Sanitized result SHA-256 |
|---|---|---|---|
| `phase2c.addendum.classic_guard` | 20:16:53 / 20:16:53 | `fb5f3f913d714eb38c579a1b7b5a4d1ebe65b8e6d2de3a55c14c732379d7af1a` | `446d5b73ac9ec02ee5b305c43d0777b4368bfdce29817ba032238b185fb45feb` |
| `phase2c.addendum.schema_tables` | 20:16:53 / 20:16:53 | `a48f103b2e7bf474930a041e29d30268842de146c20a9286d3434a239edbfd63` | `cf5a969532e3f3a55fbd077761ca372a8a3cad0bbf544aa1f17e58fc780f5f74` |
| `phase2c.addendum.schema_columns` | 20:16:53 / 20:16:53 | `b078cf4ace6aca7ebca75ce14755105b6998a14d9b675f0558a5e479b8cc4c29` | `b6050465c2041c190eedf13e239cc53686a902504b0f9f9700b5ac74e3246f39` |
| `phase2c.addendum.schema_indexes` | 20:16:53 / 20:16:53 | `74155186bcb67ce0d46f89e1b64d605fc701e62aeb4248a4c255bb4558737db0` | `459105e15123190b6e7542e13829f5378c23384e61ebb1c67218b051d851666b` |
| `phase2c.addendum.schema_declared_constraints` | 20:16:53 / 20:16:54 | `ecb62821a41215b90faec892122bc46715df85c4d89813ecc7862ae272571760` | `4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945` |
| `phase2c.addendum.schema_foreign_key_columns` | 20:16:54 / 20:16:54 | `ecd66d973ba9e66877d7da3316974c7a82c40c09b2873cf061e4c189f63365ab` | `4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945` |
| `phase2c.addendum.schema_referential_constraints` | 20:16:54 / 20:16:54 | `b9f6ee17fa6380a77ba0fbf8e238fb03a4c86f47838acfcc8ce79e7a3622fe8c` | `4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945` |
| `phase2c.addendum.patient_phone_partition` | 20:16:54 / 20:16:54 | `5ea1c46a14e9e0f3e42c1e78a1d9fe4185985993eb011ef6354aa1a6fab2564f` | `346c114a6ec72f0a16d80a2c5e310f4e923230c663d3c11ac5e3f7da9936f173` |
| `phase2c.addendum.patient_opd_alias_partition` | 20:16:54 / 20:16:55 | `acafa6f5d3c8b7ff856e754c8edfebee8dfcf9cd5dab0c5a5698c279ab669839` | `afb219808b754db4af7b899413a5bc4e318587b86aae0c47eab499100af8b256` |

Exact normalized SQL:

```sql
-- phase2c.addendum.classic_guard
SELECT DATABASE() AS database_name, VERSION() AS database_version, @@session.tx_read_only AS session_transaction_read_only, @@session.tx_isolation AS transaction_isolation, (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'uuhms' AND TABLE_TYPE = 'BASE TABLE') AS base_table_count, (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'uuhms') AS column_count, REGEXP_REPLACE('A B', '[[:space:]]+', '') AS regexp_replace_probe
```

```sql
-- phase2c.addendum.schema_tables
SELECT TABLE_NAME, TABLE_TYPE, ENGINE, TABLE_COLLATION, CREATE_OPTIONS FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'uuhms' ORDER BY TABLE_NAME
```

```sql
-- phase2c.addendum.schema_columns
SELECT TABLE_NAME, COLUMN_NAME, ORDINAL_POSITION, COLUMN_DEFAULT, IS_NULLABLE, DATA_TYPE, COLUMN_TYPE, CHARACTER_MAXIMUM_LENGTH, NUMERIC_PRECISION, NUMERIC_SCALE, DATETIME_PRECISION, CHARACTER_SET_NAME, COLLATION_NAME, COLUMN_KEY, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'uuhms' ORDER BY TABLE_NAME, ORDINAL_POSITION
```

```sql
-- phase2c.addendum.schema_indexes
SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME, COLLATION, CARDINALITY, SUB_PART, NULLABLE, INDEX_TYPE FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = 'uuhms' ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX
```

```sql
-- phase2c.addendum.schema_declared_constraints
SELECT TABLE_NAME, CONSTRAINT_NAME, CONSTRAINT_TYPE FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = 'uuhms' ORDER BY TABLE_NAME, CONSTRAINT_NAME
```

```sql
-- phase2c.addendum.schema_foreign_key_columns
SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_SCHEMA, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = 'uuhms' AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION
```

```sql
-- phase2c.addendum.schema_referential_constraints
SELECT CONSTRAINT_NAME, TABLE_NAME, REFERENCED_TABLE_NAME, UPDATE_RULE, DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = 'uuhms' ORDER BY TABLE_NAME, CONSTRAINT_NAME
```

```sql
-- phase2c.addendum.patient_phone_partition
SELECT COUNT(*) AS total_rows, SUM(COALESCE(TRIM(PhoneNo), '') = '') AS blank_after_trim_rows, SUM(COALESCE(TRIM(PhoneNo), '') <> '' AND TRIM(PhoneNo) REGEXP '^0[235][0-9]{8}$') AS valid_nonblank_rows, SUM(COALESCE(TRIM(PhoneNo), '') <> '' AND TRIM(PhoneNo) NOT REGEXP '^0[235][0-9]{8}$') AS invalid_nonblank_rows, COUNT(*) - SUM(COALESCE(TRIM(PhoneNo), '') = '') - SUM(COALESCE(TRIM(PhoneNo), '') <> '' AND TRIM(PhoneNo) REGEXP '^0[235][0-9]{8}$') - SUM(COALESCE(TRIM(PhoneNo), '') <> '' AND TRIM(PhoneNo) NOT REGEXP '^0[235][0-9]{8}$') AS difference FROM uuhms.patients
```

```sql
-- phase2c.addendum.patient_opd_alias_partition
SELECT (SELECT COUNT(*) FROM uuhms.patients) AS total_rows, (SELECT COUNT(*) FROM uuhms.patients WHERE UPPER(REGEXP_REPLACE(TRIM(OpdNo), '[[:space:]]+', '')) = '') AS blank_rows, (SELECT COALESCE(SUM(group_rows), 0) FROM (SELECT COUNT(*) AS group_rows FROM uuhms.patients WHERE UPPER(REGEXP_REPLACE(TRIM(OpdNo), '[[:space:]]+', '')) <> '' GROUP BY UPPER(REGEXP_REPLACE(TRIM(OpdNo), '[[:space:]]+', '')) HAVING COUNT(*) = 1) unique_groups) AS unique_nonblank_rows, (SELECT COALESCE(SUM(group_rows), 0) FROM (SELECT COUNT(*) AS group_rows FROM uuhms.patients WHERE UPPER(REGEXP_REPLACE(TRIM(OpdNo), '[[:space:]]+', '')) <> '' GROUP BY UPPER(REGEXP_REPLACE(TRIM(OpdNo), '[[:space:]]+', '')) HAVING COUNT(*) > 1) duplicate_groups) AS duplicate_withheld_rows, (SELECT COUNT(*) FROM (SELECT 1 FROM uuhms.patients WHERE UPPER(REGEXP_REPLACE(TRIM(OpdNo), '[[:space:]]+', '')) <> '' GROUP BY UPPER(REGEXP_REPLACE(TRIM(OpdNo), '[[:space:]]+', '')) HAVING COUNT(*) > 1) duplicate_groups) AS duplicate_group_count, (SELECT COUNT(*) FROM (SELECT BINARY OpdNo AS raw_value FROM uuhms.patients WHERE TRIM(OpdNo) <> '' GROUP BY BINARY OpdNo) raw_values) AS raw_distinct_nonblank_count, (SELECT COUNT(*) FROM (SELECT 1 FROM uuhms.patients WHERE UPPER(REGEXP_REPLACE(TRIM(OpdNo), '[[:space:]]+', '')) <> '' GROUP BY UPPER(REGEXP_REPLACE(TRIM(OpdNo), '[[:space:]]+', ''))) canonical_values) AS canonical_distinct_nonblank_count, (SELECT COUNT(*) FROM (SELECT BINARY OpdNo AS raw_value FROM uuhms.patients WHERE TRIM(OpdNo) <> '' AND BINARY OpdNo <> BINARY UPPER(REGEXP_REPLACE(TRIM(OpdNo), '[[:space:]]+', '')) GROUP BY BINARY OpdNo) changed_raw_values) AS raw_distinct_values_changed_by_canonicalizer, (SELECT COUNT(*) FROM (SELECT 1 FROM uuhms.patients WHERE TRIM(OpdNo) <> '' GROUP BY UPPER(REGEXP_REPLACE(TRIM(OpdNo), '[[:space:]]+', '')) HAVING COUNT(DISTINCT BINARY OpdNo) > 1) collision_groups) AS canonical_groups_with_multiple_raw_values, (SELECT COUNT(*) FROM (SELECT BINARY OpdNo AS raw_value FROM uuhms.patients WHERE TRIM(OpdNo) <> '' GROUP BY BINARY OpdNo) raw_values) - (SELECT COUNT(*) FROM (SELECT 1 FROM uuhms.patients WHERE UPPER(REGEXP_REPLACE(TRIM(OpdNo), '[[:space:]]+', '')) <> '' GROUP BY UPPER(REGEXP_REPLACE(TRIM(OpdNo), '[[:space:]]+', ''))) canonical_values) AS raw_to_canonical_distinct_reduction, (SELECT COUNT(*) FROM uuhms.patients) - (SELECT COUNT(*) FROM uuhms.patients WHERE UPPER(REGEXP_REPLACE(TRIM(OpdNo), '[[:space:]]+', '')) = '') - (SELECT COALESCE(SUM(group_rows), 0) FROM (SELECT COUNT(*) AS group_rows FROM uuhms.patients WHERE UPPER(REGEXP_REPLACE(TRIM(OpdNo), '[[:space:]]+', '')) <> '' GROUP BY UPPER(REGEXP_REPLACE(TRIM(OpdNo), '[[:space:]]+', '')) HAVING COUNT(*) = 1) unique_groups) - (SELECT COALESCE(SUM(group_rows), 0) FROM (SELECT COUNT(*) AS group_rows FROM uuhms.patients WHERE UPPER(REGEXP_REPLACE(TRIM(OpdNo), '[[:space:]]+', '')) <> '' GROUP BY UPPER(REGEXP_REPLACE(TRIM(OpdNo), '[[:space:]]+', '')) HAVING COUNT(*) > 1) duplicate_groups) AS difference
```
