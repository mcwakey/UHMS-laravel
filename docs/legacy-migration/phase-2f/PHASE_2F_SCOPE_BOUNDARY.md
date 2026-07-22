# Phase 2F scope boundary

## Included

- Compose the Phase 2A organisation, department, provider and reference dependencies.
- Compose Phase 2B null registration attribution and security exclusions.
- Compose Phase 2C patient identity, remediation, numbering, alias, existing-target and quarantine rules.
- Compose Phase 2D optional demographics, NOK/contact, child sequencing and privacy rules.
- Compose Phase 2E insurance provider, membership, date, consolidation, history, immutability and non-eligibility rules.
- Specify three bounded pilot cohorts, 33 uniquely owned source-field mappings, 24 dependency stages, four future atomic units, protected interfaces, dry-run, collision, idempotency, rollback/resume, reconciliation, privacy and Phase 3 handoff contracts.

## Excluded

Importers; patient/staff/reference/insurance persistence; migration/crosswalk/provenance/quarantine/reconciliation tables; migrations or schema changes; patient-number allocation code; UI; queue/synchronisation/cutover runtime; seeders; target or Classic writes; production tests; live eligibility; claims; billing; payments; accounting; and any operational service invocation.

Classic access is limited to the configured `legacy_uhms` connection with exact database `uuhms`, read-only. Target inspection is limited to approved non-production `uhms_clean`, read-only. Any mismatch fails closed.

Phase 2F may complete while commit-mode blockers remain. Completion means the foundation has a reviewed contract; it never means the pilot may write data.
