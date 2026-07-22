# Patient pilot rollback and resume contract

## Boundary

Version `2F.1.0`. The exact Classic source is connection `legacy_uhms`, database `uuhms`, and is strictly read-only. The approved non-production target is also read-only throughout Phase 2F. This is a fail-closed Phase 3 interface: it authorizes no source write, target write, importer, rollback operation or compensation action.

## Monotonic state model

`NOT_STARTED -> EXTRACTED -> CLASSIFIED -> DRY_RUN_ACCEPTED -> CORE_COMMITTING -> CORE_COMMITTED -> ALIAS_* -> CONTACT_* -> INSURANCE_HISTORY_* -> INSURANCE_CURRENT_* -> RECONCILIATION_* -> COMPLETED`.

The machine contract retains all 24 required states. Alias, contact and current-insurance stages terminate as committed or withheld; insurance history must terminate committed before a current outcome. `QUARANTINED`, `ROLLBACK_REQUIRED`, `COMPENSATION_REQUIRED` and `RECONCILIATION_FAILED` are explicit durable non-success states. Every transition binds the run/contract bundle, exact source and target coordinates, protected idempotency keys, expected prior state, attempt, write-set or zero-write evidence, mapping/provenance/reconciliation references, compensation state and keyed evidence hashes.

An unexplained checkpoint, conflicting lineage, durable target fact without its required protected bundle, missing reconciliation measurement, or changed snapshot/contract/key/collision coordinate never becomes success. It stops, reclassifies or enters the applicable failure/compensation state.

## Outcome-class rules

- An existing-target link changes only protected migration mapping/audit metadata. Recovery or compensation never mutates, deletes, restores, redirects or enriches the existing target or its children, and zero target mutation must be proved.
- An allocated number is reused only for its exact patient-core idempotency key. A replacement is never allocated; a committed number is never decremented or recycled; every consumption is committed, transactionally released or explicitly explained.
- A committed atomic unit is resolved by exact protected lineage. Missing reconciliation or checkpoint facts are repaired without replaying its target writes or deleting/recreating the unit.
- An incomplete atomic unit is transactionally rolled back when that remains provable. If partial facts escaped the boundary, they enter explicit isolation and unit-specific compensation/recovery.
- Hard deletion is not a universal rollback and is not authorized here. Optional alias/contact/current-insurance failure does not undo a valid core, and every insurance-history source row remains accounted.

## Exact crash-boundary decisions

### 1. Before number allocation

- Durable facts: classification, selected branch, patient-core idempotency inputs, pinned coordinates and zero target delta.
- Safe retry: restart preflight/classification at the same coordinates; changed evidence stops and reclassifies before allocation.
- Duplicate prevention: resolve compatible mapping/allocation lineage first and prove no Unit A intent, patient write or allocation exists.
- Rollback feasibility: nothing has been allocated or written, so no rollback applies.
- Compensation requirement: none after zero-delta proof; any unexplained allocator/target fact enters `COMPENSATION_REQUIRED`.
- Checkpoint behavior: remain `CLASSIFIED`; no Unit A success checkpoint.
- Reconciliation proof: patient, mapping, allocation and prohibited-side-effect deltas are zero.
- Operator action: refresh preflight/classification if a coordinate changed.

### 2. After number allocation, before patient commit

- Durable facts: exact allocator outcome, patient-core key, numbering fingerprint, pinned period, transaction identity and allocation status.
- Safe retry: reuse the exact allocation only after lineage/collision revalidation; never allocate a replacement.
- Duplicate prevention: one compatible allocation per core key, with patient/archive/alias namespace recheck; a unique-key collision is not success.
- Rollback feasibility: release only through proven reviewed transactional release; never manually decrement or recycle a committed allocation.
- Compensation requirement: preserve explained consumption; unexplained consumption enters `COMPENSATION_REQUIRED`.
- Checkpoint behavior: remain `CORE_COMMITTING`; record allocation evidence without core success.
- Reconciliation proof: the sequence equation classifies the allocation as committed, transactionally released or explained with zero difference.
- Operator action: investigate every unexplained allocation or sequence difference.

### 3. After patient commit, before crosswalk commit

- Durable facts: patient write set, exact allocated number, Unit A intent/transaction identity, core key and operational-isolation proof.
- Safe retry: stop normal retry; never infer ownership from likeness, create a second patient or allocate a replacement. Recovery uses exact intent and target proof only.
- Duplicate prevention: exact protected lineage must join intent, allocation and target row; equality or uniqueness alone is insufficient.
- Rollback feasibility: roll back all Unit A only while uncommitted atomic rollback is proven. A separately committed patient is not a committed Unit A and is not universally hard-deleted.
- Compensation requirement: this atomicity breach requires operational isolation, a protected orphan-migration outcome and owner-controlled recovery/compensation.
- Checkpoint behavior: never record `CORE_COMMITTED`; durably enter `COMPENSATION_REQUIRED` from `CORE_COMMITTING`.
- Reconciliation proof: patient, allocation, mapping, provenance and core reconciliation all agree as Unit A or are explicitly inconsistent; partial state cannot pass.
- Operator action: migration operator and architect jointly review before retry.

### 4. After patient/crosswalk commit, before checkpoint

- Durable facts: the exact committed Unit A patient, number, crosswalk, provenance, core reconciliation, transaction and checksum bundle.
- Safe retry: resolve the same Unit A and repair only the checkpoint; repeat no write and allocate no number.
- Duplicate prevention: one core key/crosswalk resolves to one target and number; conflict or missing bundle evidence fails closed.
- Rollback feasibility: none merely for a missing checkpoint; never delete/recreate the committed unit.
- Compensation requirement: none while the full bundle agrees; drift/inconsistency enters `COMPENSATION_REQUIRED`.
- Checkpoint behavior: write `CORE_COMMITTED` only after compare-and-verify of the durable bundle.
- Reconciliation proof: target/mapping checksum, allocation equation and core equation reproduce zero difference at the same coordinate.
- Operator action: controlled checkpoint recovery; escalate drift or lineage conflict.

### 5. During alias creation

- Durable facts: core mapping, alias key, duplicate class, collision coordinate, Unit B intent and alias pre-state.
- Safe retry: recheck parent/class/namespaces; return only exact prior lineage, otherwise create once or withhold.
- Duplicate prevention: protected alias lineage plus typed-normalized uniqueness; equality or duplicate key alone is not success.
- Rollback feasibility: roll back uncommitted Unit B only; core and allocated number remain.
- Compensation requirement: escaped partial facts receive reviewed alias-specific compensation; collision/change produces a withheld outcome, never core deletion.
- Checkpoint behavior: remain `ALIAS_PENDING` until outcome/provenance/reconciliation agree, then advance once to committed or withheld.
- Reconciliation proof: alias mapping, provenance, classification and `PATIENT-REC-ALIAS-002` pass with zero difference.
- Operator action: withhold/review changed collision or lineage; otherwise resume by exact key.

### 6. During contact creation

- Durable facts: core mapping, contact key, tuple class, Unit C intent and complete parent/contact-set/primary-count pre-state.
- Safe retry: recheck parent and one-primary invariant; return only exact prior lineage, otherwise create once or withhold.
- Duplicate prevention: protected tuple lineage is mandatory; equality is insufficient and existing-target contacts are immutable.
- Rollback feasibility: roll back uncommitted Unit C only; core remains and existing-target contacts are untouched.
- Compensation requirement: escaped partial facts receive contact-specific recovery/compensation or withholding; never patient deletion or existing-child mutation.
- Checkpoint behavior: remain `CONTACT_PENDING` until outcome/provenance/one-primary proof/reconciliation agree, then advance once to committed or withheld.
- Reconciliation proof: contact mapping/provenance and one-primary equations pass, with zero existing-target mutation.
- Operator action: withhold the optional child when safe completion is unproved; review any set difference.

### 7. During insurance-history processing

- Durable facts: complete group manifest, one intent/key/provenance record per source row and every row transaction outcome.
- Safe retry: resume only missing source-row outcomes by exact key; preserve compatible completed rows and stop on conflict.
- Duplicate prevention: one compatible outcome per protected source-row token; exact duplicate source rows retain separate lineage and none disappears.
- Rollback feasibility: roll back an incomplete row transaction or complete its metadata under the reviewed cross-resource protocol; committed compatible rows remain.
- Compensation requirement: partial cross-resource facts require row-specific recovery/compensation and an explicit pending outcome; never source-row loss or core rollback.
- Checkpoint behavior: remain `INSURANCE_HISTORY_PENDING` until every group row is terminal; then advance once to `INSURANCE_HISTORY_COMMITTED`.
- Reconciliation proof: `INS-REC-014` and group count equations account for every source row with provenance and zero difference.
- Operator action: reprocess the protected affected set and hold the group while any row is missing, failed or conflicting.

### 8. During current-membership processing

- Durable facts: complete history, group key, exact patient/provider mappings, Unit D-current intent, initialization decision and membership collision pre-state.
- Safe retry: recheck all facts; return only exact prior lineage, otherwise create once or withhold current representation.
- Duplicate prevention: one compatible current outcome per patient/provider group and at most one target representation; duplicate key or similar membership is not success.
- Rollback feasibility: roll back uncommitted Unit D-current only; history remains and unrelated/existing memberships are immutable.
- Compensation requirement: partial facts require group-specific recovery/compensation; collision or unresolved initialization becomes withheld without target mutation.
- Checkpoint behavior: remain `INSURANCE_CURRENT_PENDING` until current/withheld outcome and reconciliation agree, then advance once.
- Reconciliation proof: all group rows remain accounted, history is complete, at most one current representation exists and existing-target mutation is zero.
- Operator action: hold the group and refresh collision/initialization evidence when exact completion is unproved.

### 9. After target writes, before reconciliation

- Durable facts: exact committed atomic-unit identity, write set, transaction outcome, mappings/provenance, lineage and measured post-state.
- Safe retry: stop later stages and reconstruct reconciliation without repeating target writes.
- Duplicate prevention: exact compatible committed units are already written; never recreate them for missing reconciliation, and reject unexplained target facts.
- Rollback feasibility: do not roll back/hard-delete a valid committed unit merely for delayed reconciliation; a failed result permits only reviewed unit-specific action.
- Compensation requirement: none while pending facts agree; a failed equation/partial unit enters `RECONCILIATION_FAILED` or `COMPENSATION_REQUIRED`, never blanket deletion.
- Checkpoint behavior: remain `RECONCILIATION_PENDING`; no affected unit or run reaches `COMPLETED` before all mandatory results pass.
- Reconciliation proof: every equation is reconstructed from the exact write set/mappings/provenance and all differences/safety assertions equal zero.
- Operator action: mandatory review of every failed, missing or conflicting result.

### 10. After reconciliation, before run completion

- Durable facts: keyed passing reconciliation bundle, compatible checkpoints, terminal children, privacy, side-effect-zero proof and unchanged coordinates.
- Safe retry: revalidate drift and repair only the terminal run checkpoint; perform no target-domain write.
- Duplicate prevention: resolve every unit/checkpoint by exact lineage and prohibit replay during terminal repair.
- Rollback feasibility: no data rollback merely for a missing run-complete flag.
- Compensation requirement: none while all evidence agrees; drift or missing/nonzero proof fails closed and blocks completion.
- Checkpoint behavior: advance `RECONCILIATION_PASSED` to `COMPLETED` exactly once after full compare-and-verify.
- Reconciliation proof: all equations, checkpoint correspondences, privacy checks and prohibited-write/side-effect zeroes remain passed at the same coordinate.
- Operator action: controlled terminal repair; reopen review instead of completing when bound evidence changed.

The normative machine contract is `specifications/patient_pilot_rollback_resume_rules.json` (10 crash-boundary records and 24 states).
