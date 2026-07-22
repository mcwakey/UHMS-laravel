# Target patient identity contract

Status: **Phase 2C documentation contract; persistence is not authorized.**

This contract fixes the installed renewed-UHMS patient identity boundary that later mapping, dry-run, persistence, and reconciliation designs must obey. It does not define a Classic-to-target column map, create migration state, authorize a target write, or make the normal patient services safe for historical import.

## Authority and evidence coordinate

The governing rules are `AGENTS.md`; D-001 through D-008, D-101 through D-107, D-201, D-211, D-213, and D-214; Q-001, Q-002, Q-006 through Q-008, Q-203, Q-204, and Q-404; and the Phase 2B actor contracts. Where application behavior is stricter than the physical database, the migration boundary must enforce the stricter approved logical rule or stop for a controlled decision. It may not use spare physical capacity as an implicit policy change.

The installed-schema evidence is the local non-production database `uhms_clean`, captured in `TARGET_CONSTRAINT_MANIFEST.json` and `TARGET_SCHEMA_FINGERPRINT.json` at `2026-07-21T01:47:08+00:00`, fingerprint `12e3a4c6ca80a0c1a54ea9267d9c4935453b7a36d27c7f8ad900062c1c12bff0`. It contains 335 tables, 5,347 columns, and no database trigger, event, or stored routine. One unrelated permission-seed migration was pending at discovery; no patient-domain installed drift was confirmed. The repository schema dump is stale and is not authority.

The sanitized discovery baseline was 100 patient rows, one active yearly sequence row with `last_sequence = 100`, zero aliases, zero soft-deleted patients, and zero null registrars. These mutable counts are evidence coordinates only. Every dry-run and commit preflight must recapture them without emitting patient-identifying values.

## Installed `patients` table

The installed table is InnoDB with `utf8mb4_unicode_ci`, 46 columns, and no database trigger. `id` is target-owned; no Classic primary key may be written to it.

| # | Column | Installed type | Null/default/key | Binding contract |
|---:|---|---|---|---|
| 1 | `id` | `bigint unsigned` auto-increment | not null; primary key | Target-generated only. |
| 2 | `patient_number` | `varchar(191)` | not null; unique | Target-generated; uniqueness reserves values held by soft-deleted rows. |
| 3 | `first_name` | `varchar(191)` | not null | Operational create/update maximum is 100; no automatic source-name split or placeholder. |
| 4 | `last_name` | `varchar(191)` | not null | Operational create/update maximum is 100; no automatic source-name split or placeholder. |
| 5 | `other_names` | `varchar(191)` | null | Operational maximum is 100. |
| 6 | `date_of_birth` | `date` | not null | Operational rule is a valid date strictly before today; exact historical lower bound remains a prerequisite. |
| 7 | `height` | `decimal(5,1)` | null | Precision and source semantics require explicit validation; normal registration supplies no rule. |
| 8 | `gender` | `varchar(191)` | not null | `Gender` cast accepts exactly `male` or `female`; database has no check. |
| 9 | `blood_group` | `varchar(191)` | null | Cast accepts `A+`, `A-`, `B+`, `B-`, `AB+`, `AB-`, `O+`, `O-`; database has no check. |
| 10 | `marital_status` | `varchar(191)` | null | Cast accepts `single`, `married`, `divorced`, `widowed`; database has no check. |
| 11 | `religion` | `varchar(191)` | null | Operational maximum is 100. |
| 12 | `phone` | `varchar(191)` | not null; indexed, not unique | Operational maximum is 20; current Ghana rule is `^(?:\+233|0)[235][0-9]{8}$`; storage does not normalize. |
| 13 | `phone_secondary` | `varchar(191)` | null | Operational maximum is 30; no current regex. |
| 14 | `email` | `varchar(191)` | null | Operational email validation; not unique; no storage normalization. |
| 15 | `ghana_card_number` | `varchar(191)` | null; unique | Operational maximum is 30; no format rule. Case-insensitive unique collation includes soft-deleted rows; blank must become null or an exception, never a stored empty identifier. |
| 16 | `occupation` | `varchar(191)` | null | Operational maximum is 100; Phase 2D mapping. |
| 17 | `address` | `text` | null | Operational maximum is 500; Phase 2D mapping. |
| 18 | `city` | `varchar(191)` | null | Operational maximum is 100; Phase 2D mapping. |
| 19 | `town` | `varchar(191)` | null | Operational maximum is 100; Phase 2D mapping. |
| 20 | `region` | `varchar(191)` | null | Operational maximum is 100; Phase 2D mapping. |
| 21 | `digital_address` | `varchar(191)` | null | Operational maximum is 30; current Ghana rule is `^[A-Z]{2}-[0-9]{3,4}-[0-9]{3,4}$`. |
| 22-24 | `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship` | `varchar(191)` | null | Dormant inline columns: absent from `Patient::$fillable` and registration/display paths. Do not populate; target contacts use `emergency_contacts` and belong to Phase 2D. |
| 25 | `avatar` | `varchar(191)` | null | Path only; normal service writes/deletes public-disk files. No file import without a protected file-migration contract. |
| 26-27 | `allergies`, `chronic_conditions` | `text` | null | Operational maximum is 1,000; privacy level 3; later clinical scope. |
| 28 | `status` | `varchar(191)` | not null; default `active`; indexed | Code uses `active`, `inactive`, `archived`, `deceased`; no DB enum/check. |
| 29 | `is_active` | `tinyint(1)` | not null; default 1 | Must be coherent with status; no DB check. |
| 30 | `is_temporary` | `tinyint(1)` | not null; default 0 | Operational placeholder identities are not a migration representation. |
| 31 | `temporary_reason` | `varchar(191)` | null | Must remain coherent with temporary state. |
| 32 | `identity_confirmed_at` | `timestamp` | null | Source-evidenced chronology only. |
| 33 | `identity_confirmed_by` | `bigint unsigned` | null; user FK, delete set null | Requires separately verified field-semantic actor evidence. |
| 34 | `merged_to_patient_id` | `bigint unsigned` | null; patient FK, delete set null | Operational merge pointer, never a Classic key or importer link. |
| 35 | `merge_status` | `varchar(20)` | not null; default `ACTIVE` | Code also uses `MERGED`; no DB coherence or acyclic-chain constraint. |
| 36 | `merged_at` | `datetime` | null | Operational merge evidence only; never migration time. |
| 37 | `merged_by` | `bigint unsigned` | null; no installed user FK/index | Application actor relationship only; do not populate from unverified evidence. |
| 38 | `is_deceased` | `tinyint(1)` | not null; default 0 | Must be coherent with status and death fields; no DB check. |
| 39 | `deceased_at` | `date` | null | Source-evidenced date only. |
| 40 | `cause_of_death` | `varchar(255)` | null | Protected clinical content; separate mapping/retention rule. |
| 41 | `deceased_notes` | `text` | null | Protected clinical content; separate mapping/retention rule. |
| 42 | `marked_deceased_by` | `bigint unsigned` | null; user FK, delete set null | Requires verified action-role evidence; normal service stamps current actor. |
| 43 | `registered_by` | `bigint unsigned` | null; user FK, delete set null | Mandatory Phase 2C outcome is null plus protected absence provenance; see `PATIENT_REGISTRATION_ATTRIBUTION.md`. |
| 44-46 | `created_at`, `updated_at`, `deleted_at` | `timestamp` | null; `created_at` indexed | Eloquent timestamps/soft deletes are runtime behavior. Preserve valid source time explicitly; do not stamp migration time or infer deletion. |

Patient-level `nhis_number` and `nhis_expiry_date` were intentionally removed. Insurance belongs in `patient_insurances`; migration must not recreate or populate retired patient columns.

### Required patient identity

A new target patient requires independently evidenced, valid `first_name`, `last_name`, `date_of_birth`, `gender`, and `phone`, plus a generated target patient number. Physical nullability does not permit placeholders. Classic has only one combined name, so source-only name data cannot automatically satisfy separate first/last components. Automatic splitting, copying a single token into both fields, inventing a component, or using emergency/newborn placeholder services is prohibited by D-213.

Missing, invalid, or unrepresentable required identity quarantines the patient and complete source dependency chain. A duplicate OPD is different: it withholds the alias and creates an exception but does not by itself block an otherwise valid patient.

## Patient number and sequence contract

The installed `patient_number_sequences` table is:

| Column | Installed contract |
|---|---|
| `id` | `bigint unsigned`, auto-increment primary key |
| `prefix` | required `varchar(20)` |
| `period_type` | required native enum `never`, `yearly`, `monthly`, `daily`; default `yearly` |
| `period_key` | required `varchar(20)`; default `GLOBAL` |
| `last_sequence` | required `bigint unsigned`; default 0 |
| `created_at`, `updated_at` | nullable timestamps |

Unique key `pns_unique(prefix, period_type, period_key)` is database-enforced. The effective discovery runtime was prefix `UHMS`, pattern `{PREFIX}-{SEQUENCE}/{YEAR}`, width 6, yearly reset, timezone UTC. The repository fallback pattern differs, so resolved runtime configuration must be captured, validated, and fingerprinted for each window.

`PatientIdGeneratorService` locks an existing sequence row, but unchanged it is not migration-safe: dry-run mutates state; missing-row creation can race; allocation can commit before patient insertion; current time determines the period; collision handling appends an unreconciled, nondeterministic `uniqid()` suffix; and there is no source mapping or resume check. `Patient::generatePatientNumber()` is a second, date/scanning-based nonlocking implementation and is prohibited.

The later migration allocator must make crosswalk lookup the first operation and atomically bind source mapping state, exact sequence allocation, patient insertion, number provenance, and migration audit. It must safely retry first-row creation, pin resolved configuration and UTC period, query all patient rows including soft-deleted/merged rows, check aliases and archived patient-number snapshots in the operational lookup namespace, and fail rather than decorate a collision. Dry-run allocates nothing. Resume returns the existing mapped patient/number. Concurrent operational registration requires either a controlled freeze or one exact unified allocation ledger. Delete/recreate, sequence rewind, number recycling after commit, and replacement numbers on retry are prohibited.

## Patient alias contract

Installed `patient_aliases` has required `patient_id`, `alias_type varchar(50)`, `alias_value varchar(191)`, and `normalized_alias_value varchar(120)`; nullable `source_patient_id`, `metadata longtext`, `created_by`, and timestamps. It has no soft deletes or activity logging. FKs are: patient owner to `patients.id` with delete cascade; `source_patient_id` to another target patient with delete set null; creator to users with delete set null. Unique key `patient_aliases_alias_unique(alias_type, normalized_alias_value)` is global under `utf8mb4_unicode_ci`.

`source_patient_id` belongs to the target duplicate-folder merge workflow. It is never a Classic source-key column. Classic `PAT_ID` belongs only in the protected crosswalk/provenance layer.

Current `PatientAlias::normalize()` trims, removes whitespace, and applies PHP `strtoupper`. It is not an approved final legacy normalizer because it has no explicit Unicode NFC, Unicode-aware case rule, normalization version, validity grammar, or protected collision-release workflow. There is no installed `legacy_opd` constant/service.

Before alias persistence, Phase 3 must approve a stable `legacy_opd` type and one versioned normalizer used by extraction, grouping, target checks, insert, and later search. It must explicitly decode Classic latin1, normalize Unicode NFC, define Unicode whitespace/case behavior, preserve leading zeros and punctuation, reject invalid/control/noncharacter/blank/overlength values, test both canonical equality and target-collation equality, and retain the exact source representation only in protected provenance. All members of a duplicate normalized source group are withheld; target conflicts, including owners that are soft-deleted or merged, are withheld. No suffix, punctuation change, preferred owner, implicit release, or alias-driven patient match is allowed. `created_by` is null absent separate verified field-semantic actor evidence.

## Existing-target and soft-delete contract

`Patient` uses `SoftDeletes`; default Eloquent queries hide deleted rows. Database unique keys continue to reserve soft-deleted `patient_number` and `ghana_card_number` values. Aliases are not soft-deleted and continue to reserve their global typed key. A force delete can cascade a large clinical and financial graph.

Every collision and existing-target preflight must use unscoped/`withTrashed()` patient queries. A source may link to an existing target only through an explicit protected pre-approved crosswalk or a separately reviewed deterministic identity decision. Name, normalized name, OPD/alias, phone, email, DOB, gender, address, Ghana Card, similarity, and the merge confidence score are never sufficient alone. A link to a missing, soft-deleted, or merged target is blocking. A valid link changes no target identity, number, registrar, timestamps, state, alias, or child. Delete-and-recreate is never an idempotency strategy.

## Merge structures and prohibition

Installed merge structures are operational records:

- `patient_merge_requests`: unique required `request_number`; required main and duplicate patient FKs with delete cascade; nullable request/approve/execute user FKs with delete set null; required status default `PENDING_REVIEW`; nullable reason, confidence, resolution/preview, approval/execution/rejection fields and timestamps.
- `patient_merge_logs`: nullable request, main patient, duplicate patient, and actor FKs, all delete set null; required action; optional table/record/old/new data; `occurred_at` is required and defaults to current timestamp.
- patient merge fields are not constrained for self-reference, acyclicity, status coherence, actor chronology, or terminal-folder eligibility.

`PatientMergeService` is not an importer deduplication API. Its confidence score is advisory; it can create aliases, overwrite demographics, move selected children, stamp current actors/times, and emit PHI-rich logs/activity. Its hard-coded reassignment list is smaller than the installed patient dependency surface. Migration creates zero merge requests, merge executions, merge logs, or automatic many-source-to-one links. Suspected duplicates remain separate eligible patients with a protected review flag. Any later approved merge is a post-migration operational process after dependency coverage, privacy, actor, and approval-segregation review.

## Archive and privacy contract

`archived_patients` has nullable-unique `patient_id` with delete set null; required copied `patient_number`, `full_name`, and `archived_at`; nullable prior status, last activity, reason, payload, and timestamps. The archive command evaluates present activity, writes `archived_at = now()`, copies full patient data into a PHI-bearing payload, and changes current status. Migration must not call it, infer archive/soft-delete state from a Classic status, direct-create archive rows, or duplicate PHI without a separate retention contract.

Installed privacy structures are operational authorization records:

- `patient_privacy_directives` requires patient, type, status (default `active`), summary, and creator; patient delete cascades; creator delete is restricted; updater is nullable/set-null. Optional details and effective dates do not make source-free synthesis permissible.
- `patient_privacy_overrides` requires user, reason, start, and expiry; patient and visit are nullable; user/patient deletion cascades, visit/revoker deletion sets null. It is a current break-glass record, not historical patient data.

Current privacy is masking/permission logic, not encryption at rest. Phone, email, address, Ghana Card, membership/policy/CCC identifiers, and emergency-contact phone are level 2; allergies/chronic conditions are level 3. Patient number, gender, age, and DOB are level 0; first/last/other names are absent from the generic registry. Patient search queries raw names, identifiers, contacts, insurance, and aliases. No migration diagnostic, log, document, screenshot, fixture, or source-controlled artifact may contain those values.

Ordinary patient Eloquent writes use Spatie activity logging for dirty `first_name`, `last_name`, and `status`, potentially creating current-time PHI-bearing activity. Profile views and break-glass paths can also create activity. Historical persistence must suppress operational activity and keep the separately labelled migration audit non-PHI. Privacy directives/overrides, historical views, and break-glass events must not be synthesized. Avatar file handling remains prohibited until separately approved.

## Exact installed downstream patient dependency surface

The installed catalogue contains **84 actual foreign keys to `patients` across 79 tables**: 61 force-delete cascades and 23 set null. Soft delete invokes none of these FK actions but hides the patient from normal Eloquent queries.

### Non-null, force-delete cascade (46)

`admissions.patient_id`, `admission_discharge_clearances.patient_id`, `admission_discharge_summaries.patient_id`, `admission_location_histories.patient_id`, `admission_requests.patient_id`, `antenatal_visits.patient_id`, `appointments.patient_id`, `bed_reservations.patient_id`, `claims.patient_id`, `delivery_records.patient_id`, `dispensing_records.patient_id`, `emergency_cases.patient_id`, `emergency_contacts.patient_id`, `emergency_notes.patient_id`, `emergency_sessions.patient_id`, `history_of_presenting_complaints.patient_id`, `labor_episodes.patient_id`, `labor_observations.patient_id`, `maternity_cases.patient_id`, `medical_records.patient_id`, `medication_administrations.patient_id`, `medication_administration_schedules.patient_id`, `medication_orders.patient_id`, `newborn_records.mother_patient_id`, `nursing_notes.patient_id`, `nursing_tasks.patient_id`, `patient_aliases.patient_id`, `patient_financial_risk_history.patient_id`, `patient_financial_risk_profiles.patient_id`, `patient_insurances.patient_id`, `patient_merge_requests.duplicate_patient_id`, `patient_merge_requests.main_patient_id`, `patient_privacy_directives.patient_id`, `patient_procedures.patient_id`, `pharmacy_billing_selections.patient_id`, `physical_examinations.patient_id`, `postnatal_cases.mother_patient_id`, `postnatal_mother_observations.mother_patient_id`, `postnatal_newborn_observations.mother_patient_id`, `pregnancy_profiles.patient_id`, `prescriptions.patient_id`, `procedure_requests.patient_id`, `service_renderings.patient_id`, `triages.patient_id`, `visits.patient_id`, `vitals.patient_id`.

### Nullable, force-delete cascade (15)

`blood_crossmatches.patient_id`, `blood_issues.patient_id`, `blood_recipients.patient_id`, `blood_requests.patient_id`, `clinical_tasks.patient_id`, `complaints.patient_id`, `consultation_tasks.patient_id`, `diagnoses.patient_id`, `emergency_case_logs.patient_id`, `investigations.patient_id`, `invoices.patient_id`, `lab_requests.patient_id`, `patient_privacy_overrides.patient_id`, `payments.patient_id`, `treatments.patient_id`.

### Nullable, force-delete set null (23)

`archived_patients.patient_id`, `blood_donors.patient_id`, `claim_items.patient_id`, `consumable_usages.patient_id`, `credit_notes.patient_id`, `front_desk_call_logs.related_patient_id`, `front_desk_courier_logs.related_patient_id`, `front_desk_visitor_logs.patient_id`, `invoice_receivables.patient_id`, `journal_entry_lines.patient_id`, `maternity_billing_events.patient_id`, `newborn_records.newborn_patient_id`, `patients.merged_to_patient_id`, `patient_aliases.source_patient_id`, `patient_merge_logs.duplicate_patient_id`, `patient_merge_logs.main_patient_id`, `payment_provider_transactions.patient_id`, `payment_request_links.patient_id`, `postnatal_newborn_observations.newborn_patient_id`, `receivable_cases.patient_id`, `service_rendering_logs.patient_id`, `sponsor_authorizations.patient_id`, `visit_billing_overrides.patient_id`.

### Patient-shaped columns without a patient FK (8)

`activity_log.patient_id`, `admission_bed_charges.patient_id`, `admission_daily_consumable_charges.patient_id`, `emergency_bed_charges.patient_id`, `emergency_daily_consumable_charges.patient_id`, `invoice_items.patient_id`, `visit_consultation_routes.patient_id`, and `visit_pathway_events.patient_id`.

These eight fields require explicit application-level owner validation, protected mapping, and chain reconciliation. The merge service list is not a substitute for this manifest.

## Service-only invariants

The database alone does not enforce the following. A later migration boundary must validate them explicitly without invoking present-day workflows:

1. target-number format and the unique, row-locked `(prefix, period_type, period_key)` sequence, with exact allocation reconciliation;
2. durable source-map idempotency before allocation, which current patient services do not provide;
3. enum-compatible gender, blood-group, and marital-status strings;
4. operational length/format rules where stricter than installed widths;
5. coherence among status, `is_active`, temporary state, identity-confirmation fields, merge state/pointer/time/actor, and death state/date/actor;
6. no new records on merged folders, recognizing that guard coverage is not universal;
7. at most one primary emergency contact and one primary insurance membership, neither database-unique;
8. patient-insurance provider/tier consistency, beneficiary/card-holder consistency, cycle prevention, maximum beneficiaries, validity, and one deterministic current patient/provider membership;
9. all 84 FK links plus the eight non-FK application links resolve through protected target mappings and remain in one consistent patient chain;
10. patient search, alias partial matches, and merge confidence are lookup/review features, never identity evidence;
11. historical timestamps and actors are source-evidenced, not generated from current runtime context;
12. downstream service-only rules—including one visit per patient/day, one active admission per patient/visit, one logical medical record per route, active bed occupancy, route-context consistency, allocation validity, and financial/stock equations—remain later-domain gates after patient mapping.

## Permitted future persistence boundary

“Direct persistence” means only a reviewed, transactional migration service that validates target constraints and logical invariants while suppressing events and integrations. It never means raw copying.

| Record/action | Contract |
|---|---|
| New patient core | Migration-specific validated persistence after safe allocation; never unchanged `PatientService::create()`. |
| Patient number | Migration allocator reproducing configured sequence invariants, with crosswalk-first exactly-once behavior. |
| Unique valid legacy OPD alias | Insert only after owner mapping, final canonicalization, and source/target collision checks. |
| Explicit existing-target link | Crosswalk only; zero target mutation and zero allocation. |
| Suspected duplicate | Protected review flag only; no merge or link. |
| Emergency contact | Deferred to Phase 2D after patient mapping and contact/privacy rules. |
| Deterministic current insurance | Deferred until Phase 2A provider/tier maps and D-212 consolidation; preserve all source rows in protected history. |
| Merge, archive, privacy directive/override | Operational workflows only when genuinely authorized later; never synthesized by patient migration. |

## Runtime isolation and zero-side-effect assertions

The commit window must fail closed unless it suppresses or proves unreachable: Spatie and explicit/queued activity; external audit forwarding; authenticated/current/first/admin/importer fallback; Eloquent current timestamps; avatar/files; web child writes; merge/alias/log workflows; archive and patient schedulers; privacy view/break-glass/directive audit; notifications, SMS, email, push, queues, and outboxes; visits, queues, consultation, admission/bed, billing/AR/claims/payments/accounting, pharmacy/stock, pathway, maternity, and emergency services.

`Model::withoutEvents()` is insufficient by itself. Each chunk requires before/after controls proving zero prohibited table, queue, file, outbox, integration, and operational-audit mutation. Migration audit is separately labelled and contains run/mapping/transformation/failure/reconciliation facts without PHI or false contemporaneous events.

## Technical prerequisites before Phase 3 persistence

1. Dedicated Classic `SELECT`/metadata-only credentials and exact `uuhms` schema/fingerprint/version guards.
2. Frozen installed target schema and sanitized unscoped target-state snapshot for patients, aliases, archived numbers, and sequence rows.
3. Protected, unique source-to-target crosswalk and source-row fingerprint; versioned domain-separated HMAC token/key/rotation contract.
4. Evidence-backed first/last-name remediation input and approved per-field name, phone, DOB, gender, length, and chronology rules.
5. Migration-specific atomic patient persistence and dry-run/resume-safe allocator; operational-registration freeze or unified allocation ledger.
6. Resolved runtime numbering configuration and period-boundary contract.
7. Approved `legacy_opd` type, Unicode/NFC canonicalizer, validity grammar, privacy-safe duplicate/collision aggregates, and controlled review/release workflow.
8. Unscoped existing-target collision and explicit-crosswalk immutability checks.
9. Approved patient state matrix for active/inactive/temporary/merged/deceased combinations.
10. Protected patient/attendance/insurance dependency-chain quarantine ledger, exception ledger, checkpoints, and exact reconciliation partitions.
11. Full validation/reconciliation for the eight non-FK patient fields.
12. Fail-closed runtime isolation, separate migration audit, and post-chunk zero-side-effect checks.
13. Post-migration merge review design covering all installed dependencies and segregated approval.
14. Phase 2A provider/tier readiness and consumption of Phase 2B actor rule `TARGET-ACTOR-054`.

## Stop conditions and reconciliation assertions

Stop on schema/config/fingerprint drift; production-target suspicion; unresolved required identity; unsafe existing-target link; target number, Ghana Card, alias, or lookup-namespace collision; unknown enum/state; sequence race/underflow/unexplained advance; allocation on rerun; target unique-key race; raw PHI in diagnostics; prohibited runtime effect; missing chain outcome; or any difference in a declared partition.

Required zero assertions include: Classic key reused as target ID or number; OPD used as patient number; automatic patient match/merge; duplicate OPD alias assignment or decoration; more than one successful target/number per source; replacement number on rerun; migration-created merge/archive/privacy workflow; mutation of an explicitly linked existing target; descendant release before its patient; unexplained non-FK patient link; and force-delete/delete-recreate recovery.

The patient entity, alias, required-field, mapping, allocation, chain, and side-effect partitions must each reconcile independently. A successful patient mapping never waives alias, actor, clinical, finance, insurance, or child-domain exceptions.

## Confirmed, inferred, unresolved

**Confirmed:** the installed 46-column patient table; alias, sequence, merge, archive, and privacy structures; 84 patient FKs and eight patient-shaped non-FK fields; soft-delete and runtime behavior; and the service defects/invariants above.

**Inferred technical requirement:** a migration-specific transactional allocator/persistence boundary can reproduce the safe target invariants without normal operational workflows. Its implementation and schema/state design remain Phase 3 work.

**Unresolved prerequisites:** exact source-name remediation, DOB lower bound and same-day chronology, final OPD grammar/canonicalization, target historical status representation, retention of any source-evidenced archive/privacy fact, and whether the effective numbering pattern is the approved institutional cutover contract. None may be answered with a convenient default.
