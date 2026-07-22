# Classic UHMS Database Migration

## Core objective

Build a safe, repeatable, resumable and auditable pipeline for migrating
approved data from the Classic UHMS MySQL database into the renewed Laravel
UHMS MySQL database.

The final experience may appear as a one-click migration, but internally it
must use preflight checks, mapping, transformation, chunking, checkpoints,
reconciliation and failure reporting.

## Safety rules

- Treat the Classic UHMS database as strictly read-only.
- Never run INSERT, UPDATE, DELETE, ALTER, DROP, TRUNCATE or migrations
  against the Classic UHMS database.
- Never test against the renewed UHMS production database.
- Never expose patient-identifying production data in prompts, documentation,
  screenshots, logs, fixtures or source control.
- Use anonymised, sanitised or synthetic sample records.
- Do not silently discard invalid records.
- Do not silently map unknown statuses to convenient defaults.
- Do not merge patients using names alone.
- Do not reuse legacy primary keys as renewed UHMS primary keys unless a
  documented and approved exception exists.

## Migration architecture

- Maintain explicit legacy-to-renewed record mappings.
- Every importer must support dry-run.
- Every importer must be idempotent.
- Every importer must be resumable.
- Process large datasets in chunks.
- Record checkpoints and classified failures.
- Preserve historical dates where valid.
- Prevent historical imports from triggering inappropriate notifications,
  stock movements, billing actions or other runtime side effects.
- Financial records must reconcile exactly or produce explicit exceptions.
- Web actions and Artisan commands must use the same underlying services.

## Required workflow

1. Inspect the Classic UHMS schema.
2. Inspect the renewed UHMS schema and domain rules.
3. Produce an evidence-based mapping matrix.
4. Review data quality and unresolved decisions.
5. Build the migration foundation.
6. Pilot patient migration.
7. Continue by dependency order.
8. Build incremental synchronisation.
9. Perform final cutover and reconciliation.

## Agent workflow

- Use subagents for independent read-heavy discovery, review and testing.
- Do not let parallel agents modify overlapping files.
- Prefer read-only subagents during discovery.
- The main agent remains responsible for final decisions and integration.
- Clearly separate confirmed findings, inferences and unresolved questions.
- Each phase must include a written handoff.

## Testing

During implementation phases, run targeted safety checks only where needed for:

- Migration execution
- Database constraints
- Route and view compilation
- Localisation integrity
- Audit integrity
- Prevention of obvious regressions

Run the broad UHMS test suite after the full migration implementation batch is
complete.

## Documentation

Maintain all migration documentation under:

docs/legacy-migration/
