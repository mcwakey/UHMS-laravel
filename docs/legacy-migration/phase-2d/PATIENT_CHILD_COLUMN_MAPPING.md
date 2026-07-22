# Patient-child column mapping

The normative seven-record mapping is `specifications/patient_child_column_mappings.json`.

| ID | Source | Destination | Transform | Parent/existing-target rule |
|---|---|---|---|---|
| `PATIENT-CHILD-COL-001` | `Work` | `patients.occupation` | nullable validated free text | parent first; existing target comparison only |
| `...-002` | `Address` | `patients.address` | unstructured validated text only | no locality inference; immutable target |
| `...-003` | `NOK` | contact `name` | tuple validation, max 100 | no identity use |
| `...-004` | `NOKPhoneNo` | contact `phone` | Ghana-format validation | no patient-phone fallback |
| `...-005` | `NOKRel` | contact `relationship` | nullable validated free text | no enum/default `Other` |
| `...-006` | `Religion` | `patients.religion` | explicit four-state crosswalk | immutable target |
| `...-007` | `MaritalStatus` | `patients.marital_status` | explicit enum crosswalk | immutable target |

`Company`/`BillStatus`, all clinical fields and every Phase 2C identity field are explicitly excluded. Each included source column has exactly one Phase 2D primary owner.

