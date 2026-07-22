# Foundation data model

The `legacy_migration_*` namespace contains run, contract-bundle, snapshot, target-collision, crosswalk, remediation, provenance, quarantine-root, exception, reconciliation, audit, idempotency, checkpoint, atomic-intent, compensation and number-reservation records.

Common integrity coordinates are run identity, domain, protected source token, optional protected target token, snapshot, contract/transformation/canonicalization/HMAC versions, state, checksum, access class and retention class. Run/bundle rows persist token environment and key ID; child rows inherit that lineage through `run_id`. Repository verification of that inherited context and keyed checksum is a blocking unimplemented control.

Required cardinalities include one active patient mapping per protected source token, one compatible idempotency outcome per key, one valid quarantine root per held chain, and no passing reconciliation with a nonzero difference. Protected payloads are encrypted and hidden from normal model serialization. No foundation migration creates a patient, staff, reference or insurance row.
