# Classic staff source inventory

## Confirmed source

Only `legacy_uhms` / exact database `uuhms` was queried. Read-only and repeatable-read guards were enabled and the transaction was rolled back. The source shape was verified as 55 tables and 479 columns.

`users` is the only staff identity/access master discovered: InnoDB, `latin1_swedish_ci`, 86 rows, 11 columns, primary key `USER_ID`, no declared foreign keys and no secondary indexes. No employee/staff master, email, phone, staff number, professional registration, specialty, role or staff category column exists.

| Column | Installed definition | Confirmed evidence | Primary disposition |
|---|---|---|---|
| `USER_ID` | `int(11) NOT NULL AUTO_INCREMENT`, PK | 86 distinct; zero zero/negative/duplicate keys | Crosswalk |
| `DEP_ID` | `varchar(200) NULL` | 86 nonblank; 11 distinct; all match a department | Crosswalk |
| `FullName` | `varchar(200) NULL` | 0 blank; 84 normalized distinct; 2 duplicate groups/4 rows | Transform |
| `UserName` | `varchar(200) NULL` | 0 blank; 84 normalized distinct; 2 duplicate groups/4 rows | Protected provenance |
| `RegDate` | nullable timestamp, default current timestamp | 0 invalid; 2018-10-06 through 2022-09-24 | Evidence-only |
| `Password` | nullable text | 86 populated; values never returned | Security-excluded |
| `CanAdd` | nullable varchar(10) | 86 populated; values never returned | Security-excluded |
| `CanEdit` | nullable varchar(10) | 86 populated; values never returned | Security-excluded |
| `CanDelete` | nullable varchar(10) | 86 populated; values never returned | Security-excluded |
| `CanPrint` | nullable varchar(10) | 86 populated; values never returned | Security-excluded |
| `Status` | `varchar(25) NOT NULL DEFAULT 'ACTIVE'` | ACTIVE 85; INACTIVE 1 | Evidence-only |

The complete column contract is [staff_column_mappings.json](specifications/staff_column_mappings.json).

## Data quality and limitations

- Normalized full names have two duplicate groups covering four rows. Normalized usernames have two groups covering four rows.
- One duplicate username group spans multiple names and one spans multiple departments. Username is therefore not a safe key.
- No leading/trailing whitespace, control characters, declared-length saturation or non-printable/non-ASCII bytes were detected in the inspected staff text aggregates.
- Exact department predicate: `TRIM(users.DEP_ID) = CAST(departements.DEP_ID AS CHAR)`; matched 86, sentinel 0, orphan 0. This remains inferred because Classic declares no FK.
- `claims_specialty` is claim care-setting/catalogue evidence, not staff-specialty evidence.
- `RegDate` is creation/event evidence only. It has no update semantics and cannot detect updates or deletions.
- Classic-only fields verify zero employment identities; all 86 rows need protected HR evidence.

No raw name, username, contact, credential or permission value is included here.

