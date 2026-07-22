# Patient pilot remediation input contract

Version `2F.1.0`; protected-input specification only. No remediation value is stored in this repository.

## Required interface

A record is keyed by the versioned `patient-source-key-v1` protected token and contains independently evidenced first name, independently evidenced last name, optional other names, corrected DOB/gender/phone where needed, evidence type, issuer/source, reviewer, approval state, validity interval, revocation state, rule version and conflict flags.

Validation order is token/key/version integrity; approval and nonrevocation; evidence independence/authority; field semantic/format validity; conflict checks; validity at the pinned snapshot; accepted field projection. Any failure leaves `REMEDIATION_REQUIRED` or quarantine.

## Prohibitions

- Never split Classic combined names automatically, guess order, or copy one name into both components.
- Never borrow another patient's fact or use a suspected-duplicate candidate as remediation evidence.
- Never use insurance, NOK/contact or another child to repair patient identity automatically.
- Never turn remediation evidence into a general target-patient match.
- Never consume revoked, expired, conflicting or version-mismatched evidence.
- Never publish remediation values, reviewer identity, raw patient keys or row-level tokens.

Accepted remediation only creates a `REMEDIATION_ACCEPTED_CANDIDATE`. It does not make the patient future commit-ready. Target-state, reference, collision, allocator, storage, isolation and Phase 3 foundation gates remain mandatory.

Normative field and prohibition rules are in `specifications/patient_pilot_remediation_input_rules.json`.

