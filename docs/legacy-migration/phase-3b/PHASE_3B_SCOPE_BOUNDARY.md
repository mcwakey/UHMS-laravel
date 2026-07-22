# Phase 3B scope boundary

Assessment date: 2026-07-22

Phase 3B closes evidence-backed safety gaps in the shared migration foundation. It does not authorize or implement a domain importer, select Cohort B, execute the patient pilot, write renewed business data, access any Classic schema except read-only `uuhms`, or run `DatabaseSeeder`.

## Included

- HMAC-pinned physical non-production target identity and identity-bound foundation DDL.
- Create-only, manifest-driven and journalled partial-DDL recovery.
- External key-reference manifests, purpose-scoped protected access, keyed envelopes, rotation lineage, retention gates and access audit.
- Direct source, target-collision and run snapshot capture with protected persistence.
- Exact Phase 2F artifact-manifest, hash, contract and approval binding.
- Complete Classic privilege-surface and all-55-table `SELECT` coverage verification.
- Recorder-issued dry-run measurements and complete contract-bundle reconciliation.
- Persistent recovery journal, compare-and-set transitions, checkpoints and compensation state.
- Real application bindings for all 25 prohibited side-effect subsystems and deployment barrier verification.
- Migration-safe patient-number reservation with concurrent and rollback verification on an attested disposable MariaDB 10.4 instance.
- Privacy-safe aggregate reporting and mandatory artifact coverage.

## Explicitly excluded

- Reference, staff, patient, alias/contact, insurance, visit, clinical, billing, claim, pharmacy or stock importers.
- Patient-pilot execution, production tests, production writes, Classic writes or use of a broad-privilege Classic account.
- Cohort B selection, synchronization runtime, migration UI/orchestration and operational service-based historical creation.
- Retention-policy approval, deployment credentials, key material, infrastructure attestation and production cutover authority.

## Activation boundary

Repository implementation is fail-closed. Deployment-owned credentials, manifests, keys, target identity, retention/access classifications, installed foundation schema, recorder provider and external isolation barriers must be independently supplied and observed before any migration run can activate. Phase 4A authorization is a separate review outcome and is never importer authorization.
