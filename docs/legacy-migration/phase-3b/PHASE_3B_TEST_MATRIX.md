# Phase 3B test matrix

Assessment date: 2026-07-22

| Safety claim | Evidence | Result |
|---|---|---|
| Physical target identity is independently pinned | Host, port, TLS peer/cipher, server identity, role, network identity, schema/configuration fingerprints and keyed contract mismatch tests | Pass |
| Foundation DDL is create-only and safely rerunnable | 83 allow-listed operations across two versioned manifests; exact rerun executes zero; partial recovery executes only missing matching objects | Pass |
| Protected storage is keyed and purpose-scoped | Envelope replay/tamper, direct-model/query, purpose/authority, rotation and legacy-unsealed-path tests | Pass |
| Source/target/run snapshots are authoritative | Direct observer, nonce, physical authority, atomic protected persistence and ten collision-set tests | Pass |
| Phase 2F policy is exact | External manifest/hash/approval, 23 artifacts, contract IDs, drift/replay and unresolved-tuple tests | Pass |
| Classic account is least privilege | Global/schema/table/column/routine/role grants, read-only transaction and exact 55-table coverage tests | Pass; deployment D-101 account remains external |
| Dry-run reconciliation is observed and complete | Recorder-issued evidence, caller-array rejection, complete equation/population plan and zero-side-effect/write counters | Pass; live recorder provider remains external |
| Recovery is persistent and resumable | Ten durable crash/restart boundaries, protected journal, CAS, reseal, audit and checkpoint-repair tests | Pass |
| Runtime effects are isolated | Exactly 25 subsystem controls, operational service/container interception, framework façade sinks, Eloquent/activity suppression, restoration and external barrier proof | Pass |
| Allocator is concurrency- and rollback-safe | Independently attested disposable MariaDB 10.4.32; concurrent uniqueness, same-key rerun, periods, collision rollback and three injected faults | Pass: 1 test, 44 assertions |
| Privacy evidence is complete | Scanner v3 mandatory roots and detector coverage | Pass: 785 files, zero coverage gaps and zero unallowlisted findings |
| Shared foundation regression | Unit and feature foundation suites | Pass: 320 tests, 1,676 assertions; three explicitly gated disposable/worker tests skipped in normal run |
| Specialist independent reviews | Security/privacy; database integrity; runtime isolation | Pass: each reports Critical 0, High 0, Medium 0 |
| Broad application regression | Complete 2,367-test run plus isolated reruns of all 28 affected files | Complete: 31,756 assertions, 3 errors, 44 failures, 3 skips; 46 reproducible non-Phase-3B application failures, one deliberate memory-harness failure, no Phase 3B regression identified |
| Final independent migration re-review | Original blockers, dry-run correction, broad replay manifest, scope and privacy | Pass: Critical 0, High 0, Medium 0; Phase 4A limited to synthetic empty-cohort foundation exercise |

The disposable MariaDB test used only synthetic foundation namespaces. Its guarded teardown left zero schemas and the isolated server was shut down. No Classic, renewed shared, production or business-domain database participated.
