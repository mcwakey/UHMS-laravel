# Classic patient-child source inventory

## Capture guard

**Confirmed:** a live aggregate-only profile ran on 2026-07-21 inside a rolled-back, `READ ONLY`, `REPEATABLE-READ` consistent snapshot. `DATABASE()` was exact `uuhms`; MariaDB was 10.4.32; the guard saw 55 tables, 479 columns and fingerprint `150fcf4783fcb8bdc25f7e17fe0ece5050955f0c68e7dd03651ee8bd58498977`. The configured account remains too broad for execution; D-101 is unresolved for Phase 3.

`patients` has 16,950 rows, 24 columns, `PRIMARY(PAT_ID)`, no secondary indexes and no declared foreign keys. All seven columns are NOT NULL Latin-1 text.

## Sanitized field profile

| Field | Type | Blank | Nonblank | Distinct normalized | Duplicate groups / rows | Max/source limit | Outer spaces | Controls / encoding failures |
|---|---|---:|---:|---:|---:|---:|---:|---:|
| `Work` | varchar(15) | 6,440 | 10,510 | 1,387 | 380 / 9,503 | 15/15 | 260 | 0 / 0 |
| `Address` | varchar(25) | 982 | 15,968 | 2,198 | 465 / 14,235 | 25/25 | 719 | 0 / 0 |
| `NOK` | varchar(100) | 1,505 | 15,445 | 13,112 | 1,632 / 3,965 | 31/100 | 296 | 0 / 0 |
| `NOKPhoneNo` | varchar(10) | 2,094 | 14,856 | 11,501 | 2,187 / 5,542 | 10/10 | 11 | 0 / 0 |
| `NOKRel` | varchar(15) | 1,668 | 15,282 | 435 | 139 / 14,986 | 15/15 | 46 | 0 / 0 |
| `Religion` | varchar(15) | 1,651 | 15,299 | 3 | 3 / 15,299 | 12/15 | 0 | 0 / 0 |
| `MaritalStatus` | varchar(15) | 1,381 | 15,569 | 3 | 3 / 15,569 | 9/15 | 0 | 0 / 0 |

Null, whitespace-only, non-ASCII and target-operational-overlength counts are zero for all seven fields at this snapshot. Duplicate text is not duplicate-patient or contact-match evidence.

### Observed nonblank length distribution

| Field | 1–5 | 6–10 | 11–15 | 16–25 | 26–50 | 51–100 | >100 |
|---|---:|---:|---:|---:|---:|---:|---:|
| `Work` | 538 | 8,260 | 1,712 | 0 | 0 | 0 | 0 |
| `Address` | 6,449 | 7,490 | 1,588 | 441 | 0 | 0 | 0 |
| `NOK` | 112 | 1,687 | 10,465 | 3,161 | 20 | 0 | 0 |
| `NOKPhoneNo` | 10 | 14,846 | 0 | 0 | 0 | 0 | 0 |
| `NOKRel` | 1,537 | 13,553 | 192 | 0 | 0 | 0 | 0 |
| `Religion` | 112 | 2,749 | 12,438 | 0 | 0 | 0 | 0 |
| `MaritalStatus` | 0 | 15,569 | 0 | 0 | 0 | 0 | 0 |

### Shape diagnostics

| Field | Digits only | Contains digit | Contains ASCII alpha | Contains punctuation |
|---|---:|---:|---:|---:|
| `Work` | 4 | 9 | 10,505 | 149 |
| `Address` | 2 | 8,630 | 15,966 | 5,476 |
| `NOK` | 24 | 38 | 15,421 | 215 |
| `NOKPhoneNo` | 14,758 | 14,770 | 87 | 11 |
| `NOKRel` | 45 | 53 | 15,237 | 125 |
| `Religion` | 0 | 0 | 15,299 | 0 |
| `MaritalStatus` | 0 | 0 | 15,569 | 353 |

Shape predicates overlap and are diagnostics, not reconciliation partitions. Numeric-looking NOK names or relationships are withheld/review inputs; no raw value was inspected or published.

## NOK tuple and phone diagnostics

| Presence class | Rows |
|---|---:|
| name + phone + relationship | 14,578 |
| name + phone only | 213 |
| name + relationship only | 586 |
| phone + relationship only | 49 |
| name only | 68 |
| phone only | 16 |
| relationship only | 69 |
| all blank | 1,371 |
| **Total / difference** | **16,950 / 0** |

There are 14,577 populated phones matching conservative local Ghana shape `0[235]########`; 14,348 are in complete tuples. There are 98 populated non-digit shapes, 190 non-ten-length values, two multiple-number separators and two plus-prefix shapes. These counts are validation evidence, not permission to repair.

## Safe categorical evidence

- Religion: Christianity 12,438; Muslim 2,749; Other 112; blank 1,651; unrecognized 0.
- Marital status: Single 8,240; Married 6,976; Widow(er) 353; blank 1,381; unrecognized 0.

No high-cardinality raw values were emitted. `NOKRel` has 435 normalized nonblank groups, so no clean-enum assumption is supported. `sett_ocuupation` remains zero rows while `Work` has 1,387 groups; a catalogue cannot be fabricated.
