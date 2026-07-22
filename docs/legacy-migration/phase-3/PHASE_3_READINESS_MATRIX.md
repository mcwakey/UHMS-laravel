# Phase 3 readiness matrix

> Historical Phase 3 baseline. Phase 3B has implemented and tested the listed closure work. Use [PHASE_3B_READINESS_MATRIX.md](../phase-3b/PHASE_3B_READINESS_MATRIX.md) for the current gate.

Assessment date: 2026-07-22. “Blocked safely” means the implementation refuses activation; it does not mean the capability is complete.

| Capability group | Implementation evidence | Focused validation | Independent gate | Phase 4A authority |
|---|---|---|---|---|
| Environment and Classic account | Exact `uuhms`, schema/version/fingerprint and account guards implemented; physical server identity not pinned | Primitive tests passing | **High blocker: host/port/TLS/server identity** | None |
| Source/target/run snapshots | Immutable tables and deterministic hash DTOs exist; authoritative capture/persistence workflow missing | DTO tests passing | **High blocker** | None |
| Protected stores, access and integrity | Schema, encryption, lineage and access-guard primitive implemented; repository integration incomplete | Storage/constraint tests passing | **High blocker** | None |
| Security and privacy | Domain/version HMAC, low-entropy rejection, mandatory-root scanner and redacted reports implemented | Scanner passes with zero coverage gaps/unallowlisted findings | Partial; store integration blocks | None |
| Runtime isolation and persistence boundaries | Complete registry/interfaces; every commit and domain adapter remains disabled | Interface/fail-closed tests passing | **High blocker: no 25 real controls/call-site proof** | None |
| Allocation, idempotency, checkpoints and recovery | Foundation stores and algorithms implemented; real reservations Phase-3 blocked | Sequential/synthetic tests passing | **High blockers: durable journal and concrete concurrency/rollback evidence** | None |
| Reconciliation and dry-run reporting | Exact-zero arithmetic and aggregate field rejection exist; authoritative population/equation completeness and observed zero-write measurement missing | Primitive tests passing | **High blocker** | None |

Externally/environment blocked evidence also remains: dedicated D-101 credentials, approved non-production MariaDB 10.4 foundation deployment, post-foundation target fingerprint, approved retention schedule, real key material and complete target patient/insurance tuples.

Detailed mapping approval is not importer authorization. Phase 4A, Cohort B, every importer and every patient write remain blocked.
