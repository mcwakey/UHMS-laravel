# Legacy status and category values

## Rule

These are aggregate values observed in `uuhms`. They are not target mappings. Every target enum transformation requires explicit approval; blanks, variants, and unknowns become exceptions unless an approved rule exists.

## Encounter and patient values

| Field | Observed values/counts | Mapping concern |
|---|---|---|
| `attendance.AttStatus` | `OUTPATIENT` 42,708; `INPATIENT` 7,998; `OUTSIDER` 1,221 | Visit type versus patient class requires definition |
| `attendance.Billing` | `NHIS` 33,959; `CASH AND CARRY` 10,527; `PRIVATE INSURANCE` 7,012; combined private/NHIS 291; staff/protocol 40; blank 98 | Target payer/payment-policy model is richer |
| `attendance.Status` | `DOCTOR` 40,435; `DISCHARGING` 6,820; `VITALS` 1,260; `DONE` 930; `PHARMACY` 678; `LAB` 454; `INPATIENT-DISCH` 381; `DISCHARGE` 753; smaller admission/scan/ward variants | Mixes global visit state and department/pathway location |
| `attendance.Triage` | blank 35,271; `NORMAL` 16,463; `MANAGEABLE` 96; `EMERGENCY` 88; 9 numeric/out-of-domain values | Must not default blanks to normal |
| `attendance.NewOld` | `NEW` 34,534; `OLD` 46; blank 17,347 | Semantics/accuracy unclear |
| `patients.Sex` | `FEMALE` 9,080; `MALE` 7,282; `F` 224; `M` 198; blank 151; 14 malformed/other | Canonical enum and unknown policy required |
| `patients.BillStatus` | `NHIS` 10,587; private 3,416; cash 2,518; combined 384; blank 40; truncated variant 5 | May be default payer, not encounter payment fact |

## Financial and claim values

| Field | Observed values/counts | Mapping concern |
|---|---|---|
| `billing.Status` | `PAID` 104,938; `OWING` 6,793 | Target status derives from allocations/balances |
| `claims.ClaimStatus` | `ACTIVE` 31,692; `ARCHIVES` 200 | Does not directly match target claim lifecycle |
| `claims.TypeBill` | `ALL INCLUSIVE` 31,892 | Semantic decision needed |
| `claims.TypeServ` | `OUTPATIENT` 28,263; `INPATIENT` 3,629 | Candidate claim/visit class |

## Clinical and medication values

| Field | Observed values/counts | Mapping concern |
|---|---|---|
| `consult_prescriptions.Status` | `DONE` 188,144; `REQUESTED` 22,758 | Target prescription/order/dispensing states are distinct |
| `consult_prescriptions.BillingStatus` | `BILLED` 141,314; `OWING` 69,588 | Does not prove payment settlement |
| `consult_scan_lab.Status` | `DONE` 64,951; `REQUESTED` 7,705; `SERVED` 24 | Target investigation/sample/result states require crosswalk |
| `medicine.MedType` | `DRUG` 4,127; `CONSUMABLE` 22; blank 154 | Product type exception policy required |
| `medicine.Category` | blank 3,874/4,303; remaining values are overlapping clinical classes | Cannot be relied on as a complete category master |
| `medicine.Serve` | 86 representations; 2,903 blank | Mixes quantities, pack sizes, forms, spelling/case, and line breaks |

## Configuration/system values

- `users.Status`: `ACTIVE` 85, `INACTIVE` 1.
- Every Classic per-user permission flag is `Y`; this is not acceptable evidence for renewed roles/permissions.
- All 203 `services.ResultType` and all 200 `serv_criterias.ResultType` values are blank.
- All 460,758 `serv_results.Uploaded` values are `0`; `ResFlag` is blank on 460,752 rows.

## Preliminary status treatment

- Split source `attendance.Status` into target visit status, department/pathway state, and possibly terminal outcome; never force it into one target enum.
- Derive financial settlement only from approved reconciled amounts/allocations, not source labels alone.
- Treat `DONE`, `SERVED`, `BILLED`, and `ACTIVE` as source facts requiring domain interpretation, not normalized equivalents.
- Retain raw source value in migration evidence/failure metadata so transformations are auditable.

