# Legacy relationships

## Relationship model

**Confirmed:** `uuhms` declares zero foreign keys. Every relationship below is inferred from column names, matching values, and domain structure. An “orphan” is a non-null child value with no matching inferred parent; counts include zero/sentinel values where applicable. These results prove mapping exceptions, not necessarily source corruption.

## Phase 1B machine registry

`evidence/CLASSIC_RELATIONSHIP_MANIFEST.json` is now the authoritative rerunnable relationship evidence: 77 tested predicates, each with its exact join predicate, candidate sentinel rule, null/zero/matched/orphan counts, query hash, binding hash, observation time and result hash. It includes zero-orphan results and unsuccessful alternatives; inclusion does not approve a relationship. In particular, the alternative `serv_results.SCL_ID -> claims_scan_lab.SCL_ID` matches no nonzero rows, natural-key department/provider joins remain hypotheses, and `billing.CASH_ID` plus `medicine.GEN_ID` still have no evidenced parent.

## Principal inferred dependency graph

```text
patients
  -> insurance
  -> attendance
       -> appointement, billing, claims
       -> consult_*, vitals, nurses_note, treatment, serv_results
       -> beds (current/snapshot association)

departements -> users, services/medicine, billing, requests
users -> attendance, billing, clinical entries, stock/request activity
services -> serv_group -> serv_criterias -> serv_options_cri
services -> serv_options_out, consult_scan_lab/services, serv_results
medicine -> consult/claim prescriptions, pharmacy/store/batch/request/treatment
claims -> claim diagnosis/prescription/procedure/scan-lab children
```

## High-impact inferred orphan counts

| Child reference | Candidate parent | Orphans / child rows | Interpretation status |
|---|---|---:|---|
| `attendance.PAT_ID` | `patients.PAT_ID` | 3,338 / 51,927 | Critical; includes 155 zero IDs |
| `attendance.USER_ID` | `users.USER_ID` | 36,741 / 51,927 | Critical actor gap |
| `attendance.BED_ID` | `beds.BED_ID` | 51,801 / 51,927 | Likely sentinel/not-admitted semantics; do not treat as FK blindly |
| `appointement.ATT_ID` | `attendance.ATT_ID` | 67 / 1,364 | Nullable link requires classification |
| `billing.ATT_ID` | `attendance.ATT_ID` | 2,874 / 111,731 | Financial orphan exception |
| `billing.SERV_ID` | `services.SERV_ID` | 111,731 / 111,731 | All zero; not a usable service FK |
| `billing.USER_ID` | `users.USER_ID` | 87,186 / 111,731 | Actor/source-user gap |
| `claims.ATT_ID` | `attendance.ATT_ID` | 1,789 / 31,892 | Claim encounter exception |
| `claims_diagnosis.CLAIM_ID` | `claims.CLAIM_ID` | 269 / 57,776 | Claim-child exception |
| `claims_diagnosis.LDIAG_ID` | `list_diagnosis.LDIAG_ID` | 3,111 / 57,776 | Catalogue-version/ID gap |
| `claims_prescriptions.CLAIM_ID` | `claims.CLAIM_ID` | 4,741 / 112,152 | Claim-child exception |
| `claims_prescriptions.MED_ID` | `medicine.MED_ID` | 1,372 / 112,152 | Product mapping exception |
| `consult_complaints.ATT_ID` | `attendance.ATT_ID` | 2,358 / 65,244 | Clinical encounter exception |
| `consult_diagnosis.ATT_ID` | `attendance.ATT_ID` | 965 / 123,940 | Clinical encounter exception |
| `consult_diagnosis.LDIAG_ID` | `list_diagnosis.LDIAG_ID` | 104,619 / 123,940 | Catalogue ID is largely unusable without other evidence |
| `consult_history.ATT_ID` | `attendance.ATT_ID` | 540 / 34,532 | Clinical encounter exception |
| `consult_prescriptions.ATT_ID` | `attendance.ATT_ID` | 2,311 / 210,902 | Clinical encounter exception |
| `consult_prescriptions.MED_ID` | `medicine.MED_ID` | 49,481 / 210,902 | Major product mapping gap |
| `consult_prescriptions.BILL_ID` | `billing.BILL_ID` | 94,282 / 210,902 | Billing link often absent/stale/sentinel |
| `consult_prescriptions.USER_ID` | `users.USER_ID` | 117,079 / 210,902 | Major actor gap |
| `consult_scan_lab.ATT_ID` | `attendance.ATT_ID` | 1,290 / 72,680 | Encounter exception |
| `consult_scan_lab.SERV_ID` | `services.SERV_ID` | 4,046 / 72,680 | Service mapping exception |
| `consult_scan_lab.USER_ID` | `users.USER_ID` | 72,680 / 72,680 | All zero; no usable actor FK |
| `consult_services.ATT_ID` | `attendance.ATT_ID` | 2,684 / 60,260 | Encounter exception |
| `consult_services.SERV_ID` | `services.SERV_ID` | 12,844 / 60,260 | Service mapping exception |
| `insurance.PAT_ID` | `patients.PAT_ID` | 316 / 31,307 | Patient-insurance exception |
| `med_pharm.MED_ID` | `medicine.MED_ID` | 4,279 / 8,360 | Snapshot/product gap or catalogue history |
| `med_store.MED_ID` | `medicine.MED_ID` | 4,265 / 8,356 | Snapshot/product gap or catalogue history |
| `serv_results.ATT_ID` | `attendance.ATT_ID` | 5,858 / 460,758 | Result encounter exception |
| `serv_results.CRI_ID` | `serv_criterias.CRI_ID` | 54,793 / 460,758 | Criterion version gap |
| `serv_results.SERV_ID` | `services.SERV_ID` | 82,522 / 460,758 | Service version gap |
| `serv_results.SCL_ID` | `consult_scan_lab.SCL_ID` | 460,752 / 460,758 | Almost all zero; unusable as primary linkage |
| `vitals.ATT_ID` | `attendance.ATT_ID` | 3,784 / 60,803 | Encounter exception |
| `vitals.USER_ID` | `users.USER_ID` | 8,478 / 60,803 | Actor exception |

## Other confirmed aggregate exceptions to inferred links

- `batch.MED_ID`: 73 unmatched.
- `beds.PAT_ID`: 15 unmatched, all zero/sentinel-like.
- `claims.SPEC_ID`: 1 unmatched.
- `claims_procedures.CLAIM_ID`: 19 unmatched.
- `consult_complaints.LCOMP_ID`: 95 unmatched/zero.
- `consult_procedures`: 1 attendance and 1 procedure unmatched.
- `consult_treatment.ATT_ID`: 33 unmatched.
- `list_causes.LDIAG_ID`: all 18 unmatched.
- `list_diagnosis.LDIS_ID`: all 5,948 zero/unmatched.
- `list_signs.LDIAG_ID`: both unmatched.
- `notifications` department references: all 141 zero/unmatched.
- `request.MED_ID`: 11 unmatched; every `USER_ID` zero/unmatched.
- `serv_criterias.GROUP_ID`: 7 unmatched; `serv_group.SERV_ID`: 9 unmatched.
- `serv_options_cri.CRI_ID`: all 20 unmatched.
- `treatment`: 17 attendance, 21 non-null medicine, and 3 user references unmatched.

## Mapping rules implied by the evidence

1. No source ID may be inserted directly into a target FK.
2. Parent record maps must exist before child transforms are committed.
3. Zero and unmatched IDs must be classified per field as sentinel, missing master, stale catalogue, deleted source, or invalid.
4. Children with unavailable approved parents must become explicit failures/quarantine records; they cannot be silently dropped.
5. Textual doctor/creator snapshots may preserve attribution when an approved user mapping is impossible, but they must not be invented as target users without approval.
6. Catalogue relationships need version-aware/natural-key crosswalks rather than source numeric IDs alone.

## Reproducible evidence register

`evidence/CLASSIC_RELATIONSHIP_MANIFEST.json` is the complete Phase 1B machine-rerunnable register for all 77 tested candidate relationships. It records each exact join predicate, null/zero/sentinel treatment, aggregate partition, query/version/hash, capture time and source fingerprint cross-reference, including zero-orphan outcomes. This narrative is a readable summary; mapping approval must use the generated register rather than table-name inference alone.
