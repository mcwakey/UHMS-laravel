# Reference Extraction Specifications

Machine contract: [reference_extraction_strategies.json](specifications/reference_extraction_strategies.json).

## Guarded extraction

Every query must verify the connection database is exactly `uuhms`, the approved schema fingerprint/table/column counts/database version, and a read-only transaction. The final migration account must be limited to SELECT and metadata access; the broad discovery account is not approved for execution.

Each run records query ID/version/SHA-256, tool version, UTC time, database version and schema fingerprint. Raw values are not logged or embedded in generated evidence.

## Strategies

- PK tables use ordered full snapshots and row/content hashes. PK watermarks can discover inserts only.
- The keyless option tables use canonical multiset hashing and preserve duplicate multiplicity.
- Mutable `ON UPDATE` timestamps on supplier, bed, batch and request tables are not historical creation evidence and cannot detect deletion.
- Tables without reliable timestamps require repeated full snapshot/hash comparison at preflight and freeze.
- A changed fingerprint is a stop condition.
- Reference-adjacent transaction/snapshot tables are extracted only for dependency and exception reconciliation.

The per-table strategy, ordering key, chunk recommendation, delete detection and freeze rule are explicit for all 28 tables in the machine contract.
