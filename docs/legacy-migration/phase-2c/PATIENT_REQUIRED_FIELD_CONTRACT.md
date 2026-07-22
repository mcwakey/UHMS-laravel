# Patient required-field contract

## Scope and authority

This specification defines validity, normalization, exception, quarantine and release behavior for the installed target patient identity requirements. It consumes D-201, D-213, Q-001, Q-002 and Q-007 and Phase 2B `TARGET-ACTOR-054`. It authorizes no persistence.

**Confirmed evidence** identifies installed constraints and sanitized counts. **Policy** prohibits invented identity. **Technical specification** defines deterministic future rules. Where evidence cannot yet produce an exact final count, that is stated as a prerequisite rather than guessed.

The installed target requires non-null identity/number fields `patient_number`, `first_name`, `last_name`, `date_of_birth`, `gender`, and `phone`. It also has five non-null state fields with database defaults: `status`, `is_active`, `is_temporary`, `merge_status`, and `is_deceased`. Those defaults are not approved migration facts and must not be accepted silently. `registered_by`, `created_at` and `updated_at` are nullable. Operational validation is stronger than physical storage for name components (100 versus `varchar(191)`) and phone (20 versus `varchar(191)`); the operational limits govern migration unless a separate controlled target specification approves otherwise.

## Required-field matrix

| Target field | Source or derivation | Validity and normalization | Missing / invalid / unrepresentable | Exception and outcome | Release and reconciliation |
|---|---|---|---|---|---|
| `patient_number` | No Classic source; target allocator only. | Existing explicit link retains its number. New patient receives one value from the pinned target format/locked sequence at commit after crosswalk lookup. Must fit `varchar(191)` and be unique including soft-deleted/merged/archived lookup owners. | Missing allocator/crosswalk/config is a technical prerequisite failure. Collision, rollover, noninjective format or rerun allocation is invalid. Classic PK/OPD is never a derivation. | `LEGACY-PATIENT-NUMBER-037`, `LEGACY-PATIENT-NUMBER-038`, or `LEGACY-PATIENT-NUMBER-039`; hold/rollback the patient. | Release only after atomic mapping/allocation preconditions pass. Reconcile with `PATIENT-REC-REQ-001`: zero PK/OPD reuse, zero duplicates, one number per new success and zero new allocations for links/reruns/quarantine. |
| `first_name` | Independently evidenced protected remediation derived from the combined `PatientName`; no automatic split. | Versioned latin1 decode; Unicode NFC; outer trim; reject controls; preserve punctuation; 1-100 characters under operational rule. | Blank combined name is missing. A nonblank combined name without independent component evidence is unrepresentable, not valid. Overlength/encoding/control failure is invalid. Never copy a token into both names. | `LEGACY-PATIENT-NAME-009` missing; `LEGACY-PATIENT-NAME-011` unrepresentable; `LEGACY-PATIENT-NAME-012` overlength/control. Patient and full chain quarantine. | Release only with reviewed component evidence and revalidation. Reconcile with `PATIENT-REC-REQ-002`. |
| `last_name` | Independently evidenced protected remediation derived from the combined `PatientName`; no automatic split. | Same component rules as first name. | Same missing/unrepresentable/invalid rules. A single-token source name does not authorize a last name. | `LEGACY-PATIENT-NAME-010` missing; `LEGACY-PATIENT-NAME-011` unrepresentable; `LEGACY-PATIENT-NAME-012` overlength/control. Full chain quarantine. | Release only with reviewed component evidence and revalidation. Reconcile with `PATIENT-REC-REQ-003`. |
| `date_of_birth` | `patients.DOB`. | Full and nonzero; strictly before the pinned target validation day; `<= DATE(RegDate)`. Preserve valid date unchanged. Use 1900-01-01 only as a conservative Phase 2C quarantine gate pending an approved final historical lower bound. Same-day registration is allowed but reviewable. | Zero/null/partial is missing or unrepresentable; below-gate, future/today under target rule, or after registration is invalid for the current partition. Never substitute current/registration/January 1/inferred age. | `LEGACY-PATIENT-DOB-013` invalid, `-014` implausible/below gate, `-015` after registration, or `-016` partial/unknown. Full chain quarantine. | Release only from reliable documentary/source remediation and an approved lower-bound rule. Reconcile with `PATIENT-REC-REQ-004`. |
| `gender` | `patients.Sex`. | Trim and Unicode case-normalize: `MALE`/`M` -> `male`; `FEMALE`/`F` -> `female`. Only target enum values `male`,`female` may persist. Preserve raw value in protected provenance. | Blank is missing. Every other observed or future source value is invalid until an explicit crosswalk is approved. No default/unknown enum. | `LEGACY-PATIENT-GENDER-017` missing or `LEGACY-PATIENT-GENDER-018` unknown; full chain quarantine. | Release after explicit evidenced remediation/crosswalk and enum validation. Reconcile with `PATIENT-REC-REQ-005`; exact baseline 16,784 valid, 151 missing, 15 invalid. |
| `phone` | `patients.PhoneNo`. | Decode/trim; reject controls/extensions/multiple numbers/letters; maximum 20; accept only the pinned Ghana target regex `^(?:\+233|0)[235][0-9]{8}$`. For source-local values, preserve an accepted `0...` display form; do not invent a country code. Comparison normalization may be separate/versioned and is never identity evidence. | Blank is missing. Any nonmatching or implausible value is invalid. Country code/extension/multiple-number parsing without evidence is unrepresentable. Never synthesize a phone. | `LEGACY-PATIENT-PHONE-019` missing or `LEGACY-PATIENT-PHONE-020` invalid/unrepresentable; full chain quarantine. | Release only with verified contact remediation and revalidation. Reconcile with `PATIENT-REC-REQ-006`; target phone is nonunique. |
| `status` | No approved Classic source or derivation. | Must be selected only by an approved coherent patient-state matrix; DB default `active` is not evidence. | State policy is unrepresentable until approved; default use or independent mapping is invalid. | `LEGACY-PATIENT-STATUS-045`; patient and full chain remain quarantined. | Release after an approved state matrix jointly validates all five state fields; reconcile `PATIENT-REC-REQ-007`. |
| `is_active` | No approved Classic source or derivation. | Must be derived jointly with `status`; DB default `1` is not evidence. | Independent/default value is unrepresentable or invalid. | `LEGACY-PATIENT-STATUS-046`; patient and chain quarantine. | Approved coherent state matrix; reconcile `PATIENT-REC-REQ-008`. |
| `is_temporary` | No approved Classic source or derivation. | Temporary identity must never bypass required identity; DB default `0` is not evidence. | No approved source/state derivation. | `LEGACY-PATIENT-STATUS-047`; patient and chain quarantine. | Approved coherent state matrix; reconcile `PATIENT-REC-REQ-009`. |
| `merge_status` | No approved Classic source or derivation. | Must remain coherent with merge pointers/history and the no-automatic-merge policy; DB default `ACTIVE` is not evidence. | No default or inferred merge state may be persisted. | `LEGACY-PATIENT-STATUS-048`; patient and chain quarantine. | Approved coherent state matrix with zero automatic merges; reconcile `PATIENT-REC-REQ-010`. |
| `is_deceased` | No approved Classic source or derivation. | Must be jointly coherent with status/death facts; DB default `0` is not evidence of life status. | Absence of mapped death evidence cannot silently become false. | `LEGACY-PATIENT-STATUS-049`; patient and chain quarantine. | Approved coherent state matrix and source-evidence rule; reconcile `PATIENT-REC-REQ-011`. |
| `registered_by` | No Classic patient actor field. | Nullable target FK; approved result is null plus protected no-source-actor provenance. | Not a missing required identity error because target permits null. Any current/importer/admin/first-user/supervisor or Legacy Actor Unknown fallback is prohibited. | Phase 2B `LEGACY-STAFF-ACTOR-010`; `LEGACY-STAFF-ACTOR-018` for prohibited unknown use. Prohibited fallback is a stop/failure, not remediation. | Every new migrated patient reconciles to null plus provenance; all fallback counts zero. Existing links remain unchanged. |
| `created_at` | `patients.RegDate`. | Preserve a valid historical source instant using the pinned source timezone interpretation; suppress Eloquent current timestamps. | Invalid/zero/out-of-range/ambiguous timezone is explicit remediation/drift; never migration time. | `LEGACY-PATIENT-REMEDIATION-040` and, for baseline/schema change, `LEGACY-PATIENT-DRIFT-033`; hold persistence if representation is unsafe. | Release after versioned timezone/chronology rule. Baseline has zero null/zero, but timezone interpretation remains preflight-pinned. |
| `updated_at` | No approved direct source. `EditDate` is evidence-only. | Target is nullable. Use null plus provenance unless a separate approved historical update-time contract is adopted. | Eloquent/current migration time is prohibited. `EditDate` cannot be assumed semantic update time. | Invented fallback is a fail-closed `LEGACY-PATIENT-DRIFT-033` condition; no invented timestamp. | Null/provenance reconciles exactly under this contract. |

Optional target email, Ghana Card, blood group and other absent fields remain null. Empty strings must not be used to occupy a target uniqueness namespace. None may be inferred from name, phone, OPD, Company, BillStatus or dependent records during Phase 2C.

### Email and national identifier absence

Classic has no installed patient email or Ghana Card/national-identifier column. Their source-presence, format, verification and duplicate counts are therefore zero/not applicable, not “unknown values” to invent. The approved target representation is null because both target fields are optional. If protected remediation later supplies either value, it requires a separately approved rule version: email format plus comparison normalization; Ghana Card format plus case/whitespace normalization; source and all-target collision checks including soft-deleted rows; verified/unverified state; and immutable provenance. A Ghana Card uniqueness collision is `PATIENT_TARGET_UNIQUENESS_CONFLICT`, not permission to match or merge. Email, Ghana Card, phone or any single identity field can never establish an existing-target link.

## Exact source baseline

### Names

Classic contains only one combined `PatientName`: 211 blank and 16,739 populated, totaling 16,950. It contains 134 populated single-token rows, 507 rows with outer whitespace, one control-character row and one value at the 100-character source limit.

Every blank combined source name receives diagnostic `LEGACY-PATIENT-NAME-008` as well as the applicable target-component missing codes `LEGACY-PATIENT-NAME-009` and `LEGACY-PATIENT-NAME-010`. The diagnostic does not add a second field or entity outcome.

Under the source-only no-auto-split contract, both target component partitions begin as:

`16,950 = 0 valid + 211 missing + 0 invalid + 16,739 unrepresentable + 0 failed`

This is a pre-remediation baseline, not a claim that all populated values can ultimately be remediated. After protected remediation, each row moves to exactly one new field outcome and the equation must still have zero difference. Original and normalized names are never emitted into reconciliation output.

### Gender crosswalk

| Raw normalized class | Evidence count | Target | Mapping state | Exception |
|---|---:|---|---|---|
| `MALE` | 7,282 | `male` | valid transform | none |
| `M` | 198 | `male` | valid transform | none |
| `FEMALE` | 9,080 | `female` | valid transform | none |
| `F` | 224 | `female` | valid transform | none |
| blank/null-equivalent | 151 | none | missing | `LEGACY-PATIENT-GENDER-017` |
| `EMALE`, `FEMLE`, `MAL` | 3 total | none | malformed | `LEGACY-PATIENT-GENDER-018` |
| `1`, `2`, `10`, `22`, `23`, `24`, `26`, `27`, `32`, `38`, `42`, `74` | 12 total | none | out of domain | `LEGACY-PATIENT-GENDER-018` |

The equation is `16,950 = 16,784 valid + 151 missing + 15 invalid + 0 unrepresentable + 0 failed`. Raw values remain protected provenance. A newly observed category is invalid pending explicit approval, never a convenient default.

### DOB

Confirmed source facts are zero zero-dates, zero future-at-capture dates, one pre-1900 date, 18 DOB-after-registration dates, and 646 same-day registrations. The pre-1900 row cannot overlap the after-2021-registration condition, so the conservative technical baseline is:

`16,950 = 16,931 valid + 0 missing + 19 invalid + 0 unrepresentable + 0 failed`

This partition is evaluated again against the pinned migration validation day and target “strictly before today” rule. A changed source snapshot or newly current/future condition changes the classified counts and must reconcile, not be silently grandfathered.

### Phone

Confirmed source facts reconcile as 5,050 blank and 11,900 populated. Under the installed target-compatible local Ghana predicate `^0[235][0-9]{8}$`, 11,737 are valid nonblank and 163 are invalid nonblank; `16,950 = 5,050 + 11,737 + 163` with difference zero. Earlier diagnostics found 26 populated nondigit values and 139 populated values with trimmed length other than ten; these signals overlap. There are 935 duplicate trimmed groups covering 2,075 rows; target phone is not unique.

The 11,737 are source-local target-regex-compatible candidates. They do not authorize identity matching and do not prove any optional international-prefix transform. Before persistence, a refreshed privacy-safe query using the pinned effective target regex must reproduce the mutually exclusive equation:

`16,950 = valid + 5,050 missing + invalid + unrepresentable + failed`

No eligible-patient count may be persisted unless that refreshed equation has zero difference and all other required fields pass. The present evidence closes the Phase 2C aggregate gap; it does not authorize persistence.

### Patient number and registrar

No Classic field supplies the patient number or registrar. For patient number:

`16,950 = explicit links retaining a valid existing number + new eligible candidates requiring commit allocation + entity-not-persistable + number-rule failure`

For registration actor, every newly created source patient must fall in null-plus-provenance; verified actor, permitted unknown, and fallback counts are zero. An explicit target link changes no existing registrar.

## Name normalization and remediation evidence

The remediation input is protected and must record the source token, source-row fingerprint, independently evidenced first/last/optional-other components, evidence class, reviewer/approver, decision timestamp, rule version and immutable audit. It may not simply store an algorithmic split as “evidence.”

For each component:

1. decode from the declared source representation;
2. normalize Unicode to NFC;
3. retain exact protected original and outer-trimmed display value;
4. collapse Unicode whitespace only for comparison;
5. preserve meaningful punctuation and diacritics;
6. reject prohibited control/noncharacter content;
7. require 1-100 characters for first/last and at most 100 for optional other names;
8. never infer identity from the comparison value.

Display-name construction occurs only after approved components exist and follows target presentation. Display equality is not matching evidence.

## DOB remediation rules

A valid remediation must carry reliable source/document evidence for a complete calendar date and its review provenance. Partial year/month, estimated age and unknown DOB have no approved target representation. Pre-1900 may be accepted only through a separately controlled rule change with evidence; this contract otherwise classifies it invalid. DOB after registration requires correction of the DOB or an evidenced correction to the registration chronology, never automatic swapping or replacement. The source value and decision remain protected provenance.

## Phone handling

The source field can hold at most ten characters and therefore cannot evidence a stored `+233...` form. The future validator may accept an exact local Ghana value already matching `0[235][0-9]{8}` and preserve it. It must not prepend/drop digits, parse an extension, select one value from a multi-number string, or repair invalid characters without reviewed evidence. A verified remediation may provide a replacement value and provenance.

Phone is contact information and supporting review evidence, never a natural key. Duplicate phone values create no uniqueness exception by themselves, and phone-only target matches/merges must reconcile to zero.

## Quarantine, dependent chains, and release

Any unresolved required-field missing/invalid/unrepresentable outcome places the row in `PATIENT_MISSING_REQUIRED_IDENTITY` or `PATIENT_INVALID_REQUIRED_IDENTITY` and the state envelope `PATIENT_QUARANTINED`. Every matched attendance, insurance membership, bed occupancy link and downstream clinical/billing/claim/admission record remains in the same protected dependency chain. No dependent may be attached to another, generic, current or guessed patient.

When more than one required field fails, retain every field-specific code and report a nonidentifying multi-field aggregate. No generic exception may replace or hide the component failures, and the aggregate does not add another patient to reconciliation.

Release requires:

1. protected evidence and approval for each remediated field;
2. revalidation under the same pinned rule versions;
3. a zero-difference required-field partition for every field;
4. successful target collision and privacy/provenance preflight;
5. atomic patient mapping/number outcome;
6. topological child release and independent domain reconciliation.

An expired remediation SLA changes nothing. Dummy `UNKNOWN`, `N/A`, zero dates, synthetic phone, duplicated name components and migration dates are prohibited.

## Privacy and audit

Names, DOB, gender, phones, OPDs, addresses, identifiers and remediation documents are protected patient data. They may appear only in the protected operational crosswalk/remediation/quarantine boundary with least privilege and retention controls. Routine logs, documentation and repository artifacts contain field/class IDs and aggregates only. When row-level correlation is unavoidable, use domain-separated HMAC-SHA-256 with purpose, canonicalization version and key version; never plain hashes of low-entropy identity values. Privacy/provenance failure is a stop condition and quarantines the affected chain.
