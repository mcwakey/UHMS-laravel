# Legacy OPD alias contract

Status: Phase 2C specification only. This contract authorizes no alias importer, alias row, review ledger, schema change or database write.

## Authority and evidence

D-201/Q-001 requires each unique, valid Classic OPD number to be preserved as a typed legacy alias. D-211/Q-008 requires every alias in a duplicated source group to be withheld, with protected provenance and one classified exception per affected source patient. Q-002 prohibits merging patients to resolve uniqueness.

Confirmed evidence:

- Source is only `uuhms.patients.OpdNo`, installed as non-null `varchar(15)` under `latin1_swedish_ci`. `OriginalOpd` is blank in the captured baseline and is protected provenance, not a second alias source.
- The source canonical comparison (trim, remove all POSIX whitespace, uppercase) partitions 16,950 rows into 141 blank rows, 15,550 unique nonblank candidates and 1,259 duplicated rows in 275 groups. This equals 16,950 with difference zero.
- The baseline query used `UPPER(TRIM(OpdNo))`; it does not implement the final Unicode/internal-whitespace/collation contract. Its counts are evidence, not commit-time alias counts.
- Punctuation occurs in 16,113 rows, whitespace in five and a leading zero in one; a digits-only grammar or numeric coercion is unsupported by evidence.
- Installed `patient_aliases` requires `patient_id`, `alias_type` (50), `alias_value` (191) and `normalized_alias_value` (120), with global unique key `(alias_type, normalized_alias_value)` under `utf8mb4_unicode_ci`. It has no soft deletes.
- `patient_aliases.source_patient_id` references another target `patients.id`; it is merge-workflow data and must never hold Classic `PAT_ID`.
- `PatientAlias::normalize()` trims/removes whitespace and uses PHP `strtoupper()`, but it is not versioned, does not explicitly normalize NFC or pin Unicode case behavior, and exposes no `legacy_opd` constant. These are implementation prerequisites, not reasons to weaken this contract.

## Alias representation

| Element | Contract |
|---|---|
| Alias type | Literal lowercase `legacy_opd`. Future target code must explicitly support and validate this type before persistence. |
| Source | Exactly `uuhms.patients.OpdNo`. No other Classic column may be substituted or merged into it. |
| Target owner | The successfully mapped target patient ID. Entity mapping must exist first. |
| `source_patient_id` | Null for direct Classic OPD aliases. It is a target-patient merge FK, not provenance storage. |
| Raw representation | Exact source bytes/decoded value only in protected provenance; never repository documentation or ordinary logs. |
| Display `alias_value` | Verified latin1 decode, Unicode NFC, outer Unicode-whitespace trim only. Preserve internal whitespace, case, punctuation and leading zeros. No decoration. |
| Comparison `normalized_alias_value` | Output of the single versioned canonicalizer below; maximum 120 target characters/units as proven against the installed DB behavior. |
| `created_by` | Null unless a later field-semantic Phase 2B rule verifies an actor. Current/importer/admin/first-user and `Legacy Actor Unknown` fallbacks are prohibited. |
| Metadata | Non-PHI rule/provenance references only if target metadata is approved; raw Classic key/OPD must remain in the protected migration ledger. |

## Versioned canonicalization

One pure canonicalizer, for example contract ID `legacy-opd-comparison-v1`, must be used identically for source grouping, target preflight, persistence validation, idempotency and subsequent exact lookup:

1. Decode the Classic field according to its declared latin1 connection semantics. Decode failure is not replacement-character coercion.
2. Normalize decoded text to Unicode NFC.
3. For display, remove Unicode White_Space only at the two ends.
4. For comparison, start from the display value and remove every remaining Unicode White_Space code point.
5. Apply a deterministic Unicode default case fold (or equivalent approved Unicode-aware mapping) with a pinned Unicode/library version, then normalize to NFC again.
6. Preserve every non-whitespace code point, including leading zeros and punctuation. Do not numeric-cast, strip punctuation, insert separators, transliterate, decorate or suffix.
7. Reject decode failure, blank-after-normalization, prohibited control/noncharacter content, overlength display/comparison values, or failure of the separately approved OPD grammar.

The exact Unicode property set, casefold version, length-counting method and MariaDB-collation compatibility tests must be versioned. If extraction, PHP and target collation cannot be proven equivalent, stop; do not fall back to ASCII-only uppercase.

The source evidence does not establish a semantic allowed-character grammar. Therefore the 15,550 canonical unique nonblank rows are candidates only. A versioned Unicode/NFC implementation, privacy-safe target-collision snapshot and final grammar approval are prerequisites to persistence. The evidence specifically prohibits assuming “digits only” and requires punctuation/leading-zero preservation.

## Mutually exclusive source partition

Contract ID: `PATIENT-REC-ALIAS-002`.

Apply this precedence to every Classic patient row:

1. `failed`: extraction, source identity, decode, rule-version or canonicalization execution failed;
2. `blank`: empty after approved Unicode whitespace normalization;
3. `invalid`: nonblank but prohibited content, overlength, unrepresentable collation behavior or approved-grammar failure;
4. `duplicate_withheld`: final comparison value occurs for two or more Classic rows;
5. `target_alias_conflict`: source-unique value is reserved by a target alias and same-owner/same-provenance idempotency is not proven;
6. `target_patient_number_conflict`: source-unique value collides with a different current, soft-deleted, merged or archived target patient-number owner;
7. `alias_not_applicable`: only an explicitly approved scope exclusion;
8. `unique_valid_alias_candidate`: no earlier outcome applies.

Let `C` be Classic patient rows and the terms correspond to the outcomes above:

`C = U + D + B + I + TA + TN + NA + F`

All terms are mutually exclusive, nonnegative integers and the difference must be zero. Duplicate and target-collision flags may be retained secondarily, but cannot double-count the primary partition. Entity quarantine does not erase a unique alias candidate; it makes persistence pending until a valid target owner exists.

For source duplicate groups:

`D = sum(group_size where final normalized group_size > 1)`

and:

- duplicate-alias exceptions = `D` (one per affected source patient);
- aliases created/assigned from duplicate groups = 0;
- decorated or suffixed duplicate aliases = 0;
- automatically selected “best” owners = 0.

## Collision ladder and idempotency

Evaluate target collisions only after complete-source uniqueness under the final rule is known. Query target patients unscoped/with-trashed and aliases through their owners.

1. Same type/normalized value, same mapped target, same protected source token and same normalization/provenance version: idempotent existing alias; zero write.
2. Same target/value but missing or contradictory provenance: withhold for review; proximity is not ownership proof.
3. Same type/normalized value owned by a different target: withhold as target alias conflict.
4. Alias owned by a soft-deleted or merged target: withhold. Do not restore the patient, follow a merge pointer or release ownership.
5. Alias value collides with another target's live, soft-deleted, merged or archived patient number: withhold as target patient-number conflict.
6. Alias equals its own explicitly linked target's patient number: do not create automatically; route to reviewed redundant-alias disposition and preserve provenance without changing the number.
7. Database unique-key failure after successful preflight: classify as race/drift, roll back and stop; never modify the value to win the race.

Because the database has no cross-table uniqueness between patient numbers, archive snapshots and aliases, the future migration boundary must enforce this protected lookup namespace. Case/collation equivalence must be checked in addition to application canonical equality.

## Duplicate release process

Duplicate-withheld aliases do not block otherwise eligible patient entities or their independently valid children. Each source patient stays separate and keeps the original OPD in protected provenance.

Release can occur only after migration through controlled patient identity review:

1. an authorized identity-review owner opens the protected duplicate group using opaque tokens;
2. the reviewer verifies authoritative evidence outside weak name/phone/OPD similarity and records decision, reason, evidence class, approver and time;
3. no ownership decision may merge patients merely to satisfy alias uniqueness;
4. the candidate owner must still be live/unmerged and the entire alias/patient-number namespace must be rechecked under the same rule/configuration versions;
5. all nonowners retain protected source evidence and resolved exception history;
6. alias creation occurs through a later authorized workflow, not by editing this specification or silently changing the source value.

An expired review SLA never grants ownership. Duplicate-alias and target-namespace conflicts are owned by patient identity governance, with five business days as the review objective; privacy, active collision or contradictory identity evidence escalates immediately to the privacy/security and migration technical owners.

## Runtime reconciliation

After patient owners become available, let `U` be `unique_valid_alias_candidate`:

`U = alias_created + alias_already_same_owner_and_provenance + alias_pending_entity_release + alias_commit_failed`

These runtime outcomes are mutually exclusive. Under a frozen target window, `alias_created` equals the run-attributed `patient_aliases` row delta. Without a freeze, use an exact protected run ledger; raw table delta is insufficient.

Also prove:

- every created alias has type `legacy_opd` and one valid mapped owner;
- stored display/comparison values rederive under the recorded rule version;
- aliases whose comparison length exceeds 120 = 0;
- duplicate source aliases assigned = 0;
- blank/invalid/conflicting aliases created = 0;
- aliases with Classic `PAT_ID` in `source_patient_id` = 0;
- aliases attached to a soft-deleted/merged target by migration = 0;
- aliases reassigned or automatically released = 0;
- alias actor fallbacks = 0;
- source, target-collision and database-collation partitions each have difference zero.

## Classified failures and stop conditions

| Code | Use in this contract |
|---|---|
| `LEGACY-PATIENT-ALIAS-003` | Blank/null-equivalent OPD after final normalization. |
| `LEGACY-PATIENT-ALIAS-004` | Every source patient in a duplicate final-normalized OPD group; also any prohibited automatic ownership/decorating attempt. |
| `LEGACY-PATIENT-ALIAS-005` | Invalid, overlength or unrepresentable OPD; decode, grammar, Unicode normalization or collation-equivalence failure. |
| `LEGACY-PATIENT-ALIAS-006` | Target alias ownership conflict, including soft-deleted/merged owner and unresolved same-owner provenance. |
| `LEGACY-PATIENT-ALIAS-007` | Collision with the protected current/soft-deleted/merged/archived patient-number namespace. |
| `LEGACY-PATIENT-PRIVACY-036` | Prohibited Classic-key placement, missing protected provenance/HMAC version, or raw identifier disclosure. |

Stop the affected extraction/run when:

- exact `uuhms`/read-only/source fingerprint or 24-column patient shape is not proven;
- target alias/patient/archive schema, collation, unique key or non-production identity drifts;
- the approved alias type, semantic grammar, versioned canonicalizer or exact final privacy-safe counts are absent;
- target collision snapshot is absent or does not include soft-deleted/merged/archive ownership;
- any leading zero or punctuation changes, Unicode equivalence is inconsistent, length is unsafe, or replacement decoding occurs;
- any duplicate alias is assigned, decorated or awarded automatically;
- same-owner idempotency lacks matching protected source/rule provenance;
- target uniqueness races, partition equations differ or a row receives multiple primary outcomes;
- an alias is released/restored/reassigned automatically, or a target match/merge is inferred from the OPD;
- raw OPD, Classic key or row-level diagnostic enters documentation, logs or source control.

## Privacy and prerequisites

OPDs and crosswalks are protected patient identifiers. Documentation and generated specifications contain schema metadata, aggregate counts and contract IDs only.

- Use domain-separated HMAC-SHA-256 `legacy-opd-comparison-v1` for row-level duplicate/collision grouping and `patient-source-key-v1` for source identity. Never use plain SHA-256 for OPDs.
- Record HMAC domain, algorithm, key version and canonicalization version. Keep keys and row-level tokens outside source control and ordinary logs.
- Do not compare tokens across domains or environments. Missing key/version or unresolved rotation is a stop condition.
- The future crosswalk, alias-review ledger and quarantine ledger are protected operational data and are not implemented by Phase 2C.

Before Phase 3 alias persistence, approve explicit `legacy_opd` support, the Unicode/grammar/version contract, exact privacy-safe source and target collision measurements, protected provenance/review storage, target-state snapshot, release authorization and side-effect-isolated persistence. Until then, all unique nonblank OPDs remain candidates rather than persistence-ready aliases.
