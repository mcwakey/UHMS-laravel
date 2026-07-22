# Phase 2C discovery draft - patient numbering, alias uniqueness, and reconciliation

Status: read-only discovery handoff. This is not an implementation contract and authorizes no database write.

## Scope and evidence reviewed

This workstream reviewed the complete Phase 2C directive, `AGENTS.md`, the authoritative D-201, D-211, D-213, Q-001, Q-002, Q-006, Q-007 and Q-008 decisions, all source/target machine manifests, the Phase 2A package and machine contracts, the Phase 2B package and machine contracts, and the installed-target patient, alias, merge, archive, privacy and numbering code. All JSON manifests/specifications parsed successfully during this review. No Classic row was queried and no raw patient value was inspected.

Evidence anchors include:

- `uuhms.patients`: 16,950 rows, 24 columns, `PAT_ID` integer auto-increment primary key, no secondary index, `latin1_swedish_ci`.
- The only Classic patient number candidate is `patients.OpdNo` (`varchar(15) NOT NULL`). `PAT_ID` is protected source linkage only and can never be a target ID or patient number.
- Classic has only the combined `patients.PatientName`; there are no installed first-name, last-name or other-name columns.
- Installed target `patients.patient_number` is required and globally unique. `patients` uses soft deletes, so a soft-deleted row continues to reserve its patient number.
- Installed target `patient_aliases` is not soft-deletable and has global uniqueness on `(alias_type, normalized_alias_value)` under `utf8mb4_unicode_ci`.
- `patient_aliases.source_patient_id` is a nullable foreign key to target `patients.id`. It is not a Classic source-ID field and must never receive `uuhms.patients.PAT_ID`.
- Installed target `patient_number_sequences` uniquely keys `(prefix, period_type, period_key)` and stores `last_sequence`.
- The effective local non-production patient-number settings are prefix `UHMS`, pattern `{PREFIX}-{SEQUENCE}/{YEAR}`, sequence width 6, yearly reset, with application time in UTC. Defaults in `config/patient.php` differ from the effective pattern, so the effective runtime configuration, not source-code defaults, must be captured and fingerprinted at preflight.
- `PatientIdGeneratorService` locks an existing sequence row with `lockForUpdate`, but its missing-row insert has a first-allocation race, it advances the sequence separately when called outside a surrounding transaction, and its collision fallback appends a nondeterministic `uniqid()` suffix. Those behaviors are not acceptable unchanged for migration persistence.
- A second numbering implementation remains on `Patient::generatePatientNumber()` via `GeneratesNumbers`; it is date/scanning based and not concurrency safe. It must not be reachable from migration.
- `PatientService::create()` always allocates a fresh number, stamps `Auth::id()`, and may write avatar files. It is not a migration entry point.
- `PatientAlias::normalize()` trims, removes whitespace and applies PHP `strtoupper`; it does not explicitly perform Unicode NFC or Unicode-aware case mapping. The Phase 2C directive requires versioned Unicode-safe comparison behavior.
- The sanitized baseline duplicate query groups nonblank OPDs by `UPPER(TRIM(OpdNo))`: 275 groups / 1,259 rows. It does not remove internal whitespace as the installed alias normalizer does and does not prove target-collation equivalence. These counts are evidence, not final alias-partition counts.

## Confirmed findings, inferences, and unresolved inputs

### Confirmed

1. Renewed patient numbers must be target-generated. Classic `PAT_ID`, `OpdNo`, `OriginalOpd` and every other Classic value are prohibited number sources.
2. An explicit existing-target link reuses the existing target row and existing patient number without modification; it performs no allocation.
3. A successful rerun must resolve its durable source-to-target mapping before any number-generation call.
4. Duplicate OPD is an alias-only blocking condition unless the patient independently fails an entity rule. All members of a duplicate normalized group have their alias withheld and each receives one duplicate-alias exception.
5. Suspected duplicate identity is a review flag, not an entity merge/match/block by itself.
6. The target alias unique key includes soft-deleted patient owners because aliases remain present. There is no automatic ownership release.
7. The target database has no cross-table uniqueness between `patients.patient_number`, archived patient-number snapshots and aliases, even though patient search spans patient numbers and aliases. Migration must enforce that lookup-namespace invariant explicitly.
8. Classic contains no registration actor column. Phase 2B `TARGET-ACTOR-054` requires `patients.registered_by = null` plus protected absence provenance; Legacy Actor Unknown, current user, first user, administrator and importer are prohibited.

### Evidence-backed inference requiring a final technical contract

1. The source-only patient master cannot produce safe first/last-name target components. A nonblank combined name is unrepresentable for automatic first/last mapping, not proof of either component. Protected remediation is required before a new target patient is eligible.
2. The current OPD duplicate aggregate can undercount conflicts under a final normalizer because it trims only outer whitespace, whereas current target normalization removes all whitespace. An exact privacy-safe aggregate using the final canonicalizer is required.
3. Concurrent operational registration cannot be exactly reconciled from `last_sequence` alone. Either registration is frozen during each allocation window or every operational and migration allocation is recorded in one exact allocation ledger.
4. Existing target identity, alias, archived-number and sequence counts/hashes are not present in the installed schema manifest. A sanitized read-only target-state snapshot is required before implementation.

### Unresolved technical inputs

- An approved OPD validity grammar is absent. Until source semantics prove otherwise, leading zeros and punctuation are significant and retained. No numeric coercion, punctuation removal or decoration is allowed.
- A protected name-remediation input capable of supplying independently evidenced first and last names is absent.
- Privacy-safe invalid-phone counts and mutually exclusive DOB-condition counts are absent.
- The future protected source-to-target crosswalk, allocation state, alias-review ledger and quarantine ledger do not exist and are outside Phase 2C implementation scope.
- D-108 (never delete/recreate mapped parents to obtain idempotency) remains proposed. A post-commit reversal policy needs foundation approval; no implementation may assume destructive rollback authority.

## Deterministic patient-entity and alias partitions

Patient entity and OPD alias are two independent primary partitions. Required fields are additional independent per-field partitions. Secondary flags never add another primary patient count.

### Patient entity precedence

Assign exactly one primary entity outcome in this order:

1. `failed_extraction_or_constraint`: row identity cannot be deterministically extracted, source fingerprint/query/checkpoint fails, source PK is missing/duplicated, or a commit-time target invariant fails.
2. `excluded`: only an explicit approved source-scope exclusion; no inferred exclusion is allowed.
3. `explicit_existing_target_link`: exactly one protected pre-approved crosswalk resolves to exactly one live, unmerged target patient, and all crosswalk integrity checks pass.
4. `quarantined_existing_target_ambiguity`: malformed/multiple/conflicting crosswalk entries; missing, soft-deleted or merged target; conflicting verified evidence not explicitly approved; or a reviewed identity determination remains unresolved.
5. `source_remediation_pending`: a controlled remediation case exists and reliable evidence is actively pending. SLA expiry never invents or defaults a value.
6. `quarantined_missing_or_invalid_identity`: no valid existing-target link and one or more required source-backed target fields are missing, invalid or unrepresentable.
7. `eligible_new_patient_candidate`: no existing-target link and every source-backed required field is valid under its versioned rule.

Exact planning equation:

`C = E + N + R + QI + QT + F + X`

where `C` is Classic patient rows; `E` explicit existing-target links; `N` eligible new candidates; `R` source-remediation pending; `QI` missing/invalid/unrepresentable required identity; `QT` existing-target ambiguity/conflict; `F` extraction/constraint failures; and `X` approved exclusions. All terms are nonnegative integers and `C - (E + N + R + QI + QT + F + X) = 0`.

Duplicate OPD, suspected duplicate, name similarity, phone similarity and other review flags are secondary. They do not occur in this sum.

### OPD alias precedence

Use source `uuhms.patients.OpdNo` only and assign exactly one alias outcome in this order:

1. `failed`: extraction, decoding, canonicalization or evidence failure.
2. `blank`: null-equivalent or empty after Unicode whitespace trim.
3. `invalid`: invalid decoding, prohibited control/noncharacter content, unrepresentable length, or failure of the approved OPD grammar.
4. `duplicate_withheld`: two or more Classic rows share the final comparison-normalized value. This takes precedence over target collision categories; target collisions may remain secondary flags.
5. `target_alias_conflict`: unique-in-source value is already reserved by a target alias and same-target/same-provenance idempotency is not proven.
6. `target_patient_number_conflict`: unique-in-source value collides with a different current, archived, merged or soft-deleted target patient number in the lookup namespace.
7. `alias_not_applicable`: only an explicit approved scope exclusion. Entity quarantine alone does not erase a unique alias candidate; it leaves alias persistence pending until a valid target owner exists.
8. `unique_valid_alias_candidate`: no earlier condition applies.

Exact planning equation:

`C = U + D + B + I + TA + TN + NA + AF`

where `U` is unique valid candidates; `D` duplicate-withheld rows; `B` blank; `I` invalid; `TA` target-alias conflicts; `TN` target-patient-number conflicts; `NA` alias not applicable; and `AF` failures. Difference must be zero.

For duplicate groups: `D = sum(group_size for every final-normalized source group with group_size > 1)` and `duplicate_alias_exception_count = D`. `aliases automatically assigned from duplicate groups = 0`; `decorated/suffixed duplicate aliases = 0`.

### Required-field partitions

For each source-backed required target field `f` in `{first_name, last_name, date_of_birth, gender, phone}`:

`C = V_f + M_f + I_f + U_f + F_f`

where `V` valid, `M` missing, `I` invalid, `U` unrepresentable under the approved target contract, and `F` extraction/rule failure. Apply failure, missing, invalid, unrepresentable, valid precedence so intersections cannot double-count.

Current aggregate consequences, without accessing records:

- Combined name blank: 211. Because there are no separate name columns and automatic splitting is prohibited, remaining source-only combined names are not automatically valid first/last components; they require protected remediation.
- Phone blank: 5,050. The valid/invalid nonblank split is not yet evidenced.
- Sex/gender: the deterministic proposed crosswalk is trim plus casefold: `MALE|M -> male`, `FEMALE|F -> female`; blank remains missing and all other values invalid. Current sanitized counts then reconcile as 16,784 mapped, 151 blank and 15 malformed/out-of-domain, totaling 16,950. This transform must be included explicitly in the final crosswalk.
- DOB: zero-date and future counts are zero in the baseline; one pre-1900 condition and 18 DOB-after-registration conditions exist, but overlap was not measured. The final precedence query must produce mutually exclusive counts. Pre-1900 and DOB-after-registration are implausibility/chronology quarantine conditions unless reliable remediation is approved.

Patient number has no Classic source field. Reconcile it separately:

`C = existing_link_with_valid_existing_number + new_candidate_requiring_commit_allocation + entity_not_persistable + number_rule_failure`

No OPD or Classic PK may satisfy the patient-number field.

## Legacy OPD alias canonicalization and uniqueness

### Proposed versioned canonicalizer

Use a single contract version such as `legacy-opd-comparison-v1` for source duplicate grouping, target collision checks, persistence and later search:

1. Read through the declared Classic `latin1` connection and perform a verified, versioned decode to Unicode. Preserve the exact protected source representation separately.
2. Normalize decoded text to Unicode NFC.
3. Trim Unicode whitespace at both ends.
4. Remove all remaining Unicode whitespace for comparison, matching the intended installed target whitespace-insensitive behavior.
5. Apply Unicode-aware uppercase/casefold deterministically.
6. Preserve leading zeros, punctuation and all non-whitespace code points. Do not numeric-cast, strip punctuation, insert separators, suffix duplicates or choose a preferred owner.
7. Reject blank-after-normalization, prohibited control/noncharacter content, decode failure, approved-grammar failure and values exceeding target alias limits (`alias_value` 191, normalized value 120).

Use the literal lowercase alias type `legacy_opd`. The database permits it, but the current `PatientAlias` constants do not define it. A future pure/versioned normalizer and alias-type contract are prerequisites. If Unicode behavior cannot be made identical between extraction, PHP and MariaDB collation, stop; do not fall back to ASCII `strtoupper`.

`alias_value` should be the NFC, outer-trimmed display representation without loss of leading zeros or punctuation. The exact original remains only in protected provenance. `normalized_alias_value` is the comparison form above.

### Collision ladder

Evaluate only after source uniqueness is known:

1. Same normalized alias, same mapped target, same protected source token and same rule version: idempotent existing alias; zero write.
2. Same normalized alias and same target but missing/contradictory provenance: manual review; do not claim ownership automatically.
3. Same normalized alias owned by any different target: withhold and classify target-alias conflict.
4. Alias owned by a soft-deleted or merged target: withhold; no auto-release or following a merge pointer.
5. Value collides with another target's current/soft-deleted/archived patient number: withhold as target-patient-number conflict.
6. Collision with the same explicit target's patient number is not an automatic cross-patient conflict, but still requires a reviewed redundant-alias outcome and protected provenance; do not mutate the patient number.

The target unique key uses `utf8mb4_unicode_ci`, so preflight must test database-collation equivalence as well as application canonical equality. Alias-type comparison is also under that collation. A database unique-key error is a classified race/drift failure, never an instruction to decorate the value.

## Migration-safe patient-number allocation

### Configuration and format

At every dry-run and commit preflight, capture and hash the effective prefix, pattern, sequence width, reset period, application timezone, clock coordinate and supported placeholder set. Validate:

- reset period is one of `never|yearly|monthly|daily`;
- pattern contains `{SEQUENCE}` exactly once and produces an injective value per sequence within the active period;
- every placeholder is supported;
- prefix and period key fit the sequence table;
- the next formatted number fits `patients.patient_number` and the configured sequence width is not overflowed silently;
- the active coordinate `(prefix, period_type, period_key)` does not change during one allocation window.

A migration allocation window must not cross its configured UTC reset boundary. Close/reconcile the window and start a new run coordinate instead. Existing mappings remain unchanged.

### Allocation transaction

For a new eligible source patient, the future migration boundary must perform this order on one target database connection and in one transaction:

1. Lock/read the unique source crosswalk entry by protected source identity.
2. If it already maps successfully, validate and return the mapped patient with no allocation.
3. Create or lock a durable allocation/mapping state with a unique source constraint.
4. Lock the active sequence row. The absent-row path must use duplicate-key retry/upsert-and-lock semantics; the current check-then-insert path is insufficient for first allocation in a period.
5. Compute the next number with the pinned configuration.
6. Check the complete lookup namespace: all `patients` including soft-deleted/merged, archived patient-number snapshots, and patient aliases whose values can resolve through patient search.
7. On any collision, stop/classify and reconcile the sequence; never append a random suffix and never silently skip to an unexplained number.
8. Insert the new patient through migration-specific validated persistence, with nullable `registered_by = null` and no operational side effects.
9. Persist the successful source-to-target mapping, allocated number provenance and migration audit in the same atomic outcome.
10. Commit, then advance the resumable checkpoint. A crash after commit but before checkpoint is safe because the mapping is authoritative on resume.

If the crosswalk cannot share this transaction, an independently reviewed durable reservation/outbox protocol with equivalent exactly-once guarantees is required. Without one, implementation stops.

### Dry-run, resume, and rollback

- Dry-run performs no sequence mutation or reservation. It reports `TARGET_GENERATED_AT_COMMIT` (or an equivalent non-number state), allocation counts and invariant results. A projected number is advisory only and must never be persisted or represented as reserved.
- Resume always consults the durable mapping before allocation. A successful mapping resolves the same target ID and patient number. Changed source content creates a review/update condition; it never creates a replacement patient.
- An incomplete durable reservation may resume only the same reserved number after ownership/invariant validation. It may not allocate a second number.
- If patient insert, mapping or audit fails before commit, the single transaction rolls back. An uncommitted number may be reused because no successful patient/mapping existed.
- After commit, never decrement a sequence, recycle the number, delete/recreate the patient or generate a replacement as error recovery. Post-commit reversal needs a separately approved foundation contract; D-108 is not yet accepted.
- Existing-target links allocate zero numbers and change zero existing fields.

### Concurrency and sequence reconciliation

With operational registration frozen for a window:

`S_after - S_before = M_committed + K_explained`

where `S` is `last_sequence`, `M_committed` is committed migration allocations in that exact sequence coordinate, and `K_explained` is an explicitly recorded count of non-patient consumed sequence values. Under the proposed all-in-one transaction and fail-on-collision policy, expected `K_explained = 0`.

Without a freeze:

`S_after - S_before = M_committed + O_committed + K_explained`

where `O_committed` is exact operational allocations from the same coordinate. The current sequence table has no allocation ledger, so `O_committed` cannot be proven from `last_sequence`; concurrent execution therefore stops unless a unified allocation ledger exists.

For each active coordinate:

- `max(parsed committed sequence component) <= last_sequence`;
- count of sequence rows for the coordinate is exactly one after initialization;
- duplicate generated patient numbers = 0;
- successful newly created source patients with more than one generated number = 0;
- successful reruns generating another/replacement number = 0;
- allocations for existing-target links = 0;
- allocations for quarantined/noneligible source rows = 0;
- Classic PK copied to target ID or patient-number assignment path = 0;
- random collision suffixes used = 0;
- calls to `Patient::generatePatientNumber()` from migration = 0.

`last_sequence > max(parsed number)` is not by itself proof of corruption because prior operational failures can consume values. It must be explained by protected allocation evidence; `last_sequence < max(parsed number)` is sequence underflow and always stops.

## Existing-target collision and immutability contract

Only a protected, pre-approved crosswalk or separately reviewed deterministic identity decision may link a source patient to an existing target. Name, normalized name, OPD, phone, email, DOB, gender, address, national ID alone, fuzzy matching and weighted scoring are prohibited.

For every crosswalk entry:

1. Verify exactly one source identity and exactly one target ID.
2. Query target unscoped/with-trashed.
3. Missing, soft-deleted, merged-away or multiply referenced targets quarantine; do not restore, follow merge pointers or create a replacement.
4. Conflicting verified evidence quarantines unless the protected approval explicitly covers the conflict.
5. A valid link retains target `id`, patient number, identity fields, `registered_by`, timestamps, status, merge state, aliases and children unchanged.
6. Source facts remain protected provenance/flags only. Do not backfill even blank target fields automatically.
7. One source maps to at most one target. One target receives at most one source unless an explicit reviewed identity decision authorizes the otherwise merge-like result; no automatic many-source-to-one collapse.

Before/after protected HMAC field-group comparisons must prove zero migration-caused changes to existing targets. Any scalar, identity, number, alias, registration actor, status, deleted/merge state or child delta stops the run.

Exact assertions:

- `valid explicit links = distinct protected source tokens = distinct approved crosswalk records`;
- `existing target patient rows created by link path = 0`;
- `existing target patient rows updated by link path = 0`;
- `existing target patient numbers changed = 0`;
- `existing target aliases reassigned/released = 0`;
- `automatic existing-target matches = 0`.

## Commit and rerun reconciliation equations

For newly created target patients in a migration run, under an isolated target window:

`successful_new_source_mappings = target_patient_rows_created = target_numbers_allocated = durable_success_mappings`

and:

`successful_new_source_mappings = distinct source_tokens = distinct target_patient_ids = distinct target_patient_numbers`.

If operational target writes are not frozen, replace raw table delta with run-attributed rows from an exact protected ledger; table-count difference alone is insufficient.

For source mapping cardinality:

- `count(source tokens with >1 successful target ID) = 0`;
- `count(new target IDs owned by >1 source token without explicit reviewed decision) = 0`;
- `count(success mappings whose target row is missing, soft-deleted or merged inconsistently) = 0`;
- `count(success mappings whose stored target number differs from the target row) = 0`.

For unique valid OPD candidates after patient owners become available:

`U = alias_created + alias_already_same_owner_and_provenance + alias_pending_entity_release + alias_commit_failed`

All four are mutually exclusive runtime outcomes. `alias_created` must equal the run-attributed target alias-row delta under freeze. Duplicate-withheld, blank, invalid and conflict outcomes create zero aliases.

Registration actor assertions:

- current-user fallback = 0;
- first-user fallback = 0;
- administrator fallback = 0;
- importer fallback = 0;
- Legacy Actor Unknown use = 0;
- for every created patient, `registered_by IS NULL` plus one protected no-source-actor provenance result.

Duplicate-review assertions:

- automatic merges created = 0;
- name-only matches accepted = 0;
- phone-only matches accepted = 0;
- OPD-only matches accepted = 0;
- fuzzy/weighted matches accepted = 0;
- duplicate OPD aliases automatically assigned = 0.

## Privacy-safe provenance

Use domain-separated HMAC-SHA-256 for every row-level or low-entropy patient diagnostic. Plain SHA-256 is allowed for nonsecret schema/query/specification artifacts, not patient values.

Required protected token families:

1. `patient-source-key-v1`: exact typed source identity (`uuhms`, `patients`, `PAT_ID`, canonical base-10 integer).
2. `patient-row-content-v1`: ordered 24-column content with explicit column names/types, null markers and length-prefixed source representations; used for snapshot/update detection.
3. `legacy-opd-comparison-v1`: final normalized OPD; used only for protected duplicate/collision grouping.
4. Separate field domains for normalized name, phone, email/national identifier if later needed. Tokens from different domains must not be comparable.
5. `target-patient-immutability-v1`: canonical target identity/registration/merge field groups for same-environment pre/post comparison.

Every HMAC record requires algorithm, purpose/domain, key version and canonicalization version. The key is environment-controlled, never committed or reported. Cross-environment comparison is prohibited. Key rotation must retain a controlled way to resolve earlier crosswalk entries; losing the applicable key/version is a stop condition. HMAC diagnostics are not a substitute for the protected operational crosswalk.

Canonical messages must use an unambiguous serialization such as length-prefixed UTF-8 fields with explicit type/null markers. Concatenating values with an ambiguous delimiter is prohibited. Reports and repository artifacts contain aggregate counts and contract IDs only, never names, OPDs, phones, emails, national IDs, addresses, raw crosswalk values or row-level HMACs.

## Stop conditions

Stop the affected phase/run on any of the following:

### Evidence and prerequisite stops

- source is not exact database `uuhms`, D-101 least-privilege account is absent, or approved 55-table/479-column fingerprint/shape/version differs;
- `uuhms.patients` schema, key or 24-column contract differs;
- installed non-production target cannot be proven, production target is suspected, or patient/alias/sequence constraints/collations drift;
- effective patient-number configuration is missing, invalid, changed, noninjective or differs after config-cache resolution;
- no protected crosswalk/unique source mapping state exists;
- no atomic mapping/allocation/patient persistence design exists;
- final OPD validity/canonicalization contract or privacy-safe exact aggregate is missing;
- required name-remediation input is absent for a would-be new patient;
- required phone/DOB mutually exclusive validation evidence is missing;
- HMAC key, key version or canonicalization version is unavailable;
- target patient/alias/archive/sequence sanitized preflight snapshot is absent.

### Numbering and concurrency stops

- sequence coordinate count is not zero-or-one before safe initialization or exactly one after;
- first-row initialization cannot safely retry a unique-key race;
- sequence underflow, malformed numbers under the active pattern, unexplained config history or next-number collision is found;
- the normal collision suffix path, static model number generator or unchanged `PatientService::create()` is invoked;
- operational registrations can run concurrently without an exact unified allocation ledger;
- allocation window crosses its period boundary;
- mapping, allocation and target create cannot have one atomic outcome;
- checkpoint claims success but mapping/target/number disagree;
- rerun reaches allocation for an already successful source.

### Identity, alias and immutability stops

- any automatic name/phone/OPD/fuzzy/weighted existing-target match or automatic merge is attempted;
- duplicate OPD alias is assigned, decorated, suffixed or granted to a selected row;
- final source duplicate, target-collision or DB-collation partitions differ from their equations;
- alias normalization changes a leading zero or punctuation, fails Unicode handling, or exceeds target limits;
- Classic `PAT_ID` is written to target `patients.id`, `patient_number` or `patient_aliases.source_patient_id`;
- existing target row/number/alias/registration/status/deleted/merge state changes;
- a mapped target is missing, soft-deleted, merged or multiply/conflictingly referenced;
- required-field or entity partition difference is nonzero;
- raw PHI/identifier or row-level diagnostic enters documentation/logs/source control;
- prohibited runtime side effect or target uniqueness failure occurs.

## Principal risks

1. **Name structure is the dominant patient-entity blocker.** Classic provides one combined name while target creation requires separate first and last names; no safe automatic split exists.
2. **Current number generator is not migration-exact.** Its missing-row race, nondeterministic collision suffix and possible separate commit can violate exact reconciliation/idempotency.
3. **Dual generator drift exists.** The model trait provides a different, nonlocking pattern and must be unreachable.
4. **Alias collision evidence is incomplete.** Baseline duplicate normalization differs from current target whitespace normalization and from the required Unicode contract.
5. **Alias provenance column is misleading.** `source_patient_id` points to a target patient and cannot store the Classic key.
6. **Cross-table lookup uniqueness is application-only.** Patient number, archived snapshots and alias values can collide without a database constraint.
7. **Soft-deleted/merged ownership remains reserved.** Automatic recycling or alias release would corrupt identity history.
8. **Concurrent operational registration is not auditable from the sequence counter.** A freeze or unified ledger is mandatory.
9. **Source change detection is not a simple watermark.** `EditDate` is mutable and cannot detect deletion; resumable work needs full key/content HMAC comparison and overlap/freeze controls.
10. **Existing-target immutability requires evidence not currently captured.** Installed schema evidence contains constraints but no sanitized patient/alias/sequence state snapshot.

## Missing prerequisites before Phase 3 patient persistence

1. Protected source-patient crosswalk/state design with unique source ownership, unique new-target ownership, status/versioning, number reservation and atomic commit semantics.
2. Migration-specific pure patient-number allocator that reuses the configured format/sequence invariant but fixes missing-row concurrency and removes random suffix behavior.
3. Approved operational-registration freeze or unified allocation ledger and exact sequence-window reconciliation.
4. Versioned Unicode/NFC OPD normalizer shared by extraction, collision checks, persistence and search; explicit `legacy_opd` type support.
5. Privacy-safe exact OPD blank/invalid/duplicate/collision measurements under the final rule and target collation.
6. Sanitized target preflight snapshot for patients (including soft-deleted/merged), patient aliases, archived patient numbers and sequence coordinates.
7. Protected, evidenced first/last-name remediation input; absent this, new patient persistence is blocked.
8. Privacy-safe mutually exclusive phone validity and DOB chronology aggregates.
9. Domain-separated HMAC key-management, versioning, rotation and protected-ledger access contract.
10. Resume/checkpoint and post-commit reversal design; destructive delete/recreate or sequence rewind remains unauthorized.
11. Side-effect isolation proving no activity log, notification, file/avatar, child creation, queue, billing, stock or workflow mutation.

## Recommended consolidation decisions

- Keep duplicate OPD and suspected duplicate as secondary flags, never patient-entity primary outcomes.
- Treat all source-only nonblank combined names as unrepresentable first/last components pending protected remediation.
- Pin alias comparison to a versioned Unicode-aware whitespace-removing canonicalizer that preserves leading zeros and punctuation.
- Treat target aliases and all live/soft-deleted/merged/archived patient numbers as a protected lookup namespace for collision checks.
- Require `registered_by = null` plus provenance exactly as Phase 2B specifies.
- Make the crosswalk lookup the first operation on every commit/resume path and allocation part of the same atomic target outcome.
- Fail on numbering collision/config/sequence inconsistency; never use the current random suffix behavior.
- Do not claim Phase 2C persistence readiness until the listed aggregates, remediation input and Phase 3 state/allocator prerequisites exist.
