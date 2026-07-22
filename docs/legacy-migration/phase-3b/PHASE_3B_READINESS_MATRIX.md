# Phase 3B readiness matrix

Assessment date: 2026-07-22

| Closure area | Repository implementation | Reproducible evidence | Deployment prerequisite | Phase 4A gate |
|---|---|---|---|---|
| Physical target identity and DDL | Complete, fail-closed | Unit, manifest, installation-journal and MariaDB DDL evidence | Approved external identity contract and installed protected schema | Four-review gate passed; empty-cohort exercise only |
| Protected tokens, access and keyed integrity | Complete | Repository integration, replay, rotation, connection and direct-access denial tests | External reference manifest, secret resolver, access and retention policy | Four-review gate passed; empty-cohort exercise only |
| Authoritative snapshots and run manifest | Complete | Direct-capture and atomic protected persistence tests | D-101 account, approved target contract and recorder binding | Four-review gate passed; empty-cohort exercise only |
| Contract bundle and Phase 2F policy | Complete | Exact 23-artifact manifest/hash/approval and drift tests | External non-secret policy manifest with configured pins | Four-review gate passed; empty-cohort exercise only |
| Classic privilege verification | Complete | All privilege surfaces and exact 55-table coverage tests | DBA-provisioned D-101 account | Four-review gate passed; empty-cohort exercise only |
| Reconciliation and observed dry-run counters | Complete for empty-cohort mechanics | Closed 476 registry; default/nonempty provider blocked; observed empty-cohort acceptance is non-commit-authorizing | Deliberately bound synthetic recorder provider | Four-review gate passed; populated cohort blocked |
| Recovery journal, CAS and crash/restart | Complete | Ten fresh-process boundaries and resealed transitions | Installed protected schema and recovery authority | Four-review gate passed; empty-cohort exercise only |
| Twenty-five subsystem isolation | Complete | 127 guarded effect entries, boot checks, restoration and denial tests | Directly observed worker/scheduler/null-sink barriers | Four-review gate passed; empty-cohort exercise only |
| Patient-number allocator safety | Complete | Final-source disposable MariaDB 10.4.32 concurrency, connection and rollback proof | Approved target identity for later activation | Four-review gate passed; real reservation blocked |
| Privacy-safe reporting | Complete | Mandatory coverage; no unallowlisted finding | Approved retention schedule before real protected population | Four-review gate passed; empty-cohort exercise only |

All three specialist reviews and the final independent migration re-review pass with Critical 0, High 0 and Medium 0. The broad run completed and identified no Phase 3B regression; its 46 reproducible application cases plus one deliberate memory-harness case remain visible in `BROAD_REGRESSION_REPORT.md` and the replayable manifest. Phase 4A is authorized only for a synthetic empty-cohort foundation exercise after every external prerequisite is proven. Populated cohorts, importers, Cohort B and patient-pilot execution remain blocked.
