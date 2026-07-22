# Phase 3 exit report

> Historical Phase 3 review outcome. Phase 3B was commissioned to close these findings; its current evidence and review gate are recorded in [PHASE_3B_EXIT_REPORT.md](../phase-3b/PHASE_3B_EXIT_REPORT.md).

Assessment date: 2026-07-22  
Verdict: **FAIL — safely blocked; Phase 4A is not authorized.**

No Classic or renewed production data was written. No Classic schema other than `uuhms` was accessed. No domain importer, domain row, Cohort B selection or patient pilot was introduced or run.

## Exit criteria

| # | Criterion | Result | Evidence or blocker |
|---:|---|---|---|
| 1 | All 25 capabilities implemented or externally fail-closed | FAIL | Protected-store integration, persistent recovery, real isolation, snapshot capture/persistence, authoritative reconciliation/dry-run measurement and policy-file binding are internal gaps. |
| 2 | Environment/schema/connection/version/fingerprint reject mismatch | PASS | Runtime and pre-DDL guards fail closed. |
| 3 | Production target writes technically rejected | FAIL | Runtime/domain writes are blocked, but DDL guards do not pin host/port/TLS/server identity; a production server behind non-production labels remains possible. |
| 4 | Broad Classic credentials rejected | PASS | Grant allow-list verifier. |
| 5 | Dedicated account verification exists | PASS | `legacy-migration:verify-source-account`. |
| 6 | Classic writes impossible through foundation | PASS | Read-only query recorder/metadata transaction; no source write API. |
| 7 | Protected-store integrity and access boundaries | FAIL | Access/keyed-integrity guard is not integrated across repositories and reads. |
| 8 | HMAC/key management versioned and domain-separated | PASS | Typed canonicalization, domain/environment/key separation, rotation and low-diversity rejection. |
| 9 | Run/snapshot manifests immutable and reproducible | FAIL | Stored manifests are immutable and hash DTOs deterministic, but source/target managers do not capture/persist authoritative database observations. |
| 10 | Crosswalk cardinality/existing-target immutability | PASS | Generated uniqueness, evidence validation and commit block. |
| 11 | Quarantine roots/topological release enforceable | PASS | Atomic build/seal, authoritative uniqueness, parent coordinates; releases Phase-3 blocked. |
| 12 | Reconciliation cannot pass nonzero | PASS | Service plus DB constraints/triggers require exact zero. |
| 13 | Idempotency/checkpoint semantics implemented | FAIL | Stores exist, but no persistent `AtomicRecoveryJournal` binds recovery orchestration. |
| 14 | Ten crash boundaries tested | FAIL | Classifier coverage exists; durable crash/restart injection does not. |
| 15 | Resume produces no duplicate outcome | NOT PROVEN | Requires persistent adapter and crash/restart tests. |
| 16 | Compensation explicit/unit-specific | PASS | Allowed action registry; universal deletion prohibited. |
| 17 | Allocation concurrency-safe/crosswalk-first | FAIL | Design and sequential tests pass; concrete parallel MariaDB and rollback proof absent. |
| 18 | Dry run has zero business writes | PASS | Dry-run and package tests; no business rows created. |
| 19 | Side-effect isolation complete/tested | FAIL | Registry is complete and fail-closed; real subsystem controls/call-site bindings are absent. |
| 20 | Target-state/insurance blockers enforced | PASS for Phase 3 safety | Commit is unconditionally blocked; validators are not yet cryptographically bound to authoritative Phase 2F policy files. |
| 21 | Privacy scanner covers required artifacts | PASS | Mandatory roots, code, migrations, tests, docs and artifact root scanned. |
| 22 | No PHI/credentials/secrets introduced | PASS | Privacy scan has zero unallowlisted findings; diagnostics are redacted. |
| 23 | No domain importer added | PASS | Package safety test and file inspection. |
| 24 | No pilot/Cohort B execution | PASS | Disabled configuration and no command/path. |
| 25 | Focused tests pass | PASS | See final command evidence in `foundation_test_coverage.json`. |
| 26 | Final broad regression passes | NOT RUN | Required predecessor workstreams/reviews did not pass; broad-suite gate was not reached. |
| 27 | Security/privacy review: no Critical/High | FAIL | Protected-store guard integration remains High. |
| 28 | Database-integrity review: no Critical/High | FAIL | Durable recovery and concrete allocator evidence remain High. |
| 29 | Runtime-isolation review: no Critical/High | FAIL | Real isolation wiring, durable recovery and concurrency evidence remain High. |
| 30 | Independent migration review: no Critical/High | FAIL | Final independent review found 0 Critical, 7 High and 6 Medium findings; Phase 4A is not authorized. |

## Remaining prerequisites

- Integrate `ProtectedStoreAccessGuard` and keyed seals into every protected repository read/write path with run-bound domain/environment/key/canonicalization verification.
- Pin source and target host, port, TLS/server identity in preflight and DDL guards so labels/schema fingerprints cannot redirect writes to production.
- Implement a persistent recovery journal/CAS adapter and durable crash/restart tests at all ten boundaries.
- Bind and boot-verify real controls for every prohibited subsystem without weakening normal runtime behavior.
- Prove concurrent allocation and injected rollback on an approved disposable MariaDB 10.4 target.
- Make multi-statement MariaDB foundation DDL install safely rerunnable after partial auto-commit failure; destructive rollback stays separately unauthorized.
- Implement read-only source/target snapshot capture that persists authoritative observations instead of accepting caller-supplied hashes.
- Make reconciliation and dry-run reports measure the complete authoritative contract; never accept an operator-selected zero subset or declare unmeasured zeros.
- Bind target-state/insurance validators and contract bundles to the exact authoritative Phase 2F files/hashes.
- Supply and verify D-101 credentials, real key material, retention policy, approved target tuples and a fresh post-foundation target fingerprint.

Importer implementation, Phase 4A, Cohort B and the patient pilot remain unauthorized.
