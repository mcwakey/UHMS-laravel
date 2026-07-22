# Actor relationship register

All 55 Classic tables and 479 columns were scanned. Twenty-eight actor or actor-like columns were classified. `treatment.NurseNote` and `vitals.NurseNote` are clinical text, not staff identity columns.

The canonical per-field predicates, counts, sentinel rules, fallback, blocking scope and extraction dependency are in [staff_relationship_rules.json](specifications/staff_relationship_rules.json) and [staff_sentinel_rules.json](specifications/staff_sentinel_rules.json).

## Numeric Classic user references

| Relationship | Rows | Zero | Matched nonzero | Orphan nonzero |
|---|---:|---:|---:|---:|
| `attendance.USER_ID = users.USER_ID` | 51,927 | 0 | 15,186 | 36,741 |
| `batch.USER_ID = users.USER_ID` | 253 | 0 | 253 | 0 |
| `billing.USER_ID = users.USER_ID` | 111,731 | 0 | 24,545 | 87,186 |
| `consult_prescriptions.USER_ID = users.USER_ID` | 210,902 | 14,144 | 93,823 | 102,935 |
| `consult_scan_lab.USER_ID = users.USER_ID` | 72,680 | 72,680 | 0 | 0 |
| `nurses_note.USER_ID = users.USER_ID` | 29 | 0 | 29 | 0 |
| `request.USER_ID = users.USER_ID` | 146 | 146 | 0 | 0 |
| `treatment.USER_ID = users.USER_ID` | 69 | 0 | 66 | 3 |
| `vitals.USER_ID = users.USER_ID` | 60,803 | 0 | 52,325 | 8,478 |

Null count is zero in all nine fields. Zero is a field-specific candidate sentinel only in the three fields where observed. In every other field, a future zero is drift until reviewed.

## Free-text clinician snapshots

The diagnostic predicate is `UPPER(TRIM(child.Doctor)) = UPPER(TRIM(users.FullName))`. It is never identity proof.

| Field | Rows | Blank | One normalized-name candidate | Ambiguous-name candidate | Unmatched text |
|---|---:|---:|---:|---:|---:|
| `claims.Doctor` | 31,892 | 1,502 | 30,333 | 26 | 31 |
| `consult_complaints.Doctor` | 65,244 | 28,378 | 139 | 0 | 36,727 |
| `consult_diagnosis.Doctor` | 123,940 | 79,422 | 4,588 | 0 | 39,930 |
| `consult_history.Doctor` | 34,532 | 1,755 | 6,782 | 0 | 25,995 |
| `consult_prescriptions.Doctor` | 210,902 | 103,165 | 6,682 | 0 | 101,055 |
| `consult_procedures.Doctor` | 121 | 121 | 0 | 0 | 0 |
| `consult_scan_lab.Doctor` | 72,680 | 32,445 | 2,809 | 0 | 37,426 |
| `consult_services.Doctor` | 60,260 | 60,260 | 0 | 0 | 0 |
| `consult_treatment.Doctor` | 1,926 | 0 | 53 | 0 | 1,873 |

No text value appears in this package. Every apparent name match still requires verified employment evidence. Blank and unmatched text never synthesize a clinician.

## Additional actor-like fields explicitly classified

| Field | Sanitized evidence | Disposition |
|---|---|---|
| `billing.CASH_ID` | 111,731 rows; all zero; no nonzero user match evidence | Evidence-only; no actor predicate approved |
| `notifications.ReqFrom`, `ReqTo` | 141 rows; 7/1 distinct values | No approved destination under D-209; content excluded |
| `mat_family.DocName`, `DocPhone` | 0 rows | Excluded dormant scope; any nonzero row/fingerprint change stops extraction |
| `hospitalinfo.ClaimOfficer`, `ClaimSignature`, `Admn` | 1 nonblank row each | Protected organisation/configuration evidence; no staff destination |
| `consult_diagnosis.MdfId`, `list_diagnosis.MdfId` | 123,940/5,948 rows; all blank | Ambiguous semantics; explicitly not treated as actor evidence |

Classic-parent matches and normalized-name candidates are source evidence classes only. They do not prove employment identity or that a person performed the target action role.
