# Patient exception catalogue

The canonical catalogue is [patient_exception_codes.json](specifications/patient_exception_codes.json). It defines 49 stable exceptions. Every record includes trigger, disposition, flags, retryability, manual-review requirement, owner, SLA, release condition, reconciliation treatment, blocking/chain scope, privacy and provenance.

SLA expiry never authorizes a default, invented identity, automatic merge, alias assignment, patient reassignment, discarded row or chain release.

| Range | Scope | Principal owner | Normal blocking effect |
|---|---|---|---|
| `KEY-001..002` | Missing/duplicate source identity | Migration Technical Lead | Patient/scope stop |
| `ALIAS-003..007`, `ALIAS-041` | Blank, duplicate, invalid and target-conflicting OPD aliases | Health Records Officer / Patient Identity Lead | Alias only unless an independent identity conflict exists |
| `NAME-008..012` | Missing, ambiguous, unrepresentable or invalid names | Health Records Officer | Patient and entire chain |
| `DOB-013..016` | Invalid, implausible, reversed or unrepresentable DOB | Clinical Records Officer | Patient and entire chain |
| `GENDER-017..018` | Missing or unsupported gender | Health Records Officer | Patient and entire chain |
| `PHONE-019..020` | Missing or invalid required phone | Health Records Officer | Patient and entire chain |
| `EMAIL-021`, `NATIONAL-022` | Later optional/remediation identity conflicts | Records / Patient Identity Lead | Field or patient according to proposed use |
| `TARGET-023..026`, `TARGET-035` | Existing-target ambiguity/immutability or schema drift | Patient Identity Lead / Target Architect | Patient/run stop |
| `DUPLICATE-027..028` | Suspected duplicate flag or prohibited automatic action | Patient Identity Lead / Migration Lead | Flag only; automatic action stops run |
| `RELATIONSHIP-029`, `CHAIN-030`, `CHAIN-042` | Missing parent, held chain or conflicting roots | Migration Data Steward | Child/subchain or full chain |
| `TEXT-031` | Encoding/control/length defect | Data Quality Lead | Field; full chain if required identity |
| `ACTOR-032` | Prohibited registration attribution fallback | Security and Migration Lead | Chunk/run stop |
| `DRIFT-033`, `EXTRACTION-034` | Source or snapshot failure | Migration Technical Lead | Run/snapshot stop |
| `PRIVACY-036` | PHI/provenance/key failure | Privacy and Records Officer | Immediate containment and programme stop as directed |
| `NUMBER-037..039` | Allocation, rerun or sequence reconciliation failure | Target Architect / Migration Lead | Allocation-window/run stop |
| `REMEDIATION-040` | Missing or unreliable remediation evidence | Health Records Officer | Required field/patient remains pending |
| `DELETE-043`, `STATUS-044..049` | Destructive recovery or unsupported/field-specific target state | Migration Lead / Patient Domain Owner | Run or patient stop |

Manual review operates on protected crosswalk/quarantine records only. Documentation and ordinary logs use aggregate counts and stable codes, never raw identity values.
