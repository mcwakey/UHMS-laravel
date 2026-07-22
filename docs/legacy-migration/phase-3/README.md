# Phase 3 — migration foundation and safety infrastructure

> Phase 3B safety closure on 2026-07-22 supersedes the implementation gaps below. See [the Phase 3B exit report](../phase-3b/PHASE_3B_EXIT_REPORT.md). The original Phase 3 assessment is retained as the evidence baseline; Phase 4A remains subject to the new four-review gate.

Status: **implemented fail-closed, but not approved for Phase 4A**.

Phase 3 contains shared foundation infrastructure only. It contains no patient, staff, reference, insurance, visit or clinical importer; selects no Cohort B source cohort; and authorizes no pilot. Classic access is restricted to exact database `uuhms` through read-only metadata and `SELECT` operations. Production and domain writes are disabled.

The implementation provides guarded preflight and account verification, immutable source/target/run manifests, 16 protected foundation tables, HMAC token primitives, migration-specific persistence interfaces, dry-run reporting, reconciliation, quarantine, idempotency, allocation and recovery specifications, and six inspection/foundation commands.

Independent review found three material implementation/evidence gaps that remain fail-closed:

- protected-store HMAC authority and keyed integrity verification are not yet integrated into every repository read/write path;
- target/source physical server identity (host, port, TLS/server UUID) is not pinned, so foundation DDL remains unauthorized;
- no real application control is bound to each of the 25 prohibited side-effect subsystems;
- durable recovery orchestration and concrete concurrent/rollback allocator verification are incomplete;
- snapshot managers do not yet capture/persist authoritative observations, and reconciliation/dry-run reporting does not yet prove the complete authoritative contract;
- target-state policy is not yet bound to exact authoritative Phase 2F artifact hashes.

Consequently, all runtime commit paths, real number reservations, domain adapters, Cohort B and the patient pilot remain blocked. See `PHASE_3_EXIT_REPORT.md` and the independent reports under `reviews/`.
