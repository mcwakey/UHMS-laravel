# Patient column mapping

## Contract and classification rule

This document assigns every installed `uuhms.patients` source column exactly one primary disposition from the Phase 2C allowed vocabulary. A disposition classifies the source column; it does not override row-level validity, quarantine, or later-domain rules.

Evidence labels are: **Confirmed** for installed metadata/aggregates, **Inference** for unapproved semantics, **Policy** for accepted decisions, and **Technical specification** for the deterministic future behavior defined here. The exact source coordinate is the 24-column/16,950-row patient table under fingerprint `150fcf4783fcb8bdc25f7e17fe0ece5050955f0c68e7dd03651ee8bd58498977`.

## Complete 24-column matrix

| # | Source column | Primary disposition | Approved target/use | Transformation, validation, and failure behavior | Basis |
|---:|---|---|---|---|---|
| 1 | `PAT_ID` | **Crosswalk** | Protected source identity for the future source-to-target patient mapping and chain linkage. | Canonical typed integer; retain raw key only in protected operational provenance. Never write it to target `patients.id`, `patient_number`, or alias `source_patient_id`. Missing/duplicate/nonpositive drift stops extraction. | Confirmed PK; D-201/Q-001 policy. |
| 2 | `PatientName` | **Split** | Future protected remediation may supply independently evidenced `patients.first_name`, `last_name`, and optional `other_names`. | Decode latin1, NFC, trim/collapse comparison whitespace, preserve meaningful punctuation and original protected form. Automatic splitting, guessing order, copying one token to both fields, invention and truncation are prohibited. Source-only value is unrepresentable until remediation. | Confirmed sole combined field; D-213/Q-007. |
| 3 | `OpdNo` | **Typed legacy alias candidate** | `patient_aliases` with type `legacy_opd`, after patient mapping. Never `patients.patient_number`. | Preserve NFC/outer-trimmed display and protected raw value; use versioned Unicode comparison normalization. Blank, invalid, source-duplicate, target-alias-conflicting or target-number-conflicting values are withheld. Preserve punctuation and leading zeros; never decorate. | D-201/D-211; 141 blank, 1,259 duplicate-affected under baseline comparison. |
| 4 | `Sex` | **Transform** | `patients.gender`. | `MALE`/`M` -> `male`; `FEMALE`/`F` -> `female` after trim/case normalization. Blank is missing; all other values invalid. No default or unsupported enum. | Confirmed aggregate; target enum; D-213. |
| 5 | `DOB` | **Transform** | `patients.date_of_birth`. | Preserve only a full nonzero date strictly before commit-day and not after `DATE(RegDate)`. Use 1900-01-01 as a conservative Phase 2C quarantine gate, not a final approved historical lower bound. Below-gate, invalid, partial or unknown values require approved rule/remediation or quarantine; no inferred/default date. | Confirmed source date; D-206/D-213. |
| 6 | `PhoneNo` | **Transform** | `patients.phone`. | Trim only for candidate parsing; accept only a value satisfying the pinned Ghana target rule and max 20. Preserve an accepted source-local representation unless a separately approved canonical display transform exists. Blank/invalid quarantines; duplicate is nonblocking review evidence. Never match identity by phone. | Confirmed source; target required/nonunique; D-213/Q-002. |
| 7 | `Work` | **Deferred to Phase 2D** | Candidate patient occupation/demographic input. | Preserve protected source representation; do not map to empty `sett_ocuupation` or target occupation until Phase 2D defines semantics. | Inference only; dormant catalogue baseline. |
| 8 | `Company` | **Deferred to Phase 2E** | Candidate insurance provider/default-payer evidence. | Do not copy to patient identity or automatically match a provider. Consume Phase 2A provider crosswalk and Phase 2E membership consolidation. | Inference plus BillStatus association. |
| 9 | `Address` | **Deferred to Phase 2D** | Candidate patient address. | Phase 2D owns normalization, length, locality and privacy. It cannot establish identity. | Confirmed contact-like field. |
| 10 | `NOK` | **Deferred to Phase 2D** | Candidate `emergency_contacts.name`. | Child mapping only after patient crosswalk; never populate dormant inline target emergency-contact fields. Protected PHI. | Confirmed next-of-kin field. |
| 11 | `NOKPhoneNo` | **Deferred to Phase 2D** | Candidate `emergency_contacts.phone`. | Phase 2D phone validity and contact reconciliation; never identity matching. | Confirmed next-of-kin phone. |
| 12 | `NOKRel` | **Deferred to Phase 2D** | Candidate `emergency_contacts.relationship`. | Treat as free text pending Phase 2D; 435 categories prohibit an assumed clean enum. | Confirmed aggregate; semantics unresolved. |
| 13 | `Religion` | **Deferred to Phase 2D** | Candidate `patients.religion`. | Phase 2D must crosswalk each source category; blank remains null if target-optional, never a default religion. | Confirmed categorical evidence. |
| 14 | `MaritalStatus` | **Deferred to Phase 2D** | Candidate `patients.marital_status`. | Phase 2D must map explicitly to supported target enum; blank/out-of-domain do not default. | Confirmed categorical evidence. |
| 15 | `BillStatus` | **Deferred to Phase 2E** | Default payer/membership evidence. | Not a patient active/status value. Phase 2E owns payer interpretation and exact row preservation. | Confirmed categories; D-212 dependency. |
| 16 | `Refill` | **No approved destination** | None. | Retain only inside protected row provenance/fingerprint pending semantic approval. Do not treat it as patient, visit, prescription or refill FK. | Inference is unsafe; semantics unresolved. |
| 17 | `Allergies` | **Evidence-only** | Later clinical-history input, not Phase 2C identity. | All 16,950 rows are blank at baseline. Retain column in fingerprint; any changed/populated baseline needs clinical/privacy review. | Confirmed aggregate. |
| 18 | `Medication` | **Evidence-only** | Later clinical-history input, not Phase 2C identity. | All 16,950 rows are blank at baseline. No medication, order or stock movement is synthesized. | Confirmed aggregate. |
| 19 | `History` | **Evidence-only** | Later clinical-history input. | Seven populated rows are protected clinical text and contain control characters. No content in docs/logs; later phase must sanitize and map explicitly. | Confirmed aggregate; privacy policy. |
| 20 | `LastVisit` | **Evidence-only** | Extraction/chronology and later visit evidence only. | Do not map to patient creation/update time or create visits from it. It disagrees with maximum matched attendance for almost all matched patients. | Confirmed aggregate. |
| 21 | `EditDate` | **Evidence-only** | Update-detection evidence. | Use only with overlap plus full protected snapshot/hash. Do not automatically map to target `updated_at`; it is schema-managed and cannot detect deletions. | Confirmed `ON UPDATE` behavior. |
| 22 | `RegDate` | **Direct** | Target historical `patients.created_at`, subject to timestamp validation and migration-specific persistence. | Preserve exact valid source instant under a pinned timezone interpretation. Suppress Eloquent current-time stamping. Invalid/unrepresentable values require explicit exception; do not use migration time. | Confirmed registration candidate; zero null/zero. |
| 23 | `OriginalName` | **Protected provenance** | Protected prior-name provenance only. | All rows blank at baseline. Do not use to fill/split names without a separately verified remediation decision. | Confirmed blank aggregate. |
| 24 | `OriginalOpd` | **Protected provenance** | Protected prior-identifier provenance only. | All rows blank at baseline. Never use as target patient number or alias without a new approved semantic/uniqueness contract. | Confirmed blank aggregate; D-201. |

## Coverage proof

| Disposition | Column count |
|---|---:|
| Crosswalk | 1 |
| Split | 1 |
| Typed legacy alias candidate | 1 |
| Transform | 3 |
| Deferred to Phase 2D | 7 |
| Deferred to Phase 2E | 2 |
| No approved destination | 1 |
| Evidence-only | 5 |
| Direct | 1 |
| Protected provenance | 2 |
| **Total** | **24** |

Every installed column appears once and only once. Counts for unused dispositions are zero: Merge, Target-generated, Quarantine, Exclude. In particular, no source column has **Target-generated** disposition because the renewed patient number is generated by the target mechanism and has no Classic source column.

Stable exception alignment follows the machine mapping: `LEGACY-PATIENT-KEY-001` through `-002`; `LEGACY-PATIENT-ALIAS-003` through `-007`; `LEGACY-PATIENT-NAME-008` through `-012`; `LEGACY-PATIENT-DOB-013` through `-016`; `LEGACY-PATIENT-GENDER-017` through `-018`; `LEGACY-PATIENT-PHONE-019` through `-020`; `LEGACY-PATIENT-TEXT-031`; `LEGACY-PATIENT-DRIFT-033`; `LEGACY-PATIENT-EXTRACTION-034`; `LEGACY-PATIENT-PRIVACY-036`; and `LEGACY-PATIENT-REMEDIATION-040`.

## Target-generated and target-only outcomes

- `patients.id`: target auto-increment, never Classic-derived.
- `patients.patient_number`: `TARGET_GENERATED_AT_COMMIT` for an eligible new patient; existing explicit links retain the existing number. Crosswalk lookup precedes allocation on every run.
- `patients.registered_by`: null plus protected no-source-actor provenance under Phase 2B `TARGET-ACTOR-054`; no current/importer/admin/first-user/unknown fallback.
- `patients.updated_at`: no approved Classic direct source. A future persistence boundary must specify null or an approved historical rule rather than stamp migration time.
- Optional email, Ghana Card, blood group and other absent identity fields: null where target permits it; never inferred.
- Status, active, temporary, merge, death, archive and identity-confirmation fields: must follow a separately approved coherent target-state matrix. Classic `BillStatus` is not status evidence.

## Name, display and matching separation

The protected source combined name is not itself a target component. A reviewed remediation record must identify each supplied first/last/other component, its evidence, reviewer, decision version and source token. Each component is decoded/NFC-normalized, outer-trimmed, checked for controls and checked against the operational maximum of 100; overlength is unrepresentable, never truncated. Target display name may be constructed from approved components using target presentation rules, but that display value is not a natural key. Comparison-normalized names may produce only `PATIENT_SUSPECTED_DUPLICATE_REVIEW`; they can never merge, block an otherwise eligible record by themselves, or link to an existing target.

## Row-level consequences

A valid column transformation does not imply entity eligibility. Missing, invalid or unrepresentable first name, last name, DOB, gender or phone produces the corresponding required-field outcome and holds the patient plus its complete dependency chain. A duplicate OPD produces an alias exception and withholds only the alias. A suspected duplicate produces a protected review flag only. An explicit existing-target crosswalk links without changing any target identity field.

## Privacy and provenance

The operational crosswalk, remediation evidence, alias review and quarantine records are protected data. Raw Classic keys and identity values must not appear in generated documentation, ordinary logs or reconciliation exports. Use versioned, domain-separated HMAC-SHA-256 tokens for row-level correlation and keep key material outside source control. The source row fingerprint includes all 24 typed columns with explicit null/type/length markers and versioned latin1-to-Unicode handling; it is not a public patient identifier.
