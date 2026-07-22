# Patient relationship register

## Contract status and authority

This Phase 2C contract is a documentation-only relationship specification. It authorizes no extraction run, importer, schema change, source write, target write, or child-domain mapping. The only approved Classic schema is `uuhms`, and Classic declares zero foreign keys. Every relationship below is therefore inferred from an exact tested predicate and remains subject to its stated semantic gate.

Authoritative policy is D-201, D-207, D-209, D-211, D-213, Q-001, Q-002, Q-006, Q-007 and Q-008 in `APPROVED_DECISION_SPECIFICATIONS.md` and `DECISIONS.md`. The evidence coordinate is `CLASSIC_RELATIONSHIP_MANIFEST.json`, captured `2026-07-21T01:57:29+00:00`, bound to structural schema fingerprint `150fcf4783fcb8bdc25f7e17fe0ece5050955f0c68e7dd03651ee8bd58498977`, 55 tables and 479 columns. The manifest contains 77 tested predicates. Inclusion proves only the observed aggregate result; it does not turn an inferred join into a declared foreign key.

The register uses the manifest relationship IDs unchanged. `PATIENT-SENT-*` values are stable keys for the future `patient_sentinel_rules.json`; the machine contract must use them unchanged. In every equation the terms are, in order, `child rows = null + observed zero candidate + matched nonzero + orphan nonzero + extraction failure`. Baseline extraction failure is zero. An observed zero candidate is not a global sentinel approval.

## Complete direct patient relationship inventory

`uuhms.patients` is the patient master, `PAT_ID` is its unique primary key, and the installed Classic catalogue contains exactly five `PAT_ID` child columns outside `patients`. Consequently this inventory is complete for direct Classic patient predicates.

| Relationship ID | Sentinel rule ID | Exact predicate | Baseline partition | Field-specific null/zero contract | Orphan and blocking contract | Evidence query/hash |
|---|---|---|---:|---|---|---|
| `attendance.patient` | `PATIENT-SENT-001` | `c.\`PAT_ID\` = p.\`PAT_ID\`` | `51,927 = 0 + 155 + 48,589 + 3,183 + 0` | `attendance.PAT_ID IS NULL` and `attendance.PAT_ID = 0` are distinct patient-relationship-not-evidenced outcomes. Either roots an attendance-chain quarantine; neither creates a patient. | A nonzero unmatched value is an orphan patient reference. Hold the attendance and all registered descendants. Never reassign, guess, or create a patient. | `classic.relationship.attendance.patient`; `f39f600c79ee25cbf3cb8096e57b5041ca3d96f80a69bdc615f05990c1358f26` |
| `beds.patient` | `PATIENT-SENT-002` | `c.\`PAT_ID\` = p.\`PAT_ID\`` | `18 = 0 + 15 + 3 + 0 + 0` | Phase 2A `beds.patient` approves only `beds.PAT_ID = 0` as relationship not evidenced. A future null is invalid/drift because the installed field is non-null. No rule may generalize this zero behavior. | Hold only the patient-occupancy link/snapshot. The bed reference master remains governed by Phase 2A `EXTRACT-BEDS`. A numeric match is not sufficient admission evidence and reverse traversal through bed reuse is prohibited. | `classic.relationship.beds.patient`; `87bfc37d1b2d798c906709de041d85b0f9437403afde721ea542bd5734280705` |
| `insurance.patient` | `PATIENT-SENT-003` | `c.\`PAT_ID\` = p.\`PAT_ID\`` | `31,307 = 0 + 0 + 30,991 + 316 + 0` | Neither null nor zero is approved as a sentinel. Both were unobserved; a later occurrence is relationship/fingerprint drift requiring classification. | Each nonzero orphan is an insurance-root patient-link quarantine. Do not reassign it. A matched row remains blocked by its patient when the patient is quarantined and by later provider/membership rules. | `classic.relationship.insurance.patient`; `d2ce22f5f34bbb9e06daeee8b274f35bbc9d719ca7abe53934e4584ff4384547` |
| `mat_family.patient` | `PATIENT-SENT-004` | `c.\`PAT_ID\` = p.\`PAT_ID\`` | `0 = 0 + 0 + 0 + 0 + 0` | No sentinel behavior is inferable from an empty table. | D-209/Q-305 excludes this dormant baseline. Any row or fingerprint change is a stop for renewed scope and privacy review, not a migration candidate. | `classic.relationship.mat_family.patient`; `995013d23c23ab1de670e0f6421156eb1abccc47aaffa28d0d1f2f110ae8e9b2` |
| `mat_obstetrics.patient` | `PATIENT-SENT-005` | `c.\`PAT_ID\` = p.\`PAT_ID\`` | `0 = 0 + 0 + 0 + 0 + 0` | No sentinel behavior is inferable from an empty table. | D-209/Q-305 excludes this dormant baseline. Any row or fingerprint change is a stop for renewed scope and privacy review, not a migration candidate. | `classic.relationship.mat_obstetrics.patient`; `11fa6cc1f1df763f882578691d75d5c60f31e57abddffcdb072129136ea7709d` |

For each direct edge the future reconciliation contract is:

`child_rows = null_reference + field_approved_sentinel + matched_patient + orphan_patient + failed_extraction`

The five equations above must have zero difference. `patients.PAT_ID` is source linkage and protected provenance only; it must resolve through a future protected crosswalk and may never be written as a renewed ID, patient number, or `patient_aliases.source_patient_id`. A missing or duplicate patient primary key in a future snapshot is schema/extraction failure, not a duplicate-parent selection case.

## Attendance-rooted dependency closure

The exact child-to-parent predicate for every row in this section is `c.\`ATT_ID\` = p.\`ATT_ID\`` with parent `uuhms.attendance.ATT_ID`. A matched edge inherits the attendance chain. A missing/sentinel/orphan edge never acquires a patient from a secondary bill, service, product, clinician, bed, name, phone or OPD value.

| Relationship ID | Sentinel rule ID | Baseline partition | Field-specific chain interpretation |
|---|---|---:|---|
| `appointement.attendance` | `PATIENT-SENT-006` | `1,364 = 0 + 1 + 1,297 + 66 + 0` | Nullable `ATT_ID`: null or the one observed zero means the attendance relationship is not evidenced for chain purposes; nonzero orphan remains an appointment-root exception. Patient, department, creator and date/time are still later release gates. |
| `billing.attendance` | `PATIENT-SENT-007` | `111,731 = 0 + 30 + 108,857 + 2,844 + 0` | Nullable `ATT_ID`: null or observed zero means relationship not evidenced; nonzero orphan holds the billing row. Preserve financial facts, but do not create opening AR, posting or patient linkage. |
| `claims.attendance` | `PATIENT-SENT-008` | `31,892 = 0 + 0 + 30,103 + 1,789 + 0` | Non-null field and no observed zero: a future null/zero is drift, not an approved sentinel. Orphan claims root claim subchains and cannot post. |
| `consult_complaints.attendance` | `PATIENT-SENT-009` | `65,244 = 0 + 0 + 62,886 + 2,358 + 0` | Non-null field and no observed zero: future null/zero is drift. Orphan holds the complaint subchain. |
| `consult_diagnosis.attendance` | `PATIENT-SENT-010` | `123,940 = 0 + 4 + 122,975 + 961 + 0` | Non-null field: the four observed zeros are relationship-not-evidenced for chain classification only; they do not approve a diagnosis-domain meaning. Orphan holds the diagnosis subchain. |
| `consult_history.attendance` | `PATIENT-SENT-011` | `34,532 = 0 + 10 + 33,992 + 530 + 0` | Non-null field: observed zero is relationship-not-evidenced for chain classification only. Orphan holds the history subchain. |
| `consult_prescriptions.attendance` | `PATIENT-SENT-012` | `210,902 = 0 + 9 + 208,591 + 2,302 + 0` | Nullable field: null/observed zero means relationship not evidenced; orphan holds the prescription subchain. Product, actor, department and bill are additional gates, never alternate patient evidence. |
| `consult_procedures.attendance` | `PATIENT-SENT-013` | `121 = 0 + 0 + 120 + 1 + 0` | Nullable field and no observed null/zero: a later null is relationship not evidenced; a later zero requires drift review. The nonzero orphan holds the procedure subchain. |
| `consult_scan_lab.attendance` | `PATIENT-SENT-014` | `72,680 = 0 + 0 + 71,390 + 1,290 + 0` | Non-null field and no observed zero: future null/zero is drift. Orphan holds the investigation/order subchain. |
| `consult_services.attendance` | `PATIENT-SENT-015` | `60,260 = 0 + 2 + 57,576 + 2,682 + 0` | Nullable field: null/observed zero means relationship not evidenced; orphan holds the service subchain. |
| `consult_treatment.attendance` | `PATIENT-SENT-016` | `1,926 = 0 + 1 + 1,893 + 32 + 0` | Non-null field: observed zero is relationship-not-evidenced for chain classification only; orphan holds the treatment-plan subchain. |
| `nurses_note.attendance` | `PATIENT-SENT-017` | `29 = 0 + 0 + 29 + 0 + 0` | Non-null field and no observed zero: future null/zero is drift. Actor/content rules remain additional gates. |
| `serv_results.attendance` | `PATIENT-SENT-018` | `460,758 = 0 + 1 + 454,900 + 5,857 + 0` | Non-null field: observed zero is relationship-not-evidenced for chain classification only; orphan holds the result subchain. Direct `ATT_ID` is authoritative even when `SCL_ID` is populated. |
| `treatment.attendance` | `PATIENT-SENT-019` | `69 = 0 + 2 + 52 + 15 + 0` | Non-null field: observed zero is relationship-not-evidenced for chain classification only; orphan holds the administered-treatment subchain. |
| `vitals.attendance` | `PATIENT-SENT-020` | `60,803 = 0 + 1,198 + 57,019 + 2,586 + 0` | Non-null field: observed zero is relationship-not-evidenced for chain classification only; orphan holds the vitals subchain. |

These are edge aggregates, not disjoint chain sizes. They do not reveal how child tables overlap on the same attendance or the exact descendant volume beneath the 3,338 attendance rows whose patient relationship is not evidenced or orphaned. No document may invent those counts.

## Claim descendants

The exact predicate for each claim child is `c.\`CLAIM_ID\` = p.\`CLAIM_ID\`` with parent `uuhms.claims.CLAIM_ID`.

| Relationship ID | Sentinel rule ID | Baseline partition | Release requirement |
|---|---|---:|---|
| `claims_diagnosis.claim` | `PATIENT-SENT-021` | `57,776 = 0 + 0 + 57,507 + 269 + 0` | Non-null field; no sentinel is approved. Release only after claim, attendance, patient and diagnosis reference. |
| `claims_prescriptions.claim` | `PATIENT-SENT-022` | `112,152 = 0 + 0 + 107,411 + 4,741 + 0` | Nullable field but no null/zero observed. A later null means relationship not evidenced; zero requires drift review. Release only after claim and product reference. |
| `claims_procedures.claim` | `PATIENT-SENT-023` | `3,648 = 0 + 0 + 3,629 + 19 + 0` | Nullable field but no null/zero observed. Release only after claim and procedure reference. |
| `claims_scan_lab.claim` | `PATIENT-SENT-024` | `3 = 0 + 0 + 3 + 0 + 0` | Nullable field but no null/zero observed. Release only after claim and approved investigation/procedure reference. |

No claim or claim descendant may release or post merely because its patient edge is repaired. Provider, specialty, status, dates, Phase 2B actor semantics, target constraints and the exact Q-102 component equation remain mandatory and independent.

## Secondary consistency links

These predicates may validate a subchain but cannot establish or replace its patient root.

| Relationship ID | Sentinel rule ID | Exact predicate | Baseline partition | Contract |
|---|---|---|---:|---|
| `consult_prescriptions.billing` | `PATIENT-SENT-025` | `c.\`BILL_ID\` = p.\`BILL_ID\`` | `210,902 = 0 + 91,329 + 116,620 + 2,953 + 0` | `BILL_ID = 0` means only that this secondary finance link is not evidenced. Root the prescription through its own `ATT_ID`. A nonzero orphan or a different-attendance result quarantines the cross-link; never union chains. |
| `serv_results.scan_lab` | `PATIENT-SENT-026` | `c.\`SCL_ID\` = p.\`SCL_ID\`` | `460,758 = 0 + 460,730 + 6 + 22 + 0` | `SCL_ID = 0` is only absence of this sparse secondary order link. The six matches require same-attendance/patient proof before use. The direct result `ATT_ID` remains authoritative. |
| `serv_results.claim_scan_lab_alternative` | `PATIENT-SENT-027` | `c.\`SCL_ID\` = p.\`SCL_ID\`` | `460,758 = 0 + 460,730 + 0 + 28 + 0` | This tested alternative has zero nonzero matches and is not an approved traversal path. |
| `attendance.bed` | `PATIENT-SENT-028` | `c.\`BED_ID\` = p.\`BED_ID\`` | `51,927 = 6 + 51,785 + 120 + 16 + 0` | Null/zero provides no bed/admission evidence. A nonzero orphan is a bed-reference exception. Even a numeric match is contextual only and may not override `attendance.PAT_ID` or derive a patient by bed reuse. |

For secondary links, a null/zero may be safely treated as “link not evidenced” for Phase 2C chain traversal without asserting a later billing, result or admission semantic. Later domain specifications must still approve their persistence meaning.

Insurance provider evidence is also secondary to the exact patient edge. The Phase 2A candidate predicate is `UPPER(TRIM(c.\`Company\`)) = UPPER(TRIM(p.\`PrivateName\`)) OR UPPER(TRIM(c.\`Company\`)) = UPPER(TRIM(p.\`PrivateShort\`))`, partitioned as `31,307 = 0 null + 25,042 blank/not-evidenced + 2,248 matched + 4,017 unmatched nonblank`. This name predicate may support a provider crosswalk only; it never establishes a patient. The 36 duplicate normalized patient/provider groups covering 88 insurance rows require later D-212/Q-009 consolidation/history review and do not authorize dropping source rows or violating target `(patient_id, insurance_provider_id)` uniqueness.

## Patient-chain blocking scope

| Trigger | Patient row | Alias | Direct/descendant chain |
|---|---|---|---|
| Duplicate OPD only | Candidate if otherwise eligible | Withhold; one exception per affected patient | Not blocked by this flag alone |
| Suspected duplicate only | Separate candidate; never auto-merge | Independent alias outcome | Review flag only; never coalesce chains |
| Missing/invalid/unrepresentable required identity | Quarantine | Preserve candidate/provenance, but no persistence without target owner | Hold the entire patient-rooted chain |
| Existing-target ambiguity/conflict, patient extraction or provenance failure | Quarantine | Hold | Hold the entire patient-rooted chain |
| `attendance.PAT_ID` null/zero/orphan | No artificial patient | Not applicable | Hold attendance and every registered descendant as one attendance-rooted chain |
| `insurance.PAT_ID` orphan | No artificial patient | Not applicable | Hold that insurance row and any evidenced descendants |
| `beds.PAT_ID = 0` | No artificial patient | Not applicable | Occupancy link not evidenced; bed master is not blocked |
| Descendant parent orphan or conflicting secondary path | Unchanged | Unchanged | Hold the affected subchain; never union or reassign roots |

## Target relationship projection

The installed renewed catalogue has 84 actual foreign keys to `patients` across 79 tables: 46 non-null cascade links, 15 nullable cascade links and 23 nullable set-null links. It also has eight patient-shaped columns without a patient FK. This target surface is a persistence/reconciliation constraint, not Classic linkage evidence.

Patient mapping must precede renewed visits, appointments, patient insurances, medical records, claims, invoices, admissions and all clinical children. An admission additionally requires visit and bed; a medical record requires visit, patient and clinician; appointments require patient, department and creator; claims require provider, patient, visit and creator. Every renewed foreign key must resolve through an approved target map. The eight non-FK patient-shaped columns require explicit application-level validation in their later domain contracts; the target merge service's hard-coded table list is not a complete relationship manifest.

## Reconciliation and mandatory zero assertions

Every listed edge receives exactly one mutually exclusive outcome: extraction failure, null, field-approved sentinel or chain-level-not-evidenced value, matched parent, or non-sentinel orphan. Secondary review flags do not double-count the edge partition. Future dry-runs must also prove:

- each row belongs to at most one patient/attendance root;
- children released before an unresolved parent = 0;
- guessed or reassigned patient links = 0;
- artificial patient/reference parents = 0;
- cross-chain unions = 0;
- omitted descendants without a classified outcome = 0;
- notification content imported or used for patient inference = 0;
- dormant maternity rows imported = 0;
- patient links derived from beds, product/catalogue reuse, names, phone or OPD = 0.

## Confirmed evidence gaps

The following are unresolved evidence requirements and fail-closed gates, not permission to infer:

1. No sanitized conditional query counts descendants specifically beneath matched, quarantined and orphan attendance roots. Exact chain sizes and overlap are unknown.
2. The six `serv_results.SCL_ID -> consult_scan_lab.SCL_ID` matches have no same-attendance/patient consistency aggregate.
3. There is no consistency aggregate comparing `consult_prescriptions.ATT_ID` with the attendance reached through a matched billing row.
4. There is no consistency aggregate comparing `attendance.PAT_ID`, `attendance.BED_ID` and `beds.PAT_ID` for the 120 matched bed links.
5. Numeric matches cannot establish semantic ownership for mutable bed occupancy or sparse secondary links.
6. Classic has no admission entity or explicit consultation-route entity. Later grouping remains governed by D-214/Q-011 and D-206/Q-302.
7. `med_pharm`, `med_store` and `notifications` have no evidenced patient predicate. Description or free text may not be mined to invent one.
8. All counts are bound to the captured fingerprint and relationship query hashes. Any schema, row-count, predicate-result or dormant-baseline drift requires read-only re-profiling and approval.

No raw patient identifier or row-level HMAC belongs in this document or any generated repository artifact.
