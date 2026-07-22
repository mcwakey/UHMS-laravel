# Patient pilot crosswalk interface

## Boundary

This is a documentation-only logical interface. It does not create crosswalk tables or authorize persistence. Classic access is limited to read-only `legacy_uhms` / `uuhms`; target comparison is read-only in the approved non-production environment.

Authority: D-006, D-201, D-211, D-212 and D-213. Normative machine contract: `specifications/patient_pilot_crosswalk_interface.json` version 2F.1.0.

## Common protected envelope

Every patient and reference crosswalk message binds interface/pilot versions, contract-bundle hash, opaque run/cohort tokens, source and target snapshot tokens, schema fingerprints, HMAC-key and canonicalization versions, migration timestamp, keyed record checksum, retention class and access class.

Tokens use the Phase 2C/2D/2E domain-separated HMAC contracts. Raw patient/reference keys, target IDs, PHI and key material never enter repository artifacts.

## Patient crosswalk

Required logical fields are:

- protected source-patient and `PATIENT-PRIV-007` root tokens;
- protected target reference, nullable before mapping;
- branch: new target, explicit existing target, quarantined or not commit-eligible;
- mapping state;
- patient-number action/result reference;
- authoritative rule and transformation versions;
- existing-target evidence and idempotency references;
- keyed mapping checksum, revocation state and migration-audit reference.

One source token has at most one active target mapping at one contract/snapshot coordinate. An approved mapping resolves exactly one target. Existing-target links remain immutable and authorize neither child enrichment nor merge-pointer traversal.

The future runtime resolves a compatible prior mapping before allocating a patient number. The Classic primary key remains protected linkage evidence and is never reused as a renewed primary key.

## Reference crosswalk

The interface carries a protected source-reference token, allow-listed domain, protected target reference, exact Phase 2A crosswalk/rule version, match authority, status, conflict state and snapshot token.

Only a unique resolved result releases a dependency. Unresolved, ambiguous, conflicting, inactive or drifted parents remain held. Phase 2F does not repeat Phase 2A canonicalization or invent a provider, department or other parent.

## Reconciliation

`PATIENT-REC-EXISTING-030` remains authoritative for explicit patient links. `PATIENT-REC-PRIVACY-050` proves protected lineage. Crosswalk cardinality, immutable-target delta, prior-lineage compatibility and patient-number rerun assertions must all reconcile with zero difference.

