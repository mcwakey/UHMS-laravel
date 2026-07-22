# Phase 3 scope boundary

## Included

Environment/schema/version/fingerprint guards; D-101 account verification; coordinated snapshots and immutable run manifests; protected crosswalk, remediation, provenance, quarantine, reconciliation and audit stores; HMAC/key controls; collision snapshots; idempotency, checkpoints and recovery; migration-safe number reservation; generic persistence interfaces; side-effect isolation; target-state validation; privacy scanning; aggregate dry-run reporting.

## Prohibited

No domain importer or transformation pipeline, Cohort B selection/dry-run, patient-pilot execution, Classic write, non-`uuhms` Classic access, broad-account execution, production write/test, `DatabaseSeeder`, operational creation service, or live external integration call is authorized.

Foundation storage holds migration metadata, not business-domain records. Phase 3 readiness is not importer authorization.
