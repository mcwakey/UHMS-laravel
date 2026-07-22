# Patient number contract

Status: Phase 2C specification only. This contract authorizes no importer, schema change, sequence mutation, target write or Classic write.

## Authority and evidence

This contract implements D-201/Q-001, D-211/Q-008, D-213/Q-007, D-102, D-104/D-214, D-105 and D-210. It consumes the Phase 2A fail-closed extraction pattern and the Phase 2B actor contract without reopening either.

Confirmed evidence:

- Classic `uuhms.patients` has 16,950 rows. `PAT_ID` is its integer auto-increment primary key and `OpdNo` is its only OPD-number field. Neither is a renewed patient-number source.
- The installed non-production target fingerprint is `12e3a4c6ca80a0c1a54ea9267d9c4935453b7a36d27c7f8ad900062c1c12bff0`. Installed `patients.patient_number` is non-null `varchar(191)` with a unique key; soft deletion does not release that key.
- Installed `patient_number_sequences` has an unsigned `last_sequence` and one unique row per `(prefix, period_type, period_key)`. `period_type` permits only `never`, `yearly`, `monthly` and `daily`.
- The effective local runtime observed during target discovery was prefix `UHMS`, pattern `{PREFIX}-{SEQUENCE}/{YEAR}`, width 6, yearly reset and UTC. The repository fallback pattern is different. The observed effective pattern is evidence of the inspected environment, not a portable or approved hard-coded migration value.
- The sanitized discovery baseline contained 100 patient rows and one current configured sequence row with `last_sequence = 100`. This mutable aggregate is not a cutover guarantee and must be recaptured at preflight/freeze.
- `PatientIdGeneratorService::generate()` locks an existing sequence row, but its missing-row path can race, it can commit allocation separately from patient creation, and it appends a nondeterministic suffix on collision. `Patient::generatePatientNumber()` is a second, nonlocking generator. `PatientService::create()` always allocates a new number, stamps `Auth::id()` and may write an avatar.
- Phase 2B `TARGET-ACTOR-054` requires `registered_by = null` plus protected absence provenance for this source. Number allocation must not cause an actor fallback.

The authoritative evidence sources are `TARGET_CONSTRAINT_MANIFEST.json`, `TARGET_SCHEMA_FINGERPRINT.json`, `TARGET_INSTALLED_SCHEMA_INVENTORY.md`, the Classic schema/aggregate manifests, the four Phase 2C discovery drafts, `config/patient.php`, `PatientIdGeneratorService`, `PatientService`, `Patient` and the installed sequence migration.

## Core invariants

1. Every newly created renewed patient receives exactly one target-generated number.
2. Classic `PAT_ID`, `OpdNo`, `OriginalOpd` and every other Classic value are prohibited as the target primary key or patient number.
3. An explicit existing-target link keeps its existing target number and allocates no number.
4. A successful source mapping is authoritative on rerun: resolve and validate it before any allocator call.
5. One source identity maps to at most one successful target patient and one generated number. One new target patient/number belongs to at most one source identity unless a separately approved reviewed identity determination explicitly says otherwise.
6. Soft-deleted, merged and archived target number history remains reserved. No delete/recreate, restore, merge-pointer following, sequence rewind or number recycling is an idempotency technique.
7. Dry-run changes neither sequence nor mapping state and never claims that a projected number is reserved.
8. A target uniqueness error, collision or sequence discrepancy is a classified failure, not permission to suffix, skip silently or retry with a different unexplained value.

## Effective configuration prerequisite

Every dry-run and commit preflight must capture the cache-resolved effective values, not merely source-code defaults:

- prefix;
- complete pattern;
- sequence width;
- reset period;
- application timezone;
- supported placeholder set and formatter version;
- allocation clock and resolved period key;
- target database identity, schema fingerprint and relevant collations.

Canonicalize these values with explicit names/types and hash them with SHA-256 as nonsecret configuration metadata. Pin the fingerprint to the run and allocation window. The effective configuration must satisfy all of the following:

- `{SEQUENCE}` occurs exactly once and the formatted value is injective for different sequence values within the active coordinate;
- every placeholder is supported and clock-derived placeholders agree with the pinned timezone/period;
- reset period is one installed enum value;
- prefix and period key fit their target columns;
- width is positive, capacity is sufficient for the planned window, and overflow cannot silently widen or truncate the value;
- the complete formatted number fits `patients.patient_number`;
- the coordinate `(prefix, period_type, period_key)` is stable for the window.

The observed effective local prefix/pattern combination (`UHMS` plus `{PREFIX}-{SEQUENCE}/{YEAR}`) may be reproduced only when this preflight proves it is still the cache-resolved cutover configuration. A changed, absent or noninjective configuration stops the run. An allocation window must close and reconcile before its configured UTC reset boundary; a later coordinate starts a new window without changing earlier mappings.

## Required future allocation boundary

For one eligible new source patient, the future migration-specific persistence boundary must execute the following order on one target connection and in one transaction:

1. Derive the protected typed source token for exact `uuhms.patients.PAT_ID`; never expose or write the raw key as a target identifier.
2. Lock/read the crosswalk or mapping state by its unique source constraint.
3. If a successful mapping exists, validate its target ID, target number, state and provenance and return it with zero allocation and zero target mutation.
4. Otherwise create or lock one durable allocation/mapping state. Concurrent attempts for the same source must converge on that state.
5. Lock the active sequence row. If absent, use a duplicate-key-safe initialize/retry-and-lock protocol; the current check-then-insert path is insufficient.
6. Increment once and format with the pinned configuration and clock coordinate.
7. Check the complete lookup namespace: all live/soft-deleted/merged `patients.patient_number` values, archived patient-number snapshots, and aliases exposed by patient search. Use target collation semantics as well as application canonical equality.
8. On collision, roll back and classify the exact conflict. Do not append a suffix or scan forward silently.
9. Insert the validated patient through the migration-specific boundary, with `registered_by = null`, explicit approved historical timestamps and all operational side effects suppressed.
10. Persist the successful crosswalk, allocation/configuration provenance and migration audit as part of the same atomic outcome.
11. Commit, then advance the source checkpoint. A crash after commit and before checkpoint advance is safe because resume consults the mapping first.

If crosswalk state, sequence allocation, patient creation and audit cannot share this atomic outcome, implementation is blocked until an independently reviewed reservation/outbox protocol proves equivalent exactly-once behavior. Calling unchanged `PatientService::create()`, `PatientIdGeneratorService::generate()` or `Patient::generatePatientNumber()` from migration is prohibited.

## Dry-run, retry, resume and rollback

| Situation | Required outcome |
|---|---|
| Dry-run eligible new patient | `TARGET_GENERATED_AT_COMMIT`; validate format, capacity, namespace and expected counts without allocation/reservation. Any projected value is advisory only. |
| Successful mapping already exists | Return the same target ID and stored target number after invariant checks; sequence delta 0. |
| Same source races in two workers | Unique source ownership plus locking permits at most one allocation/commit; the loser resolves the winner's mapping. |
| Incomplete durable reservation | Resume only that reservation after ownership, configuration and namespace validation; never allocate a second number. |
| Failure before atomic commit | Roll back patient, mapping, audit and sequence change together. No successful number exists. |
| Crash after commit/before checkpoint | Resume from mapping; checkpoint may advance only after mapping/target verification. |
| Changed source content after success | Classify for review/update; never create a replacement patient or number. |
| Post-commit correction/reversal | Preserve number and mapping. Sequence rewind, delete/recreate and replacement allocation are prohibited pending a separately approved foundation reversal contract. |
| Explicit existing-target link | Reuse immutable target ID/number; allocation and sequence delta 0. |

## Concurrency contract

Sequence-row locking prevents duplicate sequence increments only when every allocator uses the same coordinate and protocol. It does not by itself make source mapping idempotent or make operational/migration consumption exactly reconcilable.

The approved choices for a commit window are:

1. freeze operational patient registration and prove the freeze remains effective; or
2. use one unified, exact allocation ledger for every operational and migration allocation, with the same locking/configuration semantics.

The installed sequence counter alone cannot distinguish operational allocations from migration allocations. Concurrent operational registration without the unified ledger is therefore a stop condition, even though the database unique key may prevent duplicate final values.

For a frozen coordinate:

`S_after - S_before = M_committed + K_explained`

For a ledgered concurrent coordinate:

`S_after - S_before = M_committed + O_committed + K_explained`

`S` is `last_sequence`, `M_committed` is atomic committed migration allocations, `O_committed` is exact operational allocations, and `K_explained` is explicitly evidenced non-patient consumption. With the specified atomic fail-on-collision path, expected `K_explained = 0`; any nonzero value requires protected allocation evidence and review.

## Collision and soft-delete rules

- Query patients unscoped/with-trashed. A soft-deleted or merged patient continues to own its patient number.
- An archived snapshot is collision evidence even if its parent is unavailable. `archived_patients.patient_number` is indexed but not unique, so application preflight must include it.
- A patient alias colliding with a proposed number is a lookup-namespace conflict even though no cross-table database unique key exists.
- Never restore a soft-deleted patient, follow a merge pointer, release an alias, overwrite an archive, or modify an existing target merely to make a number available.
- If the next formatted value collides, stop the affected window and classify it as target patient-number conflict/sequence inconsistency. The current random suffix behavior is forbidden.

## Reconciliation

Contract ID: `PATIENT-REC-NUMBER-020`.

Under an isolated target window:

`successful_new_source_mappings = target_patient_rows_created = target_numbers_allocated = durable_success_mappings`

and:

`successful_new_source_mappings = distinct source tokens = distinct new target IDs = distinct generated target numbers`

When target writes are not frozen, raw table deltas are insufficient; use exact run-attributed ledger rows. For every sequence coordinate also prove:

- coordinate rows after initialization = 1;
- `max(parsed committed sequence component) <= last_sequence`;
- duplicate generated target numbers = 0;
- successful source tokens with more than one target ID/number = 0;
- successful target IDs/numbers owned by more than one source without explicit reviewed authority = 0;
- successful reruns that allocate or replace a number = 0;
- allocations for explicit existing-target links, quarantined rows or excluded rows = 0;
- Classic keys or OPDs used in a target ID/number assignment = 0;
- random/decorated collision suffixes = 0;
- migration calls to either current runtime generator = 0;
- mappings whose target is missing, soft-deleted, merged inconsistently, or has a different number = 0.

`last_sequence < max(parsed committed sequence component)` is underflow and always stops. A higher `last_sequence` can reflect historical consumption, but every gap within the migration window must be explained by protected ledger evidence; absence of that evidence stops reconciliation.

## Classified failures and ownership

| Code | Use in this contract |
|---|---|
| `LEGACY-PATIENT-NUMBER-037` | Target-number collision; invalid/drifted/noninjective configuration; prohibited Classic identifier or unsafe generator path; period rollover; target number/config constraint drift. |
| `LEGACY-PATIENT-NUMBER-038` | Existing successful mapping reaches allocation; source/target mapping cardinality conflict; replacement number attempt; checkpoint/mapping/target disagreement. |
| `LEGACY-PATIENT-NUMBER-039` | Sequence underflow/gap discrepancy; unsafe concurrent allocator; first-row initialization race; allocation atomicity or sequence-window reconciliation failure. |
| `LEGACY-PATIENT-PRIVACY-036` | Missing/invalid provenance or HMAC contract, key/version failure, or protected identifier disclosure. |

Number/configuration, uniqueness, atomicity, production-target suspicion and privacy failures are immediate stop/owner-review conditions. The migration technical owner and patient identity governance owner jointly approve release. SLA expiry never authorizes a suffix, sequence skip, mapping replacement or unsafe retry.

## Privacy and protected provenance

Repository artifacts and ordinary logs contain no patient number, Classic OPD, raw Classic key, raw crosswalk value or row-level diagnostic. The operational mapping/allocation ledger is protected patient data.

- Use domain-separated HMAC-SHA-256 `patient-source-key-v1` for typed source identity and a separate `patient-number-allocation-v1` domain for row-level allocation evidence.
- Record purpose/domain, algorithm, key version and canonicalization version. Canonical messages use length-prefixed UTF-8 values with explicit type/null markers.
- Keys remain environment-controlled and uncommitted. HMACs are compared only within an authorized environment; key/version loss is a stop condition.
- Plain SHA-256 is permitted for the nonsecret configuration/schema/specification fingerprint, never for low-entropy patient identifiers.
- Reports contain only aggregate counts, rule IDs and nonidentifying fingerprints; row-level HMACs remain in the protected operational ledger.

## Stop conditions and Phase 3 prerequisites

Stop before or during allocation if any core invariant, reconciliation equation or preflight check differs, including:

- source is not exact read-only `uuhms`, the D-101 account is absent, or the approved 55-table/479-column source shape/fingerprint differs;
- target is production/suspected production, or target patient/alias/archive/sequence schema, key or collation differs;
- effective configuration is absent, invalid, changed after preflight, or differs across workers;
- protected unique source mapping/allocation state, atomic persistence design, HMAC key/version or sanitized target-state snapshot is absent;
- operational registration is not frozen and no exact unified ledger exists;
- the active coordinate changes or its reset boundary is crossed;
- first-row initialization cannot resolve a unique-key race safely;
- the complete lookup namespace was not checked, a collision occurs, or sequence gaps/underflow are unexplained;
- an existing successful source reaches allocation, or checkpoint/mapping/target facts disagree;
- a prohibited generator, actor fallback, side effect, Classic-key assignment, deletion/recreation, number recycling or sequence rewind is attempted;
- target uniqueness fails or protected patient data reaches documentation, logs or source control.

Before Phase 3 patient persistence, approve and implement the protected crosswalk/allocation state, atomic migration boundary, pure migration-safe allocator, configuration fingerprint, freeze or unified ledger, collision snapshot, checkpoint/reversal protocol, side-effect isolation and privacy key-management contract. This document defines those prerequisites; it does not implement them.
