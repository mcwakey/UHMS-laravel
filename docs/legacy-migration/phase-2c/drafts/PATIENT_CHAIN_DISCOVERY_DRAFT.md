# Phase 2C patient relationship and dependency-chain discovery draft

## Scope and evidence status

This read-only draft covers the Classic patient relationship workstream only. It does not fully map any Phase 2D/2E or later child domain and does not authorize import, target persistence, or source writes.

Authorities consumed:

- `AGENTS.md`.
- `APPROVED_DECISION_SPECIFICATIONS.md` and `DECISIONS.md`, especially D-201, D-207, D-209, D-211, D-213, Q-001, Q-002, Q-006, Q-007 and Q-008.
- `CLASSIC_SCHEMA_MANIFEST.json`, `CLASSIC_RELATIONSHIP_MANIFEST.json`, `CLASSIC_AGGREGATE_RESULTS.json`, `CLASSIC_INCREMENTAL_CAPABILITY_MATRIX.md`, `LEGACY_RELATIONSHIPS.md`, `LEGACY_DATA_QUALITY_REPORT.md`, `LEGACY_DATABASE_INVENTORY.md`, `MIGRATION_DEPENDENCIES.md` and `PHASE_2_ENTRY_REPORT.md`.
- Phase 2A reference relationship, sentinel, reconciliation and extraction contracts.
- Phase 2B actor relationship, sentinel, reconciliation, extraction and downstream dependency contracts.
- The installed target schema/constraint manifests and target required-field, creation-rule and side-effect reports.

Evidence coordinate: the Classic relationship manifest was captured at `2026-07-21T01:57:29+00:00`, is bound to source schema fingerprint `150fcf4783fcb8bdc25f7e17fe0ece5050955f0c...`, and contains 77 tested predicates. The complete fingerprint must be consumed from the manifest rather than from this shortened human-readable reference.

Confirmed facts are distinguished from proposed Phase 2C contract behavior below. Classic declares **zero foreign keys**. Consequently every relationship is inferred, even where a primary-key-shaped parent and matching values exist. No name, phone, OPD, member number, free text, or other weak field is used to infer a patient link.

## Patient master and complete direct relationship inventory

The Classic patient master is `uuhms.patients`, primary key `PAT_ID`, with 16,950 captured rows. `PAT_ID` is schema-primary-key unique. Any duplicate parent key or missing parent primary key in a later snapshot is schema/fingerprint drift and a fail-closed stop, not a duplicate-parent resolution case.

The installed 55-table/479-column Classic schema contains exactly five `PAT_ID` child columns outside `patients`; therefore the authoritative direct patient relationship inventory is complete at five relationships.

| Relationship ID | Exact inferred predicate | Child partition: rows = null + candidate zero + matched + nonzero orphan | Null/zero and orphan behavior required for Phase 2C | Patient-chain scope |
|---|---|---:|---|---|
| `attendance.patient` | `c.\`PAT_ID\` = p.\`PAT_ID\`` | `51,927 = 0 + 155 + 48,589 + 3,183` | `PAT_ID` is nullable in schema but no null was observed. Specify null and zero separately as “patient relationship not evidenced”; both root an attendance-chain quarantine. A nonzero unmatched value is an orphan parent reference and roots the same kind of quarantine with a distinct reason. Never create or guess a patient. | A valid patient quarantine blocks the attendance and all descendants. A missing attendance parent relationship creates an attendance-rooted chain, not a patient-rooted chain. |
| `insurance.patient` | `c.\`PAT_ID\` = p.\`PAT_ID\`` | `31,307 = 0 + 0 + 30,991 + 316` | No null or zero was observed. Do not approve an unobserved zero sentinel silently; a later null/zero is drift requiring classification. Each nonzero orphan insurance row is quarantined as an insurance-rooted patient-link exception and is never reassigned. | Patient quarantine blocks matched membership rows. An orphan blocks only that insurance row and any evidenced descendants; none are registered. |
| `beds.patient` | `c.\`PAT_ID\` = p.\`PAT_ID\`` | `18 = 0 + 15 + 3 + 0` | Phase 2A already approves only `PAT_ID = 0` for this field as “relationship not evidenced.” It is not a patient and does not create one. No nonzero orphan was observed. Future null/nonzero orphan values fail closed. | Quarantine/release the occupancy snapshot/link, not the bed reference master. Do not pull unrelated encounters into a patient chain by traversing backwards through a reused bed. |
| `mat_family.patient` | `c.\`PAT_ID\` = p.\`PAT_ID\`` | `0 = 0 + 0 + 0 + 0` | Empty baseline; no sentinel behavior can be evidenced. Any nonzero row count or changed fingerprint is a D-209/Q-305 stop requiring scope review. | Excluded dormant module; no operational patient chain at the captured baseline. |
| `mat_obstetrics.patient` | `c.\`PAT_ID\` = p.\`PAT_ID\`` | `0 = 0 + 0 + 0 + 0` | Empty baseline; no sentinel behavior can be evidenced. Any nonzero row count or changed fingerprint is a D-209/Q-305 stop requiring scope review. | Excluded dormant module; no operational patient chain at the captured baseline. |

For each direct relationship, the reconciliation equation is:

`child_rows = null_reference + field_approved_sentinel + matched_patient + orphan_patient + failed_extraction`

At the captured evidence coordinate `failed_extraction = 0`. “Candidate zero” above reproduces the evidence-manifest partition; it is not an approval except for `beds.PAT_ID`, whose Phase 2A contract explicitly approves zero as relationship-not-evidenced. Phase 2C must issue field-specific codes so null, zero, nonzero orphan, fingerprint failure and chain quarantine remain separately auditable.

## Chain identifiers and traversal invariants

The future quarantine ledger is protected data and is not implemented in Phase 2C. The specification should require an opaque, stable chain token formed with domain-separated HMAC-SHA-256, including canonicalization/key versions, rather than exposing a Classic primary key:

- patient-rooted: domain `legacy-patient-chain` over the protected Classic `patients.PAT_ID`;
- attendance-rooted orphan: domain `legacy-attendance-chain` over `attendance.ATT_ID`;
- insurance-rooted orphan: domain `legacy-insurance-chain` over `insurance.INS_ID`;
- bed-occupancy-rooted orphan if later observed: domain `legacy-bed-occupancy-chain` over `beds.BED_ID`.

Raw Classic keys stay only in the protected operational crosswalk/quarantine provenance. Documentation, routine logs and reconciliation exports use aggregates and opaque tokens.

Traversal invariants:

1. The direct `PAT_ID` relationship is authoritative for chain membership where it exists. No identity-like field may replace it.
2. An attendance child with `ATT_ID` derives its root only through the exact child-to-`attendance.ATT_ID` predicate. A secondary `BILL_ID`, `SCL_ID`, product, catalogue, user or bed link must not merge two patient chains.
3. A claim child derives its encounter root through `child.CLAIM_ID -> claims.CLAIM_ID -> attendance.ATT_ID`.
4. If a row has two evidenced paths that resolve to different attendances/patients, record a cross-link conflict, quarantine the affected subchain, and do not union, reassign or select a “better” path.
5. Reverse traversal through shared reference masters is prohibited. In particular, do not traverse patient -> `beds.PAT_ID` -> every `attendance.BED_ID`, patient -> product -> other prescriptions, or patient -> service/catalogue -> other encounters.
6. Reference rows such as beds, providers, medicines, services and clinical catalogues can be extracted/mapped independently. Only their patient-specific use/occupancy link is held with the chain.
7. A duplicate OPD alias exception or suspected-duplicate review flag alone does not quarantine the patient entity or dependency chain. Missing/invalid required patient identity, ambiguous existing-target linkage, patient extraction failure, or a missing patient parent does.

## Attendance-rooted dependency closure

The attendance relationship is the principal patient-to-encounter bridge. Its registered immediate children and their exact captured partitions are below. “Zero” means the manifest’s field candidate-zero partition; Phase 2C/later domain specifications must approve each field separately and may not generalize a zero rule.

| Domain / relationship | Exact predicate | Rows = null + zero + matched + orphan | Chain behavior |
|---|---|---:|---|
| Appointment: `appointement.attendance` | `c.\`ATT_ID\` = p.\`ATT_ID\`` | `1,364 = 0 + 1 + 1,297 + 66` | Matched rows inherit attendance chain. The 67 unmatched/sentinel-inclusive rows are appointment-root exceptions until a valid attendance/patient/required derivation is established. |
| Billing: `billing.attendance` | `c.\`ATT_ID\` = p.\`ATT_ID\`` | `111,731 = 0 + 30 + 108,857 + 2,844` | Matched rows inherit attendance chain. Missing/orphan links block the billing row from operational finance/opening treatment; preserve financial facts for exception reconciliation. |
| Claims: `claims.attendance` | `c.\`ATT_ID\` = p.\`ATT_ID\`` | `31,892 = 0 + 0 + 30,103 + 1,789` | Matched claims and all claim descendants inherit attendance chain. Orphan claims root claim subchains and cannot post or be assigned to a patient. |
| Complaints: `consult_complaints.attendance` | `c.\`ATT_ID\` = p.\`ATT_ID\`` | `65,244 = 0 + 0 + 62,886 + 2,358` | Matched rows inherit attendance consultation aggregate; orphans quarantine as clinical subchains. |
| Diagnoses: `consult_diagnosis.attendance` | `c.\`ATT_ID\` = p.\`ATT_ID\`` | `123,940 = 0 + 4 + 122,975 + 961` | Same; catalogue/clinician validity remains an additional gate. |
| Consultation history: `consult_history.attendance` | `c.\`ATT_ID\` = p.\`ATT_ID\`` | `34,532 = 0 + 10 + 33,992 + 530` | Same; RTF/plain-text safety and clinician grouping remain later gates. |
| Prescriptions: `consult_prescriptions.attendance` | `c.\`ATT_ID\` = p.\`ATT_ID\`` | `210,902 = 0 + 9 + 208,591 + 2,302` | Patient-linked pharmacy chain. Product, actor, department and billing links are secondary dependencies, never alternate patient evidence. |
| Procedures: `consult_procedures.attendance` | `c.\`ATT_ID\` = p.\`ATT_ID\`` | `121 = 0 + 0 + 120 + 1` | Matched rows inherit attendance chain; procedure reference remains an additional gate. |
| Investigation/order: `consult_scan_lab.attendance` | `c.\`ATT_ID\` = p.\`ATT_ID\`` | `72,680 = 0 + 0 + 71,390 + 1,290` | Matched orders inherit attendance chain; service and clinician dependencies remain gates. |
| Other services: `consult_services.attendance` | `c.\`ATT_ID\` = p.\`ATT_ID\`` | `60,260 = 0 + 2 + 57,576 + 2,682` | Matched rows inherit attendance chain; service dependency remains a gate. |
| Treatment plan: `consult_treatment.attendance` | `c.\`ATT_ID\` = p.\`ATT_ID\`` | `1,926 = 0 + 1 + 1,893 + 32` | Matched rows inherit consultation chain; clinician/content validity remains a gate. |
| Nursing note: `nurses_note.attendance` | `c.\`ATT_ID\` = p.\`ATT_ID\`` | `29 = 0 + 0 + 29 + 0` | Inherits attendance chain; actor and content handling remain gates. |
| Results: `serv_results.attendance` | `c.\`ATT_ID\` = p.\`ATT_ID\`` | `460,758 = 0 + 1 + 454,900 + 5,857` | The direct attendance edge is the result’s patient-chain root. Empty/malformed results are not synthesized. Secondary `SCL_ID` is consistency evidence only. |
| Administered treatment: `treatment.attendance` | `c.\`ATT_ID\` = p.\`ATT_ID\`` | `69 = 0 + 2 + 52 + 15` | Patient-linked medication/treatment chain; product and actor dependencies remain gates. |
| Vitals: `vitals.attendance` | `c.\`ATT_ID\` = p.\`ATT_ID\`` | `60,803 = 0 + 1,198 + 57,019 + 2,586` | Matched rows inherit attendance chain; actor/measurement validity remains a later gate. |

These are edge-level aggregates, not disjoint chain cardinalities. The evidence does not show how many rows from different child tables overlap on the same attendance or how many descendants belong to the 3,338 attendance rows with missing/sentinel-inclusive patient links. Phase 2C must not invent those counts.

## Claim descendants

| Relationship | Exact predicate | Rows = null + zero + matched + orphan | Quarantine/release dependency |
|---|---|---:|---|
| `claims_diagnosis.claim` | `c.\`CLAIM_ID\` = p.\`CLAIM_ID\`` | `57,776 = 0 + 0 + 57,507 + 269` | Requires released claim, attendance and patient chain, then approved diagnosis reference. |
| `claims_prescriptions.claim` | `c.\`CLAIM_ID\` = p.\`CLAIM_ID\`` | `112,152 = 0 + 0 + 107,411 + 4,741` | Patient-linked pharmacy claim component; requires released claim and product reference. |
| `claims_procedures.claim` | `c.\`CLAIM_ID\` = p.\`CLAIM_ID\`` | `3,648 = 0 + 0 + 3,629 + 19` | Requires released claim and procedure reference. |
| `claims_scan_lab.claim` | `c.\`CLAIM_ID\` = p.\`CLAIM_ID\`` | `3 = 0 + 0 + 3 + 0` | Requires released claim and procedure/investigation reference. |

Claim descendants must not release before their claim. A claim must not release/post before its attendance-to-patient link, provider/specialty/status/date rules, actor contract and exact Q-102 component reconciliation are satisfied. `ClaimTotal` and independent service/investigation/pharmacy components remain separate facts; a patient-link repair does not waive a financial mismatch.

## Cross-links and secondary clinical/billing dependencies

These relationships do not establish a patient but affect subchain consistency and release:

| Relationship | Exact predicate | Rows = null + zero + matched + orphan | Required interpretation |
|---|---|---:|---|
| `consult_prescriptions.billing` | `c.\`BILL_ID\` = p.\`BILL_ID\`` | `210,902 = 0 + 91,329 + 116,620 + 2,953` | Root the prescription through its own `ATT_ID`. Use `BILL_ID` only to validate/attach compatible finance evidence. If prescription and bill resolve to different attendances/patients, quarantine the cross-link; do not merge chains. |
| `serv_results.scan_lab` | `c.\`SCL_ID\` = p.\`SCL_ID\`` | `460,758 = 0 + 460,730 + 6 + 22` | Root result through its direct `ATT_ID`; this sparse candidate link cannot override it. Require same-chain agreement for use. |
| `serv_results.claim_scan_lab_alternative` | `c.\`SCL_ID\` = p.\`SCL_ID\`` | `460,758 = 0 + 460,730 + 0 + 28` | Tested alternative has zero nonzero matches and is not an approved traversal path. Do not infer claim-result linkage from this field. |
| `attendance.bed` | `c.\`BED_ID\` = p.\`BED_ID\`` | `51,927 = 6 + 51,785 + 120 + 16` | A bed is contextual admission/occupancy evidence only. Null/zero is relationship-not-evidenced only after field approval; nonzero orphan is a bed-reference exception. Do not infer admission solely from a bed link or use bed reuse to join patients. |

Other registered reference dependencies that gate later release include `consult_*`/`claims_*` links to services, medicines, procedures, diagnoses, criteria and departments. Their Phase 2A crosswalks must be consumed; missing references never create synthetic catalogue parents.

## Domain-specific chain conclusions

### Insurance

`patients -> insurance` is direct. The captured 31,307 insurance rows include 316 patient orphans, 17,029 blank member numbers, 25,042 blank company values, 21,444 blank scheme values, 1,019 duplicate member-number groups covering 2,555 rows, and 36 duplicate normalized patient/provider groups covering 88 rows.

The provider-name candidate predicate in the relationship manifest is:

`UPPER(TRIM(c.\`Company\`)) = UPPER(TRIM(p.\`PrivateName\`)) OR UPPER(TRIM(c.\`Company\`)) = UPPER(TRIM(p.\`PrivateShort\`))`

Its aggregate partition is `31,307 = 0 null + 25,042 blank/candidate-sentinel + 2,248 matched + 4,017 unmatched nonblank`. This natural-name predicate is provider evidence only and does not establish a patient. Q-009 permits one current patient/provider membership only when deterministic, while preserving every source row in protected history/provenance. Target `(patient_id, insurance_provider_id)` uniqueness means the 36 duplicate groups require later consolidation/history review; it does not authorize discarding rows.

Release order: patient mapping -> direct insurance patient-link reconciliation -> provider crosswalk -> member/date/status/current-membership rules -> uniqueness conflict review -> target membership/history candidate. Patient-orphan insurance rows remain insurance-root quarantines until the exact patient link is remediated.

### Attendance, consultations, diagnoses, prescriptions, investigations/results, vitals, notes and treatments

All listed Classic clinical tables reach patients through `attendance.PAT_ID`; none contains a direct `PAT_ID`. The required release topology is:

`patient -> attendance/visit candidate -> evidence-backed consultation route/medical record -> clinical child -> child reference/result`

D-214/Q-011 allows one historical consultation grouping per attendance only when one clinician and compatible department/service context are evidenced. Phase 2B therefore remains a hard dependency: attendance has 15,186 matched and 36,741 orphan Classic user references; clinician text fields are diagnostic only and cannot match by name. Consultation routes block if clinician grouping is ambiguous. Actor/reference/content errors can keep a child or consultation aggregate quarantined after the patient relationship itself is repaired.

### Appointments

Classic `appointement` has no direct patient, department or creator field. It may inherit a patient only through a valid `ATT_ID` relationship. Of 1,364 rows, 1,297 attendance links match and 67 are zero/orphan combined. Target appointment creation additionally requires department, date/time and creator; Phase 2B says no Classic actor exists and required `appointments.created_by` cannot use `Legacy Actor Unknown`. Thus a valid patient/attendance chain is necessary but not sufficient for release.

### Billing and claims

Billing and claims are attendance descendants. Repairing the patient link never authorizes financial posting. Billing must also resolve its department/actor and reconcile `Bill - Discount - Paid` against reported `Balance` to tolerance 0.01. Phase 2B records `billing.USER_ID`: `111,731 = 0 null + 0 zero + 24,545 matched + 87,186 orphan`; target invoice creator has no permitted financial fallback. Claims require their own provider/specialty/status/date/actor and Q-102 component reconciliation. Preserve all mismatches as explicit exceptions.

### Admissions and beds

Classic has no admission table. Admission candidates are properties of attendance (`AdmitDate`, `DischDate`, status fields and `BED_ID`) plus separately mutable `beds` occupancy snapshots. Equal admission/discharge dates do not prove admission. The patient chain must never be derived from a bed. `beds.PAT_ID` has three matched occupancy snapshots and fifteen approved zero “relationship not evidenced” values; `attendance.BED_ID` has six null, 51,785 zero candidates, 120 matches and 16 nonzero orphans.

Release order for an admission candidate: patient -> attendance -> verified admitted actor (Phase 2B says required and no unknown fallback) -> corroborated admission evidence/date chronology -> mapped bed/ward reference -> admission candidate. The bed reference master may migrate independently, but occupancy state remains held with its patient/admission chain. Current target bed occupancy must be reconciled only at controlled cutover; historical services must not occupy/release beds.

### Patient-linked pharmacy versus stock snapshots

Patient-linked Classic medication evidence is limited to:

- `consult_prescriptions -> attendance -> patient`;
- `claims_prescriptions -> claims -> attendance -> patient`;
- `treatment -> attendance -> patient`.

`med_pharm` and `med_store` contain `MED_ID` but no `PAT_ID` or `ATT_ID`. They are stock/price snapshots, not patient chains. The relationship manifest provides no safe patient predicate for them. `billing.BillDesc` text must not be mined to invent a pharmacy-patient relationship. Patient-linked medication release additionally depends on the Phase 2A product crosswalk and later pharmacy/clinical rules; stock movement must never be triggered.

### Notifications

`notifications` has 141 rows and no `PAT_ID` or `ATT_ID`; its only tested relationships are department fields, both all-zero candidate sentinels. There is no evidenced patient traversal. D-209/Q-303 excludes all Classic notification content from renewed operational tables and permits only non-identifying aggregate reconciliation. Never inspect free text to infer a patient and never trigger historical notifications.

### Dormant maternity

`mat_family` and `mat_obstetrics` are both empty and have direct but zero-cardinality patient predicates. D-209/Q-305 excludes them at this baseline. A nonzero row count or fingerprint change before cutover stops the pipeline for renewed scope/privacy review; it must not be folded silently into a patient chain.

## Quarantine blocking and ordered release contract

| Trigger | Blocking scope | Release requirement |
|---|---|---|
| Duplicate OPD alias only | Alias only; patient and valid children remain candidates | Controlled post-migration alias ownership review; no merge/decorated alias. |
| Suspected duplicate patient only | Review flag only; no automatic merge and no chain coalescence | Controlled target identity-review workflow. |
| Missing/invalid required patient identity, unresolved target uniqueness/identity ambiguity, patient extraction/provenance failure | Patient row and entire patient-root dependency chain | Evidenced remediation or controlled identity determination, successful patient mapping/crosswalk, then direct edge reconciliation. |
| Null/zero/nonzero-orphan `attendance.PAT_ID` | Attendance and every registered descendant, including appointment, clinical, billing, claim, admission and medication subchains | Approved exact patient relationship; then release attendance before descendants. |
| Orphan `insurance.PAT_ID` | Insurance row and evidenced insurance descendants only | Approved exact patient relationship, then provider/membership gates. |
| `beds.PAT_ID = 0` | Occupancy link is not evidenced; bed master itself is not blocked | No release as patient occupancy without new approved evidence. |
| Child-to-attendance or claim-to-claim orphan | That child/subchain; never reassign to another patient | Exact missing parent remediation, same-chain consistency, and all domain prerequisites. |
| Conflicting secondary path (`BILL_ID`, `SCL_ID`, bed) | Conflicting link/subchain; do not union patient chains | Evidence-backed conflict resolution proving one consistent root. |

An unresolved patient or attendance root prohibits partial descendant release. Once the exact patient relationship and parent mapping are established, release is topological rather than all-at-once: parent patient first, direct attendance/insurance/occupancy link second, encounter/claim/consultation aggregate next, and descendants last. A child may remain in its own domain quarantine for actor, catalogue, chronology, content, finance or target-constraint reasons; repairing the patient link does not waive those gates.

For every release event, reconcile the original chain token, every edge outcome and descendant count before/after. Required zero outcomes include children released before unresolved parent = 0, reassigned or guessed patient links = 0, artificial patients = 0, cross-chain unions = 0, and descendants omitted without a classified outcome = 0.

## Reconciliation dependencies

Phase 2C should create one mutually exclusive relationship reconciliation contract for each edge listed above. Aggregate edge equations are necessary but insufficient. Future dry-run must also produce:

- count of patient-root chains held/released;
- count of attendance-root orphan chains by null, zero and nonzero orphan reason;
- descendant row counts per opaque chain token, without raw identifiers;
- cross-link conflict counts for prescription/bill, result/order and attendance/bed paths;
- per-domain held/released/failed partitions;
- proof that a row belongs to no more than one patient/attendance root;
- proof that all source rows receive exactly one primary disposition while flags remain secondary;
- before/after target counts and hashes with no unexplained delta;
- separate financial equations and target sequence/uniqueness checks.

The target manifest confirms that renewed visits, appointments, patient insurances, claims, invoices, medical records and many clinical tables use patient foreign keys; admissions additionally require patient, visit and bed. These target foreign keys are persistence prerequisites, not Classic linkage evidence. All target IDs must resolve through protected crosswalks. Classic PK reuse is prohibited.

## Extraction and change-detection dependencies

All patient-chain extraction must use the approved `uuhms` read-only guard, schema fingerprint, query/tool hashes, an ordered canonical full snapshot and deterministic protected row/content hashes. No relevant Classic table has reliable delete detection. A primary-key or timestamp watermark may assist insert/update discovery but never replaces freeze-time full comparison.

| Table group | Stable order/key | Timestamp capability | Required dependency treatment |
|---|---|---|---|
| `patients` | `PAT_ID ASC` | `EditDate` is mutable `ON UPDATE`; `RegDate`/`LastVisit` are event/business candidates | Parent snapshot first. `(EditDate,PAT_ID)` overlap may detect changes; full snapshot/hash detects missed updates/deletes. All direct relationship extraction is bound to the identical patient snapshot identity. |
| `insurance` | `INS_ID ASC` | `IssueDate`/`ExpiryDate` are business dates, not row-change watermarks | Full snapshot/hash; classify patient edge only after patient snapshot, provider only after Phase 2A provider crosswalk. |
| `attendance` | `ATT_ID ASC` | `AttDate` insert/event candidate; no reliable update watermark | Full snapshot/hash; patient edge first, Phase 2B actor dependency `STAFF-EXT-002`, then descendant extraction. |
| `appointement` | `APP_ID ASC` | `AppDate` event candidate only | Full snapshot/hash after attendance snapshot; no actor field and no fallback. |
| `beds` | `BED_ID ASC` | `BedDate` mutable `ON UPDATE` | Consume Phase 2A `EXTRACT-BEDS`; full snapshot/hash mandatory. Separate reference master from occupancy link. |
| `billing` | `BILL_ID ASC` | `PayDate` mutable `ON UPDATE`; `BillDate` event candidate | Full snapshot/hash; attendance edge and Phase 2B `STAFF-EXT-004`; exact decimal reconciliation. |
| `claims` | `CLAIM_ID ASC` | `ClaimDate` event candidate only; admission/discharge are business dates | Full snapshot/hash after attendance; Phase 2B text actor dependency `STAFF-EXT-011`; claim descendants after claims. |
| `claims_diagnosis`, `claims_prescriptions`, `claims_procedures`, `claims_scan_lab` | `DIAG_ID`, `PRES_ID`, `PRO_ID`, `SCL_ID` ascending | respective event timestamp only | Full snapshot/hash after claims; require Phase 2A diagnosis/product/procedure crosswalks. |
| `consult_complaints`, `consult_diagnosis`, `consult_history`, `consult_prescriptions`, `consult_procedures`, `consult_services`, `consult_treatment` | respective PK ascending | event timestamps only | Full snapshot/hash after attendance; consume Phase 2B actor/text extraction contracts and Phase 2A references. |
| `consult_scan_lab` | `SCL_ID ASC` | `SclUpdate` mutable `ON UPDATE`; `SclDate` event candidate | Full snapshot/hash; `(SclUpdate,SCL_ID)` overlap can assist only. Phase 2B `STAFF-EXT-006`/`STAFF-EXT-017`; service crosswalk required. |
| `serv_results` | `RES_ID ASC` | no timestamp | Full snapshot/hash only after attendance and investigation/reference snapshots. No `SCL_ID`-only patient resolution. |
| `nurses_note` | `NUR_ID ASC` | `NurDate` event candidate only | Full snapshot/hash after attendance; Phase 2B `STAFF-EXT-007`. |
| `treatment` | `TREAT_ID ASC` | `TreatDate` event candidate only | Full snapshot/hash after attendance; Phase 2B `STAFF-EXT-009`; product crosswalk. |
| `vitals` | `VIT_ID ASC` | `VitDate` mutable `ON UPDATE` | Full snapshot/hash; overlap may assist only; Phase 2B `STAFF-EXT-010`. |
| `mat_family`, `mat_obstetrics` | `MAFA_ID`, `MAOB_ID` ascending | event/business dates only | Zero-row full snapshot/fingerprint guard; any row is a stop, not an incremental import candidate. |
| `notifications` | `NOTID ASC` | `RecDate` mutable `ON UPDATE` | Aggregate count/fingerprint only for exclusion; do not extract content into patient-chain artifacts. |

Resume coordinates are valid only within one immutable verified snapshot: last completely emitted primary key plus snapshot identity. If snapshot identity changes, restart the affected extraction and descendant reconciliation; do not continue from a stale watermark. All patient/child snapshots used in one relationship reconciliation must be captured under a consistent freeze/snapshot coordinate or be explicitly classified as temporally inconsistent and stopped.

## Evidence gaps and stop conditions

1. The evidence has edge aggregates but no sanitized conditional query that counts descendants belonging specifically to matched, quarantined or orphan attendance roots. Exact chain sizes and overlap are unknown.
2. Except for `beds.PAT_ID`, Phase 2A did not approve the direct patient sentinels. Phase 2C must bind explicit field-specific null/zero behavior and codes; the Phase 1B “candidate sentinel” label alone is not authority.
3. No direct patient creator exists in Classic. Phase 2B authoritatively requires `patients.registered_by = null` plus protected provenance because target nullability permits it; `Legacy Actor Unknown`, importer/current/admin/first-user fallbacks are prohibited.
4. The manifest cannot prove semantic ownership merely from numeric matches. This particularly affects mutable bed occupancy and sparse `SCL_ID` cross-links.
5. The six `serv_results.SCL_ID -> consult_scan_lab.SCL_ID` matches have no same-attendance/patient consistency aggregate. The claim-scan alternative matches zero. Neither may establish patient identity without a new sanitized consistency query/specification.
6. No sanitized conflict aggregate tests whether `consult_prescriptions.ATT_ID` and its matched billing row’s `ATT_ID` agree. This must be measured before linking prescription finance evidence.
7. No sanitized consistency aggregate tests `attendance.PAT_ID`, `attendance.BED_ID` and `beds.PAT_ID` for the 120 matched bed links. Bed-based chain traversal remains prohibited.
8. Classic has no admission entity or explicit consultation route. Grouping and admission evidence remain later-phase decisions governed by Q-302 and D-214/Q-011.
9. No source relationship links `med_pharm`, `med_store` or `notifications` to a patient. Any attempt to infer one from descriptions/names/content is prohibited.
10. Maternity has zero-row evidence only. A changed fingerprint/nonzero count invalidates the dormant exclusion baseline.
11. Counts are tied to the captured fingerprint. Any row-count, schema, query hash, source version or relationship-partition drift must fail closed and be re-profiled read-only.
12. The source has no general change/delete journal. PK/timestamp watermarks cannot prove updates or deletions; full snapshot/hash and freeze comparison remain mandatory.

## Consolidation requirements for final Phase 2C artifacts

The final `PATIENT_RELATIONSHIP_REGISTER`, sentinel rules, dependency-chain rules, exception catalogue, reconciliation contracts and extraction strategies should preserve every exact predicate/count above and cross-reference:

- one stable relationship ID and sentinel-rule ID per edge;
- field-specific null, zero and nonzero-orphan codes;
- patient-root, attendance-root, insurance-root and occupancy-root quarantine classes;
- chain token privacy/HMAC contract;
- explicit topological release and no-partial-release rule;
- Phase 2A reference/extraction contract IDs and Phase 2B actor/extraction contract IDs;
- separate patient-link and domain-specific blocking outcomes;
- future sanitized queries for the three cross-link consistency gaps and conditional chain counts;
- source fingerprint/query/result hashes and extraction snapshot identity;
- zero automatic merges, guessed links, artificial parents, cross-chain unions, notification content imports and dormant maternity imports.

This draft contains no raw patient identifiers, names, OPD values, phone numbers, member numbers, clinical text or notification content.
