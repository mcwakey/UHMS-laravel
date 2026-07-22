# Phase 2D scope boundary

## Included exactly once

| Phase 2C contract | Classic column | Phase 2D destination candidate |
|---|---|---|
| `PATIENT-COL-007` | `Work` | `patients.occupation` |
| `PATIENT-COL-009` | `Address` | `patients.address` only |
| `PATIENT-COL-010` | `NOK` | `emergency_contacts.name` as tuple member |
| `PATIENT-COL-011` | `NOKPhoneNo` | `emergency_contacts.phone` as tuple member |
| `PATIENT-COL-012` | `NOKRel` | nullable free-text `emergency_contacts.relationship` |
| `PATIENT-COL-013` | `Religion` | nullable `patients.religion` |
| `PATIENT-COL-014` | `MaritalStatus` | nullable enum-cast `patients.marital_status` |

`PAT_ID` is consumed only as protected parent identity. `OpdNo` is consumed only through `PATIENT-ALIAS-001..017` and `PATIENT-REC-ALIAS-002`.

## Excluded

- Phase 2E: `Company`, `BillStatus`.
- Later clinical scope: `Allergies`, `Medication`, `History`.
- Later visit/unresolved scope: `LastVisit`, `Refill`.
- Phase 2C provenance: `OriginalName`, `OriginalOpd`; both remain zero-alias drift guards.
- Phase 2C required patient fields: `PhoneNo`, name, DOB and gender.

No field is pulled into scope merely because it resides on the patient row. `sett_ocuupation` remains excluded and empty; any nonzero pre-cutover count is a D-209 stop.

## Parent behavior

A committed protected patient map precedes every child. Patient quarantine holds all children; child failure never moves a child to another patient. Contact/demographic values cannot identify, match, merge or deduplicate patients. Explicit existing-target links permit comparison evidence only and preserve all target data, except the separately governed Phase 2C D-201 alias stage.

