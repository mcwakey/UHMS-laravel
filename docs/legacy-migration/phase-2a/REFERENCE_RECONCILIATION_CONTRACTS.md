# Reference Reconciliation Contracts

Machine contract: [reference_reconciliation_contracts.json](specifications/reference_reconciliation_contracts.json).

## Universal equation

For each source table:

`source rows = matched existing + create candidates + configure separately + evidence only + deferred + excluded + quarantined + failed`

The categories are mutually exclusive and the difference must be zero. Phase 2A expected database writes are zero.

Every contract requires measured disposition counts, duplicate/unresolved diagnostics, target before/after counts, relationship measures, UTC measurement time and SHA-256 source/target hashes. Those execution measurements are intentionally null in this specification phase and become mandatory preflight/dry-run outputs. Missing measures, unknown exception codes, unexplained target deltas or hash/fingerprint changes are explicit stop conditions.

## Critical contracts

- `4,303 medicine = mapped drug products + mapped consumable products + product exceptions + excluded`.
- `mapped drug products = linked drug companions + drug-companion exceptions`; consumables with a drug companion must be zero.
- `38 sett_private rows = mapped legacy provider IDs + exceptions`, while separately reconciling 33 canonical keys and five duplicate groups.
- `18 beds = mapped existing + create candidates + exceptions`; all require configured ward assignment.
- For every relationship: `child = null + candidate sentinel + matched non-sentinel + orphan non-sentinel`.
- All rows in Classic `accounts`, `acc_petty`, stock snapshots, batches and requests create zero Phase 2A transaction rows.
- All price/cost fields with unresolved semantics create zero target price/cost rows.
- Finance entries, journals, postings, payments, receivables, opening balances and stock movements created in Phase 2A must equal zero.

Money is parsed and compared with arbitrary-precision decimals at an approved scale. Source `double` values are never reconciled with binary floating point or silently rounded.
