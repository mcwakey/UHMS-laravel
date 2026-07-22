# Patient registration attribution

Status: **binding Phase 2C attribution contract; no persistence is authorized.**

This contract applies to historical Classic patients represented as new renewed-UHMS patient rows. It consumes Phase 2B `TARGET-ACTOR-054` and narrows the general D-202 unknown-actor policy for this exact field.

## Evidence and installed target constraint

Classic `uuhms.patients` has no creator, registrar, user, staff, username, clinician, or other registration-actor column. It has `RegDate`, which is candidate registration time evidence, but time does not identify an actor. Classic declares no patient-to-user relationship. Name, department, current operator, extraction account, and migration executor context are not registrar evidence.

Installed target `patients.registered_by` is nullable `bigint unsigned`. FK `patients_registered_by_foreign` references `users.id`, with `ON UPDATE RESTRICT` and `ON DELETE SET NULL`. `Patient::registeredBy()` does not include soft-deleted users, so a retained FK can become invisible through the normal relationship. FK compatibility proves only that an ID exists; it does not prove that the person registered the historical patient.

Normal `PatientService::create()` always overwrites supplied attribution with `Auth::id()`. It is therefore prohibited as a historical patient import entry point.

## Mandatory outcome

For every newly created migrated patient under the current evidence coordinate:

```text
patients.registered_by = null
```

and exactly one protected provenance result must record that the Classic patient master contains no registration-actor evidence.

This is not an unknown user, a system user, or a failed patient. Missing registration attribution does not block an otherwise valid patient because the target field is nullable and `TARGET-ACTOR-054` makes the missing actor attribution-only. The absence must be explicit and reconcilable; it must not be silently dropped.

## Prohibited substitutions

The following are always prohibited for `patients.registered_by` absent a new approved field-semantic evidence contract:

- `Legacy Actor Unknown`;
- current/authenticated user;
- migration executor or importer account;
- first target user;
- administrator or system operator;
- source/target database account;
- department supervisor or registration-desk user;
- a verified historical staff identity that lacks evidence of performing this registration;
- any actor inferred from patient name, dates, OPD, attendance, clinician text, department, or target FK availability.

Use `LEGACY-STAFF-ACTOR-018` for any attempted prohibited unknown-actor substitution. Use `LEGACY-STAFF-ACTOR-010` for unresolved or prohibited actor mapping under `TARGET-ACTOR-054`. A prohibited attempt stops the affected commit/reconciliation; it is never repaired by selecting another fallback.

## Protected absence provenance

The future protected migration ledger—not `patients`, `activity_log`, a merge log, a public report, or source control—must bind the absence outcome to:

1. opaque source-patient token generated with domain-separated HMAC-SHA-256;
2. source system/schema/table identity and verified source snapshot/fingerprint;
3. source row-content fingerprint and canonicalization version;
4. target field `patients.registered_by` and rule ID `TARGET-ACTOR-054`;
5. disposition `TARGET_NULL_PROVENANCE_ONLY`;
6. reason `NO_APPROVED_CLASSIC_REGISTRATION_ACTOR_EVIDENCE`;
7. applicable Phase 2B exception/rule version;
8. migration run, transformation, approval, and reconciliation references;
9. target patient mapping reference after successful creation, stored only in the protected operational crosswalk;
10. explicit assertion that no unknown/current/first/admin/importer fallback was used.

The ledger may contain the typed Classic primary key only inside protected crosswalk storage. Documentation, ordinary logs, exception exports, screenshots, metrics, and source-controlled artifacts use aggregate counts and nonreversible opaque tokens only. Low-entropy identifiers must use keyed, domain-separated HMAC rather than plain hashes. Keys remain environment-controlled and versioned.

Migration execution identity belongs in the separately labelled migration audit. It must never be copied to `patients.registered_by` or represented as the historical registrar. Likewise, `RegDate` may become an approved historical timestamp after chronology validation, but it does not alter the null actor outcome.

## Existing-target links

An explicit protected link to an existing renewed patient allocates no patient and changes no field. It preserves the existing target `registered_by` exactly as found, including null. Classic absence provenance may be retained as source evidence, but it must not overwrite, clear, validate, or contradict the target registrar automatically.

A target registrar that points to a soft-deleted user is not rewritten. The relationship visibility issue is recorded for review; migration must not restore the user, follow another identity, or substitute null as part of the link path.

## Future evidence change

If a previously unknown Classic registration-actor field or protected external evidence is discovered, it does not automatically authorize population. The proposed actor must return through:

1. the Phase 2B verified-employment ladder;
2. unique protected source-to-target actor resolution;
3. proof that the source field semantically means “registered this patient,” not merely creator of a related record;
4. target action-role and chronology validation for this field;
5. a reviewed revision of `TARGET-ACTOR-054` and this contract;
6. updated extraction, exception, provenance, and reconciliation versions.

Until all six are approved, the required outcome remains null plus protected absence provenance. General D-202 permission to use `Legacy Actor Unknown` in some historical fields does not override the field-specific prohibition here.

## Persistence and runtime controls

The migration-specific patient persistence boundary must set `registered_by` explicitly to null and must not pass through `PatientService::create()`. It must also suppress Eloquent/Spatie operational activity, authenticated-user stamping, observers, notifications, integrations, and current-time workflow evidence. The separately labelled migration audit records the executor and run truthfully without impersonating a Classic registrar.

The target insert, protected source-to-target mapping, protected absence-provenance result, and migration-audit reference must commit atomically or through an independently reviewed exactly-once protocol. Dry-run writes none of them and reports only aggregate attribution partitions.

## Reconciliation

For new patient candidates under the current contract:

```text
source candidates
  = verified target registrar
  + permitted Legacy Actor Unknown
  + target null/provenance-only
  + quarantined
  + failed
```

Current expected outcomes are:

- verified target registrar = 0;
- permitted Legacy Actor Unknown = 0;
- target null/provenance-only = every successfully created new patient;
- unauthorized unknown/current/first/admin/importer/system fallback = 0;
- patient blocked solely because registrar is absent = 0;
- successful new patient with non-null `registered_by` = 0;
- successful new patient lacking exactly one protected absence result = 0.

For explicit existing-target links, reconcile separately: target rows updated = 0; target registrar changes = 0; new absence results must not be misreported as target-field changes.

Before/after checks must prove that no linked existing patient's registrar changed and that no new patient received a non-null registrar. Reconciliation output contains aggregates only.

## Prerequisites and stop conditions

Persistence remains blocked until the protected crosswalk/provenance store, domain-separated HMAC key/version controls, atomic patient persistence, separate migration audit, dry-run/checkpoint/reconciliation framework, and runtime-isolation gates are reviewed. The Phase 2B hardened historical-user prerequisite is not needed merely to store null; it becomes relevant only if future verified field-semantic evidence authorizes a historical actor.

Stop on a non-null registrar for a newly migrated patient; any fallback selection; missing or duplicate absence provenance; raw actor/patient identifiers in diagnostics; target registrar mutation on an existing-target link; actor-rule version drift; or use of migration execution identity as historical attribution.

## Confirmed and unresolved

**Confirmed:** Classic has no patient registration actor; target `registered_by` is nullable with a user FK; normal creation stamps the authenticated user; and Phase 2B explicitly prohibits Legacy Actor Unknown for this field.

**Unresolved only if new evidence appears:** whether a newly discovered source or external actor fact proves both verified employment identity and registration-action semantics. No such evidence exists in the approved Phase 2C coordinate.
