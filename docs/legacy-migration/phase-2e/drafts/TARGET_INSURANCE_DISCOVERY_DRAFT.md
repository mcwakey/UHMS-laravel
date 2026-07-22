# Phase 2E renewed target insurance discovery draft

## Scope and safety

This draft is the target-schema specialist handoff for Phase 2E. It inspects only the renewed Laravel application and the installed local non-production target. It does not inspect or query Classic, map Classic row values, authorize persistence, or implement an importer.

- **Target environment:** confirmed `local`; application timezone `UTC`.
- **Installed target:** confirmed `uhms_clean`, MariaDB `10.4.32`.
- **Read-only evidence:** `php artisan legacy-migration:inspect-target --connection=mysql --expected-database=uhms_clean` completed in its built-in read-only transaction; a separate aggregate probe ran with `SET SESSION TRANSACTION READ ONLY` and was rolled back.
- **Installed fingerprint:** `12e3a4c6ca80a0c1a54ea9267d9c4935453b7a36d27c7f8ad900062c1c12bff0`, unchanged from the Phase 1B baseline.
- **Installed shape:** 335 tables and 5,347 columns. Relevant migrations are installed. The only repository migration absent from the ledger is the unrelated `2026_07_20_000001_seed_admission_billing_amount_permission`.
- **Executable database objects:** zero triggers, zero scheduled events, zero stored routines and zero views in the Phase 1B/refresh evidence.
- **Writes:** zero database writes. Temporary local inspection output was removed after comparison. No identifiers or row-level insurance values were emitted.

Evidence classes used below:

- **Confirmed installed evidence:** refreshed `information_schema` metadata or sanitized aggregate query against `uhms_clean`.
- **Confirmed code behaviour:** current repository code.
- **Inference:** a conclusion that follows from confirmed evidence but is not itself enforced.
- **Phase 2 technical consequence:** a fail-closed requirement for the consolidated Phase 2E specification; not importer authorization.

## Installed target catalogue

### `patient_insurances`

Confirmed installed columns:

| # | Column | Installed type | Null | Default | Target meaning / warning |
|---:|---|---|---|---|---|
| 1 | `id` | `bigint unsigned` | no | auto increment | Target-owned identity; never a Classic key. |
| 2 | `patient_id` | `bigint unsigned` | no | none | Required FK to `patients.id`; delete cascades. |
| 3 | `insurance_provider_id` | `bigint unsigned` | no | none | Required FK to `insurance_providers.id`; delete cascades. |
| 4 | `insurance_tier_id` | `bigint unsigned` | yes | null | FK to `insurance_tiers.id`; target does not DB-enforce that the tier belongs to the same provider. |
| 5 | `member_type` | native `enum('holder','beneficiary')` | no | `holder` | Operational family-role state. A missing Classic fact must not silently become `holder`. |
| 6 | `card_holder_insurance_id` | `bigint unsigned` | yes | null | Self-FK, `SET NULL` on delete. Same-provider/tier/patient-family consistency is not DB-enforced. |
| 7 | `membership_number` | `varchar(191)` | yes | null | Restricted identifier; no unique index. |
| 8 | `policy_number` | `varchar(191)` | yes | null | Restricted identifier; no unique index. |
| 9 | `ccc_code` | `varchar(64)` | yes | null | Indexed operational verification code, not historical eligibility proof. |
| 10 | `start_date` | `date` | yes | null | Nullable representation exists. Normal membership controllers do not accept it. |
| 11 | `expiry_date` | `date` | yes | null | Nullable representation exists. |
| 12 | `is_primary` | `tinyint(1)` | no | `0` | No database uniqueness enforcing one primary per patient. |
| 13 | `is_active` | `tinyint(1)` | no | `1` | Unsafe database default for historical import; must be explicitly classified. |
| 14 | `created_at` | `timestamp` | yes | null | No historical timestamp authority follows from nullability. |
| 15 | `updated_at` | `timestamp` | yes | null | No historical timestamp authority follows from nullability. |

Constraints and indexes:

- Primary key: `id`.
- Unique boundary: `patient_insurance_unique(patient_id, insurance_provider_id)`. This implements D-212's maximum one target row per patient/provider; it does not justify dropping source history.
- FKs: patient and provider are required and cascade on parent deletion; tier and card-holder links are nullable and become null on referenced deletion.
- Non-unique indexes: `is_active`, `ccc_code`, provider, tier and card-holder link.
- No soft-delete column, no generated column and no trigger.

Repository evidence: `database/migrations/2026_04_13_100001_create_patient_insurances_table.php:11-23`, `2026_04_13_200004_add_start_date_to_patient_insurances.php:11-14`, `2026_04_21_220100_add_tier_member_to_patient_insurances_table.php:14-34`, and `2026_05_12_000001_add_ccc_code_to_patient_insurances_and_invoice_items.php:36-39`.

### `insurance_providers`

Confirmed installed columns:

`id`, required `name`, required `short_name`, required `type` with database default `public`, nullable `contact_phone`, `contact_email`, `address`, `contract_number`, required `is_active` default `1`, required `is_default` default `0`, nullable `verification_driver`, `verification_method`, `verification_channel`, `verification_config`, `verification_credentials_key`, nullable timestamps, nullable `insurance_type_id`, nullable indexed `code`, nullable `description`, nullable claim/verification override fields (`requires_claim_submission`, `requires_verification_code`, `verification_code_label`, `claim_workflow_override`, `claim_export_format_override`).

Constraints and consequences:

- `insurance_type_id` is a nullable FK to `insurance_types.id` with `SET NULL` on delete.
- Provider `name`, `short_name`, `code`, `(name,type)` and default-provider status are not database-unique.
- Installed `type` is a string, while the model casts it to `App\Enums\InsuranceType` (`self`, `nhia`, `private`, `corporate`). The installed default `public` is not an enum case. Phase 2A already prohibits relying on that default.
- Provider contact, contract, verification configuration and credentials have no approved source in Phase 2E and must not be populated from membership rows.
- The provider is a Phase 2A crosswalk dependency, not something an insurance row may create.

Repository evidence: `database/migrations/2026_04_12_600001_create_insurance_providers_table.php:11-21`, `database/migrations/2026_05_06_122923_add_verification_columns_to_insurance_providers_table.php:27-42`, `database/migrations/2026_05_25_100000_add_insurance_type_claim_workflows.php:33-55`, and `app/Models/InsuranceProvider.php:15-50`.

### `insurance_types`

Confirmed installed columns are `id`, required `name`, unique required `code varchar(40)`, nullable `description`, nullable `claim_workflow`, required boolean claim/verification requirement flags defaulting false, nullable verification label and export format, required `is_active` default `1`, and nullable timestamps.

The installed active reference codes are exactly `CORPORATE`, `NHIA`, `PRIVATE`, and `SELF`. These are safe reference values. `patient_insurances` has no `insurance_type_id`: the type relationship is provider -> insurance type, while `member_type` means holder/beneficiary. Therefore a Classic insurance-type value cannot be copied into `member_type` and must use a separate explicit crosswalk/provenance rule.

### `insurance_tiers`

Confirmed installed columns are:

- identity/relationship: `id`, required `insurance_provider_id` FK (cascade on provider delete);
- catalogue: required `name`, nullable `code` and `description`, `is_default` default false, `is_active` default true, `sort_order` default zero;
- coverage: `coverage_percentage decimal(5,2)` default `100.00`; nullable per-visit, annual, monthly, visit-count and interval limits;
- family rules: nullable `max_beneficiaries` and holder/beneficiary-specific limit overrides;
- nullable timestamps.

There is no unique tier name/code/natural key and no database constraint enforcing one default tier per provider. The operational controller enforces one default only in its own update path. A new provider created through the normal controller automatically receives a `Standard`/`STD` tier with 100% coverage (`app/Http/Controllers/Admin/Insurance/InsuranceProviderController.php:31-43`). That is an operational convenience, not migration evidence.

No dedicated installed insurance scheme/plan table or patient-insurance scheme/plan/free-text column was found. `insurance_tiers` are operational coverage/benefit definitions and must not be assumed equivalent to a Classic scheme. Scheme representation is therefore a Phase 3 prerequisite unless a provider-scoped, reviewed mapping resolves to an already-existing tier without mutating it.

### `insurance_verifications`

Confirmed installed columns are required `patient_insurance_id`, required `insurance_provider_id`, nullable `visit_id`, required `driver` and `status`, nullable `reference_code`, membership-number snapshot, member name, expiry snapshot, actor/time, payload/message and timestamps.

- Membership deletes cascade verification rows; provider deletion is restricted; visit/user deletion nulls their links.
- Verification status is a string column, not a SQL enum/check. Application cases are `pending`, `valid`, `invalid`, `expired`, `not_required`, `error`, and `manual_override`.
- This is an operational verification-event table, not a protected Classic membership-history table.
- `verified_by` and `verified_at` are nullable in the database, so no actor/time invention is required by installed constraints. Phase 2E must create no verification rows.

Repository evidence: `database/migrations/2026_05_06_122924_create_insurance_verifications_table.php:10-33`, `app/Models/InsuranceVerification.php`, and `app/Enums/VerificationStatus.php`.

## Sanitized installed-state collision baseline

The aggregate read-only snapshot found:

| Domain | Aggregate evidence |
|---|---|
| Patient memberships | **Nonzero small-cell counts suppressed because the population is below 10 and synthetic/de-identification status is not evidenced.** Zero duplicate patient/provider groups and zero multiple-primary-patient groups are retained. |
| Membership identifiers | Small-cell breakdown suppressed; no values emitted. |
| Dates | Small-cell breakdown suppressed. |
| Tier/family state | Small-cell breakdown suppressed. |
| Providers | Small-cell installed-state counts suppressed. Structural constraints and reference codes remain documented. |
| Types | Installed safe reference codes only: `CORPORATE`, `NHIA`, `PRIVATE`, `SELF`. |
| Tiers | Small-cell installed-state counts suppressed. |
| Verifications | Small-cell counts/status breakdown suppressed; nullable fields and status vocabulary remain structural evidence. |
| Soft deletes | absent from memberships, providers, tiers and verifications. |

**Phase 2 technical consequence:** the target collision snapshot is not empty, but nonzero small-cell results remain protected. Every later run must refresh it under the same target fingerprint and use protected comparison tokens. Existing rows are immutable. Installed nullability—not small-cell row content—proves that null membership identifiers are technically representable; it does not approve creation.

## Confirmed model and application behaviour

### Membership model and date-derived state

`PatientInsurance` casts dates, booleans and `member_type`; it exposes active/valid scopes and derived `is_expired`/`is_valid` accessors (`app/Models/PatientInsurance.php:30-121`). Confirmed consequences:

- `valid` means `is_active = true` and expiry is null or strictly greater than `now()`.
- `is_valid` ignores `start_date`, tier/provider active state, verification, and eligibility.
- `is_expired` evaluates a date at runtime; the evaluation coordinate is not pinned.
- An expiry equal to the runtime date can be treated as past because a date casts to midnight. Inclusive end-date semantics are not established.
- These helpers cannot be the migration's current-state classifier. Phase 2E needs a pinned date/timezone/rule/snapshot coordinate and must explicitly handle start date.

### Normal patient registration and membership CRUD are unsafe for historical creation

Normal patient registration:

- chooses an active/default-first tier when none is supplied;
- forces `member_type=holder`, all rows active, and the first submitted row primary;
- permits nullable identifiers but validates registration expiry as `after:today`;
- does not accept/start `start_date`.

Evidence: `app/Http/Controllers/Admin/Patients/PatientController.php:134-162` and `app/Http/Requests/StorePatientRequest.php:54-92`.

`PatientInsuranceController`:

- rejects duplicate provider assignment at the service path in addition to the DB unique key;
- chooses a default active tier when omitted;
- defaults holder and forces active on create;
- can clear every other primary row before create/update/set-primary;
- validates membership/policy max 50, while registration accepts max 100 and the database accepts 191;
- accepts expiry but not start date;
- can update, deactivate and delete existing memberships;
- does not establish route-model ownership between `{patient}` and `{insurance}` in these methods; this is a runtime integrity risk outside migration.

Evidence: `app/Http/Controllers/Admin/Patients/PatientInsuranceController.php:15-132,162-188`.

**Phase 2 technical consequence:** no normal patient-registration, membership CRUD, provider CRUD or tier CRUD path may be used by a future historical migration. A reviewed migration-specific persistence boundary must validate the installed FKs/enum/unique key plus service-only invariants while explicitly supplying only evidenced/approved initialization values.

### Read paths can write cash memberships

`InsuranceService::getCashAndCarryResult()` and `getPatientInsurances()` call `PatientInsurance::firstOrCreate()` for the default provider when the patient lacks a cash row (`app/Services/InsuranceService.php:337-351,410-452`). `resolveForVisit()` falls back through this path.

This means apparently read-oriented patient-insurance display/visit-resolution calls can create an active holder membership. Phase 2E inspection and later migration validation must not invoke these methods. Cash/self-pay `BillStatus` evidence must not be turned into a provider membership by this fallback.

### Verification is strongly side-effecting

`InsuranceVerificationService::verify()` always persists an `insurance_verifications` record, including when no provider driver exists. It then:

- records driver/status and snapshots membership/name/expiry;
- assigns `Auth::id()` and `now()`;
- may link the verification to a visit;
- writes a patient-context activity log;
- may call manual, code or external API drivers.

Evidence: `app/Services/Insurance/Verification/InsuranceVerificationService.php:29-103`, `app/Services/Insurance/Verification/VerificationManager.php`, and `config/insurance_verification.php`.

Phase 2E must create zero verification rows, assign zero verifier actors/times, call zero verification drivers and emit zero operational activity events. `Legacy Actor Unknown`, importer/current/admin/first user are all prohibited verifier fallbacks. Null/not-evidenced is supported by the target's nullable verification actor/time columns, but verification absence is represented by no verification event, not a fabricated `pending` or `not_required` event.

### Billing, visit, claim and usage coupling

Confirmed runtime dependencies, all outside Phase 2E persistence scope:

- visits link `visit_insurance_id` and `insurance_verification_id`; changing visit insurance logs activity;
- `InsuranceService` evaluates tier coverage and may query `insurance_usages` plus covered invoice items;
- claim creation copies membership and verification facts and can select a valid membership by provider;
- invoice items/usages reference patient insurance and may drive financial totals.

Evidence: `app/Models/Visit.php:354-362`, `app/Http/Controllers/Admin/Visits/VisitController.php:357-409`, `app/Models/PatientInsurance.php:126-282`, and `app/Services/Claims/GenericClaimWorkflow.php:44-90,317-346`.

**Boundary:** no visit payer context, verification, usage, invoice, receivable, payment, accounting or claim object may be created or recomputed in Phase 2E. Future historical membership persistence must isolate queues/integrations and avoid these services.

### Search, display and privacy

- Patient search includes membership-number substring matching (`app/Models/Patient.php:417-433`). Imported raw identifiers would immediately become operationally searchable.
- Membership/policy/CCC are configured as level-2 restricted insurance fields with dedicated view/edit permissions and identifier masking (`config/patient_privacy.php:42-44`).
- `PatientPrivacyService` masks these values for display and logs sensitive categories without raw values.
- `ActivityLogService` lists membership, policy and CCC among sensitive keys, but future migration evidence still belongs in a separate protected migration audit.

Phase 2E artifacts must retain aggregate counts and domain-separated protected tokens only. No raw target or source membership identifiers may enter source control.

### Audit, observers, events and deletion

- `InsuranceProvider` uses Spatie `LogsActivity`, logging dirty `name`, `type` and `is_active` changes under the `insurance` log.
- `PatientInsurance`, `InsuranceTier` and `InsuranceVerification` do not use the activity-log trait.
- No insurance model observer is registered. This does not make operational controllers/services safe: their explicit writes and activity logging remain.
- Patient merge directly moves or deactivates membership rows by provider, then redirects references from visits, invoices, invoice items, claims, usages and verifications (`app/Services/PatientMergeService.php:271-346`). It is prohibited for migration consolidation and incompatible with existing-target immutability.
- Database cascade deletion can erase memberships and downstream verification/usage evidence. Phase 2E performs no deletes, merges or parent changes.

## Database-enforced versus service-only invariants

| Invariant | Database | Service/code | Phase 2E consequence |
|---|---|---|---|
| At most one membership per patient/provider | Enforced by unique key | Also checked on normal create | Consolidate source rows; one target candidate maximum; retain every source row separately. |
| Patient/provider exist | Required FKs | Requests use `exists` | Patient and Phase 2A provider crosswalk must resolve before release. |
| Tier exists | Nullable FK | Controller checks tier belongs to provider | Migration must enforce same-provider tier; DB alone is insufficient. |
| Member type vocabulary | Native SQL enum | Enum cast/request rules | Never default to holder without evidence/approved initialization. |
| Beneficiary has card holder | Not enforced | Controller checks only presence when beneficiary | Phase 2E has no approved family-link source; do not infer beneficiary/card holder. |
| Card holder shares provider/tier and valid family context | Not enforced | Not comprehensively enforced | Quarantine/leave unrepresented; never synthesize a card-holder membership. |
| One primary membership per patient | Not enforced | Controller clears other rows | No implicit primary. Multiple-primary prevention requires migration validation/concurrency control. |
| Active/current validity | `is_active` default only | Runtime expiry checks | Explicit pinned classifier required; DB default is prohibited authority. |
| Start <= expiry | Not enforced | No complete operational validation | Field-specific date classifier/quarantine required. |
| Identifier required/unique | Neither | Nullable; path-specific max lengths | Blank is representable but policy gated; duplicates are diagnostic, never patient identity. |
| Tier/provider default uniqueness | Not enforced | CRUD best effort | Target snapshot must detect drift; no automatic default-tier assignment. |
| Provider canonical type | String/default not safe | Enum cast and type table | Explicit Phase 2A mapping required; never use `public` default. |
| Eligibility/verification | Not on membership row | Runtime event/service | Leave not evidenced; never fabricate. |
| Historical membership versions | No structure | No suitable history service | Protected migration-history store is a prerequisite. |

## Existing-target collision and immutability contract

For an explicitly linked existing target patient, target inspection supports the following deterministic comparison outcomes only:

1. **Exact compatible prior migration result:** the protected migration idempotency key, rule version and source-row set match; make no update.
2. **Existing-target immutable evidence:** record source-versus-target differences in protected provenance only; make no update/create/delete.
3. **Conflict requiring review:** target patient/provider row exists but differs in identifier, tier, member type, dates, flags, verification or history; quarantine the source group.
4. **Idempotency failure:** a claimed prior migration row lacks/mismatches protected run evidence; stop.
5. **Target-state drift:** target fingerprint or protected pre/post token changed after comparison; stop and refresh.

Required zero assertions: existing target membership fields changed, target membership deleted, provider changed, identifiers changed, dates changed, active/primary changed, verification changed, second patient/provider row created, or merge service invoked all equal zero.

The DB has no soft-delete state for membership/provider/tier/verification. A missing previously snapshotted row is therefore deletion/drift, not a hidden soft-deleted match.

## Target dependency order

The target-side release order is:

1. Verify non-production target connection, installed fingerprint and collision snapshot.
2. Resolve Phase 2A `insurance_types`/provider crosswalk (`NK-004`, `VC-001`, `VC-002`, provider exceptions, `RECON-SETT_PRIVATE`, `EXTRACT-SETT_PRIVATE`) without weakening its no-auto-match rule.
3. Resolve Phase 2C patient crosswalk and patient/quarantine-chain token; an existing-target link remains immutable.
4. Classify source provider/type/scheme/member/date/current-state facts in protected staging/provenance.
5. Resolve a scheme only to a uniquely reviewed existing same-provider tier; otherwise use protected provenance/quarantine pending a representation prerequisite.
6. Consolidate each mapped patient/provider group deterministically; produce at most one target candidate and preserve every source row.
7. Recheck target collision/immutability, unique key, FKs, native enum, same-provider tier, one-primary, date and no-side-effect invariants.
8. Only a later authorized migration-specific persistence layer may insert a candidate. It must not run operational registration, CRUD, cash fallback, verification, merge, visit, billing or claim services.
9. Reconcile protected history/source rows independently from the target current membership.

## Target field disposition guidance

This is target-side guidance, not Classic column mapping:

| Target field/domain | Permitted Phase 2E specification state |
|---|---|
| `patient_id` | Resolved Phase 2C target ID only; no insurance evidence may identify or redirect a patient. |
| `insurance_provider_id` | Unique Phase 2A provider crosswalk result only; no stub/default/fuzzy result. |
| `insurance_tier_id` | Null unless an explicit provider-scoped scheme-to-existing-tier rule is approved and unique. Never accept the controller's default tier silently. |
| `member_type` | Source-evidenced only. Target default `holder` is not authority. If Classic has no family-role fact, require an explicit approved initialization rule or withhold. |
| `card_holder_insurance_id` | Null unless a separately evidenced and approved family relationship exists; no such target evidence authorizes inference in Phase 2E. |
| `membership_number` | Nullable technically; preserve exact display form/leading zeros/punctuation subject to length/encoding rules; protected comparison form only. Never numeric-cast or use for patient identity. |
| `policy_number` | Populate only if a distinct source policy semantic is confirmed; never duplicate membership number for convenience. |
| `ccc_code` | Not a destination for generic source status/member/scheme evidence. Operational verification code only; absent unless directly evidenced and separately approved. |
| `start_date` / `expiry_date` | Valid source dates unchanged; nullable unknown representation is installed. Invalid/reversed/unrepresentable chronology quarantines. |
| `is_active` | Explicit output of pinned current-state selection only; never DB/model default. Date-derived active-looking is not verified eligibility. |
| `is_primary` | False unless a deterministic policy selects exactly one primary across all patient memberships and target snapshot permits it. D-212 alone does not select primary. |
| timestamps | Source-evidenced only through migration-specific persistence/provenance; do not use runtime `now()` as history. |
| verification/eligibility | No target membership verification status exists. Create no event; state is not evidenced/deferred. |
| coverage/limits | Target-owned tier configuration. Never derive from Classic membership/company/payer labels. |

## Target-side blockers and Phase 3 prerequisites

The target permits the Phase 2E specification to proceed, but persistence remains blocked by:

1. protected source-membership history/provenance and crosswalk storage;
2. a patient/provider group consolidation/idempotency ledger;
3. a migration-specific membership persistence boundary with side-effect isolation;
4. explicit `member_type` initialization/unknown policy where Classic has no evidenced holder/beneficiary fact;
5. scheme/plan representation because no dedicated installed target destination exists;
6. pinned current-state/date classifier, including inclusive expiry semantics and timezone;
7. one-primary concurrency-safe validation and same-provider tier/card-holder invariants;
8. protected existing-target collision refresh and immutability token contract;
9. null/not-evidenced verification and eligibility contract (implemented as no operational verification event);
10. HMAC key/domain management, quarantine/release, rollback/resume and separate migration audit;
11. isolation from cash-membership `firstOrCreate`, verification drivers, activity logging, patient merge, visit, billing, claim and usage services.

## Confirmed, inferred and specified conclusions

### Confirmed

- The installed schema enforces one patient/provider row and required patient/provider parents.
- Membership/policy/start/expiry/tier/card-holder are nullable; active defaults true and holder defaults `holder`.
- There is no dedicated scheme/plan or membership-history target structure.
- There is no membership eligibility/verification state; verification is an operational child event.
- Existing target insurance data is nonempty and must be collision-checked.
- Operational services can create cash memberships, default tiers/coverage, verification events, actors/timestamps, visit links and activity logs.
- Insurance member/policy/CCC values are restricted privacy fields and membership numbers are searchable.
- There are no insurance observers or database triggers, but explicit service side effects remain.

### Inferred

- `insurance_tiers` might sometimes correspond to a source scheme, but equality cannot be assumed; a provider-scoped reviewed crosswalk is required.
- Installed nullability makes unknown identifiers/dates representable, but whether a blank identifier allows target creation remains a Phase 2 technical policy outcome, not a schema conclusion.
- The runtime expiry helper likely treats expiry as exclusive and can treat the current date as expired; a pinned migration classifier must not inherit this implicitly.

### Phase 2 technical specification consequences

- Future membership creation must use a migration-specific validated persistence layer, not normal services/controllers.
- Target defaults are not migration authority. `is_active`, `member_type`, tier and primary state require explicit evidence/approved initialization.
- Every Classic row must remain in protected provenance even when many rows consolidate to one current target row or no row.
- Existing target memberships remain immutable; all comparisons are protected and aggregate-only in repository artifacts.
- Eligibility, verification, coverage, claim and financial history are never fabricated.

## Handoff

Target discovery is sufficient for Phase 2E consolidation. The parent specification should cite the refreshed fingerprint and collision counts, model the absent scheme/history destinations as explicit Phase 3 prerequisites, and make all operational-service invocation counts zero. It must not treat this target readiness as importer authorization.
