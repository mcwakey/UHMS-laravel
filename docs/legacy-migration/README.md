# Classic UHMS migration discovery

## Status

Phase 1A completed and was independently reviewed on 2026-07-20. Phase 1B evidence closure and governance-pack preparation completed independent review on 2026-07-21. On 2026-07-21 the project owner directly accepted all 36 controlled outcomes across the nine packs; no workshop occurred. The final decision-authority package also passed independent migration review with no remaining evidence-backed defects. All 29 Phase 1B business-policy questions are resolved and Phase 2 detailed mapping may begin.

No importer, migration-state table, administrative UI, synchronization implementation, orchestration code, or database behavior was introduced by this governance update. Neither database was modified.

The stakeholder-confirmed Classic source is the MySQL/MariaDB schema `uuhms`. The actual non-production renewed target `uhms_clean` has now been captured read-only and compared with repository migrations, models, enums, services and the stale schema dump. This capture does not authorize use of either database for production migration tests.

## Evidence labels

- **Confirmed**: observed directly in read-only database metadata/aggregates or repository code.
- **Inferred**: a reasoned interpretation that still needs validation.
- **Accepted policy**: approved by direct project-owner decision; still not implemented behavior.
- **Phase 2 task**: technical detail needed to implement accepted policy; not an unsigned governance blocker.
- **Proposed**: an architectural recommendation outside the owner directive; not implemented behavior.

Counts and data-quality findings are aggregate and contain no patient-identifying data. Approved categorical literals remain readable; unapproved literals are collapsed inside the database before results are returned, and the artifact records only their combined row count and distinct-value count. Repository paths are used as target-side evidence. Classic relationships are inferred unless explicitly stated because `uuhms` has no foreign keys.

## Rerunnable Phase 1B commands

```powershell
php artisan legacy-migration:inspect-target --connection=mysql --expected-database=uhms_clean --output=docs/legacy-migration
php artisan legacy-migration:capture-classic-evidence --connection=legacy_uhms --expected-database=uuhms --output=docs/legacy-migration/evidence
```

Both commands fail closed on database-name mismatch, constrain output below `docs/legacy-migration`, establish REPEATABLE READ plus session-level READ ONLY before opening a transaction, and issue only trusted static `SELECT` evidence queries after those controls. The Classic artifact records the observed isolation, read-only flag and connection transaction level rather than inferring snapshot state from `tx_read_only` alone. The Classic command refuses every connection/schema pair except `legacy_uhms`/`uuhms`. These safeguards do not make the current broad-privilege Classic account acceptable for migration execution; a DBA-provisioned SELECT-only account remains mandatory.

## Discovery package

- [MASTER_MIGRATION_PLAN.md](MASTER_MIGRATION_PLAN.md): programme phases, gates, controls, and handoff.
- [DECISIONS.md](DECISIONS.md): accepted policy and remaining proposed technical architecture decisions.
- [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md): resolved business questions and retained Phase 2 technical tasks.
- [LEGACY_DATABASE_INVENTORY.md](LEGACY_DATABASE_INVENTORY.md): all 55 Classic tables and classifications.
- [LEGACY_RELATIONSHIPS.md](LEGACY_RELATIONSHIPS.md): declared and inferred relationships.
- [LEGACY_STATUS_VALUES.md](LEGACY_STATUS_VALUES.md): observed workflow/status vocabularies.
- [LEGACY_DATA_QUALITY_REPORT.md](LEGACY_DATA_QUALITY_REPORT.md): aggregate exceptions and risks.
- [TARGET_SCHEMA_INVENTORY.md](TARGET_SCHEMA_INVENTORY.md): renewed domains and schema caveats.
- [TARGET_DOMAIN_RELATIONSHIPS.md](TARGET_DOMAIN_RELATIONSHIPS.md): target dependency graph and invariants.
- [TARGET_REQUIRED_FIELDS.md](TARGET_REQUIRED_FIELDS.md): important destination constraints.
- [TARGET_CREATION_RULES.md](TARGET_CREATION_RULES.md): operational creation rules unsafe for history.
- [TARGET_SIDE_EFFECTS.md](TARGET_SIDE_EFFECTS.md): observers, events, schedulers, and mutation risks.
- [PRELIMINARY_MAPPING_MATRIX.md](PRELIMINARY_MAPPING_MATRIX.md): evidence-based candidate mappings and gaps.
- [MIGRATION_DEPENDENCIES.md](MIGRATION_DEPENDENCIES.md): preliminary import order and deferred links.
- [RECOMMENDED_MIGRATION_SCOPE.md](RECOMMENDED_MIGRATION_SCOPE.md): proposed inclusion, exclusion, and decision gates.
- [TARGET_INSTALLED_SCHEMA_INVENTORY.md](TARGET_INSTALLED_SCHEMA_INVENTORY.md): actual non-production installed catalogue.
- [TARGET_SCHEMA_DRIFT_REPORT.md](TARGET_SCHEMA_DRIFT_REPORT.md): migration-ledger, dump, conditional and raw-DDL drift.
- [evidence/](evidence/): sanitized Classic schema, query, aggregate, relationship, incremental and fingerprint manifests.
- [workshops/](workshops/): nine evidence packs with direct-owner controlled outcomes; no workshop was conducted.
- [APPROVED_DECISION_SPECIFICATIONS.md](APPROVED_DECISION_SPECIFICATIONS.md): canonical approved policy wording.
- [WORKSHOP_DECISION_EXTRACTION.md](WORKSHOP_DECISION_EXTRACTION.md): all 36 outcomes and their Phase 2 tasks.
- [WORKSHOP_CONTRADICTION_REPORT.md](WORKSHOP_CONTRADICTION_REPORT.md): shared-decision reconciliation and residual technical work.
- [PHASE_2_ENTRY_REPORT.md](PHASE_2_ENTRY_REPORT.md): detailed-mapping entry scope, sequence, and implementation blockers.
- [PHASE_1B_READINESS_MATRIX.md](PHASE_1B_READINESS_MATRIX.md): domain-by-domain mapping gates.
- [PHASE_1B_EXIT_REPORT.md](PHASE_1B_EXIT_REPORT.md): exit-criteria and independent-review outcome.

## Principal discovery conclusions

1. `uuhms` contains 55 InnoDB tables, 479 columns, no views, no foreign keys, and 1,548,058 rows.
2. The current database account is capable of DDL/DML and the server/default session state is writable. The discovery command separately established and verified its session-level read-only transaction. Migration execution must not begin until a dedicated `SELECT`-only Classic account and schema fingerprint gate exist.
3. The source has substantial orphan, duplicate, enum, date, clinical-completeness, and financial-reconciliation exceptions. None may be silently discarded or defaulted.
4. Renewed UHMS operational services commonly generate identifiers, stamp current actors/times, bill, post accounting, mutate stock/bed/queue state, notify users, or write audit activity. Historical import requires a migration-specific persistence boundary and explicit side-effect suppression.
5. The actual target has 335 tables and 5,347 columns. The checked-in `database/schema/mysql-schema.sql` covers 106 tables and 133 migrations, so it cannot be treated as installed-target truth.
6. The target ledger has 305 of 306 repository migrations. The missing item is a permission-data migration, while the installed schema is current; the intended unique active-invoice index is absent and only its non-unique fallback exists.
7. Patient identity, actors, finance, stock, statuses, dates, orphans, privacy, and cutover policies are approved. Their field-level transformations, exception codes, reconciliation contracts, and extraction/runtime designs remain Phase 2 work.

## Phase handoff

Phase 1B has produced and independently passed the evidence package. The later direct-owner directive supersedes the workshop process and resolves the business-policy gates without claiming a meeting, quorum, signature, or committee approval.

Begin **Phase 2A — Organisation and Reference Crosswalk Specifications**. Detailed mapping is authorized in the sequence in [PHASE_2_ENTRY_REPORT.md](PHASE_2_ENTRY_REPORT.md). Importer implementation remains blocked until the complete Phase 2 contracts and reviewed migration foundation exist.
