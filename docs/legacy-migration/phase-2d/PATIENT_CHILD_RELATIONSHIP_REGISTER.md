# Patient-child relationship register

Exactly five logical relationships are normative. Same-row projections are not claimed Classic foreign keys.

| Relationship | Predicate / target | Parent prerequisite | Blocking and existing-target behavior | Sentinel / reconciliation |
|---|---|---|---|---|
| `PATIENT-CHILD-REL-001` | same patient row → four target demographic fields | Phase 2C protected map | parent holds all; field-local holds after success; existing target comparison only | `...SENT-001`; REC 003–006 |
| `PATIENT-CHILD-REL-002` | same row `(NOK,NOKPhoneNo,NOKRel)` → one contact tuple | protected parent classification | parent holds tuple; invalid child local; existing target evidence only | `...SENT-002`; REC-001 |
| `PATIENT-CHILD-REL-003` | protected crosswalk target patient → contact FK | committed exact live target plus valid tuple | never value-based/reassigned; existing target zero mutation | `...SENT-003`; REC-002 |
| `PATIENT-CHILD-REL-004` | same-row `OpdNo` → `legacy_opd` alias | `PATIENT-ALIAS-013` | all behavior delegated to Phase 2C | `...SENT-004`; `PATIENT-REC-ALIAS-002` |
| `PATIENT-CHILD-REL-005` | `PATIENT-PRIV-007` root → every child outcome | verified token/snapshot versions | token failure holds every child | `...SENT-005`; `PATIENT-REC-CHAIN-100` |

The JSON registry records null/blank/invalid behavior, exception, extraction and provenance references for every relationship. No artificial patient/contact/reference parent is allowed.

