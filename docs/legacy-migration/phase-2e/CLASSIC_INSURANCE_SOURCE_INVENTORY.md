# Classic insurance source inventory

**Confirmed, refreshed read-only from `uuhms`.** The source has 31,307 rows and nine columns. `INS_ID` is the sole primary/indexed key; there are no foreign keys or timestamps. Null, nonpositive and duplicate `INS_ID` counts are all zero.

| Ordinal | Column | Type / source behavior | Phase 2E disposition |
|---:|---|---|---|
| 1 | INS_ID | int, auto-increment, PK | protected provenance only |
| 2 | PAT_ID | int, required | exact Phase 2C parent join |
| 3 | InsType | varchar(25), required | explicit type crosswalk |
| 4 | Scheme | varchar(200), required, default `-` | protected history; no target field |
| 5 | MemberNo | varchar(15), required | nullable target candidate; never invented |
| 6 | Company | varchar(25), required | Phase 2A provider crosswalk |
| 7 | IssueDate | date, required | field-specific date rule |
| 8 | ExpiryDate | date, required | field-specific date rule |
| 9 | Plan | varchar(255), required | protected history; no target field |

## Sanitized aggregates

- InsType: PRIVATE INSURANCE 15,654; NHIS 15,653.
- Company: 25,042 blank; 2,422 distinct nonblank.
- MemberNo: 17,029 blank. Under the approved exact case-sensitive, punctuation-preserving comparison, 1,019 duplicate groups cover 2,555 rows; 1,002 cross-patient groups cover 2,521 rows; 255 cross-provider groups cover 621 rows; and 14 same-patient/provider groups cover 28 rows. The disjoint row partition is 17,029 blank + 2,516 cross-patient duplicate + 18 cross-provider/same-patient duplicate + 16 same-patient/provider duplicate + 5 placeholder + 11,723 valid single-scope = 31,307.
- Scheme: 21,444 blank, 1,954 hyphen; 111 substantive distinct values.
- Plan: 31,299 blank; seven substantive distinct values.
- Patient edge: `31,307 = 0 null + 0 zero + 30,991 matched + 316 orphan`.
- Text-control diagnostics are zero. Five member values look placeholder-like; one member and two company values reach declared length and require non-emitting review.

No row-level source key, member value, company/scheme/plan value or date is recorded here.
