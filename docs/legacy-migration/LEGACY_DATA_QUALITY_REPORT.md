# Legacy data quality report

## Summary

The Classic source is usable only through an exception-aware migration. Confirmed issues affect identity, logical referential integrity, dates, clinical completeness, money, stock, and incremental extraction. Counts are sanitized aggregates.

## Phase 1B evidence refinements

- All 46 temporal columns now have machine-rerunnable null, zero, valid-min/max, pre-1900 and future aggregates. Selected cross-field checks remain separately reported.
- 18 patient DOB values occur after their registration timestamp; 144/146 stock requests have `SuppDate < ReqDate`.
- Nonblank normalized OPD duplicates are 275 groups/1,259 rows. Phase 1A's 276/1,400 included the 141-row blank group.
- Nonblank normalized insurance-member duplicates are 1,019 groups/2,555 rows, refining the earlier 1,018-group observation.
- Decimal evaluation produces 906 billing equation mismatches over 0.01; Phase 1A's binary-double evaluation produced 907. Two rows are exactly 0.01 under decimal arithmetic.
- Machine aggregates now cover critical blank fields, pharmacy/store/batch cross-snapshot differences, query/result hashes and a separate structural-schema versus evidence-snapshot fingerprint.
- The schema database default is `utf8mb4_general_ci`, while all 55 installed Classic tables and their character columns are `latin1_swedish_ci`; cross-system text normalization must be explicit and tested.

## Patient identity and required data

- 16,950 patients.
- 211 empty names; 141 empty OPD numbers; 151 empty sex; 5,050 empty phones.
- 275 duplicate nonblank normalized OPD groups cover 1,259 rows. The earlier Phase 1A 276/1,400 figure included the 141-row blank-value group.
- Target patient aliases are globally unique per normalized type/value, so the duplicate OPD groups cannot be attached to multiple distinct patients without an approved alternative representation.
- 1,001 duplicate normalized-name groups; names alone are forbidden for merging.
- 35 populated phones contain non-digits; 139 are not ten characters.
- One DOB is in 1597/pre-1900.
- `OriginalName` and `OriginalOpd` are empty for every patient.

**Risk:** renewed patients require first/last name, date of birth, gender, phone, and unique patient number. Approved exception and identity rules are blocking.

## Insurance

- 31,307 rows; 316 inferred patient orphans.
- 17,029 empty member numbers and 1,019 duplicate nonblank normalized member-number groups covering 2,555 rows.
- 36 duplicate `(PAT_ID, InsType, Company, Scheme)` groups cover 88 rows before provider natural-key consolidation; target permits only one insurance row per patient/provider.
- 17,091 zero issue dates; 15,717 zero expiry dates.
- 66 expiry-before-issue rows.
- 19 future issue dates (maximum year 5051) and 54 future expiry dates (maximum year 2810).

## Encounter and admission chronology

- 51,927 attendance rows; 3,338 inferred patient orphans and 36,741 inferred user orphans.
- 1,003 discharge-before-admission rows.
- 50,531 share the same admission/discharge date; 6,602 of those are inpatient.
- Admission date equals attendance date on 50,607 rows and discharge date on 51,251 rows, strongly suggesting defaults/placeholders in some cases.
- Five appointments are future relative to discovery; one reaches year 2203.
- Claims contain 20 discharge-before-admission rows and one future discharge in 2030.

## Clinical completeness and representation

- `consult_complaints.Description`: 64,757 empty of 65,244.
- `consult_diagnosis.Description`: 69,664 empty; `MdfId` empty on all rows.
- `consult_history`: 4,477 empty plain and 2,132 empty RTF histories.
- `consult_prescriptions`: 278 null/zero medicines; 1,428 null/non-positive quantities; most strength/description values empty.
- `consult_procedures`: status, billing status, and result empty for all 121.
- `consult_scan_lab`: 11,892 empty results; interpretation empty for all 72,680.
- `consult_services.Result`: empty/null for all 60,260.
- `serv_results.Result`: 126,066 empty; `ResultOutcome` empty for all 460,758.
- Paired plain/RTF and catalogue/free-text fields may be alternatives; empty values are not automatically invalid.

Vitals are strings. Most populated values are numeric-like, but non-numeric values exist in weight, temperature, BP, BPT, FBS, HR, Pox, SpO2, and BMI. `Routine` has 16,996 distinct values and appears to contain time-like/free text, not a stable enum. Preserve raw values alongside parse failures during mapping.

## Logical referential integrity

No foreign keys exist. Important inferred orphan counts include:

- attendance→patient 3,338; billing→attendance 2,874; claims→attendance 1,789.
- insurance→patient 316.
- prescriptions→medicine 49,481 and prescriptions→billing 94,282.
- result→attendance 5,858; result→criterion 54,793; result→service 82,522.
- pharmacy/store snapshots have more than four thousand unmatched medicine references each.

See `LEGACY_RELATIONSHIPS.md` for the readable summary and `evidence/CLASSIC_RELATIONSHIP_MANIFEST.json` for the complete reproducible edge register. All-zero references remain unresolved sentinels, not target FKs.

## Duplicates and master data

- `medicine`: 2,669 empty names, 3,853 empty codes, two duplicate non-empty code groups, 3,874 empty categories.
- Services: 14 blank departments and seven duplicate normalized non-empty names.
- Users: two duplicate normalized non-empty usernames.
- Diagnosis catalogue: 1,687 empty ICD-10 values and 853 duplicate non-empty code groups (possibly legitimate granularity).
- Both keyless service-option tables contain duplicate identifiers/natural-key candidates.

## Financial reconciliation

### Billing

- 111,731 rows.
- Bill 6,588,851.33; discount 5,055.00; paid 5,764,343.70; balance 819,795.39.
- 906 rows differ from candidate decimal equation `Bill - Discount - Paid = Balance` by more than 0.01; aggregate difference -342.76. The Phase 1A binary-double query reported 907.
- One row has a negative component; 79 `OWING` rows have non-positive balances.

### Claims

- Claim total 4,116,482.48; service tariff 1,905,117.96; investigation tariff 0; pharmacy tariff 2,210,257.96.
- 43 rows differ from the candidate component equation by more than 0.01; aggregate difference 1,106.56.

The equations are inferred, not source constraints. Finance must approve formulas before failures are classified. Target calculations must use decimal—not binary floating-point—arithmetic.

## Stock and procurement

- `med_pharm`: 323 rows with negative quantity/price/threshold components; ten duplicate medicine groups.
- `med_store`: 111 negative-component rows; two duplicate medicine groups.
- 252 of 253 batches are past expiry but marked not expired.
- 52 request rows contain a negative quantity field.

Negative stock might be a deliberate deficit, but must be an explicit approved exception. Classic does not evidence a complete movement ledger; only source-evidenced facts and/or a clearly labelled approved opening position may be used, with a bounded cutover that prevents double counting.

## Incremental extraction risks

- 23 tables have no timestamp/date.
- Nine tables use automatically overwritten timestamps.
- `serv_results` has 460,758 rows and no event date.
- Relationship columns on large tables are largely unindexed, so extraction queries must be bounded and performance-tested read-only.

## Required disposition classes

Later mapping must classify each exception as: source remediation; approved deterministic transform; quarantined/retryable; retained raw-but-unmapped; or explicitly excluded with owner approval. “Skip” and convenient defaults are not dispositions.
