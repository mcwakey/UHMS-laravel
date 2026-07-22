# Phase 2E reconciliation, extraction, privacy, and dependency analysis draft

## Status and evidence boundary

This is a read-only analytical handoff for lead-agent consolidation. It is not an approved importer, executable query pack, migration-state design, target-persistence design, schema change, eligibility engine, or authorization to write either database. No database was accessed while preparing it. Repository artifacts under `docs/legacy-migration/` were the only data source, and the only approved Classic database referenced here is `uuhms`.

Evidence labels in this draft are strict:

- **Confirmed evidence** means the installed-schema manifest or existing privacy-safe aggregate package records the fact.
- **Approved policy** means `APPROVED_DECISION_SPECIFICATIONS.md`, `DECISIONS.md`, D-205, D-206, D-207, D-212, Q-004, Q-005, Q-009, or Q-301 mandates it.
- **Consumed contract** means an approved Phase 2A-2D contract is referenced without reinterpretation.
- **Phase 2E technical specification** means a deterministic proposed contract for consolidation.
- **Open evidence item** means the consolidated package must remain fail-closed until a privacy-safe measurement or the target specialist closes it.

Claims, claim descendants, billing, invoice, payment, receivable, accounting, insurance price, online verification, provider credentials, and current-visit payer behavior are outside Phase 2E. References to those domains below are dependency or required-zero controls only.

## Confirmed baseline

`uuhms.insurance` has 31,307 rows and exactly nine installed columns: `INS_ID`, `PAT_ID`, `InsType`, `Scheme`, `MemberNo`, `Company`, `IssueDate`, `ExpiryDate`, and `Plan`. `INS_ID` is the sole primary/unique index and is an auto-incrementing non-null `int`; there is no change timestamp or secondary index. `IssueDate` and `ExpiryDate` are business dates, never update watermarks. The table uses `latin1_swedish_ci`.

The existing Phase 2C relationship is authoritative:

`31,307 = 0 null + 0 zero + 30,991 matched nonzero + 316 orphan nonzero + 0 failed`

The repository also confirms 17,029 blank member numbers, 1,019 duplicated normalized nonblank member-number groups covering 2,555 rows, 36 duplicate `(patient, insurance type, company, scheme)` groups covering 88 rows, 17,091 zero issue dates, 15,717 zero expiry dates, 66 expiry-before-issue rows, 19 future issue dates, and 54 future expiry dates. Quality diagnostics can overlap and are not primary reconciliation buckets.

Only the safe categorical values already published in aggregate evidence may appear in source control. No raw `Company`, `Scheme`, `Plan`, `MemberNo`, patient/source key, row date, or target identifier may be emitted.

## Cross-phase contracts consumed without reopening

| Concern | Required contract |
|---|---|
| Provider natural key and duplicates | Phase 2A `NK-004`; punctuation remains significant; 38 `sett_private` rows form 33 normalized `(PrivateName, mapped PrivateType)` keys; automatic match is false |
| Provider extraction and reconciliation | `EXTRACT-SETT_PRIVATE`, `RECON-SETT_PRIVATE`, `VC-001`, `VC-002` |
| Patient parent edge | Phase 2C `PATIENT-REL-003`, `PATIENT-SENT-003`, `PATIENT-REC-REL-003`, `PATIENT-CHAIN-002` |
| Insurance extraction baseline | Phase 2C `PATIENT-EXT-022`, dependent on `PATIENT-EXT-001` and Phase 2A provider evidence |
| Parent/root privacy | `PATIENT-PRIV-001`, `PATIENT-PRIV-002`, `PATIENT-PRIV-007`, `PATIENT-PRIV-009` |
| Existing patient identity | `PATIENT-TARGET-001` through `PATIENT-TARGET-014`, especially immutable `PATIENT-TARGET-009` and `PATIENT-REC-EXISTING-030` |
| Child ordering/privacy | Phase 2D `PATIENT-CHILD-EXT-001`, `PATIENT-CHILD-REL-005`, `PATIENT-CHILD-REC-009`, `PATIENT-CHILD-REC-010` |

The insurance stage may run only after the Phase 2C parent outcome exists. A quarantined patient holds all matched insurance rows under the unchanged `PATIENT-PRIV-007` root. A nonzero orphan is held under `PATIENT-PRIV-009`. Insurance evidence cannot create, identify, merge, redirect, or reassign a patient. Phase 2D alias/contact/demographic outcomes remain independent: an alias withholding or optional child-field exception does not authorize insurance inference, and insurance similarity cannot affect those outcomes.

The Phase 1 provider-name candidate predicate is aggregate evidence, not an approved automatic match. Phase 2E must consume a versioned Phase 2A provider-crosswalk result. It must not independently weaken `NK-004`, introduce punctuation-insensitive/fuzzy matching, create a provider, or select `OTHER`, the first provider, or the most common provider.

## Recommended relationship register

The final `insurance_relationship_rules.json` should use stable IDs and include the complete fields required by the directive. Proposed IDs follow.

| ID | Exact source/target predicate or resolution | Null/blank/zero and ambiguity behavior | Block, chain, reconciliation, extraction |
|---|---|---|---|
| `INSURANCE-REL-001` | `uuhms.insurance.PAT_ID = uuhms.patients.PAT_ID` | Consume `PATIENT-SENT-003`; null or zero is unobserved invalid drift, not benign; orphan nonzero never gets a synthetic parent | Insurance row/subchain; `PATIENT-REC-REL-003`; shared `PATIENT-EXT-001`/`INSURANCE-EXT-001`; protected root required |
| `INSURANCE-REL-002` | `(strict canonical Company, explicit mapped InsType)` resolves through a versioned Phase 2A provider crosswalk output derived from `NK-004`; no direct target search by free text | blank company is missing-provider evidence; no punctuation stripping or fuzzy matching; zero/placeholder text is not an approved provider | Membership blocked when blank, unmatched, ambiguous, duplicate-source unresolved, type-incompatible, or target-conflicting; `INSURANCE-REC-003`; `INSURANCE-EXT-003` |
| `INSURANCE-REL-003` | `insurance.InsType` resolves through the Phase 2E explicit provider-type/membership-type/payer-category crosswalk | null/blank/out-of-domain blocks; each target semantic is mapped separately; no private/NHIS/cash default | Affected row/group; `INSURANCE-REC-008`; same insurance snapshot |
| `INSURANCE-REL-004` | Installed `Scheme` and `Plan` resolve through a provider-scoped plan rule or protected free-text history only; no fabricated plan master | null/blank/source default/placeholder handling is field-specific; ambiguity withholds current representation | Affected row/group; `INSURANCE-REC-009`; same insurance snapshot |
| `INSURANCE-REL-005` | protected patient identity plus unique mapped target provider defines the target uniqueness group | either missing parent/provider prevents group release; one group produces at most one current target membership | Whole patient/provider group; `INSURANCE-REC-007`; protected group token |
| `INSURANCE-REL-006` | `patients.Company` may corroborate only a separately valid insurance provider candidate for the same protected patient and exact provider crosswalk version | blank is absence, not cash; disagreement is a conflict flag; no membership from Company alone | Candidate group only; `INSURANCE-REC-010`; shared patient projection |
| `INSURANCE-REL-007` | `patients.BillStatus` resolves through an explicit payer-category crosswalk | null/blank/unknown are their own outcomes; cash is not a provider sentinel; category never establishes provider membership alone | Corroboration/conflict only; `INSURANCE-REC-011`; shared patient projection |
| `INSURANCE-REL-008` | `PATIENT-PRIV-007` patient-root token attaches matched insurance rows to the Phase 2C hold | missing/version-mismatched token stops; no substitute token or patient lookup | Entire matched insurance subchain; `INSURANCE-REC-012`; shared source bundle |
| `INSURANCE-REL-009` | `PATIENT-PRIV-009` represents each insurance-root orphan chain from the exact nonzero unresolved `PAT_ID` edge | no artificial patient and no reassignment; release only after approved exact parent relationship | Orphan row/subchain; `INSURANCE-REC-012`; orphan HMAC domain |
| `INSURANCE-REL-010` | selected current membership outcome links to every source-row history/provenance outcome in its protected patient/provider group | no source row is dropped or represented as a second current membership | Whole group; `INSURANCE-REC-001`, `INSURANCE-REC-007`; history ledger prerequisite |

Each final relationship record must additionally carry source/target, parent prerequisites, exact predicate, matched/orphan counts where evidenced, exception code, existing-target behavior, privacy class, extraction dependency, and a literal reconciliation equation. Unknown counts remain `null`/`not measured`, never zero.

## Field-specific sentinel registry

No rule below propagates to any other field.

| ID | Field | Required rule |
|---|---|---|
| `INSURANCE-SENT-001` | `insurance.PAT_ID` | Consume `PATIENT-SENT-003`: captured null=0 and zero=0; future null/zero is invalid missing-parent drift, not an approved absence; nonzero orphan creates insurance-root quarantine. |
| `INSURANCE-SENT-002` | `insurance.Company` | Classic NOT NULL. Blank-after-strict-trim means provider missing, not cash/private/NHIS/OTHER. Literal zero, dash, N/A, or placeholder-looking text is not a sentinel unless later sanitized evidence and an explicit rule approve that exact value. |
| `INSURANCE-SENT-003` | `insurance.InsType` | Classic NOT NULL. Blank/unknown is an exception. Only explicitly enumerated safe source categories map; no default. |
| `INSURANCE-SENT-004` | `insurance.Scheme` | Classic NOT NULL with source default `-`. Blank and exact source-default/placeholder candidates are distinguished; neither may create a scheme. Whether `-` means absent is an open evidence item, not a global dash rule. |
| `INSURANCE-SENT-005` | `insurance.Plan` | Classic NOT NULL. Blank/placeholder/opaque text remains distinct protected evidence; no fabricated plan and no reuse of the `Scheme` rule. |
| `INSURANCE-SENT-006` | `insurance.MemberNo` | Blank is unknown member number only, never missing provider. Numeric-looking values remain strings; `0`, repeated digits, punctuation, or placeholder-looking strings are invalid/review candidates, not universal null. Leading zeros and punctuation are preserved. |
| `INSURANCE-SENT-007` | `insurance.IssueDate` | Exact zero/placeholder date is field-specific unknown/not-evidenced only if the final target representation supports it; never replace it. Invalid/future/unrepresentable dates retain distinct exceptions. |
| `INSURANCE-SENT-008` | `insurance.ExpiryDate` | Exact zero/placeholder date is field-specific unknown/not-evidenced only if supported; future is not automatically invalid; reversed chronology is quarantined and never swapped. |
| `INSURANCE-SENT-009` | source current/status | No installed insurance status/current field exists. Absence is not an active, verified, current, or eligible default. Current-state is a versioned classifier outcome only. |
| `INSURANCE-SENT-010` | `patients.Company` | Null/blank means no patient-level company evidence. It is not cash and cannot independently create membership. Other placeholder candidates need explicit evidence. |
| `INSURANCE-SENT-011` | `patients.BillStatus` | Null/blank/unknown are explicit payer-category outcomes. An explicit cash class is payer evidence, never a missing-company or provider sentinel and never an insurance membership. |

## Exception and SLA structure

Every final exception record should include: stable code, category, severity, trigger, source scope, primary disposition, secondary flags, retryability, manual-review flag, owner role, SLA, release condition, reconciliation treatment, blocking scope, chain behavior, privacy classification, and provenance requirement. Use these owner/SLA classes consistently:

- **Migration Technical Lead / immediate stop and containment before rerun**: source key/schema/query/fingerprint/extraction drift, nonzero partition difference, prohibited write/default/actor, or idempotency defect.
- **Patient Identity Lead / before affected parent or insurance chain release**: missing, orphan, invalid, or quarantined patient relationship. Approval requires exact parent evidence; insurance values never establish it.
- **Insurance and Claims Owner / before affected membership group enters the patient pilot**: provider/type/scheme/member/date/current-candidate conflicts. A later SLA breach never creates a fallback.
- **Target Application Architect / before target-collision gate or persistence design approval**: target uniqueness/default/nullability/scheme/verification representation and immutable target conflicts.
- **Privacy and Records Officer / immediate containment before artifact publication or affected-chain release**: raw-value, HMAC, retention, or provenance failure.

Minimum catalogue coverage should allocate stable `LEGACY-INSURANCE-*` codes for all 37 directive categories: source key missing/duplicate; patient missing/quarantined/invalid; provider missing/unmatched/ambiguous/source-duplicate/type-conflict; type missing/unknown; scheme missing/ambiguous; member blank/invalid/duplicate/cross-patient/target-conflict; issue zero/invalid/future; expiry zero/invalid/before-issue; conflicting-current/patient-provider-duplicate; existing-target conflict/prohibited mutation; eligibility not evidenced/prohibited verifier fallback; target uniqueness/default drift; invalid encoding; source fingerprint drift; extraction failure; and privacy/provenance failure.

Recommended extra distinct codes are `PLAN_UNREPRESENTABLE`, `FUTURE_EXPIRY_REVIEW` (informational or current-state classification, not automatically invalid), `CONSOLIDATION_IDEMPOTENCY_FAILURE`, and `RECONCILIATION_DIFFERENCE`. Exact duplicate source rows must not reuse the conflicting-duplicate code. An informational `ELIGIBILITY_NOT_EVIDENCED` classification cannot release an otherwise invalid membership and must never be interpreted as verified eligibility.

An expired SLA never permits a default provider/type/scheme/payer, invented member number, date coercion/swap, row omission, source-to-patient reassignment, target mutation, fabricated verification/eligibility, or unsupported membership release.

## Mutually exclusive reconciliation design

Quality flags may overlap. Each primary partition below uses explicit precedence and exactly one bucket per population member. Every run stores rule/spec/query versions, source and target snapshot identities, count definitions, numerator/denominator, difference, and failure code. The required tolerance is integer zero.

### `INSURANCE-REC-001` source-row outcome

Population: all 31,307 source insurance rows. Apply primary precedence:

1. `failed` (extraction, decoding, privacy, provenance, or unclassifiable pipeline outcome);
2. `excluded` (only an explicit approved scope rule; expected zero for all nine in-scope columns/rows);
3. `existing_target_immutable_evidence`;
4. `patient_orphan`;
5. `patient_quarantined`;
6. `invalid_or_unrepresentable`;
7. `provider_unresolved` (blank, unmatched, ambiguous, type conflict, or target conflict retained as secondary subtype);
8. `conflicting_current_candidate`;
9. `exact_duplicate_source` (nonrepresentative exact duplicates only);
10. `historical_provenance_only`;
11. `current_membership_candidate`.

`31,307 = sum(the eleven buckets)` and difference must be zero. Every row, including exact duplicates, retains one protected history/provenance record. A primary bucket does not erase secondary diagnostics.

### `INSURANCE-REC-002` patient relationship

Precedence: failed, null, approved zero sentinel, orphan nonzero, matched nonzero. The confirmed baseline remains:

`31,307 = 0 + 0 + 30,991 + 316 + 0`

The order displayed in the final equation must match its labelled buckets. Patient quarantine is a separate release state among matched rows and must not be added to this relationship equation.

### `INSURANCE-REC-003` provider outcome

Population: all source insurance rows. Precedence: failed, provider blank, provider unmatched, provider ambiguous, provider target conflict, provider mapped uniquely. Provider-source duplicate and provider-type conflict are secondary diagnostics unless they make resolution ambiguous/unmatched. The aggregate candidate predicate's 2,248 matches and 4,017 nonblank nonmatches are evidence only; they are not a final provider partition until the Phase 2A crosswalk version is bound.

### `INSURANCE-REC-004` member-number outcome

Population: all source insurance rows. Precedence: failed, not applicable (only if an explicitly approved insurance-type rule says so), target conflict, duplicate across patients, duplicate within the same patient/provider, invalid, blank, valid unique in approved provider-scoped namespace. A same-patient value reused across different providers is a secondary cross-provider diagnostic unless target/provider namespace rules make it a target conflict. Never treat a duplicate member number as patient identity evidence. The confirmed blank count is 17,029; the confirmed duplicate diagnostics overlap this future primary partition and must not be summed into it.

### `INSURANCE-REC-005` issue and expiry field outcomes

Create independent issue-date and expiry-date partitions with precedence: failed, unrepresentable, invalid, zero/unknown, future, valid. Future expiry is a valid temporal class for current-state evaluation, not automatically an invalid date. The captured counts are diagnostics pending a frozen validation coordinate.

### `INSURANCE-REC-006` chronology/current-state outcomes

For chronology, use one primary bucket with precedence: failed; unrepresentable/invalid date; both unknown; issue known/expiry unknown; issue unknown/expiry known; expiry before issue; equal known dates; future issue (including both future); future expiry with nonfuture issue; valid ordered nonfuture period. Keep secondary flags for past/future dates without double counting.

Then classify each otherwise representable row at one pinned evaluation coordinate as `chronology_invalid`, `date_unknown`, `future_looking`, `expired_looking`, `active_looking`, or `failed`. Record date, timezone, rule version, and source snapshot identity. Reclassification under a later clock requires a new version and reconciliation; it is not an in-place silent change. `active_looking` does not mean verified or eligible.

### `INSURANCE-REC-007` patient/provider consolidation

Use two coordinated partitions:

1. **Group outcome:** every mapped patient/provider group is exactly one of `one_current_selected`, `history_only`, `conflicting_candidates`, `existing_target_immutable`, `invalid_or_unresolved`, or `failed`.
2. **Rows within each group:** every source row is exactly one of `selected_current_representation` (cardinality 0 or 1), `compatible_history`, `exact_duplicate_nonrepresentative`, `conflicting_candidate`, `invalid`, or `failed`.

For exact-equal fact sets, choose the representative solely to anchor provenance using the stable protected `INS_ID ASC` ordering. That choice is not evidence of currentness. Collapse exact duplicate fact sets before compatibility/current-state analysis. Do not select latest PK, latest expiry, zero-as-oldest/newest, or most complete row. One group produces at most one current target membership, while every row remains in history.

### Supporting field and collision reconciliations

- `INSURANCE-REC-008`: every observed `InsType` row count resolves to exactly one explicit type-crosswalk outcome; safe category counts sum to 31,307.
- `INSURANCE-REC-009`: independent `Scheme` and `Plan` partitions cover every source row as supported target representation, blank/placeholder, protected free text, ambiguous/unrepresentable, invalid, or failed.
- `INSURANCE-REC-010`: `patients.Company` projection covers every Phase 2C patient row as blank, corroborates valid source provider, conflicts, company-without-insurance, insurance-with-blank-company, evidence-only/unrepresentable, or failed under a single precedence. Cross-comparison counts are secondary if categories overlap.
- `INSURANCE-REC-011`: every patient `BillStatus` has one explicit payer-category crosswalk outcome; no category independently creates provider membership.
- `INSURANCE-REC-012`: every matched insurance row retains `PATIENT-PRIV-007`; every orphan retains `PATIENT-PRIV-009`; missing root token, wrong domain/version, cross-chain union, reassignment, or release-before-parent equals zero.
- `INSURANCE-REC-013`: target collision snapshot classifies every candidate/group as no collision, exact prior migration result, existing-target immutable evidence, conflict requiring review, idempotency failure, target-state drift, or failed; target mutation remains zero.
- `INSURANCE-REC-014`: every source row has exactly one protected provenance/history outcome and source rows silently discarded equals zero.
- `INSURANCE-REC-015`: privacy, side effects, and out-of-scope persistence required zeros.

## Required zero assertions

The final machine contract should encode numeric expected value `0` for all of these, not prose booleans:

1. artificial providers created;
2. artificial patients created from insurance;
3. insurance rows reassigned to another patient;
4. duplicate current target memberships per patient/provider;
5. source rows silently discarded;
6. member numbers invented or numeric-cast;
7. dates coerced, guessed, replaced, truncated, or swapped;
8. eligibility fabricated;
9. verification fabricated;
10. current/importer/admin/unknown/migration verifier actors assigned;
11. existing target memberships mutated, deleted, merged, enriched, or primary/current flags changed;
12. claims created;
13. invoices, receivables, payments, allocations, journals, or accounting created;
14. raw member/policy numbers, raw patient/company/scheme/plan values, row dates, identifiers, or row HMACs in repository artifacts;
15. source rows lacking protected provenance;
16. groups producing more than one current target membership;
17. provider/type/payer/scheme convenient defaults;
18. live eligibility/verification service calls or operational side effects.

## Coordinated extraction specification

### `INSURANCE-EXT-001`: Classic insurance snapshot

Consume and refine Phase 2C `PATIENT-EXT-022`, never create a conflicting alternative. Required properties:

| Property | Value |
|---|---|
| Source | exact `uuhms.insurance`; all nine installed columns |
| Stable order | `INS_ID ASC`; raw key remains protected |
| Initial chunk | 2,000 |
| Mode | full ordered snapshot plus protected row/content HMAC |
| Incremental proof | none; `IssueDate`/`ExpiryDate` are business dates only |
| Resume | last fully emitted protected `INS_ID` plus unchanged immutable insurance snapshot identity |
| Update detection | complete key/content comparison |
| Missing/delete detection | complete ordered key-set comparison; absence alone is not deletion evidence |
| Parent dependency | exact coordinated `PATIENT-EXT-001` snapshot and `PATIENT-REL-003` partition |
| Repository output | aggregate counts, contract/query/version/hash metadata only |

The future normalized query is a nine-column `SELECT` from `uuhms.insurance ORDER BY INS_ID ASC`; its exact normalized SQL, query version, and SHA-256 must be frozen by the consolidated package. Any key null/duplicate, shape drift, order instability, snapshot change during resume, or nonzero relationship/row partition difference stops.

### `INSURANCE-EXT-002`: patient projection

Project only `PAT_ID`, `Company`, and `BillStatus` from the exact `PATIENT-EXT-001` row frame. Do not create a separately timed patient extraction. Bind the protected ordered parent-key-set digest, row count, 24-column patient row-content snapshot, source fingerprint, query/tool versions, and Phase 2C parent outcome version. Recommended chunk remains 1,000 because it is the same patient frame. A changed patient snapshot invalidates Company/BillStatus comparisons and the insurance relationship partition.

### `INSURANCE-EXT-003`: provider dependency bundle

Consume the exact versioned `EXTRACT-SETT_PRIVATE`/`NK-004`/`RECON-SETT_PRIVATE` output. Phase 2E must record provider-crosswalk specification version, source snapshot identity, target provider collision snapshot identity, and each lookup outcome. It must not re-run a weaker name/short-name predicate or create an independent provider map. If the provider snapshot changes, every Phase 2E provider/group classification is invalidated.

### `INSURANCE-EXT-004`: target collision snapshot

The target specialist must provide the exact installed structures first. The future strategy is read-only and captures aggregate/protected state for live and soft-deleted patient/provider memberships, duplicate patient/provider state, provider state, member-number collision namespace, plan/scheme representation, verification fields/defaults, unique/FK/index constraints, and target fingerprint. Emit no target patient/member/provider IDs or values. Capture immediately before dry-run and again at any later authorized commit gate; unexplained drift stops. Phase 2E itself performs no target write.

### Coordinated bundle and ordering

Future preflight should establish one verified Classic `REPEATABLE READ`, `READ ONLY` snapshot bundle containing the Phase 2C patient row frame, all insurance rows, and Phase 2A provider dependency evidence, or prove the referenced immutable snapshots share the approved freeze coordinate. Recommended dependency order is:

1. D-101/D-102 connection, exact `uuhms`, 55-table/479-column, database-version, schema/query/tool/key guards;
2. Phase 2A provider snapshot/crosswalk version;
3. Phase 2C patient snapshot and parent outcomes;
4. patient Company/BillStatus projection from that same row frame;
5. insurance full snapshot;
6. patient edge, type/provider/scheme/member/date classifications;
7. consolidation and complete provenance allocation;
8. separately coordinated read-only target collision snapshot;
9. all partition and zero-assertion gates.

A bundle identity should hash nonsecret specification/query/schema metadata plus the protected component snapshot identities. Checkpoints are valid only within the same bundle. Any changed component invalidates all dependent classifications. PK high-water marks can discover possible inserts only; they cannot prove updates or deletions. A final staged-freeze snapshot/full comparison remains mandatory under D-210.

## Privacy and protected provenance contract

Insurance membership is restricted patient-linked financial/coverage data. Repository, prompt, screenshot, fixture, standard log, and test-output prohibitions include raw or reversible member/policy numbers, patient/source/target identifiers, patient names, provider membership details, identifying Company/Scheme/Plan values, row-level dates, raw Classic keys, raw target IDs, row-level hashes/HMACs, and eligibility results.

Permitted repository material is schema metadata, nonsecret schema/query/spec hashes, contract IDs, aggregate counts, approved nonidentifying categorical allow-list values, exception/reconciliation definitions, and required-zero results. Test failures report only file and contract path, never a serialized offending value.

Protected operational storage later must retain source snapshot identity, source table/column/row token, exact decoded source representation where legally/operationally required, canonicalization and rule versions, patient-root/orphan-chain binding, provider-crosswalk version/outcome, class and exceptions, target comparison outcome, reconciliation allocation, and migration-audit identity. It must not simulate operational insurance history, verification, eligibility, or actor activity.

### Domain-separated HMAC purposes

| Contract | Domain | Typed canonical inputs and purpose |
|---|---|---|
| `INSURANCE-PRIV-001` | `patient-insurance-row-v1` | database, table, typed source `INS_ID`, nine-column row content, snapshot/canonicalization version; source-row identity/content |
| `INSURANCE-PRIV-002` | `patient-insurance-member-v1` | decoded member string preserving leading zeros/punctuation, explicit provider namespace discriminator when required, canonicalization version; protected duplicate diagnostics only |
| `INSURANCE-PRIV-003` | `patient-insurance-group-v1` | underlying typed patient source identity plus versioned mapped provider identity and grouping-rule version; idempotency/consolidation group |
| `INSURANCE-PRIV-004` | `patient-insurance-orphan-v1` | typed unresolved patient reference plus typed `INS_ID`, source snapshot/rule version; orphan chain |
| `INSURANCE-PRIV-005` | `patient-insurance-target-comparison-v1` | protected source group inputs and target patient/provider/membership comparison fields; same-environment immutable comparison only |

Use HMAC-SHA-256 with an environment-controlled secret, mandatory key version, purpose/domain, environment, canonicalization version, and a typed length-prefixed UTF-8 message. Recommended field encoding is `field-name length || field-name || type tag || null flag || value byte length || value bytes`; integers use canonical decimal text but identifiers are never numeric-cast from source strings. Strictly decode Classic `latin1`, normalize text to NFC, apply only field-specific trim/case rules, and preserve punctuation/leading zeros where required.

Never embed or compare a token from one domain as if it were a token in another domain; build each token from its approved underlying typed values inside the protected boundary. Never compare across environments, key versions, or canonicalization versions. Key/version loss, mixed versions in one snapshot, or token publication is a blocking privacy/provenance failure. HMAC values never enter source control.

## Targeted test design

Add one filesystem-only Phase 2E specification consistency test; do not query a database or run the broad suite. It should:

1. Parse all 19 required Phase 2E JSON files with throwing JSON decode; require version `2E.*`, phase `2E`, exact source database `uuhms`, `implementation_authorized=false`, and privacy-safe markers.
2. Assert `insurance_column_mappings.json` classifies exactly the nine installed insurance columns once each and classifies only `patients.Company` and `patients.BillStatus` once each outside that table.
3. Assert no claims, claim-child, billing, invoice, receivable, payment, allocation, accounting, pricing, verification-runtime, credential, or current-visit field has a Phase 2E mapping/persistence record. An explicit out-of-scope dependency label is allowed.
4. Resolve every provider dependency to Phase 2A `NK-004`, `EXTRACT-SETT_PRIVATE`, `RECON-SETT_PRIVATE`, and the applicable value-crosswalk version; resolve every patient dependency to `PATIENT-REL-003`, `PATIENT-SENT-003`, `PATIENT-REC-REL-003`, `PATIENT-EXT-001`, `PATIENT-EXT-022`, and root tokens.
5. Assert every relationship has exactly one field-specific sentinel rule and that no sentinel record has a global-propagation flag.
6. Assert every reconciliation partition declares an ordered precedence and unique buckets, an equation, integer tolerance/difference zero, failure bucket, exception, and snapshot/version dependencies.
7. Assert each source row receives exactly one row, patient, provider, member, issue-date, expiry-date, chronology/current-state, and provenance outcome in the contract model; diagnostic flags cannot be primary buckets.
8. Assert consolidation uses the protected patient/provider group, permits `selected_current_representation` cardinality only 0 or 1, and requires one provenance outcome per source row.
9. Assert exact duplicates are separate from conflicting candidates and that stable `INS_ID` order is used only for exact-duplicate representative provenance, never current-state selection.
10. Assert every required zero has numeric expected value 0, including target mutation, actor fallback, fabricated eligibility/verification, default provider/scheme/payer, row loss, and out-of-scope writes.
11. Assert extraction consumes `PATIENT-EXT-022` and the exact `PATIENT-EXT-001` snapshot, orders insurance by `INS_ID ASC`, uses all nine columns, uses full content/key-set comparisons, and treats dates as business dates only.
12. Assert `INSURANCE-EXT-002` projects only `PAT_ID`, `Company`, `BillStatus` from the same patient snapshot and cannot define an independent snapshot time.
13. Assert all HMAC domains are exactly purpose-separated, require key/canonicalization/environment versions and typed length-prefixing, and prohibit row tokens in repository output/cross-domain comparison.
14. Scan Markdown/JSON artifacts for prohibited raw-value properties and positive target-write/importer/runtime configuration. Allow source column names and explicit negative/required-zero assertions. Report path/property only.
15. Cross-reference every relationship, sentinel, exception, reconciliation, extraction, class, privacy, Phase 2A, Phase 2C, and Phase 2D ID; reject dangling or duplicate IDs.

Privacy scanning cannot prove absence of every possible patient value by regex alone. Structural allow-listing is required: Company/Scheme/Plan/member objects may contain only aggregate counts, classes, contract IDs, hashes over nonsecret metadata, and protected-runtime rules; no `source_value`, example, row, token, or date list is permitted. Safe categorical `InsType` and `BillStatus` values must be explicitly allow-listed before publication.

## Phase 3 prerequisites and fail-closed gaps

Record, but do not implement:

- protected patient-insurance crosswalk and source-membership history/provenance store;
- consolidation ledger and patient/provider group idempotency key;
- exact-duplicate fact canonicalizer and deterministic compatible-history/current-state classifier;
- existing-target immutable collision comparator and target fingerprint refresh;
- date validator/evaluation coordinate and unknown-date representation;
- eligibility-not-evidenced and verification-null representation;
- provider-crosswalk runtime that consumes Phase 2A without fuzzy matching;
- scheme/plan target or protected free-text representation;
- target patient/provider uniqueness validator;
- migration-specific patient-insurance persistence with queues, notifications, billing, claim, audit, and verification side effects isolated;
- protected audit/provenance, HMAC key management/rotation, quarantine/release, resume/rollback, and reconciliation storage;
- D-101 least-privilege `SELECT`/metadata account for exact `uuhms` and D-102 fail-closed guards.

Open evidence items that must not be guessed are: final Phase 2E privacy-safe aggregate partitions; exact target member-number nullability/length/namespace; target plan/scheme representation; target current/primary/default/verification fields and database/service invariants; soft-delete and uniqueness behavior; the approved meaning of source `Scheme` versus `Plan`; whether blank member numbers can produce a membership; current-state selection beyond date-derived `active-looking`; and the protected existing-target collision snapshot.

The package must remain fail-closed if any source row lacks exactly one outcome/provenance record, a group can produce more than one current membership, patient/provider identity is unresolved, the coordinated patient snapshot cannot be shared, a target default would fabricate active/eligible/verified state, target history cannot be represented without overwriting a current row, or any raw protected value enters an artifact.

