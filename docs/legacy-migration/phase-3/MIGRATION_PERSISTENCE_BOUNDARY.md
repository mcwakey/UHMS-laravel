# Migration persistence boundary

Phase 3 defines interfaces and invariant DTOs only for patient core, explicit existing-target link, alias, emergency contact, insurance history and current membership. There is no Classic extraction, transformation or importer implementation.

Every boundary requires a valid run, pinned snapshots, required mappings, an approved complete target-state tuple, applicable insurance initialization approval, valid idempotency lineage, active side-effect isolation, explicit dry-run/commit authority, fresh target-collision snapshot and available provenance/reconciliation stores.

Existing-target branches permit metadata-only linking and zero target mutation. Boundary implementations must use direct migration-specific persistence in future phases, never operational services or global validation weakening.
