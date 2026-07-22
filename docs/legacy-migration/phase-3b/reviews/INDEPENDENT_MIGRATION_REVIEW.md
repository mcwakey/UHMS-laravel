# Phase 3B independent migration review

Review date: 2026-07-22  
Reviewer role: independent Classic-to-Renewed migration reviewer  
Review mode: current-tree inspection, machine-contract review and synthetic/local tests; no Classic, shared target, renewed production, importer, Cohort B, patient pilot or business-domain write was used  
Verdict: **PASS for the Phase 3B repository gate**

## Executive decision

The Phase 3B repository gate passes with **Critical 0, High 0, Medium 0**. The corrected authoritative dry-run implementation closes `IMR-P3B-001` and the original `IMR-P3-007` for the bounded Phase 4A foundation scope. The retained exact broad-run configuration closes `IMR-P3B-002`. **Phase 4A synthetic foundation exercise is authorized**, subject to the external activation prerequisites below.

This authorization is deliberately narrow. It does not authorize a populated cohort, protected patient-data population, a domain importer, Cohort B selection, a patient pilot, commit mode, a Classic write, a renewed production write or any business-domain write. Those remain blocked.

The repository-wide suite remains non-green and must not be represented as a green historical baseline. That visible application debt does not create an open Phase 3B finding because its privacy-safe classification and exact execution configuration are now retained and the migration-focused suite is green.

## Broad regression traceability closure

### IMR-P3B-002 — closed

`BROAD_REGRESSION_MANIFEST.json` is a substantial traceability improvement. It contains 47 unique case identifiers, 28 unique affected test-file paths and current hashes, counts that reconcile to 46 application cases plus one memory-harness case, the exact command string and hash, current `phpunit.xml` hash, aggregate results, explicit disclosure that no historical green baseline exists, transitive-diff rationale and no failure output. All 28 recorded file hashes match the current tree and have zero diff from repository `HEAD`.

The exact executed `phpunit.phase3b-broad.xml` is now retained. Its SHA-256 is `909a01e12aeddd604ae9f86c769d8ac477b190c058c1dd3551054805fc8cab75`, exactly matching `BROAD_REGRESSION_MANIFEST.json`. Direct comparison with the separately hashed `phpunit.xml` confirms that the only semantic change is `memory_limit` from `512M` to `-1`; the remaining textual difference is formatting of the unchanged source-include element. The command and both configuration byte sets are independently replayable.

The evidence supports the bounded conclusion that no Phase 3B regression was identified: the corrected focused foundation suite is green, sampled application failures reproduce as ordinary value/localisation/routing failures, affected test files are unchanged, normal inactive runtime behavior is covered, and the report openly states that the repository-wide suite is not green. It does not establish a historical green baseline. No raw failure output or rejected JUnit content is retained.

Evidence: `docs/legacy-migration/phase-3b/BROAD_REGRESSION_MANIFEST.json`; `docs/legacy-migration/phase-3b/BROAD_REGRESSION_REPORT.md`; `docs/legacy-migration/phase-3b/PRIVACY_DETECTOR_CLOSURE.md`.

## Correction closure — authoritative dry-run evaluator

### IMR-P3B-001 / IMR-P3-007 — closed for Phase 4A foundation scope

The corrected implementation has a closed registry of **476** measurements derived from the exact 23-artifact Phase 2F bundle:

- 341 contract assertions;
- 17 required inputs;
- 43 accounting/control outputs: 10 requirements, 21 required outputs and 12 exit requirements;
- 13 classified outcomes; and
- 62 mandatory zero counters.

`RecorderDerivedMeasurementAdapter` contains explicit allow-lists for all 135 detailed measurements. A new or unknown detail has no fallback and remains missing. Caller-supplied measurement arrays, expected values, differences and tolerances remain prohibited. The evaluator computes and seals every accepted measurement itself.

There is no default or production aggregate provider and no container binding for `AuthoritativeAggregateObservationProvider` or `AuthoritativeRecorderEvidenceProvider`. The only concrete aggregate provider is `ObservedEmptyCohortAggregateProvider`. Its observation object has a private constructor and can be created only after the sealed source snapshot proves zero patient-root, patient-child and insurance rows. An independent direct probe confirmed that a nonzero patient source count raises `LogicException`. The adapter also accepts only `observed_empty_cohort/1`; a populated cohort therefore cannot obtain a complete accepted verdict from repository code.

The apparent shared zero handling is not a generic fabricated-zero fallback. It is reachable only for the fixed 62-ID allow-list and only from the snapshot-bound empty-cohort object. Named source/target/runtime counters use direct read-only, before/after and subsystem observations. Without the trusted empty-cohort provider, all 135 detailed measurements are `blocked_not_measured`. A named runtime attempt or target before/after mismatch produces `FAIL_CLOSED`.

The accepted synthetic path emits all 476 measurements with no missing or failed entry and returns only `DRY_RUN_ACCEPTED_NOT_COMMIT_AUTHORIZED`. The aggregate command report always emits `commit_authorized=false`, with observed source, target and business-domain write counts. A populated cohort requires a separately implemented and independently reviewed provider; none exists or is authorized here.

Evidence: `app/Services/LegacyMigration/Foundation/Reconciliation/Phase2FMeasurementPlan.php`; `AuthoritativeAggregateObservation.php`; `ObservedEmptyCohortAggregateProvider.php`; `AuthoritativeRecorderSession.php`; `RecorderDerivedMeasurementAdapter.php`; `AuthoritativeDryRunEvaluator.php`; `app/Console/Commands/LegacyMigration/FoundationReconcileCommand.php`; `tests/Feature/LegacyMigration/Foundation/AuthoritativeRunCaptureCoordinatorTest.php`; `tests/Unit/LegacyMigration/Foundation/Reconciliation`; `tests/Unit/LegacyMigration/Foundation/Reporting`.

## Original Phase 3 finding dispositions

The four original reports contain 13 High and 13 Medium report entries, with deliberate overlap. Their deduplicated union is seven High and eight Medium matters. Every entry was rechecked.

| Original finding(s) | Original severity | Final disposition |
|---|---:|---|
| `IMR-P3-001` production target identity | High | **Closed in repository implementation.** Physical observation and DDL authority are server/network/TLS/role/attestation/schema/configuration/session bound. Real approved non-production identity and production-network denial remain external. |
| `IMR-P3-002`, `SEC-P3-001` protected-store trust/access | High | **Closed.** Full token context, keyed envelopes, purpose-scoped projection, direct-access denial, resealed transitions, audited access and cross-connection rejection are repository-integrated. |
| `IMR-P3-003` authoritative source/target/run snapshots | High | **Closed in implementation.** Direct read-only source and physically authorized target capture, nonce-bound manifests, ten collision namespaces, run and exact bundle are atomically protected. D-101 and a real target remain external. |
| `IMR-P3-004`, `DBI-004`, `RTI-002` durable recovery | High | **Closed.** Ten abrupt process-exit/fresh-boot boundaries, protected observation, changed-evidence denial, legal CAS and checkpoint-only repair pass. |
| `IMR-P3-005`, `RTI-001` application-bound isolation | High | **Closed in repository implementation.** Exactly 25 subsystems, framework sinks and 127 operational entries are boot-verified, measured and restored. External worker/scheduler/null-sink proof remains required. |
| `IMR-P3-006`, `DBI-005`, `RTI-003` allocator concurrency/rollback | High | **Closed on current-hash disposable MariaDB evidence.** Contention, same-key reuse, collision, three transaction faults, rollback, deadlock retry, connection loss, protected lineage and ordinal reconciliation are covered. Real reservation remains disabled. |
| `IMR-P3-007` complete authoritative dry-run measurement | High | **Closed for empty-cohort Phase 4A mechanics.** The closed 476 registry is complete; missing provider, nonzero runtime, target mismatch and nonbinding accepted command paths are tested. No populated provider exists. |
| `IMR-P3-008`, `DBI-008` evidence/audit semantics | Medium | **Closed for foundation scope.** Remediation/provenance require verified immutable envelopes; source-row/exact-duplicate completeness is validated; protected migration audit is required. |
| `IMR-P3-009`, `SEC-P3-004`, `SEC-P3-006` key/access/retention lifecycle | Medium | **Closed for fail-closed Phase 3B scope.** External key references, authority-bound append-only rotation, classification/purpose access and denied purge are implemented. Real custody/retention approval remains external before protected population. |
| `IMR-P3-010` authoritative target-state policy binding | Medium | **Closed.** Exact path/hash/version/contract-ID/approval binding covers 21 JSON specifications and two decision documents; unresolved patient/insurance tuples remain commit blockers. |
| `IMR-P3-011`, `SEC-P3-005` privacy detector gaps | Medium | **Closed.** Scanner v3 covers the cited formats and operational-guard paths; unexpected target enums are count-only. |
| `IMR-P3-012`, `DBI-009` partial MariaDB DDL recovery | Medium | **Closed.** Create/add-only manifests, identity/session re-observation, exact object adoption, drift/destructive refusal and zero-operation rerun are evidenced. |
| `IMR-P3-013` Classic table-level privilege inspection | Medium | **Closed in implementation.** Global/schema/table/column/routine/role surfaces and all-55-table `SELECT` coverage on exact `uuhms` are verified without attempting a write. D-101 remains external. |
| `DBI-007` ordinal identity | Medium | **Closed.** Duplicate lineage/ordinal, invalid range, missing coverage and count mismatch are rejected. |
| `DBI-010` test/capability traceability | Medium | **Closed for migration foundation evidence.** Current recovery/isolation/allocator hashes and named suites agree. Broad application traceability remains separately recorded as `IMR-P3B-002`. |

## Confirmed safety and scope

- Physical identity and partial DDL recovery remain non-production, identity-bound, create/add-only and fail closed. Real target/TLS approval is not inferred from disposable evidence.
- Protected storage, key rotation, purpose-scoped access and denied purge remain protected and fail closed; no real key or protected patient data was used.
- Source, target, run and collision snapshots remain authoritative and protected; no live capture occurred.
- Phase 2F policy binding remains exact over all 23 artifacts; unresolved target-state decisions still block commit.
- Classic privilege inspection covers all privilege surfaces and the 55-table catalogue; no Classic connection was opened.
- Ten durable crash/restart boundaries, 25-subsystem isolation, 127 operational guards and current-hash MariaDB allocator evidence remain intact.
- The raw broad JUnit remains correctly purged after eight detector matches. The retained manifest contains stable test identifiers and hashes, not failure output or record values.
- Configuration defaults keep foundation, commit, Cohort B, importer, production, protected population, recovery, evaluator and DDL activation disabled.
- Only six foundation commands exist. No domain importer, populated aggregate provider, patient/alias/contact/insurance persistence adapter, Cohort B selector, pilot runner or business-table migration exists.
- `DatabaseSeeder.php` is unchanged and was not run. No production, Classic or renewed business-domain write was performed.

## Independent verification

- Corrected dry-run selection: `php artisan test tests/Unit/LegacyMigration/Foundation/Reconciliation tests/Unit/LegacyMigration/Foundation/Reporting tests/Feature/LegacyMigration/Foundation/AuthoritativeRunCaptureCoordinatorTest.php --compact` — **12 passed, 60 assertions**.
- Direct nonempty-source probe — `ObservedEmptyCohortAggregateProvider` refused a source snapshot with a nonzero patient-root count.
- Full focused foundation suite: `php artisan test tests/Unit/LegacyMigration/Foundation tests/Feature/LegacyMigration/Foundation --compact` — **320 passed, 1,676 assertions, 3 expected gated/worker skips**.
- Broad manifest checks — 47 unique cases; 28 unique files; broad count 47; isolated distinct application-method count 44; command, base configuration, executed configuration, repository `HEAD` and all 28 file hashes match; no retained failure output; historical green baseline explicitly unavailable.
- Mandatory privacy scan after the final review update — scanner `P3B-PRIVACY-SCANNER-3`, 785 files, coverage difference 0, 66 allow-listed findings, 0 unallowlisted findings, release not blocked.

No disposable MariaDB server or broad repository suite was restarted by this reviewer. Current source hashes and retained attestations were inspected. No raw JUnit was recreated.

## External Phase 4A activation prerequisites

Repository authorization is not sufficient to run the exercise. Phase 4A still requires the DBA-provisioned D-101 account, owner-approved physical non-production/TLS identity and network denial of production, external Phase 2F and key-reference manifests with exact pins, real secret resolver/key custody, installed protected foundation schema, approved access/retention classifications, directly observed queue-worker/scheduler/null-sink barriers, and a deliberately bound synthetic recorder provider for the empty-cohort exercise. None may be replaced by a caller boolean or local environment label.

Any nonzero patient, child or insurance source population must stop. A populated cohort requires a new separately reviewed aggregate provider and separate authorization; Phase 4A does not grant it.

## Final authorization

| Gate | Decision |
|---|---|
| Critical findings | **0** |
| High findings | **0** |
| Medium findings | **0** |
| Phase 3B repository gate | **PASS** |
| Phase 4A synthetic empty-cohort foundation exercise | **AUTHORIZED subject to external prerequisites** |
| Populated cohort, importer, Cohort B and patient pilot | **BLOCKED** |
| Classic, production and business-domain writes | **BLOCKED** |

The non-green broad suite still prevents any stronger whole-application green-baseline or release-readiness claim. Phase 4A must stop on any external prerequisite mismatch, nonempty source population, missing measurement, failed measurement, privacy finding, write delta or unproved isolation barrier.
