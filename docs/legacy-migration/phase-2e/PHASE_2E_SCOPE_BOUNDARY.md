# Phase 2E scope boundary

## Included

- Full `uuhms.insurance` schema and sanitized aggregates.
- `uuhms.patients(PAT_ID, Company, BillStatus)` on the Phase 2C snapshot.
- Phase 2A provider crosswalk integration and installed target membership constraints.
- Membership classification, consolidation, history, dates, sentinels, exceptions, reconciliation, extraction and privacy.

## Excluded

Claims and claim children; invoices, billing, receivables, payments and accounting; service/product insurance pricing; visit payer context; CCC or eligibility API execution; verification creation; importers and writes.

The only approved Classic schema is exactly `uuhms`. Classic access is read-only. Target inspection is non-production and read-only. Phase 2E specifications do not authorize persistence.
