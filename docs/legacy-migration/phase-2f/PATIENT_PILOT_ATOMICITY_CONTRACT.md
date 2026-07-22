# Patient pilot atomicity contract

## Boundary

Version `2F.1.0`; exact Classic boundary `legacy_uhms`/`uuhms` read-only; approved non-production target read-only during Phase 2F. These are future interfaces, not persistence implementation.

## Atomic units

| Unit | Atomic contents | Failure scope |
|---|---|---|
| A — patient core | Crosswalk-first idempotency check, sequence lock/increment, patient insert with approved state, historical creation timestamp, `registered_by=null`, protected absence provenance, mapping, core reconciliation and checkpoint | All commit together or none succeeds. After commit, never allocate a replacement number |
| A-link — existing target | Protected link, migration audit and zero-mutation proof | Ledger-only; target is never changed |
| B — OPD alias | Parent recheck, source duplicate class, target namespaces, alias or durable withheld outcome, provenance, reconciliation and checkpoint | Roll back Unit B only; core remains |
| C — emergency contact | Parent/set recheck, child key, valid tuple, one-primary proof, provenance, reconciliation and checkpoint | Roll back/withhold Unit C only |
| D-history | Durable protected outcome for every source insurance row | Group rollback or explicit pending intents; no row may disappear |
| D-current | Complete history, resolved patient/provider group, at-most-one current representation or withheld outcome, reconciliation and checkpoint | Unit D group only; unrelated target membership immutable |

If ledgers and target rows are not in one transactional resource, Phase 3 must implement a reviewed prepare/commit protocol with durable intents, enforced unique keys, recovery proof and explicit compensation. Atomicity must not be claimed from best-effort ordering.

## Operational visibility

The installed target has no migration staging state. A committed but incompletely reconciled active patient must not become operationally visible. Phase 3 must either isolate the migration environment until mandatory reconciliation passes or introduce an owner-approved staging/activation representation with application guards. Automatic soft delete, archive, inactive state or hard delete is not authorized compensation.

Normal patient, number, contact, insurance, merge or verification services are not these units. Machine contract: `specifications/patient_pilot_atomicity_rules.json` (8 records).
