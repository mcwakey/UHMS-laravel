# Protected crosswalk design

Crosswalks bind a protected source token to a protected target reference within run, domain, snapshot and version coordinates. Raw Classic keys and target identifiers are not searchable plaintext.

The active mapping uniqueness constraint is source-domain scoped. A rerun must resolve a compatible mapping before allocation or persistence. Existing-target links are metadata-only and never authorize mutation. Conflicting target lineage, changed snapshot coordinates or multiple active mappings fail closed.

Crosswalk state changes are explicit and audited; a successful mapping is never inferred from target-row similarity or a unique-key error.

The schema enforces lineage/cardinality and Phase 3 blocks non-test writes. `ProtectedStoreAccessGuard` is not yet integrated with crosswalk reads/writes, so cryptographic context and keyed-integrity authorization are not operational and crosswalk use remains blocked.
