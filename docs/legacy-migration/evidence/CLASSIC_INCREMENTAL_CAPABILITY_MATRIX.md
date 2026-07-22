# Classic incremental extraction capability matrix

Generated at `2026-07-21T01:57:29+00:00` by the SELECT-only Classic evidence command.

All strategies are **candidates**, not approved synchronization policy. Classic has no general delete/change journal.

| Table | Primary key | Date/timestamp semantics | `ON UPDATE` columns | Candidate extraction treatment | Approved |
|---|---|---|---|---|---|
| `accounts` | `ACC_ID` | `IncDate`: business date; not a row-change watermark; `RegDate`: event/creation timestamp candidate; update semantics are not evidenced | None | (event/creation timestamp, primary key) may detect inserts only; periodic full snapshot required for updates/deletes | No |
| `acc_petty` | `PET_ID` | None | None | primary-key high-water detects inserts only; periodic full snapshot required for updates/deletes | No |
| `acc_titles` | `TI_ID` | None | None | primary-key high-water detects inserts only; periodic full snapshot required for updates/deletes | No |
| `appointement` | `APP_ID` | `AppDate`: event/creation timestamp candidate; update semantics are not evidenced | None | (event/creation timestamp, primary key) may detect inserts only; periodic full snapshot required for updates/deletes | No |
| `attendance` | `ATT_ID` | `AttDate`: event/creation timestamp candidate; update semantics are not evidenced; `AdmitDate`: business date; not a row-change watermark; `DischDate`: business date; not a row-change watermark | None | (event/creation timestamp, primary key) may detect inserts only; periodic full snapshot required for updates/deletes | No |
| `bank` | `BANK_ACC_ID` | `RegDate`: event/creation timestamp candidate; update semantics are not evidenced | None | (event/creation timestamp, primary key) may detect inserts only; periodic full snapshot required for updates/deletes | No |
| `batch` | `BATCH_ID` | `ExpiryDate`: business date; not a row-change watermark; `BatchDate`: schema-managed mutable timestamp candidate; updates may be visible but deletes are not | `BatchDate` | (mutable timestamp, primary key) watermark plus periodic full snapshot; semantics must be approved | No |
| `beds` | `BED_ID` | `BedDate`: schema-managed mutable timestamp candidate; updates may be visible but deletes are not | `BedDate` | (mutable timestamp, primary key) watermark plus periodic full snapshot; semantics must be approved | No |
| `billing` | `BILL_ID` | `BillDate`: event/creation timestamp candidate; update semantics are not evidenced; `PayDate`: schema-managed mutable timestamp candidate; updates may be visible but deletes are not | `PayDate` | (mutable timestamp, primary key) watermark plus periodic full snapshot; semantics must be approved | No |
| `claims` | `CLAIM_ID` | `AdmitClaim`: business date; not a row-change watermark; `DischClaim`: business date; not a row-change watermark; `ClaimDate`: event/creation timestamp candidate; update semantics are not evidenced | None | (event/creation timestamp, primary key) may detect inserts only; periodic full snapshot required for updates/deletes | No |
| `claims_diagnosis` | `DIAG_ID` | `DiagDate`: event/creation timestamp candidate; update semantics are not evidenced | None | (event/creation timestamp, primary key) may detect inserts only; periodic full snapshot required for updates/deletes | No |
| `claims_prescriptions` | `PRES_ID` | `PresDate`: event/creation timestamp candidate; update semantics are not evidenced | None | (event/creation timestamp, primary key) may detect inserts only; periodic full snapshot required for updates/deletes | No |
| `claims_procedures` | `PRO_ID` | `ProDate`: event/creation timestamp candidate; update semantics are not evidenced | None | (event/creation timestamp, primary key) may detect inserts only; periodic full snapshot required for updates/deletes | No |
| `claims_scan_lab` | `SCL_ID` | `SclDate`: event/creation timestamp candidate; update semantics are not evidenced | None | (event/creation timestamp, primary key) may detect inserts only; periodic full snapshot required for updates/deletes | No |
| `claims_specialty` | `SPEC_ID` | None | None | primary-key high-water detects inserts only; periodic full snapshot required for updates/deletes | No |
| `consult_complaints` | `COMP_ID` | `CompDate`: event/creation timestamp candidate; update semantics are not evidenced | None | (event/creation timestamp, primary key) may detect inserts only; periodic full snapshot required for updates/deletes | No |
| `consult_diagnosis` | `DIAG_ID` | `DiagDate`: event/creation timestamp candidate; update semantics are not evidenced | None | (event/creation timestamp, primary key) may detect inserts only; periodic full snapshot required for updates/deletes | No |
| `consult_history` | `HIST_ID` | `HistDate`: event/creation timestamp candidate; update semantics are not evidenced | None | (event/creation timestamp, primary key) may detect inserts only; periodic full snapshot required for updates/deletes | No |
| `consult_prescriptions` | `PRES_ID` | `PresDate`: event/creation timestamp candidate; update semantics are not evidenced | None | (event/creation timestamp, primary key) may detect inserts only; periodic full snapshot required for updates/deletes | No |
| `consult_procedures` | `PRO_ID` | `ProDate`: event/creation timestamp candidate; update semantics are not evidenced | None | (event/creation timestamp, primary key) may detect inserts only; periodic full snapshot required for updates/deletes | No |
| `consult_scan_lab` | `SCL_ID` | `SclDate`: event/creation timestamp candidate; update semantics are not evidenced; `SclUpdate`: schema-managed mutable timestamp candidate; updates may be visible but deletes are not | `SclUpdate` | (mutable timestamp, primary key) watermark plus periodic full snapshot; semantics must be approved | No |
| `consult_services` | `REC_ID` | `RecDate`: event/creation timestamp candidate; update semantics are not evidenced | None | (event/creation timestamp, primary key) may detect inserts only; periodic full snapshot required for updates/deletes | No |
| `consult_treatment` | `TREATP_ID` | `TreatPDate`: event/creation timestamp candidate; update semantics are not evidenced | None | (event/creation timestamp, primary key) may detect inserts only; periodic full snapshot required for updates/deletes | No |
| `departements` | `DEP_ID` | None | None | primary-key high-water detects inserts only; periodic full snapshot required for updates/deletes | No |
| `gens` | `ID` | None | None | primary-key high-water detects inserts only; periodic full snapshot required for updates/deletes | No |
| `hospitalinfo` | `ID` | None | None | primary-key high-water detects inserts only; periodic full snapshot required for updates/deletes | No |
| `insurance` | `INS_ID` | `IssueDate`: business date; not a row-change watermark; `ExpiryDate`: business date; not a row-change watermark | None | (event/creation timestamp, primary key) may detect inserts only; periodic full snapshot required for updates/deletes | No |
| `list_causes` | `LCOS_ID` | None | None | primary-key high-water detects inserts only; periodic full snapshot required for updates/deletes | No |
| `list_complaints` | `LCOMP_ID` | None | None | primary-key high-water detects inserts only; periodic full snapshot required for updates/deletes | No |
| `list_diagnosis` | `LDIAG_ID` | None | None | primary-key high-water detects inserts only; periodic full snapshot required for updates/deletes | No |
| `list_disorder` | `LDIS_ID` | None | None | primary-key high-water detects inserts only; periodic full snapshot required for updates/deletes | No |
| `list_procedures` | `LPRO_ID` | None | None | primary-key high-water detects inserts only; periodic full snapshot required for updates/deletes | No |
| `list_signs` | `LSIGN_ID` | None | None | primary-key high-water detects inserts only; periodic full snapshot required for updates/deletes | No |
| `list_symptoms` | `LSYMP_ID` | None | None | primary-key high-water detects inserts only; periodic full snapshot required for updates/deletes | No |
| `mat_family` | `MAFA_ID` | `BookDate`: event/creation timestamp candidate; update semantics are not evidenced; `FaDOB`: business date; not a row-change watermark | None | (event/creation timestamp, primary key) may detect inserts only; periodic full snapshot required for updates/deletes | No |
| `mat_obstetrics` | `MAOB_ID` | `BirthDate`: business date; not a row-change watermark | None | (event/creation timestamp, primary key) may detect inserts only; periodic full snapshot required for updates/deletes | No |
| `medicine` | `MED_ID` | `RegDate`: event/creation timestamp candidate; update semantics are not evidenced | None | (event/creation timestamp, primary key) may detect inserts only; periodic full snapshot required for updates/deletes | No |
| `med_pharm` | `PHARM_ID` | None | None | primary-key high-water detects inserts only; periodic full snapshot required for updates/deletes | No |
| `med_store` | `STORE_ID` | None | None | primary-key high-water detects inserts only; periodic full snapshot required for updates/deletes | No |
| `notifications` | `NOTID` | `RecDate`: schema-managed mutable timestamp candidate; updates may be visible but deletes are not | `RecDate` | (mutable timestamp, primary key) watermark plus periodic full snapshot; semantics must be approved | No |
| `nurses_note` | `NUR_ID` | `NurDate`: event/creation timestamp candidate; update semantics are not evidenced | None | (event/creation timestamp, primary key) may detect inserts only; periodic full snapshot required for updates/deletes | No |
| `patients` | `PAT_ID` | `DOB`: business date; not a row-change watermark; `LastVisit`: event/creation timestamp candidate; update semantics are not evidenced; `EditDate`: schema-managed mutable timestamp candidate; updates may be visible but deletes are not; `RegDate`: event/creation timestamp candidate; update semantics are not evidenced | `EditDate` | (mutable timestamp, primary key) watermark plus periodic full snapshot; semantics must be approved | No |
| `request` | `REQ_ID` | `ReqDate`: schema-managed mutable timestamp candidate; updates may be visible but deletes are not; `SuppDate`: event/creation timestamp candidate; update semantics are not evidenced | `ReqDate` | (mutable timestamp, primary key) watermark plus periodic full snapshot; semantics must be approved | No |
| `services` | `SERV_ID` | None | None | primary-key high-water detects inserts only; periodic full snapshot required for updates/deletes | No |
| `serv_criterias` | `CRI_ID` | None | None | primary-key high-water detects inserts only; periodic full snapshot required for updates/deletes | No |
| `serv_group` | `GROUP_ID` | None | None | primary-key high-water detects inserts only; periodic full snapshot required for updates/deletes | No |
| `serv_options_cri` | None | None | None | full_snapshot_with_canonical_row_hash; no key checkpoint | No |
| `serv_options_out` | None | None | None | full_snapshot_with_canonical_row_hash; no key checkpoint | No |
| `serv_results` | `RES_ID` | None | None | primary-key high-water detects inserts only; periodic full snapshot required for updates/deletes | No |
| `sett_ocuupation` | `OQP_ID` | None | None | primary-key high-water detects inserts only; periodic full snapshot required for updates/deletes | No |
| `sett_private` | `PINS_ID` | None | None | primary-key high-water detects inserts only; periodic full snapshot required for updates/deletes | No |
| `suppliers` | `SUP_ID` | `RegDate`: schema-managed mutable timestamp candidate; updates may be visible but deletes are not | `RegDate` | (mutable timestamp, primary key) watermark plus periodic full snapshot; semantics must be approved | No |
| `treatment` | `TREAT_ID` | `TreatDate`: event/creation timestamp candidate; update semantics are not evidenced | None | (event/creation timestamp, primary key) may detect inserts only; periodic full snapshot required for updates/deletes | No |
| `users` | `USER_ID` | `RegDate`: event/creation timestamp candidate; update semantics are not evidenced | None | (event/creation timestamp, primary key) may detect inserts only; periodic full snapshot required for updates/deletes | No |
| `vitals` | `VIT_ID` | `VitDate`: schema-managed mutable timestamp candidate; updates may be visible but deletes are not | `VitDate` | (mutable timestamp, primary key) watermark plus periodic full snapshot; semantics must be approved | No |

## Blocking decisions

- D-210/Q-401/Q-402: approve snapshot/change-capture, timestamp semantics, delete detection, tie-breaking and keyless-table identity.
- `serv_options_cri` and `serv_options_out` are keyless and require canonical full-snapshot hashing.
