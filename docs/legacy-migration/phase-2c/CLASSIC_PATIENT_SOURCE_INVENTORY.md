# Classic patient source inventory

## Purpose, authority, and evidence boundary

This is the Phase 2C human specification of the Classic patient master. It is documentation only: it authorizes no importer, schema change, source write, target write, or production test.

The following labels are used throughout:

- **Confirmed evidence** means installed schema metadata or sanitized aggregate evidence.
- **Inference** means a likely source meaning that is not itself an approved mapping.
- **Policy** means an accepted requirement from D-201, D-211, D-213, Q-001, Q-002, Q-006, Q-007 or Q-008.
- **Technical specification** means the deterministic Phase 2C behavior required of a future implementation.

The authoritative structural evidence is `docs/legacy-migration/evidence/CLASSIC_SCHEMA_MANIFEST.json`, captured at `2026-07-21T01:57:29+00:00` for exact database `uuhms`, MariaDB `10.4.32-MariaDB`, with source fingerprint `150fcf4783fcb8bdc25f7e17fe0ece5050955f0c68e7dd03651ee8bd58498977`. Aggregate evidence comes from `CLASSIC_AGGREGATE_RESULTS.json` and the query definitions in `CLASSIC_QUERY_MANIFEST.json`. The read-only discovery refresh confirmed the same patient-table shape and was completed under a `REPEATABLE READ`, read-only transaction which was rolled back.

**Policy:** Classic is strictly read-only. D-101 remains an execution blocker because the configured account has broader schema privileges than the required dedicated SELECT/metadata-only account.

## Confirmed patient master

`uuhms.patients` is the only installed table named `patients`. It is an InnoDB base table with `latin1_swedish_ci`, exactly **16,950 rows**, **24 columns**, and one index: unique primary BTREE `PRIMARY(PAT_ID)`. Classic declares no foreign keys and this table has no secondary or business-key index.

| # | Column | Installed definition | Null/default/extra | Confirmed source role |
|---:|---|---|---|---|
| 1 | `PAT_ID` | `int(11)` | NOT NULL; auto-increment; primary key | Stable source row/link key only. |
| 2 | `PatientName` | `varchar(100)` | NOT NULL; latin1 | The only installed patient-name field; combined name. |
| 3 | `OpdNo` | `varchar(15)` | NOT NULL; latin1 | Only installed Classic OPD/patient-number candidate. |
| 4 | `Sex` | `varchar(10)` | NOT NULL; latin1 | Source sex/gender category. |
| 5 | `DOB` | `date` | NOT NULL | Source date of birth. |
| 6 | `PhoneNo` | `varchar(10)` | NOT NULL; latin1 | Primary source phone field. |
| 7 | `Work` | `varchar(15)` | NOT NULL; latin1 | Occupation-like text; semantics not proven. |
| 8 | `Company` | `varchar(25)` | NOT NULL; latin1 | Payer/company-like text; semantics not proven. |
| 9 | `Address` | `varchar(25)` | NOT NULL; latin1 | Address/contact text. |
| 10 | `NOK` | `varchar(100)` | NOT NULL; latin1 | Next-of-kin name. |
| 11 | `NOKPhoneNo` | `varchar(10)` | NOT NULL; latin1 | Next-of-kin phone. |
| 12 | `NOKRel` | `varchar(15)` | NOT NULL; latin1 | Next-of-kin relationship/free text. |
| 13 | `Religion` | `varchar(15)` | NOT NULL; latin1 | Patient demographic category. |
| 14 | `MaritalStatus` | `varchar(15)` | NOT NULL; latin1 | Patient demographic category. |
| 15 | `BillStatus` | `varchar(25)` | NOT NULL; latin1 | Default payer/billing category, not patient active status. |
| 16 | `Refill` | `int(11)` | NOT NULL | Ambiguous operational/pointer field. |
| 17 | `Allergies` | `varchar(500)` | NOT NULL; latin1 | Clinical-history text. |
| 18 | `Medication` | `varchar(500)` | NOT NULL; latin1 | Clinical-history text. |
| 19 | `History` | `varchar(500)` | NOT NULL; latin1 | Clinical free text. |
| 20 | `LastVisit` | `datetime` | NOT NULL | Business/derived visit timestamp; not creation evidence. |
| 21 | `EditDate` | `timestamp` | NOT NULL; current timestamp; on-update current timestamp | Mutable row-change candidate with one-second precision. |
| 22 | `RegDate` | `timestamp` | NOT NULL; current timestamp | Registration timestamp candidate. |
| 23 | `OriginalName` | `varchar(250)` | NOT NULL; latin1 | Previous/original-name provenance slot. |
| 24 | `OriginalOpd` | `varchar(250)` | NOT NULL; latin1 | Previous/original-OPD provenance slot. |

Confirmed absent are separate first, last and other-name columns; email; Ghana Card or other national identifier; blood group; patient active/status field; registration actor/creator; and a distinct general notes field. No absent value may be inferred from another field.

## Primary-key and identifier evidence

**Confirmed evidence:** `PAT_ID` has 16,950 distinct values, zero nulls, zero nonpositive values and zero duplicate groups. Its range is 1 through 18,724, leaving 1,774 unused positions; the observed next auto-increment value was 18,725. Gaps do not prove deletion.

**Policy:** `PAT_ID` is protected source linkage only. It must never become target `patients.id`, a renewed patient number, or `patient_aliases.source_patient_id` (the latter is a target-patient FK). Each unique valid `OpdNo` may become a typed legacy alias, never the renewed patient number. Duplicate, blank, invalid, or conflicting OPDs are withheld without making the patient entity ineligible on that fact alone.

Future missing and duplicate source-key drift use `LEGACY-PATIENT-KEY-001` and `LEGACY-PATIENT-KEY-002`. They are extraction stops, not keys to repair or renumber.

## Identity quality aggregates

### Combined name

**Confirmed evidence:** 211 rows are blank after trim and 16,739 are populated. There are 507 rows with binary outer whitespace, 134 populated single-token names, one control-character row, one value at the 100-character source limit, and a maximum length of 100. Normalized `UPPER(TRIM(PatientName))` produces 1,001 duplicate groups covering 2,278 rows. Stronger but still nondeterministic review signals are 230 groups/466 rows for normalized name plus DOB plus normalized sex, and 190 groups/384 rows when trimmed phone is also added.

**Inference:** no schema evidence establishes how the combined name should be divided.

**Policy and technical specification:** names and weak combinations are review evidence only. Never merge or match on them. Decode latin1, preserve the original only in protected provenance, normalize comparison text to Unicode NFC, trim and collapse comparison whitespace, and preserve meaningful punctuation. Do not automatically split, duplicate a component, invent a component, or silently truncate. Because the target requires separate first and last names, the source-only baseline is not automatically representable and requires protected remediation.

Exception alignment is `LEGACY-PATIENT-NAME-008` for the blank combined source field, `-009`/`-010` for missing required target first/last components, `-011` for ambiguous combined-name separation and `-012` for overlength/control-component failure.

### OPD alias evidence

The captured source-only comparison `UPPER(TRIM(OpdNo))` reconciles exactly:

| Outcome | Rows/groups |
|---|---:|
| Classic patient rows | 16,950 |
| Blank after trim | 141 rows |
| Unique nonblank canonical values | 15,550 rows |
| Duplicate nonblank canonical values | 1,259 rows in 275 groups |
| Difference | 0 |

Additional confirmed shape evidence: one outer-whitespace row, five values containing whitespace, zero control-character rows, zero digits-only rows, 696 alphanumeric-only rows, 16,113 rows containing punctuation, one leading-zero row, maximum length 15, and three rows at that limit. Two duplicate groups contain multiple binary forms which collapse under trim/case comparison.

The Phase 2C source canonical expression trims, removes every POSIX-whitespace run and uppercases. It reconciles `16,950 = 141 + 15,550 + 1,259` with difference zero. The 15,550 rows remain candidates, not confirmed valid aliases: the semantic validity grammar, versioned Unicode/NFC runtime parity and target-collision aggregate are Phase 3 prerequisites. Punctuation and leading zeros remain significant; no numeric coercion, punctuation stripping, suffixing, decoration, “best owner,” or automatic release is allowed.

Stable alias exceptions are `LEGACY-PATIENT-ALIAS-003` blank, `-004` duplicate, `-005` invalid, `-006` target-alias collision and `-007` target patient-number collision.

### Sex/gender

Normalization for the observed categorical evidence is `UPPER(TRIM(Sex))`.

| Normalized source class | Count | Technical state |
|---|---:|---|
| `FEMALE` | 9,080 | Maps to target `female`. |
| `MALE` | 7,282 | Maps to target `male`. |
| `F` | 224 | Maps to target `female`. |
| `M` | 198 | Maps to target `male`. |
| blank | 151 | Missing; no default. |
| `EMALE`, `FEMLE`, `MAL` | 1 each | Malformed; remediation or quarantine. |
| `1`, `2`, `10`, `22`, `23`, `24`, `26`, `27`, `32`, `38`, `42`, `74` | 1 each | Out of domain; remediation or quarantine. |

The partition is 16,784 recognized, 151 missing and 15 malformed/out of domain, totaling 16,950. Unsupported values must never be persisted into the target enum cast.

### Date of birth

**Confirmed evidence:** zero zero-dates and zero future dates were observed; one row is pre-1900; 18 DOBs are after `DATE(RegDate)`; 646 equal `DATE(RegDate)`. The conservative technical predicate `DOB >= 1900-01-01`, `DOB <= UTC_DATE()` and `DOB <= DATE(RegDate)` passes 16,931 rows. Equality with registration is a review signal but is not automatically invalid. Patient-derived extrema are deliberately omitted from documentation.

Partial dates cannot be represented by the installed source type. No invalid DOB may be changed to current date, registration date, January 1, or an inferred/default age.

### Phone

**Confirmed evidence:** 5,050 rows are blank and 11,900 are populated. Under the installed target-compatible local Ghana predicate `^0[235][0-9]{8}$`, 11,737 are valid nonblank and 163 are invalid nonblank; `16,950 = 5,050 + 11,737 + 163` with difference zero. Earlier shape evidence found 11,751 ten-digit values, 11,747 ten digits beginning with `0`, 26 populated nondigit values, 139 populated values whose trimmed length is not ten and 15 with outer whitespace. These defect signals overlap. There are 935 exact trimmed duplicate groups covering 2,075 rows. Maximum length is ten and 11,771 stored values are at that limit.

The exact source-local validity partition is now captured. Any later international-prefix normalization remains evidence-dependent. Duplicate phones are allowed by the target and are review evidence only. Phone alone must never link or merge a patient.

### Source-only required-field intersection

Using provisional checks for nonblank combined name, recognized gender, conservative DOB and preliminary phone shape, 11,732 rows pass all four. Independent counts are name present 16,739; recognized gender 16,784; conservative DOB 16,931; preliminary phone shape 11,747. This is not an eligible-patient count: separate first/last names remain unrepresentable without remediation, and target collision checks have not run.

## Other patient-column quality

All 18 character columns use source latin1. The discovery ASCII round-trip aggregate found zero non-ASCII-byte rows in each column; this does not remove the requirement for explicit, versioned decoding.

| Column | Blank | Populated | Maximum / declared limit | At limit | Outer whitespace | Control-character rows |
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

`NOKRel` has 435 distinct nonblank trimmed values, too many to assume a clean relationship code. `sett_ocuupation` has zero rows, so `Work` cannot be mapped to that catalogue; any changed nonzero baseline is a D-209 stop. Allergies and Medication are empty; History has seven populated rows, all containing control characters. Clinical content was not emitted.

Safe categorical aggregates are: BillStatus—NHIS 10,587; PRIVATE INSURANCE 3,416; CASH AND CARRY 2,518; PRIVATE INSURANCE + NHIS 384; blank 40; PRIVATE INSURANCE + 5. Religion—CHRISTIANITY 12,438; MUSLIM 2,749; OTHER 112; blank 1,651. MaritalStatus—SINGLE 8,240; MARRIED 6,976; WIDOW(ER) 353; blank 1,381. These are evidence, not target mappings; the former is Phase 2E and the latter two are Phase 2D.

`Refill` ranges from 0 to 54,301 with 203 distinct values: 16,748 rows are zero and 202 are positive. Of the positive rows, 182 happen to match an `attendance.ATT_ID`, 78 a `patients.PAT_ID`, 144 an `insurance.INS_ID`, and 159 a `billing.BILL_ID`. Identifier-domain overlap means these counts do not prove any relationship. Its primary disposition remains **No approved destination**.

## Registration, update, and actor evidence

`RegDate`, `EditDate` and `LastVisit` each have zero null/zero values. Patient-derived extrema are retained only in protected evidence and are not documented. `EditDate` is later than RegDate for 9,678 rows, equal for 7,271 and earlier for one.

There are 16,825 distinct edit timestamps across 16,950 rows, a collision excess of 125 rows. Twenty-six adjacent-PK pairs regress in registration time, so PK order is not chronology.

No registration actor column or declared patient-to-user relationship exists. Phase 2B `TARGET-ACTOR-054` therefore controls: target `patients.registered_by` is null plus protected absence provenance. Current user, importer, first user, administrator, supervisor and `Legacy Actor Unknown` are prohibited. Missing actor evidence does not block the patient because the target FK is nullable.

`LastVisit` is not a reliable maximum-attendance derivation: among 16,366 patients with matched attendance, 23 equal the maximum matched attendance timestamp, 12,158 are earlier and 4,185 later; 584 patients have no matched attendance.

## Direct patient relationships and chain impact

There are exactly five child `PAT_ID` columns outside `patients`; all are inferred relationships because Classic declares no FKs.

| Relationship | Child rows | Null | Candidate zero | Matched nonzero | Orphan nonzero | Difference |
|---|---:|---:|---:|---:|---:|---:|
| `attendance.PAT_ID -> patients.PAT_ID` | 51,927 | 0 | 155 | 48,589 | 3,183 | 0 |
| `beds.PAT_ID -> patients.PAT_ID` | 18 | 0 | 15 | 3 | 0 | 0 |
| `insurance.PAT_ID -> patients.PAT_ID` | 31,307 | 0 | 0 | 30,991 | 316 | 0 |
| `mat_family.PAT_ID -> patients.PAT_ID` | 0 | 0 | 0 | 0 | 0 | 0 |
| `mat_obstetrics.PAT_ID -> patients.PAT_ID` | 0 | 0 | 0 | 0 | 0 | 0 |

Only `beds.PAT_ID = 0` already has an approved Phase 2A relationship-not-evidenced sentinel. The other zero/null states need field-specific handling; no global zero sentinel exists. A quarantined patient blocks its entire matched chain. An orphan attendance blocks that attendance and every dependent clinical, billing, claim, admission and related record. No child may be reassigned or partially released.

## Extraction and privacy requirements

`PAT_ID ASC` is the stable ordering/resume coordinate. A PK watermark detects later inserts only. `EditDate` can support an overlap scan but cannot prove all updates or any deletion. The future extraction contract must use an ordered full snapshot across all 24 columns, explicit latin1 decoding, a deterministic protected row HMAC, snapshot identity, and freeze-time comparison.

Raw names, OPDs, phones, addresses, identifiers, clinical text, emergency-contact values, crosswalk values and row-level diagnostics must not enter source control, ordinary logs or reconciliation exports. Where row correlation is unavoidable, use domain-separated HMAC-SHA-256 with purpose, key version and canonicalization version; never plain SHA-256 for low-entropy identity values. A source schema/fingerprint change, database other than exact `uuhms`, non-read-only session, missing HMAC version/key, or nonzero dormant baseline is a fail-closed stop.

Invalid decoding/control content, source drift, extraction failure and privacy/provenance failure use `LEGACY-PATIENT-TEXT-031`, `LEGACY-PATIENT-DRIFT-033`, `LEGACY-PATIENT-EXTRACTION-034` and `LEGACY-PATIENT-PRIVACY-036` respectively.
