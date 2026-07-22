# Phase 2F target-state, collision, idempotency and rollback draft

## Scope and authority

This is the target-only discovery draft for Phase 2F. It inspects the renewed Laravel application and the approved local non-production target read-only. It does not access Classic, approve a business decision, authorize persistence or replace any Phase 2A–2E rule.

Authoritative composed contracts remain:

- Phase 2C `PATIENT-TARGET-*`, `PATIENT-NUM-*`, `PATIENT-ALIAS-*`, `PATIENT-COL-*` and patient reconciliation contracts;
- Phase 2D `PATIENT-CHILD-IMMUT-*`, `PATIENT-CHILD-CONTACT-*` and child reconciliation contracts;
- Phase 2E `INS-TARGET-*`, `INS-CLASS-*`, `INS-ELIG-*` and insurance reconciliation contracts;
- approved policies D-201, D-202, D-205, D-206, D-207, D-211, D-212, D-213 and D-214.

The labels below have strict meanings:

- **Confirmed:** installed metadata, sanitized read-only aggregate evidence or repository code.
- **Approved policy:** an already accepted owner decision composed from the named upstream contract.
- **Technical recommendation:** a proposed Phase 3 design; not Classic evidence and not yet persistence authority.
- **Blocking prerequisite:** commit mode remains prohibited until the stated representation or capability is approved and implemented.

## Read-only target capture

The target was refreshed on 2026-07-21 using session-level read-only transactions which were rolled back. No target rows were written.

| Evidence | Result | Classification |
|---|---|---|
| Laravel environment | `local`; application timezone UTC | Confirmed |
| Database | `uhms_clean`, MariaDB 10.4.32 | Confirmed approved non-production target |
| Structural fingerprint | `12e3a4c6ca80a0c1a54ea9267d9c4935453b7a36d27c7f8ad900062c1c12bff0` from the installed-target catalogue captured earlier on the same programme baseline | Confirmed baseline; refresh is mandatory before any future pilot |
| Installed tables | `patients`, `patient_aliases`, `emergency_contacts`, `patient_insurances`, `patient_number_sequences`, `archived_patients`, `insurance_verifications` | Confirmed |
| Installed triggers | none | Confirmed |
| Current collision shape | soft-deleted patients, merged/redirected patients, archive rows, aliases and emergency contacts are each zero | Confirmed mutable aggregate, not a guarantee |
| Small target populations | insurance memberships and verification records exist; nonzero values below ten are suppressed | Confirmed privacy-safe classification |
| Safety checks | duplicate patient numbers, duplicate patient/provider memberships, patients with multiple primary contacts and patients with multiple primary insurances are each zero | Confirmed mutable aggregate, not a future invariant |
| Effective numbering configuration | prefix `UHMS`; pattern `{PREFIX}-{SEQUENCE}/{YEAR}`; width 6; yearly reset; UTC | Confirmed mutable configuration |

The capture contains no row identifiers or patient values. Every count and configuration is mutable. Cohort C must bind a fresh target snapshot coordinate, structural fingerprint, configuration fingerprint and protected set fingerprints; commit must recheck them.

## Installed target constraints and missing invariants

### Patients

`patients` has a generated bigint primary key and unique `patient_number`; `first_name`, `last_name`, `date_of_birth`, `gender`, `phone`, `status`, `is_active`, `is_temporary`, `merge_status` and `is_deceased` are non-null. `registered_by`, identity-confirmation actors, merge/death actors and timestamps are nullable FKs to users. The model soft-deletes through `deleted_at`.

The database does **not** constrain patient status values, the coherence of `status` with `is_active`, temporary/identity state, merge fields, death fields, or archive state. There is no row version. The application treats `active`, `inactive`, `deceased` and `archived` as meaningful status values and treats `merge_status='MERGED'` or a non-null merge pointer as merged.

### Patient numbers and aliases

`patient_number_sequences` uniquely keys `(prefix, period_type, period_key)`. `patients.patient_number` is unique, but no database constraint prevents a generated patient number from colliding with `archived_patients.patient_number` or a patient alias. `patient_aliases` uniquely keys `(alias_type, normalized_alias_value)` and has no soft delete. Its `source_patient_id` is a target-patient FK and cannot contain a Classic key.

The existing generator locks one sequence row, but its missing-row path is check-then-insert and its collision path appends a `uniqid()` suffix. `PATIENT-NUM-006`, `PATIENT-NUM-007` and `PATIENT-NUM-016` therefore make the normal generator unreachable from migration. A dry-run number is symbolic and nonbinding.

### Emergency contacts

`emergency_contacts` requires patient, name and phone; `is_primary` defaults false. It has no uniqueness key, source identity, migration token, soft delete or database one-primary constraint. Therefore neither contact existence nor primary state is idempotent without the protected migration ledger.

### Insurance

`patient_insurances` requires patient and provider, and uniquely keys `(patient_id, insurance_provider_id)`. `member_type` is the non-null enum `holder|beneficiary` with default `holder`; `is_active` defaults true; `is_primary` defaults false. Tier, holder link, member/policy/CCC values and dates are nullable. There is no soft delete, history/provenance, eligibility state or migration identity.

The uniqueness key prevents a second patient/provider row but cannot prove that an existing row has the same source lineage or contents. A duplicate-key result is therefore not idempotent success. `INS-TARGET-002` permits only a protected, version-matching prior-run no-op; `INS-TARGET-003`, `INS-TARGET-006` and `INS-TARGET-010` otherwise stop.

`insurance_verifications` is a separate operational table. Phase 2F creates no verification or eligibility row under `INS-ELIG-001`–`INS-ELIG-006`.

## Patient target-state matrix

This matrix records the complete state needed for a future new-patient branch. Database/model defaults are evidence about behavior, not migration authority.

| Target field | Installed form/default | Application semantics and risk | Genuine Classic evidence; separately labelled upstream authority | Technical recommendation | Existing-target branch | Unresolved outcome and reconciliation |
|---|---|---|---|---|---|---|
| `status` | varchar(191), non-null, default `active`; no check | Used for active/inactive/deceased/archived visibility and workflow | **Classic evidence:** no approved patient-state source; `patients.BillStatus` is payer evidence under `PATIENT-COL-015`. **Approved authority:** `PATIENT-REQ-007` requires explicit coherent policy. | Recommend `active` only as an owner-approved target initialization for a newly created, fully eligible permanent patient | Preserve byte-for-byte | **Blocking state decision.** Count every projected value; zero default reliance |
| `is_active` | boolean, non-null, default true | `Patient::active()` requires both `status=active` and true | **Classic evidence:** no approved renewed active-state fact. **Approved authority:** `PATIENT-REQ-008` requires joint status approval. | Recommend true only together with approved `status=active`; never let DB fill it silently | Preserve | **Blocking state decision.** Require coherent `(status,is_active)` pairs |
| `is_temporary` | boolean, non-null, default false | Temporary emergency identity invokes confirmation/merge workflows | **Classic evidence:** no approved renewed temporary-state field/fact. **Approved authority:** `PATIENT-REQ-009` prohibits temporary placeholders bypassing identity quarantine. | Recommend explicit false | Preserve | **Blocking state decision.** Migration-created temporary patients must equal zero |
| `temporary_reason` | nullable varchar | Meaningful only for temporary identity | None | Explicit null | Preserve | Must be null when `is_temporary=false` |
| `identity_confirmed_at` | nullable timestamp | Claims a renewed identity-confirmation workflow occurred | **Classic evidence:** no approved renewed confirmation event/time. Remediation approval is not such an event. | Explicit null | Preserve | Invented confirmation timestamps zero |
| `identity_confirmed_by` | nullable user FK | Attributes renewed confirmation | **Classic evidence:** no approved confirmation actor. **Approved authority:** D-202 prohibits fallback actor. | Explicit null | Preserve | Invented confirmation actors zero |
| `merge_status` | varchar(20), non-null, default `ACTIVE`; no check | `MERGED` plus pointer controls redirects and record acceptance | **Classic evidence:** no approved renewed merge-status field/fact. **Approved authority:** Q-002 prohibits automatic merge but does not approve `ACTIVE` initialization. | Recommend explicit `ACTIVE` as new-folder initialization, not a Classic fact | Preserve; merged state blocks link | **Blocking state decision.** Automatic merged state zero |
| `merged_to_patient_id` | nullable self-FK | Redirects identity and records | None permitted | Explicit null | Preserve; never follow automatically | New-patient non-null count zero |
| `merged_at` | nullable datetime | Claims a merge event | None permitted | Explicit null | Preserve | Invented merge times zero |
| `merged_by` | nullable user reference in model; installed column has no confirmed FK in the selected manifest | Claims a merge actor | **Classic evidence:** no approved merge actor. **Approved authority:** D-202 prohibits fallback actor. | Explicit null | Preserve | Invented merge actors zero |
| `is_deceased` | boolean, non-null, default false | Controls visit/appointment safety and death UI | Phase 2C supplies no approved patient-level death fact | Recommend explicit false with semantic “not marked deceased in renewed UHMS”, not evidence the person is alive | Preserve | **Blocking state decision.** Must be approved with its exact semantics |
| `deceased_at` | nullable date | Historical clinical/administrative fact | None in Phase 2C patient contract | Explicit null | Preserve | Must be null when `is_deceased=false` |
| `cause_of_death`, `deceased_notes` | nullable | Highly sensitive clinical facts | None | Explicit null | Preserve | Invented values zero |
| `marked_deceased_by` | nullable user FK | Attributes death marking | None; actor fallback prohibited | Explicit null | Preserve | Invented actor zero |
| `registered_by` | nullable user FK | Normal service sets current authenticated user | **Classic evidence:** no Classic registrar field/fact. **Approved authority:** Phase 2B/`PATIENT-ACTOR-003` approves `null` plus protected absence provenance. | Explicit null under the approved rule | Preserve | Current/admin/importer/unknown registrar zero |
| `deleted_at` | nullable timestamp; soft-delete marker | Hides row from ordinary scoped queries while retaining unique number | No deletion instruction | Recommend explicit null only for an approved new visible patient | Preserve; non-null blocks existing link | **Blocking state/activation decision.** Soft-delete must never be used as an implicit staging mechanism |
| `created_at` | nullable timestamp | Operational archive/search/report inputs | **Classic evidence:** `RegDate` is the historical candidate. **Approved authority:** `PATIENT-COL-022` maps a valid value explicitly. | Explicit approved historical `RegDate`; never migration time | Preserve | Migration-time substitutions zero |
| `updated_at` | nullable timestamp | Current modification marker and weak concurrency indicator | **Classic evidence:** `EditDate` exists. **Approved authority:** `PATIENT-COL-021` makes it extraction evidence only and forbids target mapping without a later rule. | Recommend explicit null/not-evidenced with provenance unless owner approves another truthful representation; migration boundary must suppress automatic timestamping | Preserve | **Blocking timestamp representation decision.** Classic `EditDate` or migration time must not be silently used |

Required identity fields, patient number and optional demographics remain governed by their single Phase 2C/2D owner contracts and are not redefined here.

### Coherence rules

Before a future commit, an owner-approved matrix must define one exact tuple, not field-by-field defaults. The recommended new permanent tuple is `status=active`, `is_active=true`, `is_temporary=false`, `merge_status=ACTIVE`, `is_deceased=false`, with all temporary/confirmation/merge/death actor and event fields null, `registered_by=null`, `deleted_at=null`, historical `created_at`, and explicitly not-evidenced `updated_at`. This is a **technical recommendation**, not source evidence or an approval.

Any unresolved field blocks future Unit A **commit**, and Stage 10 records the exact patient-and-chain quarantine; it does not stop dry-run classification. A tuple is invalid if any subordinate event/actor field contradicts its false/active parent state. Existing targets are never normalized to this tuple.

## Insurance initialization matrix

| Target field | Installed form/default | Phase 2E evidence | Future new-patient representation | Existing-target behavior | Blocking rule / reconciliation |
|---|---|---|---|---|---|
| `patient_id` | required FK, cascade delete | Resolved patient parent | Created target patient mapping only | No create/enrichment | Parent mapping must exist before Unit D |
| `insurance_provider_id` | required FK, cascade delete | Approved Phase 2A provider crosswalk only | Exact approved target provider | Immutable | Invented/default provider zero |
| `insurance_tier_id` | nullable FK, null on delete | `INS-CONS-010` requires null/not evidenced | Null/not evidenced; no crosswalk or default tier may populate it | Preserve | Exact upstream null outcome; default/crosswalk invention prohibited |
| `member_type` | non-null enum; default `holder` | Holder/beneficiary is not evidenced | **No truthful installed value is currently authorized.** Recommend either an approved explicit unknown/not-evidenced target representation or history-only/no current row | Preserve | **Commit blocker.** DB/model `holder` default must never apply |
| `card_holder_insurance_id` | nullable self-FK | `INS-CONS-010` requires null/not evidenced | Null/not evidenced; no holder relationship may be inferred | Preserve | Exact upstream null outcome |
| `membership_number` | nullable varchar | Phase 2E exact member rules | Evidence-backed value or null under `INS-CLASS-007`; never generated | Preserve | Collision and duplicate class must be resolved independently |
| `policy_number` | nullable varchar | `INS-CONS-010` requires null/not evidenced | Null/not evidenced; never copy member number or scheme/plan evidence | Preserve | Exact upstream null outcome |
| `ccc_code` | nullable varchar(64) | `INS-CONS-010` requires null/not evidenced | Null/not evidenced; no CCC/verification value may be inferred | Preserve | Exact upstream null outcome; no verification implied |
| `start_date` | nullable date | Field-specific D-206 rules | Valid evidenced issue/start date only | Preserve | Unknown/invalid date does not become current |
| `expiry_date` | nullable date | Field-specific D-206 rules | Valid evidenced expiry date only | Preserve | Never swap/coerce; unknown remains history-only |
| `is_primary` | non-null default false | Classic does not evidence target primary status | Recommend explicit false and target-owned later selection, subject to owner approval | Preserve | Default cannot be accepted silently; migration-created multiple primary zero |
| `is_active` | non-null default true | Active-looking dates do not evidence eligibility or target activation | **No approved exact initialization.** Safest current result is history-only until an owner-approved current-state meaning exists | Preserve | **Commit blocker.** No default true; eligibility and active status remain distinct |
| `created_at`, `updated_at` | nullable timestamps, Eloquent normally sets current time | Not Classic insurance event timestamps | Recommend explicit null/not-evidenced plus protected provenance unless separately approved | Preserve | Migration-time historical appearance zero |
| verification fields/table | separate table | Eligibility not evidenced | Create zero verification rows; no actor/status/reference/payload/time | Preserve | Mandatory zero under `INS-ELIG-002` |

Consequently, Phase 2F may classify and reconcile every insurance source row, but a future target current-membership insert remains blocked by unresolved `member_type` and `is_active` representation. Stage 18 emits a classified current-withheld result and continues; historical provenance is still mandatory for every row and cannot wait silently or disappear. Invention, mutation, silent loss/default use or an unclassified row/group remains run-stopping.

## Cohort C target-state and collision branches

Every branch is detected against a protected target snapshot and rechecked under the shortest suitable lock or compare-and-swap immediately before its future atomic unit. Counts may be published; target values, IDs and row-level tokens may not.

| Collision/state | Detection coordinate and required query scope | Deterministic result | Existing exception authority | Blocking scope and release |
|---|---|---|---|---|
| Environment or schema mismatch | Stage 0 and immediately before commit; exact environment/database/fingerprint/constraints | Stop entire run | target drift plus `LEGACY-PATIENT-DRIFT-033` / target conflict class | Global; recapture and independent approval |
| Explicit crosswalk absent, duplicated, revoked or wrong owner | Preflight and Unit A/link lock; protected source/target cardinality | Existing-target ambiguity quarantine; never fall through to create | `LEGACY-PATIENT-TARGET-023` | Patient chain; corrected/revoked approved crosswalk |
| Target patient missing | Unscoped primary-key lookup | Quarantine; allocate zero | `LEGACY-PATIENT-TARGET-025` | Patient chain; reviewed correction |
| Target soft-deleted | `withTrashed`/unscoped lookup | Quarantine; never restore or replace | `LEGACY-PATIENT-TARGET-026` | Patient chain; controlled identity review |
| Target merged/redirected | Unscoped row plus merge fields | Quarantine; never call `getFinalPatient()` or follow pointer | `LEGACY-PATIENT-TARGET-026` | Patient chain; controlled identity review |
| Existing-target scalar/child changed since approval | Protected canonical field-group HMAC plus row/set locks | Stop/review; no backfill or normalization | `LEGACY-PATIENT-TARGET-024`, `PATIENT-TARGET-013` | Existing-link branch; refreshed approved snapshot |
| Generated patient-number collision | All live/trashed/merged patients, archive evidence and aliases; recheck under sequence allocation boundary | Stop; no suffix, second allocation or recycle | `LEGACY-PATIENT-NUMBER-037` / `-039` | Unit A; configuration/ownership resolution |
| Sequence/configuration drift or period rollover | Effective config fingerprint, exact sequence row, UTC period | Stop current coordinate; do not project/reserve a replacement | `PATIENT-NUM-002`, `-012`, `-017` | Unit A/run; pin new reviewed coordinate |
| OPD alias collision | Source-wide class plus all target aliases and patient/archive number namespaces | Withhold alias only; never infer patient match | `LEGACY-PATIENT-ALIAS-006` / `-007` | Unit B only; identity review may release |
| Same-owner prior alias with verified lineage | Alias unique key plus protected source/version lineage | Idempotent no-op | `PATIENT-ALIAS-010` | None after equality/reconciliation |
| Contact exists for newly created mapped parent | Parent contact-set fingerprint at Unit C | Stop/withhold unless it is the exact prior migration child lineage | Phase 2D contact conflict class | Unit C; target refresh/lineage proof |
| Multiple target primary contacts | Aggregate/set check at snapshot and Unit C | Stop; never clear/reselect primary | `LEGACY-PATIENT-CHILD-CONTACT-013` | Unit C/affected parent; operational owner repairs outside migration |
| Explicit existing-target patient has any contact state | Protected comparison only | Zero create/update/delete; evidence-only | `PATIENT-CHILD-CONTACT-007`, `PATIENT-CHILD-IMMUT-006/007` | No enrichment is releasable in Phase 2F |
| Existing patient/provider membership | Unscoped exact patient/provider query plus protected lineage | Verified identical prior-run lineage: no-op; otherwise immutable conflict | `LEGACY-INSURANCE-TARGET-030` / `-041` | Unit D group; lineage or separate owner resolution |
| Duplicate target patient/provider state | Constraint/set check | Stop; never select one or delete/dedupe | `LEGACY-INSURANCE-TARGET-032` | Unit D/global invariant; target repair outside migration |
| Target member-number collision | Domain-separated protected comparison across target membership namespace | History-only/conflict; no overwrite or reassignment | Phase 2E member and target conflict codes | Unit D group; controlled review |
| Provider crosswalk changed/missing/ambiguous | Versioned Phase 2A crosswalk plus target provider state | Quarantine insurance rows/group; patient identity unchanged | `LEGACY-INSURANCE-PROVIDER-007` / `-008` | Unit D group; approved provider map |
| Existing-target membership/child enrichment attempted | Pre/post target set HMAC | Stop and fail safety reconciliation | `LEGACY-INSURANCE-TARGET-040`, `PATIENT-TARGET-009` | Run/affected chain; no Phase 2F release path |
| Target row changes between dry-run and commit | Re-resolve under lock/versioned HMAC at each unit | Invalidate projection and reclassify; never switch owner/candidate | target conflict classes above | Affected unit/run depending fingerprint scope |

Collision precedence must remain environment/fingerprint → crosswalk/target identity → patient-state → number → alias → optional contact → insurance. A later child collision cannot revise the earlier patient identity branch.

## Target-state fingerprint sets

The future protected target collision manager needs canonical set fingerprints, not only `updated_at`:

1. patient row existence and every scalar, including deletion, merge, archive and actor fields;
2. patient-number namespace across live/trashed/merged patients, archive rows and aliases;
3. alias rows and ownership for the candidate normalized alias;
4. complete contact child set and primary cardinality for the mapped patient;
5. complete patient/provider membership set plus relevant member-number collision set;
6. provider/tier state and Phase 2A crosswalk versions;
7. sequence configuration, active period and sequence row;
8. installed columns, defaults, enum values, constraints and collation.

Use typed length-prefixed canonicalization and domain-separated HMAC inside the protected runtime. A plain hash of patient numbers, aliases, phone/contact data or member numbers is prohibited. The repository receives only aggregate result categories and nonsecret structural/configuration hashes.

## Target idempotency implications

Installed uniqueness is necessary but insufficient:

- `patients.patient_number` prevents a duplicate value but not a replacement number on rerun;
- alias uniqueness prevents duplicate normalized aliases but does not prove source ownership or rule version;
- no contact uniqueness exists;
- patient/provider uniqueness cannot distinguish a prior migration result from unrelated target data;
- target rows contain no migration run/token/version fields;
- nullable `updated_at` is not a reliable version counter.

The protected crosswalk/provenance ledger is therefore the authority. Stable future keys should have these target-specific forms (logical interfaces only):

| Outcome | Stable protected key inputs |
|---|---|
| Patient core | domain `patient-core`; protected source-patient token; patient transformation bundle; canonicalization version |
| Existing-target link | domain `patient-existing-link`; source token; protected target token; approved crosswalk version |
| Number allocation | domain `patient-number`; patient-core key; number-config fingerprint; pinned period |
| OPD alias | domain `patient-alias-legacy-opd`; source token; mapped target token; canonical alias token; alias rule version |
| Emergency contact | domain `patient-contact-nok`; source token; target-parent token; tuple HMAC; child rule version |
| Insurance source history | domain `insurance-history-row`; protected source-row token; extraction/transformation version |
| Insurance group | domain `insurance-patient-provider`; mapped patient token; provider-crosswalk target token; consolidation version |

On rerun, resolve a successful patient crosswalk before touching the sequence. A row/unique-key match without protected lineage is conflict, not success. Contract, source snapshot, target snapshot or canonicalization drift creates a new reviewable evaluation; it does not overwrite the old outcome or silently reuse it.

## Future atomic boundaries

### Unit A — patient core

One transaction or reviewed equivalent must include the crosswalk-first check, number sequence lock/increment, patient insert with the approved complete state tuple, protected absence provenance, successful mapping, core reconciliation and checkpoint outcome. The number must not remain consumed without a durable explained outcome. `PatientService::create`, the current generator and ordinary Eloquent creation are not the migration boundary.

If the protected ledgers live outside the same transactional database, “atomic” cannot be claimed. Phase 3 must either co-locate the authoritative records in one transaction or implement a reviewed prepare/commit protocol with a durable intent, unique idempotency keys, recovery proof and explicit compensation state.

### Unit B — OPD alias

After Unit A/link success, recheck mapping, canonical source duplicate class, target alias/number namespaces and prior lineage. Alias plus mapping/provenance/reconciliation/checkpoint commits together. Duplicate/blank/invalid/colliding aliases produce a durable withheld/absence outcome and zero target alias rows.

### Unit C — emergency contact

After Unit A, recheck the mapped parent, contact-set fingerprint, protected child key and one-primary invariant. Contact plus provenance/reconciliation/checkpoint commits together. A valid optional contact failure is withheld/quarantined and must not undo a valid core; it can never mutate an existing target patient.

### Unit D — insurance history and current representation

Every source insurance history outcome is durable before the source row is considered accounted. Patient/provider grouping, at-most-one current representation, group provenance, reconciliation and checkpoint are atomic at group scope. A current representation may be withheld while all source history remains accounted. Existing-target memberships are immutable. Current-row creation remains blocked until `member_type` and `is_active` initialization are approved.

## State machine and durable facts

The future state machine is monotonic and ledger-backed:

`NOT_STARTED → EXTRACTED → CLASSIFIED → DRY_RUN_ACCEPTED → CORE_COMMITTING → CORE_COMMITTED → ALIAS_{PENDING|COMMITTED|WITHHELD} → CONTACT_{PENDING|COMMITTED|WITHHELD} → INSURANCE_HISTORY_{PENDING|COMMITTED} → INSURANCE_CURRENT_{PENDING|COMMITTED|WITHHELD} → RECONCILIATION_{PENDING|PASSED|FAILED} → COMPLETED`.

`QUARANTINED`, `ROLLBACK_REQUIRED` and `COMPENSATION_REQUIRED` are explicit non-success dispositions. No unexplained state may be interpreted as success. Each transition stores the run/contract bundle, source/target snapshot coordinates, stable idempotency key, attempt, expected prior state, measured result hashes and checkpoint version.

`CORE_COMMITTING` requires a durable intent before target mutation. `CORE_COMMITTED` requires target patient, patient number, sequence outcome, crosswalk, provenance and core reconciliation to agree. Child stages require `CORE_COMMITTED`; none may release before its valid parent.

## Crash, rollback, compensation and resume matrix

| Crash boundary | Durable facts required | Safe retry and duplicate prevention | Rollback/compensation | Checkpoint/reconciliation/operator action |
|---|---|---|---|---|
| Before number allocation | accepted dry-run, pinned snapshots/config, no Unit A intent | Revalidate all coordinates; allocate nothing until crosswalk-first check | Nothing to roll back | Remain `DRY_RUN_ACCEPTED`; rerun preflight |
| After Unit A intent but before sequence change | durable intent and idempotency key | Lock intent; prove no target/crosswalk/sequence delta | Cancel intent if unchanged | Record zero deltas, return to accepted state |
| After number sequence change but before patient insert | same transaction identity and locked sequence before-image | Transaction rollback must restore sequence; retry same logical key | Roll back whole Unit A; never manually decrement outside transaction | If sequence changed without rollback proof, `COMPENSATION_REQUIRED`; operator reconciles gap |
| After patient insert but before crosswalk/provenance | all are within same uncommitted Unit A transaction | Rollback; retry only after proving no committed patient/number | Roll back Unit A | Any committed orphan patient is a critical atomicity failure, not a new-patient success |
| After patient commit but before crosswalk commit | This state must be impossible under approved Unit A | Do not allocate replacement or create second patient | No universal delete. Mark compensation required and isolate operational use pending recovery | Recover crosswalk only from durable intent plus exact target proof, or owner-controlled compensation |
| After patient/crosswalk commit but before checkpoint | committed mapping, number, provenance and core recon keyed identically | Crosswalk wins; resolve same target/number, write missing checkpoint only | No rollback/reallocation | Prove Unit A equation then advance checkpoint |
| During alias creation | Unit A mapping plus alias intent and pre-state fingerprint | Unique key + protected alias lineage; exact prior result is no-op | Roll back Unit B only; never core | Reclassify collision on retry; commit/withhold with zero difference |
| After alias commit before checkpoint | alias mapping/provenance/recon evidence | Resolve exact lineage; never create a second row | No rollback required if equality proven | Repair checkpoint only after alias equation passes |
| During contact creation | Unit A mapping, tuple token, pre-contact set | Protected child key; target row alone is insufficient | Roll back Unit C; core remains | Retry after parent/set recheck; otherwise withhold |
| After contact commit before checkpoint | exact protected lineage and post-set fingerprint | Resolve same contact; do not create another/flip primary | No hard delete if equality proven | Repair checkpoint after one-primary and child equations pass |
| During insurance-history processing | source-row history intents for the complete group | Per-source-row keys; resume missing rows only | Roll back incomplete group or retain explicit pending intents; source row never disappears | History count must equal group source count before current projection |
| During current-membership processing | complete history, group intent, provider/patient mapping, pre-membership set | Patient/provider key plus protected lineage; duplicate DB key is not success | Roll back Unit D group. Do not update/delete unrelated existing row | Retry only after collision refresh; otherwise withhold current representation |
| After target writes but before reconciliation | committed unit records and measured post-state | Resolve by idempotency keys; no repeat target writes | Unit-specific compensation only; no universal patient delete | Reconcile before advancing; failure becomes `RECONCILIATION_FAILED` |
| After reconciliation but before run completion | signed/hashed passing results and all terminal child dispositions | Complete ledger/checkpoint only | None if state still agrees | Recheck target drift, then mark completed |
| Existing-target link interrupted | protected link intent and immutable target pre-state | Re-resolve exact target unscoped; write only missing mapping/audit | Never mutate, delete, restore or redirect target | Prove zero target delta before checkpoint |

### Rollback limitations

Hard deletion is not a general rollback. Patient FKs can cascade or null a large graph; alias and insurance FKs cascade on patient/provider deletion; archives and merge history preserve identity ownership. A committed patient number is never decremented, recycled or replaced under `PATIENT-NUM-011`.

For a newly created patient that has escaped its atomic boundary, Phase 3 needs an approved operational-isolation and compensation design. The installed patient schema has no migration-staging state. Setting inactive, soft-deleting, archiving or force-deleting would itself change business state and is not authorized here. Therefore either:

1. keep the future commit runtime and operational access isolated until Unit A and mandatory reconciliation pass; or
2. add an owner-approved Phase 3 staging/activation representation with explicit application guards and compensation semantics.

This is a **blocking foundation requirement**. An active default row must not become visible merely because the insert committed.

Optional alias/contact/current-insurance failure does not invalidate a reconciled patient core; it produces a durable withheld/quarantine outcome. Insurance history accounting remains mandatory. Existing-target links are compensated only in the migration ledger; the target patient and its children are never changed.

## Dangerous operational paths and required isolation

| Path | Confirmed behavior | Migration risk / disposition |
|---|---|---|
| `PatientService::create` | generates number, sets `registered_by=Auth::id()`, stores avatar, calls `Patient::create` | Prohibited: wrong actor, unsafe generator, file and activity side effects |
| `Patient` model | `LogsActivity`, soft deletes, active/merge scopes and casts | Ordinary Eloquent create logs contemporary activity; migration boundary must explicitly suppress operational activity while writing separate migration audit |
| `PatientIdGeneratorService` | sequence transaction/lock, check-then-insert missing row, random collision suffix | Prohibited unchanged; Phase 3 migration allocator must implement `PATIENT-NUM-*` atomically |
| `PatientController::store` | creates patient, then contacts and memberships in separate loops; first contact/membership primary; membership holder/active; default tier lookup | Prohibited: partial graph, invented holder/active/primary/tier state |
| `EmergencyContactController` | bulk clears primary flags, creates/updates, hard deletes; route patient/contact ownership is not independently checked | Prohibited: existing-child mutation and non-atomic one-primary behavior |
| `PatientInsuranceController` | defaults holder and active; may select tier; bulk clears primary; hard deletes; route ownership not independently established | Prohibited: invented state and existing membership mutation |
| `InsuranceService` | apparently read-oriented methods can `firstOrCreate` a default cash-and-carry active holder membership | Prohibited and important: no operational insurance resolver/getter may run during migration or reconciliation |
| `InsuranceVerificationService` | may call provider driver, creates verification with current actor/time, links visit and writes activity | Prohibited; verification/eligibility rows and external calls must be zero |
| `PatientMergeService` / emergency identity workflow | follows final patient, creates aliases, transfers demographics, moves/deduplicates insurance and other children, attributes current user and logs security activity | Prohibited; violates no-auto-merge, immutability and historical-actor rules |
| `ArchiveInactivePatientsCommand` | snapshots patient, changes status to archived and `is_active=false` based partly on timestamps | Scheduler must be paused/isolated; imported historical `created_at` can immediately make a patient archive-eligible |
| Spatie activity logging / external critical forwarder | patient creates/updates can create activity; activity save may forward externally | Disable operational activity generation and forwarding; `Model::withoutEvents()` alone is insufficient |
| Queues, schedulers, notifications, SMS/email, visits, billing/accounting, stock and search/report consumers | application services can create or expose downstream operational state | Environment-isolate runtime and prove zero calls/events/writes outside approved migration units |

No current operational service is required as the migration persistence authority. Future direct writes are permissible only inside a reviewed migration-specific boundary that enforces all installed constraints plus stricter logical invariants, manages the number allocator, writes protected provenance/reconciliation, and suppresses side effects. Reference providers/tiers must already be resolved; the patient pilot does not create them.

## Side-effect zero proof

Capture before/after protected counts and event-sink/queue instrumentation for:

- `activity_log` operational entries for patient/alias/contact/insurance;
- notifications, queued jobs, SMS/email and external audit forwarding;
- insurance verification and provider driver calls;
- cash/default memberships and default tiers;
- patient merge requests/logs, redirects and reassigned children;
- archive rows/status changes;
- visits, appointments, billing, claims, payments, receivables, GL/accounting, stock and pathway/bed/queue records;
- files/avatar writes and search/index dispatch.

Every prohibited delta and call count must be zero. `withoutEvents()` proves only part of this requirement and cannot suppress explicit service writes, external drivers, schedulers or direct activity calls.

## Phase 3 blockers and verification obligations

Commit-mode patient creation remains blocked until Phase 3 implements and independently reviews:

1. owner-approved coherent patient target-state and timestamp matrix;
2. owner-approved insurance `member_type`, `is_active` and `is_primary` representations, including a truthful not-evidenced strategy;
3. a migration-safe number allocator satisfying `PATIENT-NUM-*`, unified with operations or protected by a registration freeze;
4. protected crosswalk/provenance/quarantine/reconciliation stores with enforced uniqueness and transaction/recovery semantics;
5. target collision snapshot manager with unscoped/with-trashed/archive/alias/contact/insurance set fingerprints;
6. target row/set locking or optimistic HMAC compare-and-swap at each atomic unit;
7. side-effect-isolated migration persistence which bypasses normal patient/contact/insurance/merge/verification services;
8. a staging/activation or environment-isolation design preventing an incompletely reconciled patient from operational visibility;
9. monotonic checkpoints, durable intents, resume and compensation states;
10. privacy-safe HMAC key/canonicalization management and aggregate-only reporting;
11. crash-injection tests at every boundary in the matrix;
12. concurrency tests for operations versus migration number allocation, aliases, primary contacts and patient/provider membership;
13. tests proving existing-target scalar and child changes, automatic merge/match, replacement number, duplicate child/membership, verification and all prohibited side effects remain zero.

## Handoff conclusions

- The target can structurally hold a fully remediated patient core, one typed legacy alias, one emergency contact and at most one membership per patient/provider, but installed defaults do not supply migration truth.
- Existing-target Cohort C is comparison/linkage only: target patients and pre-existing children remain immutable.
- Current target aggregate zeros are useful fixtures for preflight expectations, not collision guarantees.
- Patient state and insurance initialization are genuine commit blockers. Phase 2F can finish its mapping specification while carrying them explicitly into Phase 3.
- Database uniqueness does not replace protected idempotency lineage.
- Unit A must be truly atomic; Units B–D are independently resumable and reconciled, with optional child failures withheld rather than silently discarded.
- A universal delete rollback is unsafe. Durable resume is preferred; compensation requires explicit target-state isolation and owner-approved semantics.
- No importer, schema, migration table, application behavior or database row was created or changed by this workstream.
