# Patient extraction specifications

## Contract status

This is a future extraction contract, not executable configuration or authorization to connect to a database. It defines deterministic, read-only extraction for the Classic patient master and the relationship tables required to reconcile patient quarantine chains. It creates no importer, checkpoint table, crosswalk, quarantine ledger or target record.

The only source is exact schema `uuhms`. The approved structural evidence coordinate is:

| Item | Required value |
|---|---|
| Structural schema fingerprint | `150fcf4783fcb8bdc25f7e17fe0ece5050955f0c68e7dd03651ee8bd58498977` |
| Captured structural shape | 55 base tables / 479 columns |
| Fingerprint evidence | `docs/legacy-migration/evidence/CLASSIC_SCHEMA_FINGERPRINT.json` |
| Relationship evidence | `docs/legacy-migration/evidence/CLASSIC_RELATIONSHIP_MANIFEST.json` (77 tested predicates) |
| Classic account prerequisite | D-101 dedicated `SELECT`/metadata-only account scoped to `uuhms` |
| Extraction policy | D-102 and D-210/Q-401/Q-402/Q-403/Q-404 |

The broad-privilege discovery account is not approved for migration execution. Any future run must prove exact connection/database identity, least privilege and session read-only state before the first source query.

## Universal extraction envelope

Every table strategy below uses the same fail-closed envelope:

1. Connect only through the approved Classic connection and verify `DATABASE() = 'uuhms'`.
2. Verify the dedicated account has only approved `SELECT` and metadata access. Reject any write-capable migration runtime.
3. Verify compatible MariaDB version, structural fingerprint, 55-table/479-column shape, required table/column types, primary indexes and query/tool hashes.
4. Start one verified consistent `REPEATABLE READ`, `READ ONLY` transaction/snapshot for a coordinated extraction group. Roll it back/close it without source writes.
5. Select explicitly named installed columns; never use an unversioned `SELECT *` contract. Preserve source types, nulls and multiplicity.
6. Order by the table's installed primary key ascending. A page is complete only after every row and its protected row fingerprint is emitted successfully.
7. Decode Classic `latin1` deliberately, encode canonical text as UTF-8, normalize comparison text to Unicode NFC, and preserve source representation only in protected provenance. Extraction must fail on undecodable or ambiguous text; it must not replace characters silently.
8. Compute a domain-separated `HMAC-SHA-256` row/content fingerprint over the ordered installed columns using explicit column names/types, null markers and length-prefixed values. Record purpose domain, key version and canonicalization version. Never use plain SHA-256 for patient values or Classic keys.
9. Bind each table snapshot to a nonidentifying snapshot identity: structural fingerprint, table-contract version, extraction query ID/version/SHA-256, tool-code hash, database version, transaction/freeze coordinate, row count, ordered primary-key-set digest and ordered protected row-fingerprint aggregate.
10. Emit only aggregate counts, contract IDs and nonsecret schema/query/specification hashes to repository artifacts. Raw PHI, raw Classic identifiers and row-level HMACs remain in the protected operational boundary.
11. A primary-key watermark may find later inserts but does not prove updates or deletes. Unless a future approved CDC/freeze mechanism supplies that proof, ordered full snapshot plus protected content/key comparison remains mandatory.
12. All parent and child snapshots used in one relationship reconciliation must share the same consistent snapshot/freeze coordinate. A temporally inconsistent group is stopped, never merged approximately.

Changing a chunk size must not change the order, canonicalization, snapshot identity, edge partition or reconciliation result. The recommendations below are conservative Phase 3 starting points and require non-production load testing.

## Patient master strategy

| Contract item | `uuhms.patients` requirement |
|---|---|
| Captured rows/schema | 16,950 rows; 24 columns; primary key `PAT_ID`; no secondary index |
| Ordering/stable key | `PAT_ID ASC`; `PAT_ID` is protected source identity only |
| Initial chunk recommendation | 1,000 rows |
| Timestamp candidates | `EditDate`, `RegDate`, `LastVisit` |
| Timestamp reliability | `EditDate` is mutable `ON UPDATE CURRENT_TIMESTAMP`, one-second precision and unindexed; it may assist overlap scans but cannot prove every semantic update or any delete. `RegDate` and `LastVisit` are business/event timestamps, not change watermarks. |
| Required mode | Full ordered snapshot and protected fingerprint across all 24 installed columns |
| Optional assist | Versioned `(EditDate, PAT_ID)` overlap scan followed by complete key/content reconciliation |
| Resume coordinate | Last fully emitted `PAT_ID` plus immutable patient snapshot identity |
| Update detection | Protected row-content comparison; timestamp overlap is advisory only |
| Delete detection | Full ordered primary-key-set comparison only |
| Protected HMAC domains | `patient-source-key-v1` for the typed source key; `patient-row-content-v1` for ordered 24-column content |
| Direct-child dependency | The five direct relationship scans must bind to this exact patient snapshot identity |

PK order is stable but not registration chronology. The captured key range has gaps and observed registration-time regressions, so neither gaps nor PK order may be interpreted as deletions or event sequence. A rerun with changed content resolves the same protected source identity and becomes an update/review outcome; it does not create another patient.

## Direct patient-edge extraction

| Table | Baseline / order / chunk | Timestamp and capability | Required treatment and dependencies |
|---|---|---|---|
| `attendance` | 51,927 rows; `ATT_ID ASC`; 2,000 | `AttDate` is an insert/event candidate only; no reliable update/delete watermark | Full snapshot/hash. Classify exact `attendance.PAT_ID = patients.PAT_ID` first against the identical patient snapshot. Co-snapshot actor field under Phase 2B `STAFF-EXT-002`; descendants follow only after edge reconciliation. |
| `insurance` | 31,307 rows; `INS_ID ASC`; 2,000 | `IssueDate`/`ExpiryDate` are business dates only | Full snapshot/hash. Patient edge precedes Phase 2A provider crosswalk and later membership consolidation. No member/company value may establish a patient. |
| `beds` | 18 rows; `BED_ID ASC`; 500 | `BedDate` is mutable `ON UPDATE`; it cannot prove all updates/deletes | Consume Phase 2A `EXTRACT-BEDS` unchanged: full snapshot/hash mandatory. Separate bed reference master from patient occupancy edge. |
| `mat_family` | 0 rows; `MAFA_ID ASC`; 500 if scope is reopened | `BookDate` event and `FaDOB` business date only | Zero-row snapshot/fingerprint guard only. Any row or fingerprint change stops under D-209/Q-305. Do not extract content into patient artifacts. |
| `mat_obstetrics` | 0 rows; `MAOB_ID ASC`; 500 if scope is reopened | `BirthDate` business date only | Zero-row snapshot/fingerprint guard only. Any row or fingerprint change stops under D-209/Q-305. Do not extract content into patient artifacts. |

All five exact predicates and baseline partitions are normative in `PATIENT_RELATIONSHIP_REGISTER.md`. A relationship partition may be computed only from co-snapshotted parent and child key sets. The future evidence record must include relationship ID, its stable `PATIENT-SENT-*` rule ID, query version/hash, patient snapshot identity, child snapshot identity, null/field-sentinel/matched/orphan/failed counts and zero partition difference.

## Attendance descendants

Every table below requires a full ordered snapshot/hash because no listed business/event timestamp proves both updates and deletes. Extraction follows the co-snapshotted attendance key set and classifies the exact `child.ATT_ID = attendance.ATT_ID` edge before any secondary dependency.

| Table | Baseline / primary order / initial chunk | Timestamp candidate and reliability | Additional extraction dependency |
|---|---|---|---|
| `appointement` | 1,364; `APP_ID ASC`; 1,000 | `AppDate` is event/date-time only | After attendance. There is no Classic creator field; no actor fallback. |
| `billing` | 111,731; `BILL_ID ASC`; 2,000 | `BillDate` is event candidate; `PayDate` is mutable `ON UPDATE` and may assist overlap only | After attendance; co-snapshot Phase 2B `STAFF-EXT-004`; preserve decimal fields independently for Q-101. |
| `claims` | 31,892; `CLAIM_ID ASC`; 2,000 | `ClaimDate` is event candidate; `AdmitClaim`/`DischClaim` are business dates | After attendance; co-snapshot Phase 2B `STAFF-EXT-011`; provider/specialty/status/date/amount evidence remains separate. Claim descendants follow only after claim snapshot. |
| `consult_complaints` | 65,244; `COMP_ID ASC`; 2,000 | `CompDate` is event candidate only | After attendance; later clinician/grouping/content rules. |
| `consult_diagnosis` | 123,940; `DIAG_ID ASC`; 2,000 | `DiagDate` is event candidate only | After attendance; consume Phase 2A diagnosis reference and relevant Phase 2B actor/text contract. |
| `consult_history` | 34,532; `HIST_ID ASC`; 1,000 | `HistDate` is event candidate only | After attendance; protected RTF/plain-text handling and clinician grouping remain later gates. |
| `consult_prescriptions` | 210,902; `PRES_ID ASC`; 2,000 | `PresDate` is event candidate only | After attendance; product reference and actor extraction. `BILL_ID` is a secondary consistency link only. |
| `consult_procedures` | 121; `PRO_ID ASC`; 500 | `ProDate` is event candidate only | After attendance; procedure reference and actor/grouping rules. |
| `consult_scan_lab` | 72,680; `SCL_ID ASC`; 2,000 | `SclDate` is event candidate; `SclUpdate` is mutable `ON UPDATE` and may assist `(SclUpdate,SCL_ID)` overlap only | After attendance; Phase 2B `STAFF-EXT-006` and `STAFF-EXT-017`; Phase 2A service/investigation reference. |
| `consult_services` | 60,260; `REC_ID ASC`; 2,000 | `RecDate` is event candidate only | After attendance; service/reference and clinician/grouping gates. |
| `consult_treatment` | 1,926; `TREATP_ID ASC`; 1,000 | `TreatPDate` is event candidate only | After attendance; later clinician/content rules. |
| `nurses_note` | 29; `NUR_ID ASC`; 500 | `NurDate` is event candidate only | After attendance; co-snapshot Phase 2B `STAFF-EXT-007`. |
| `serv_results` | 460,758; `RES_ID ASC`; 2,000 | No timestamp exists | Full snapshot/hash only after attendance and service/criterion reference snapshots. Direct `ATT_ID` is the root; never resolve patient by `SCL_ID` alone. |
| `treatment` | 69; `TREAT_ID ASC`; 500 | `TreatDate` is event candidate only | After attendance; Phase 2B `STAFF-EXT-009` and Phase 2A product map. |
| `vitals` | 60,803; `VIT_ID ASC`; 2,000 | `VitDate` is mutable `ON UPDATE`; `(VitDate,VIT_ID)` overlap may assist only | Full snapshot/hash after attendance; co-snapshot Phase 2B `STAFF-EXT-010`. |

Each strategy's resume coordinate is the last fully emitted installed primary key plus that table's immutable snapshot identity. Every table has update detection by full protected content comparison and delete detection by full ordered key-set comparison only. Mutable timestamps may reduce a diagnostic scan but never replace the freeze-time full comparison.

## Claim descendants

Each claim-child snapshot follows the identical `claims` snapshot and classifies `child.CLAIM_ID = claims.CLAIM_ID` before catalogue/product dependencies.

| Table | Baseline / primary order / initial chunk | Timestamp reliability | Additional dependency |
|---|---|---|---|
| `claims_diagnosis` | 57,776; `DIAG_ID ASC`; 2,000 | `DiagDate` is event candidate only | Released claim plus Phase 2A diagnosis crosswalk |
| `claims_prescriptions` | 112,152; `PRES_ID ASC`; 2,000 | `PresDate` is event candidate only | Released claim plus Phase 2A product crosswalk |
| `claims_procedures` | 3,648; `PRO_ID ASC`; 1,000 | `ProDate` is event candidate only | Released claim plus Phase 2A procedure crosswalk |
| `claims_scan_lab` | 3; `SCL_ID ASC`; 500 | `SclDate` is event candidate only | Released claim plus approved investigation/procedure reference |

Resume, update and delete rules are the same as attendance descendants: primary key plus immutable snapshot identity, full protected content comparison for updates and full key-set comparison for deletes.

## Secondary-link and excluded-table extraction

The same row snapshots support these exact secondary predicates without a second, temporally different extraction:

- `consult_prescriptions.BILL_ID = billing.BILL_ID`;
- `serv_results.SCL_ID = consult_scan_lab.SCL_ID`;
- tested but unapproved alternative `serv_results.SCL_ID = claims_scan_lab.SCL_ID`;
- `attendance.BED_ID = beds.BED_ID`.

Secondary matches are consistency evidence only. Their extraction output records aggregate edge partitions and protected conflict tokens; it never changes the root selected by direct `PAT_ID`, `ATT_ID` and `CLAIM_ID` traversal.

`notifications` has 141 rows and no `PAT_ID` or `ATT_ID`. Under D-209/Q-303 its strategy is `NOTID ASC`, aggregate row count/fingerprint only; `RecDate` is mutable `ON UPDATE` but is not a patient watermark. No notification content enters extraction artifacts and no patient inference is attempted.

`med_pharm` and `med_store` are governed by Phase 2A stock/product snapshot contracts. They have no patient or attendance predicate and are excluded from the patient-chain extraction set. Their descriptions or billing text must not be mined for linkage.

## Snapshot identity and coordinated resume

A resume coordinate is valid only within one immutable, verified table snapshot. It consists of:

`{table_contract_version, snapshot_identity, last_fully_emitted_primary_key}`

On resume, re-verify the database/schema/query/tool/key/canonicalization versions and snapshot identity before reading the next page. If identity changed, discard only the uncommitted extraction attempt and restart the affected table snapshot from its beginning. Never continue a new snapshot from an old key watermark.

Parent change invalidates dependent relationship reconciliation even when the child table itself appears unchanged:

- changed patient snapshot invalidates all five direct patient-edge partitions;
- changed attendance snapshot invalidates every attendance child and all claim roots;
- changed claims snapshot invalidates all four claim-child partitions;
- changed billing, scan-lab or bed snapshot invalidates its secondary-link reconciliation.

A checkpoint advances only after the complete chunk, protected fingerprints, edge outcomes and aggregate counts have been durably recorded by the future migration foundation. A target commit checkpoint is outside this extraction specification and may advance only after the corresponding source snapshot identity is still valid.

## Initial, incremental and freeze comparison

### Initial/preflight snapshot

- Take full ordered snapshots for `patients`, the five direct child tables and every in-scope descendant table.
- Verify all direct, attendance, claim and secondary edge equations against the captured relationship contract or record a controlled, read-only re-profiled evidence version.
- Produce privacy-safe row counts, primary-key multiplicity, snapshot identities, edge partitions and classified failures.
- Verify `patients.PAT_ID` is present, unique and stable; a duplicate/missing PK is a stop.

### Incremental discovery

- A `PAT_ID`, `ATT_ID`, `INS_ID` or other PK high-water mark may report later inserts only.
- `(EditDate,PAT_ID)`, `(PayDate,BILL_ID)`, `(SclUpdate,SCL_ID)`, `(VitDate,VIT_ID)` and `(BedDate,BED_ID)` overlap scans may report candidate changes only.
- Every candidate delta must resolve against protected source identity and content fingerprint; it may not create a second target parent.
- Because no reliable delete journal exists, a full key/content snapshot comparison remains required before any release/cutover.

### Freeze/final snapshot

- Capture a final coordinated read-only snapshot after the approved staged freeze.
- Recompute full table identities and every edge partition.
- Compare preflight and freeze key/content sets; classify inserts, changed rows and evidenced missing keys without calling a missing key a deletion unless the freeze/source-control contract proves it.
- Re-run all chain and financial reconciliation gates. No critical unexplained delta may remain at go/no-go.

## Privacy and provenance

Protected operational evidence may contain raw source values only when necessary and access-controlled. Documentation, source control, standard logs and screenshots must never contain patient names, OPD numbers, phones, emails, national identifiers, addresses, insurance/member numbers, clinical text, emergency-contact values, raw Classic keys, raw crosswalk values or row-level HMACs.

Every HMAC record includes algorithm, purpose/domain, key version and canonicalization version. Keys never enter source control. Key rotation must preserve controlled resolution of prior crosswalk/checkpoint entries; loss of the applicable key/version is a stop. Schema, query, tool and specification artifacts may use plain SHA-256 because they contain no patient value.

## Preflight and stop conditions

Stop before or during extraction when any of the following occurs:

- connection/database is not exact `uuhms`;
- D-101 least-privilege account or session `READ ONLY` proof is absent;
- database version compatibility, 55-table/479-column shape, structural fingerprint, table/column/index contract or query/tool hash differs without approved read-only re-profiling;
- `patients` is not exactly the expected 24-column table with unique primary key `PAT_ID`;
- an extraction key is null/duplicate, order is unstable, a chunk cannot be reproduced, or snapshot identity changes during resume;
- parent and child snapshots do not share a consistent snapshot/freeze coordinate;
- an edge outcome or field-specific null/zero/orphan class is unclassified, or a partition difference is nonzero;
- the maternity zero-row baseline changes;
- raw PHI, raw identifiers or row-level diagnostics enter documentation/logs/source control;
- HMAC key, key version or canonicalization version is unavailable or changes within a snapshot;
- a PK/timestamp watermark is being used as proof of updates or deletes;
- extraction attempts any Classic DDL/DML or invokes target persistence/operational side effects.

## Evidence gaps that must remain explicit

1. Edge-level aggregates do not count descendant rows specifically beneath matched, quarantined or orphan attendance roots. A sanitized conditional chain-count query/version is required before chain-volume reconciliation.
2. The six nonzero `serv_results.SCL_ID -> consult_scan_lab.SCL_ID` matches lack same-attendance/patient consistency evidence. The claim-scan alternative has no nonzero matches and is not a traversal path.
3. No sanitized aggregate proves that a matched `consult_prescriptions.BILL_ID` resolves to the same attendance/patient as the prescription's direct `ATT_ID`.
4. No sanitized aggregate proves consistency among `attendance.PAT_ID`, `attendance.BED_ID` and `beds.PAT_ID` for the 120 matched bed references.
5. The source has no general change/delete journal. Full protected snapshot/hash and controlled freeze comparison are mandatory.
6. Maternity exclusion has zero-row evidence only; any new row invalidates scope.
7. Some descendant zero values are classified only as “relationship not evidenced for chain traversal.” Their later domain semantics remain unresolved and cannot be promoted to a global sentinel.
8. Exact target patient/alias/archive/sequence collision state is a separate sanitized target preflight prerequisite and is not supplied by Classic extraction.

These gaps prohibit unsafe release or inference; they do not permit dropping a row or attaching it to another chain.
