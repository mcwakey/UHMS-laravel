# Phase 2D renewed target patient/contact discovery draft

Status: **read-only target discovery evidence for lead consolidation; no persistence is authorised.**

This draft inspects only the renewed Laravel UHMS repository and the installed non-production target database. It does not inspect Classic UHMS, redefine the Phase 2C patient/alias contracts, or authorise an importer, schema change, seeder, target write, existing-patient enrichment, merge, or operational service call.

## Evidence classification and coordinate

- **Confirmed installed evidence:** `docs/legacy-migration/TARGET_CONSTRAINT_MANIFEST.json`, captured at `2026-07-21T01:47:08+00:00` from local non-production database `uhms_clean` on MariaDB `10.4.32`, plus a fresh SELECT-only metadata check in a repeatable-read, transaction-read-only session on 2026-07-21.
- **Installed structural fingerprint:** `12e3a4c6ca80a0c1a54ea9267d9c4935453b7a36d27c7f8ad900062c1c12bff0` from `TARGET_SCHEMA_FINGERPRINT.json`. The focused recheck confirmed the patient/contact structures below; it did not replace or recalculate the complete fingerprint.
- **Confirmed repository behaviour:** cited Laravel migrations, models, enums, requests, controllers, services, views and configuration.
- **Approved policy consumed:** D-201, D-211, D-213, D-214 and the Phase 2C patient-parent, existing-target, alias, quarantine, registration-attribution and privacy contracts.
- **Proposed Phase 2D technical rules:** explicitly labelled below. They are inputs to lead consolidation, not runtime authority.

The fresh target check verified `APP_ENV=local`, exact database `uhms_clean`, `@@session.tx_read_only = 1`, the three relevant installed migration-ledger entries in batch 1, and zero database triggers on `patients` or `emergency_contacts`. No target rows were written. Sanitised aggregate state was 100 patient rows, zero emergency-contact rows, and zero patient rows with any nonblank dormant inline emergency-contact value. That aggregate state is not a production baseline and must be recaptured under target-state guards before implementation.

## Installed patient demographic destinations

| Installed field | Physical contract | Operational contract | Target meaning and Phase 2D consequence |
|---|---|---|---|
| `patients.occupation` | nullable `varchar(191)`, no index/check/FK/default | nullable string, maximum 100 | Free-text storage. The create/edit UI presents a configured list, but request and database layers do not enforce membership. Valid Classic free text can therefore be represented without creating a catalogue, subject to the Phase 2D 100-character operational limit. |
| `patients.address` | nullable `text` (`65535` maximum), no index/check/FK/default | nullable string, maximum 500 | Authoritative unstructured address destination. It is distinct from locality and digital-address fields. |
| `patients.city` | nullable `varchar(191)` | nullable string, maximum 100 | Separate locality field. Classic unstructured address is not evidence for this field. |
| `patients.town` | nullable `varchar(191)` | nullable string, maximum 100 | Separate locality field. Classic unstructured address is not evidence for this field. |
| `patients.region` | nullable `varchar(191)` | nullable string, maximum 100 | Separate locality field; the UI uses installed country/region reference rows, but the database stores a name string and has no FK. Classic unstructured address is not evidence for this field. |
| `patients.postal_code` | **not installed** | no patient model, request, migration or view contract | There is no Phase 2D target destination. Do not invent one or write an organisation postal code into a patient. |
| `patients.digital_address` | nullable `varchar(191)` | nullable string, maximum 30; Ghana setup regex `^[A-Z]{2}-[0-9]{3,4}-[0-9]{3,4}$` | Separate GhanaPost-style value. Do not infer it from Classic `Address`, geocode, or device/location code. |
| `patients.religion` | nullable `varchar(191)`, no check/index/FK/default | nullable string, maximum 100 | Free text at persistence/request level. The UI vocabulary is `Christianity`, `Islam`, `Traditional`, `Hindu`, `Buddhist`, `Other`, `None`; it is presentation vocabulary, not a database enum. |
| `patients.marital_status` | nullable `varchar(191)`, no database check | nullable `MaritalStatus` request enum and model cast | Exact supported values are `single`, `married`, `divorced`, `widowed`. A migration validator must reject/withhold every other nonblank value before model hydration or persistence. |
| `patients.phone_secondary` | nullable `varchar(191)` | nullable string, maximum 30 | Installed and active, but it is not a Phase 2D source destination: Classic `PhoneNo` is already governed by Phase 2C and `NOKPhoneNo` belongs to `emergency_contacts.phone`. |

Evidence: `Patient::$fillable` and casts (`app/Models/Patient.php:28-78`), store/update rules (`app/Http/Requests/StorePatientRequest.php:50-70`; `UpdatePatientRequest.php:62-73`), UI vocabularies (`resources/views/components/patient-personal-information-card.blade.php:86-120`), and `app/Enums/MaritalStatus.php:9-12`.

### Dormant inline fields

The installed `patients.emergency_contact_name`, `patients.emergency_contact_phone` and `patients.emergency_contact_relationship` columns are nullable `varchar(191)`. They are absent from `Patient::$fillable`, patient store/update rules, and current display/registration paths. Current code reads and writes `emergency_contacts` child rows instead. The fresh aggregate check found all three inline fields blank across the local target baseline.

**Confirmed conclusion:** the three inline columns are dormant and are not authoritative destinations. Phase 2D must write zero values to them. Any future code activation or nonblank target-state drift is a stop condition requiring renewed target analysis.

## Installed `emergency_contacts` aggregate

| Column | Installed type | Null/default | Constraint/operational rule |
|---|---|---|---|
| `id` | `bigint unsigned` auto-increment | NOT NULL | Primary key; never reuse a Classic key. |
| `patient_id` | `bigint unsigned` | NOT NULL | FK to `patients.id`, `ON UPDATE RESTRICT`, `ON DELETE CASCADE`; nonunique BTREE index. |
| `name` | `varchar(191)` | NOT NULL | Operational maximum 100. No enum, check or index. |
| `phone` | `varchar(191)` | NOT NULL | Registration maximum 20 plus configured Ghana regex; standalone contact controller maximum 20 but no regex. |
| `phone_secondary` | `varchar(191)` | nullable, default null | Registration maximum 30; standalone controller maximum 20. Classic NOK has no separate secondary phone, so Phase 2D candidate value is null. |
| `relationship` | `varchar(191)` | nullable, default null | Operational maximum 50. No enum/check. UI vocabulary: `Spouse`, `Parent`, `Child`, `Sibling`, `Relative`, `Friend`, `Other`. |
| `is_primary` | `tinyint(1)` | NOT NULL, default `0` | Boolean model cast only. No database check, unique constraint, partial index or trigger limits primaries. |
| `created_at`, `updated_at` | nullable `timestamp` | default null | Eloquent creation normally supplies current time. Classic NOK fields carry no child timestamp evidence, so migration-time timestamps would be invented history. |

Installed constraints consist only of the primary key and patient FK. Installed indexes consist only of the primary key and nonunique `patient_id` index. There are no generated columns, soft-delete column, unique contact key, contact type, provenance/source key, idempotency key, created-by actor, updated-by actor, relationship reference, primary-contact constraint, database check or trigger.

Repository evidence: migration `database/migrations/2026_04_13_100002_create_emergency_contacts_table.php:13-21`, model `app/Models/EmergencyContact.php`, and installed manifest/recheck. The installed schema matches this repository structure for the inspected aggregate.

## Operational validation and vocabulary drift

The same target row has different validation depending on entry path:

| Entry path | Name | Primary phone | Secondary phone | Relationship | Primary behavior |
|---|---:|---|---:|---:|---|
| Patient registration | required, max 100 | required, max 20, configured country regex | max 30 | max 50, any string accepted by request | first submitted array row is assigned primary |
| Standalone contact controller | required, max 100 | required, max 20, **no country regex** | max 20 | max 50, any string accepted | requesting primary bulk-demotes siblings; otherwise no primary is guaranteed |
| Database/Eloquent model alone | max 191 physical only | max 191 physical only | max 191 physical only | max 191 physical only | default false; multiple true rows permitted |

Consequences:

1. Phase 3 cannot choose validation by whichever operational controller happens to be convenient. A versioned migration validator must enforce one approved contract before persistence.
2. The configured Ghana patient/contact format is `(?:\+233|0)[235][0-9]{8}`. This proves accepted target forms; it does not prove that every Classic ten-character value is a Ghana phone. Deliberate normalization and invalid/multiple/extension handling remain Phase 2D source specifications.
3. Relationship is free text technically. The seven UI choices are not an enum. Unknown Classic text must not be converted to `Other`; a free-text fallback outside the UI vocabulary would be representable and displayed, but would not round-trip through the current select control. It therefore requires an explicit Phase 2D allow-list/representation decision.
4. The configured occupation list is not a reference table and is not enforced by request or database. It must not be used to fabricate a Classic occupation catalogue or reject otherwise valid approved free text merely for list nonmembership.
5. Religion is also string-backed. The seven UI labels are an explicit target presentation vocabulary but not an enforced domain. `Other` is a legitimate literal only for an explicitly mapped source category; it is not a fallback.
6. Marital status differs: its model cast and request enum are authoritative application constraints despite the missing database check.

## Target relationship and sequencing conclusions

### Confirmed

- `Patient::emergencyContacts()` is `hasMany`; `primaryEmergencyContact()` is `hasOne(...)->where(is_primary, true)` (`app/Models/Patient.php:265-273`).
- The child FK makes a committed target patient mandatory before a contact can exist.
- The target contains no source identity or idempotency field on the contact row.
- Contact creation is separate from patient creation in `PatientController::store()`: `PatientService::create()` returns first, then the controller loops and creates children. The controller does not wrap the patient and children in an explicit transaction (`PatientController.php:118-131`).
- One Classic NOK tuple can address `name`, `phone` and nullable `relationship`; there is no target `contact_type` field and no Classic evidence for `phone_secondary`.

### Proposed Phase 2D technical specification

1. Consume the Phase 2C protected patient mapping first. Do not resolve a patient through name, phone, address, occupation, religion, marital status, NOK similarity, target search or merge preview.
2. For a newly created migrated patient only, persist demographic values as part of the validated patient boundary rather than a later enrichment update. Persist a complete valid contact as a separate child only after the patient map is durable and the patient-root quarantine token is released.
3. Use a protected migration contact crosswalk/idempotency key. Resume resolution must use that protected key, not an unkeyed hash or contact-value search. One source tuple per patient does not protect against duplicate rows after a retry.
4. Set `phone_secondary = null`; no source evidence supports copying patient `PhoneNo` or any other value.
5. Set contact timestamps to null plus protected absence provenance unless a later evidence contract proves contact-specific times. Do not let Eloquent synthesize migration execution time as historical contact time.
6. A complete valid sole Classic NOK candidate for a newly created patient may be assigned as the initial primary contact **only as an approved target-initialisation rule**, not as a claimed Classic historical fact. This is consistent with current registration behavior. Partial/invalid contacts are never primary. The lead specification must make this proposed choice explicit and reconcile at most one migration-created primary per patient.
7. Existing-target links create, update, delete, transfer, merge, demote or promote zero contacts and update zero demographics. Differences are protected evidence only. Multiple existing primaries are a target conflict/stop, not migration authority to repair.
8. A contact-local failure withholds/quarantines the optional child; it does not invalidate a Phase 2C-eligible patient. A patient-root quarantine blocks every Phase 2D value and contact.

## Direct insertion versus current domain services

| Record/change | Current operational path | Historical migration conclusion |
|---|---|---|
| New patient with demographic values | `PatientService::create()` generates a number and overwrites `registered_by` with `Auth::id()` | **Do not use.** Consume the Phase 2C migration-specific patient boundary so numbering, null registration attribution, historical timestamps, activity suppression and atomic crosswalk rules remain correct. |
| Patient demographic update | `PatientService::update()` updates fillable fields and fires model behavior | No update for explicitly linked existing targets. New-patient values belong in the original migration insert. |
| Registration contact | Controller performs ordinary Eloquent child create after patient creation | **Do not use as migration service.** It lacks protected idempotency, provenance, coordinated atomicity and contact timestamp controls. |
| Standalone contact add/edit/delete | Controller validates and directly creates/updates/hard-deletes model rows | **Do not use.** It mutates primary flags operationally, lacks migration provenance/idempotency and cannot express quarantine/reconciliation. |
| Patient merge | `PatientMergeService` can transfer demographics, create aliases from patient number/Ghana card/phone, and reassign emergency contacts | **Strictly prohibited for migration mapping.** It can change existing targets, create contact-driven aliases, and combine primary/duplicate contact sets. |

The future safe persistence technique is a migration-specific, schema-guarded and target-state-guarded boundary that applies direct validated values while enforcing the patient FK, target lengths/enums, protected idempotency, one-primary invariant, timestamp-absence provenance and child reconciliation. “Direct” here does not mean ad hoc SQL or bypassing validation; it means avoiding operational services whose business effects misrepresent historical facts. Phase 2D does not implement that boundary.

## Runtime behavior and dangerous side effects

### Patient model and audit

`Patient` uses `LogsActivity`, `SoftDeletes` and number-generation traits. Spatie logging monitors first name, last name and status. Normal Eloquent patient creation/update can therefore create operational activity containing identity fields. `PatientService::create()` also generates a new number and attributes registration to the authenticated user. `Model::withoutEvents()` alone does not isolate queues, archive tasks, integration services or direct service writes.

`ActivityLogService` masks phone, secondary phone, address and digital address in its own structured snapshots, but its sensitive-field list does not include occupation, religion, marital status, NOK name or relationship. Patient model activity and merge logs do not establish migration-safe provenance. The migration must emit only a separate, protected migration audit and zero contemporaneous operational activities.

### Emergency-contact model and controller

`EmergencyContact` has no `SoftDeletes`, `LogsActivity`, observer, event map or dedicated domain service. Controller deletion is a hard delete. Setting a contact primary bulk-updates sibling rows; no transaction or database uniqueness prevents concurrency from producing more than one primary.

The update/destroy action receives both `Patient $patient` and independently bound `EmergencyContact $contact` but does not assert `contact.patient_id == patient.id`. The update can therefore use one patient to demote contacts and then update a differently owned route-bound contact; destroy similarly does not scope the contact to the patient. This is a dangerous existing operational pathway and must never be used for migration or existing-target comparison.

### Registration partiality and primary state

Patient registration creates the patient first and contacts/insurances afterward without an explicit encompassing transaction. A child failure can leave a patient without its intended child. Registration marks array index zero primary but the standalone contact path can create no primary; direct writes can create multiple primaries. Display code selects the first true primary or falls back to the first contact, so bad primary state can be hidden and ordering is not a semantic guarantee.

### Merge behavior

`PatientMergePreviewService` includes emergency contacts and recommends a demographic source when the main value is blank. `PatientMergeService` may then update main demographics, creates aliases including phone aliases, and generically rewrites `emergency_contacts.patient_id` from duplicate to main. It does not deduplicate contact tuples or repair multiple-primary collisions. Invoking this workflow would breach Phase 2C/2D immutability and no-contact-matching rules.

### Search and display

`Patient::scopeSearch()` matches emergency-contact name and phone (`Patient.php:414-435`). Search therefore exposes contact data as a patient-discovery signal and must not be used for migration identity, existing-target linkage or child deduplication. The patient profile masks contact phone through `PatientPrivacyService` but renders contact name and relationship directly. Contact phone is privacy level 2; occupation, religion and marital status are level 1; address is level 2. Phase 2D policy is stricter: all source child/demographic values remain protected regardless of these operational UI levels.

### Privacy authorization gaps

Registration checks `patients.emergency_contact.edit`/aggregate rights before accepting contact phones, but the standalone contact controller itself checks only request validation and the surrounding patient-edit route/UI authorization; it does not call `PatientPrivacyService` for contact mutations. Contact name and relationship have no dedicated entries in `patient_privacy.fields`. These are target hardening gaps, not permission for migration to expose them.

### Archive behavior

`patients:archive-inactive` can copy the complete patient attribute payload, including Phase 2D demographics, into `archived_patients.payload` and change patient status to archived. If historical `created_at`/`updated_at` values are loaded before related visits, a patient can appear immediately inactive. Schedulers/archive commands must be paused or isolated until the full patient chain and cutover checks are complete. Migration policy must also account for the duplicated protected payload in retention/privacy design.

### Notifications and SMS

No patient/contact model observer, event or listener was found that sends a notification, email or SMS solely because an emergency contact was created. Application searches found no contact-phone routing outside privacy/display/management code. This is a confirmed current-code absence, not a permanent guarantee: Phase 3 still requires event/listener/queue/integration guards and a zero-message reconciliation assertion.

### Delete behavior

The contact FK cascades on physical patient deletion. Patient normal deletion is soft delete, so the database cascade occurs only on a physical delete. Contacts themselves have no soft-delete recovery. Migration rollback must use protected, run-owned mapping evidence and must never broadly delete contacts or depend on patient cascade semantics.

## Target-safe normalization implications

These are target constraints, not Classic profiling conclusions:

- Decode Classic text deliberately, normalize Unicode NFC, trim outer whitespace, reject forbidden control characters, and preserve a protected source representation.
- Enforce operational maxima (occupation 100, address 500, religion 100, contact name 100, contact phone 20, relationship 50) even though physical columns are wider.
- Do not populate `city`, `town`, `region`, `digital_address` or nonexistent `postal_code` from unstructured `Address`.
- Do not create an occupation reference row. A configured occupation-label match is useful display compatibility, not an identity/natural-key rule.
- Map marital status only to the four exact enum values; blank may remain null, while an unrecognized nonblank value is a field-level withheld/exception outcome.
- Religion may carry only an explicitly approved normalized display mapping. Blank is null; unrecognized values are not silently null or `Other`.
- Contact relationship is nullable free text but current UI is a seven-label selector. Use explicit safe mappings; blank is null; unknown is withheld/review unless a future explicit free-text preservation rule is approved.
- A contact requires both valid name and valid primary phone because the installed columns are NOT NULL and operational validation requires both. Relationship is optional. Source phone may never be replaced by the patient's own phone.
- Contact comparison canonicalization is for protected migration idempotency only. It must never become patient identity evidence or a cross-environment/domain hash.

## Required target guards and reconciliation assertions

Before any future Phase 3 patient-child persistence, fail closed unless:

1. environment and exact database are approved non-production/migration targets;
2. full target schema fingerprint and installed migration ledger match the approved coordinate;
3. all columns, lengths, nullability, defaults, FK actions, indexes and enum cases above match;
4. dormant inline fields remain unused by current code and target state;
5. the patient crosswalk and patient-root release token exist and uniquely resolve;
6. existing-target links are classified before child evaluation;
7. a protected migration contact idempotency record is available;
8. operational events, activity, schedulers, archive jobs, queues and integrations are isolated;
9. application target state has no unexplained multiple-primary or duplicate run-owned contact state;
10. the target has not introduced a contact observer, new uniqueness rule, relationship enum, non-null timestamp, notification route or active inline emergency-contact behavior.

Mandatory target-side zero assertions:

- dormant inline fields populated by migration = 0;
- existing-target demographic/contact/primary/timestamp/activity changes = 0;
- contacts attached without one committed protected patient mapping = 0;
- contacts attached to a different patient through similarity/search/merge = 0;
- migration-created contacts with missing name or phone = 0;
- migration-created contacts exceeding operational lengths or invalid target phone format = 0;
- migration-created patients with more than one primary contact = 0;
- contacts duplicated by resume = 0;
- migration-time contact timestamps presented as Classic event times = 0;
- contact-derived aliases or patient matches = 0;
- operational activity, archive mutation, notification, email or SMS caused by migration = 0.

## Phase 3 target prerequisites

The renewed application still needs the following migration foundation before persistence can be authorised:

1. migration-specific patient/contact persistence boundary with explicit transaction and topological release semantics;
2. protected patient-child crosswalk/provenance and deterministic contact idempotency key;
3. target enum, Unicode, collation, length, control-character and phone validators;
4. one-primary validator plus locked/atomic creation for a new patient contact set;
5. explicit null/absence handling for contact timestamps, `phone_secondary`, relationship and demographics;
6. existing-target immutable comparison service that emits only protected outcomes;
7. observer/Spatie activity/queue/scheduler/archive/integration isolation and post-run zero assertions;
8. quarantine/exception ledger with retry, SLA and child-local versus patient-root blocking scopes;
9. rollback/resume logic restricted to run-owned rows proven through protected mappings;
10. schema fingerprint, target-state and code-behavior guards covering the dangerous pathways above;
11. privacy controls for contact name and relationship as well as phone/address, regardless of current UI registry gaps;
12. targeted regression tests for FK enforcement, at-most-one-primary, idempotent resume, null timestamps, no existing-target mutation and no operational side effects.

## Confirmed findings, proposed conclusions and unresolved implementation items

### Confirmed

- Proper emergency contacts are `emergency_contacts` children; inline patient emergency-contact columns are dormant.
- Contact name and phone are target-required; relationship is nullable free text; no contact type exists.
- There is no database contact uniqueness or one-primary invariant, no contact soft delete, and no contact audit/service layer.
- Occupation, address and religion are nullable string/text targets; marital status is an application enum with four exact values but no DB check.
- Locality/digital-address fields are separate and `patients.postal_code` does not exist.
- Current patient registration makes the first submitted contact primary, but this is application initialization behavior rather than Classic evidence.
- Operational services, merge, search, archive and activity behavior are unsafe migration entry points.

### Proposed for lead consolidation

- Use the sole complete valid Classic NOK tuple as the initial primary contact only for a newly created target patient, labelled as target initialization and validated as at most one; never do so for partial or existing-target cases.
- Store no migration execution timestamps on the contact; use null plus protected absence provenance.
- Preserve valid occupation/address/religion/marital candidates only on newly created patients; leave unsupported derived locality fields null and withhold invalid optional fields without blocking the patient.
- Use explicit UI-compatible relationship/religion crosswalks; do not use `Other` as a convenience default.

### Remaining implementation design (Phase 3, not Phase 2D)

- Physical protected crosswalk, provenance, exception and reconciliation storage designs.
- Atomic patient/contact persistence and precise rollback mechanics.
- Protected contact idempotency message/domain/key version.
- Runtime isolation implementation and archive scheduler control.
- Whether current privacy/controller route-binding gaps are remediated as application hardening before migration; migration safety cannot rely on those paths either way.
