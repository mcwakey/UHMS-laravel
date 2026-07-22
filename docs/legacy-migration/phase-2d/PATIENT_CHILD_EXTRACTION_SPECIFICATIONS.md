# Patient-child extraction specifications

Phase 2D uses one projection over the exact coordinated `PATIENT-EXT-001` full patient snapshot. It is not a second independently timed extraction.

| Property | Contract |
|---|---|
| ID | `PATIENT-CHILD-EXT-001` |
| Query ID/version | `phase2d.patients.child_projection` / `2D.1.0` |
| Columns | `PAT_ID`, `Work`, `Address`, `NOK`, `NOKPhoneNo`, `NOKRel`, `Religion`, `MaritalStatus` |
| Exact order | `PAT_ID ASC` |
| Exact normalized SQL hash | `db20d1995d2c525e8f3efc90a8170581a2ed0832e122623e893467e0dd3722bc` |
| Shared coordinate | identical immutable `PATIENT-EXT-001` snapshot |
| Fingerprint dependency | `PATIENT-PRIV-002` / `patient-row-content-v1` |
| Field canonicalization | `patient-child-field-v1` |
| Chunk/resume | 1,000; last fully emitted protected `PAT_ID` plus unchanged snapshot identity |
| Output | row data only inside protected runtime; repository aggregate-only |

Update detection remains full protected content comparison; `EditDate` overlap is advisory. Delete detection remains full ordered key-set comparison; absence never proves deletion. Preflight and final freeze use the same Phase 2C coordinate.

Stop on any non-`uuhms` connection, missing D-101 read-only proof, schema/fingerprint/key/count/query/version drift, second snapshot, parent/outcome mismatch, nonblank `OriginalName`/`OriginalOpd`, HMAC-version failure, raw-PHI output or any database write.

