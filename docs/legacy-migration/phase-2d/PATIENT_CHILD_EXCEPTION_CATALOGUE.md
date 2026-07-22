# Patient-child exception catalogue

The normative catalogue is `specifications/patient_child_exception_codes.json`. It defines stable `LEGACY-PATIENT-CHILD-*` codes with severity, trigger, source/blocking scope, disposition, retryability, manual-review owner/SLA, release condition, reconciliation, chain and privacy/provenance handling.

| Family | Examples | Default scope / owner |
|---|---|---|
| parent/chain | unresolved or quarantined patient | all children / Migration Identity Lead |
| alias guard | parent unavailable, redefinition, Original* drift, mutation | alias or shared snapshot / Identity Governance or Architect |
| contact | blank, missing/invalid/overlength member, partial, target conflict | child-local unless target/root failure / Registration Lead |
| occupation/address | blank, invalid, overlength | field-local / Patient Data Steward |
| religion/marital | blank, unknown, invalid, enum mismatch | field-local or run stop on target drift / Data Steward/Application Owner |
| text/privacy | decode/control, PHI exposure, unverifiable provenance | field/root/run containment / Data Quality, Privacy or Governance |
| environment | source fingerprint, target constraint, extraction mismatch | fail closed / Migration Architect or Application Owner |

Optional blanks that validly map to null are informational successful-absence outcomes, not data errors. A nonblank unknown never shares that outcome. SLA expiry never authorizes a default, discard, repair, child reassignment, alias assignment or release.

Phase 2C exception identities remain authoritative for patient/alias/root failures and are referenced rather than copied or weakened.

