# Phase 2E Classic insurance discovery draft

## Scope and evidence controls

This draft is the Classic-only input to Phase 2E. It covers exactly `uuhms.insurance`, the `patients.PAT_ID`, `patients.Company` and `patients.BillStatus` projection needed by this phase, the exact predicate `insurance.PAT_ID = patients.PAT_ID`, and aggregate comparisons to the Phase 2A `sett_private` provider catalogue. Claims, billing, payments, accounting, visits and operational eligibility are not inspected or mapped here.

The installed `insurance` table and aggregate results were refreshed on 2026-07-21 through the configured `legacy_uhms` connection only after verifying `DATABASE() = 'uuhms'`. Each query bundle ran under MariaDB 10.4.32, `REPEATABLE-READ`, session `TRANSACTION READ ONLY`, used `UTC_DATE() = 2026-07-21` where a temporal coordinate was required, and was rolled back. The broad-privilege discovery account remains prohibited for migration execution under D-101. No Classic or target write occurred.

Repository evidence is aggregate-only. It contains no patient key, member/policy number, company or scheme literal, row-level date, row hash or other identifying value. Safe low-cardinality `InsType`, `BillStatus` and `sett_private.PrivateType` categories are the only source literals reported.

Evidence labels used below are:

- **Confirmed**: installed manifest or refreshed aggregate query evidence.
- **Inferred**: a semantic interpretation supported by aggregates but not declared by Classic constraints.
- **Technical specification candidate**: fail-closed Phase 2 rule proposed for consolidation by the lead agent.
- **Unresolved**: requires target evidence, a protected classifier, or Phase 3 runtime design.

## Installed table contract

**Confirmed:** `insurance` is an InnoDB table with `latin1_swedish_ci` collation, 31,307 rows, nine installed columns, one primary-key index on `INS_ID`, no secondary index, no declared foreign key and no generated/timestamp/update column.

| Ordinal | Column | Installed definition | Primary Phase 2E disposition | Evidence-backed meaning and handling |
|---:|---|---|---|---|
| 1 | `INS_ID` | `int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY` | Protected provenance | Protected source-row identity and stable extraction order only; never a target PK and never a chronology signal. |
| 2 | `PAT_ID` | `int(11) NOT NULL` | Crosswalk | Exact Phase 2C patient-parent lookup input. It cannot identify or redirect a patient by any other insurance evidence. |
| 3 | `InsType` | `varchar(25) NOT NULL` | Crosswalk | Explicit insurance-type/payer/provider-type evidence; mappings must remain separate. |
| 4 | `Scheme` | `varchar(200) NOT NULL DEFAULT '-'` | Scheme crosswalk | Plan/scheme candidate requiring provider-scoped interpretation; blank/default-hyphen and ambiguous text never create a plan. |
| 5 | `MemberNo` | `varchar(15) NOT NULL` | Protected membership identifier | Restricted identifier. Preserve leading zeroes and punctuation; never numeric-cast, publish or use for patient identity. |
| 6 | `Company` | `varchar(25) NOT NULL` | Provider crosswalk | Provider-name candidate only through the Phase 2A crosswalk. Blank/unmatched/ambiguous blocks provider-specific membership release. |
| 7 | `IssueDate` | `date NOT NULL` | Date transform | Field-specific issue-date classifier; zero means unknown/not evidenced, not an invented date. |
| 8 | `ExpiryDate` | `date NOT NULL` | Date transform | Field-specific expiry-date classifier; a future expiry is not automatically invalid. |
| 9 | `Plan` | `varchar(255) NOT NULL` | Protected provenance | Sparse, semantically unresolved coverage/plan evidence. It may flag a `Scheme` conflict but cannot override or create a target plan without an approved target representation. |

The nine columns above are classified exactly once. The installed table contains no explicit status/current flag, verification status/source/time/actor, eligibility result, registration timestamp or modification timestamp. Those facts must not be fabricated.

The Phase 2E patient projection is also classified exactly once:

| Column | Primary disposition | Rule |
|---|---|---|
| `patients.PAT_ID` | Crosswalk | Consume the Phase 2C protected patient identity/crosswalk and snapshot coordinate. |
| `patients.Company` | Evidence-only | Corroborating provider/default-payer evidence or conflict flag only; never creates membership by itself. |
| `patients.BillStatus` | Crosswalk | Explicit payer-category evidence; never a patient-active status and never a provider-specific membership by itself. |

## Row identity and patient relationship

**Confirmed:** `INS_ID` has zero null, nonpositive or duplicate rows. This makes it a stable full-snapshot order, not a temporal watermark.

The refreshed patient relationship partition has zero difference:

`31,307 = 0 null + 0 zero + 30,991 matched nonzero + 316 orphan nonzero`.

**Technical specification candidate:** consume the Phase 2C chain contract without change. A matched but quarantined patient holds every linked insurance row under the existing patient-chain token. Each of the 316 nonzero orphans creates an insurance-rooted quarantine using the protected `PATIENT-PRIV-009` / `legacy-insurance-chain-v1` domain. Neither member number, company, type, scheme, plan nor `BillStatus` may select another patient. There is no approved zero sentinel in this snapshot; a future zero or null is a field-specific invalid/missing parent outcome, not a global zero-is-null rule.

## Insurance-type evidence

**Confirmed:** every `InsType` row belongs to one of two approved safe categories; there are no blank or unapproved values.

| Source category | Rows | Classic semantic class | Technical consequence |
|---|---:|---|---|
| `NHIS` | 15,653 | NHIS coverage-type evidence | May corroborate an NHIS provider/membership candidate; does not itself identify a provider or prove eligibility. |
| `PRIVATE INSURANCE` | 15,654 | Private-insurance coverage-type evidence | May corroborate a compatible Phase 2A provider candidate; does not itself identify a provider or prove eligibility. |

**Technical specification candidate:** define separate crosswalk outputs for provider type, patient-membership type and payer category. Do not reuse one enum mapping where target domains differ, and do not default an unknown future value to either class.

## Provider/company evidence and Phase 2A dependency

The Phase 2A contract remains authoritative: `NK-004` is normalized name plus mapped type with NFC, trim, whitespace collapse and casefold; punctuation remains significant; fuzzy matching is prohibited; automatic match is not allowed. `EXTRACT-SETT_PRIVATE` requires a full ordered `PINS_ID ASC` snapshot/hash because no reliable timestamp exists.

**Confirmed:** the Phase 2A catalogue contains 38 rows, 33 normalized `(PrivateName, PrivateType)` keys, five duplicate groups covering ten rows, no blank name/short/type, and exactly 19 `CORPORATE` plus 19 `PRIVATE INSURANCE` type rows.

The source-name/short-name candidate comparison decomposes as follows. It is deliberately weaker than `NK-004` because it does not prove mapped type compatibility or a unique target crosswalk.

| Candidate outcome for `insurance.Company` | Rows | Normalized distinct values |
|---|---:|---:|
| Blank | 25,042 | not applicable |
| No `sett_private` name/short candidate | 4,017 | 2,403 |
| Exactly one source catalogue candidate | 2,247 | 18 |
| More than one source catalogue candidate | 1 | 1 |
| Total | 31,307 | 2,422 nonblank values |

This refines the Phase 1 existence-match count of 2,248 into 2,247 single-source candidates plus one ambiguous-source candidate. Neither class is an approved target mapping until the Phase 2A protected provider crosswalk, mapped type, target-existing match and crosswalk version all resolve uniquely. The 4,017 unmatched rows must not use `OTHER`, the first/current/most-popular provider, or a fabricated provider stub.

### `patients.Company`

**Confirmed:** 11,652 of 16,950 patients have blank `Company`; the 5,298 nonblank rows contain 1,775 normalized values. Relative to source insurance rows for the same patient:

- 1,193 patients have a nonblank patient company agreeing with at least one insurance company;
- 4,104 have insurance rows but the patient company disagrees with every insurance company;
- one has a nonblank company but no insurance row;
- 10,181 patients have blank company but at least one insurance row.

Against the source `sett_private` name/short candidate predicate, patient company partitions into 11,652 blank, 4,066 unmatched, 1,232 single candidate and zero ambiguous rows.

**Inferred:** `patients.Company` is an unresolved mixture of payer, sponsor/employer, provider and historical text rather than a deterministic membership field. The conflict volume is direct evidence against treating it as current insurer.

**Technical specification candidate:** use it only to corroborate an already-valid patient/provider candidate when normalized company evidence agrees and all Phase 2A provider gates pass. Disagreement adds a conflict flag. It never creates a membership, provider or plan.

## `patients.BillStatus` safe value evidence

All 16,950 rows fall into the six observed classes below.

| Source value | Patients | Proposed payer evidence | Can corroborate an insurance row? | Can establish provider membership? |
|---|---:|---|---|---|
| `CASH AND CARRY` | 2,518 | Self-pay/cash payer category | Only as absence/conflict evidence; it is not an insurer | No; never fabricate a cash provider |
| `NHIS` | 10,587 | NHIS payer-category evidence | Yes, type-level only | No |
| `PRIVATE INSURANCE` | 3,416 | Private-insurance payer-category evidence | Yes, type-level only | No |
| `PRIVATE INSURANCE + NHIS` | 384 | Mixed/multiple-payer evidence | Yes, as a multi-payer flag requiring row evidence | No; do not collapse to one provider |
| `PRIVATE INSURANCE +` | 5 | Malformed/incomplete mixed-payer evidence | No deterministic corroboration | No; classified exception |
| Blank | 40 | Payer not evidenced | No | No |

**Technical specification candidate:** these are explicit Phase 2E payer classes, not patient status or live eligibility. Any fingerprint change or new value is an unknown-category exception under D-205. None can independently create a provider-specific patient-insurance row.

## Member-number evidence

**Confirmed (superseded exact-comparison evidence):** 17,029 rows have blank member number. Under the approved exact case-sensitive, punctuation-preserving comparison there are 1,019 duplicate nonblank groups covering 2,555 rows.

Duplicate diagnostics overlap and must not be added together:

- 1,002 duplicate values cross patients, covering 2,521 rows;
- 255 duplicate values cross source provider/type/company representations, covering 621 rows;
- 14 same-patient/source-provider/member groups cover 28 rows.

The disjoint precedence partition is 17,029 blank + 2,516 cross-patient duplicate + 18 cross-provider/same-patient duplicate + 16 same-patient/provider duplicate + 5 placeholder + 11,723 valid single-scope = 31,307.

Five rows match the approved placeholder-looking classifier; one row reaches the installed 15-character limit. No member number contains a control character in this snapshot. These aggregates do not disclose any identifier.

**Technical specification candidate:** decode Latin1 deliberately, retain protected raw provenance, and use NFC plus outer trim without case folding or numeric conversion. Preserve case, leading zeroes and punctuation. Blank or placeholder-looking values are not invented. Duplicate values are review evidence only and never patient identity or merge evidence. Multiple-number parsing and byte-level decode failures remain protected extraction classifiers; they cannot be safely concluded from aggregate repository evidence.

## Scheme and plan evidence

**Confirmed:** `Scheme` is blank on 21,444 rows, including a distinct count separate from the 1,954 literal default-hyphen rows; 111 normalized substantive scheme values remain after excluding blank and hyphen. `Plan` is blank on 31,299 rows and has only eight nonblank rows across seven normalized substantive values.

The complete row partition is:

`31,307 = 21,443 both blank + 9,856 scheme only + 1 plan only + 1 equal nonblank + 6 different nonblank`.

No scheme or plan value contains a control character; no value reaches its installed maximum length.

**Inferred:** `Scheme` is heterogeneous plan/free-text evidence. `Plan` is too sparse, and six of eight populated rows disagree with populated `Scheme`, so neither may silently overwrite the other.

**Technical specification candidate:** treat blank and the installed `Scheme` default `'-'` as field-specific not-evidenced sentinels. Preserve `Plan` in protected provenance and flag populated disagreements. Do not create a plan master from free text, treat equality as patient identity, or use an unmatched scheme to select a provider. A target scheme/plan destination remains dependent on target discovery.

## Duplicate and consolidation evidence

Normalized source grouping produces:

| Diagnostic | Groups | Rows |
|---|---:|---:|
| Duplicate `(PAT_ID, InsType, Company)` | 37 | 90 |
| Duplicate `(PAT_ID, InsType, Company, Scheme)` | 36 | 88 |
| Complete duplicates across every non-PK installed membership field | 32 | 80 |
| Conflicting variants within the duplicate four-field tuple | 4 | 8 |

The last two rows form an exact partition of the 36 duplicate tuple groups and 88 rows for this snapshot: 32/80 exact duplicate groups/rows plus 4/8 conflicting groups/rows.

**Technical specification candidate:** exact duplicates retain one protected provenance row per `INS_ID`; they do not create duplicate target memberships. Conflicting groups quarantine. A source patient/provider candidate cannot become a target group until the patient crosswalk and Phase 2A provider crosswalk are both unique. `INS_ID` order and greatest expiry are not current-state selection rules.

## Date quality and pinned active-looking coordinate

The only evaluation coordinate used in this discovery is `2026-07-21`, UTC, under the captured read-only snapshot. This is a versioned evidence coordinate, not a later runtime clock.

**Confirmed:** issue/expiry chronology partitions all 31,307 rows with zero difference:

`31,307 = 15,683 both unknown + 1,408 issue unknown/expiry known + 34 issue known/expiry unknown + 14,088 ordered + 28 equal + 66 expiry-before-issue`.

Additional overlapping quality flags are:

- issue zero: 17,091; expiry zero: 15,717;
- nonzero pre-1900 issue: 0; nonzero pre-1900 expiry: 0;
- future issue: 19; future expiry: 54;
- known, ordered, nonblank-company windows active-looking at the pinned coordinate: 36 rows;
- source `(PAT_ID, InsType, Company)` groups with more than one such active-looking row: 0.

The zero active-looking duplicate-group count does not prove a uniquely selectable target membership: provider crosswalk ambiguity, target collisions, unknown dates, scheme/member conflicts and patient quarantine remain separate gates.

**Technical specification candidate:** preserve valid dates unchanged. Zero is unknown/not evidenced only through a supported field-specific target representation. Equal dates are not automatically invalid but require the approved field rule. Expiry-before-issue is quarantined and never swapped. Future expiry is allowed as future coverage evidence. Future issue is a classified exception unless scheduled-coverage semantics are later evidenced. Current/migration/patient/provider/guessed dates are prohibited. Patient-registration chronology is a Phase 2C protected dependency and was not re-queried outside the exact Phase 2E patient projection.

## Text, encoding and length evidence

No control character was found in `InsType`, `Company`, `Scheme`, `MemberNo` or `Plan`. Two company rows and one member-number row reach their installed maximum lengths. This is truncation-risk evidence, not proof that the source was historically truncated. Because Classic text is stored as Latin1, database-level character checks cannot prove original-byte intent; decoding must fail closed in the future protected extractor.

## Relationship and sentinel candidates

| Field/relationship | Exact predicate or classifier | Null/blank/zero/sentinel handling | Blocking outcome |
|---|---|---|---|
| Insurance patient | `insurance.PAT_ID = patients.PAT_ID` | No null/zero observed; do not generalize | Orphan row becomes its own protected insurance chain; quarantined patient holds matched rows |
| Provider | Phase 2A `NK-004` after protected source provider crosswalk | Blank `Company` is provider not evidenced; `OTHER` is never a default | Blank, unmatched, duplicate or ambiguous provider blocks membership release |
| Insurance type | Exact safe category crosswalk | Blank/unknown is not private, NHIS or cash | Unknown blocks membership mapping |
| Scheme | Provider-scoped explicit scheme rule | Blank and literal default hyphen are not evidenced | Preserve provenance; block target plan creation where required |
| Member number | Protected normalized comparison within patient/provider scope | Blank and placeholder-looking are not invented | Nullable target may permit unknown only if target contract and all other gates approve; otherwise hold |
| Issue date | Cast zero-date classifier, then valid date rule | Zero is unknown, never oldest/latest/current | Unsupported unknown or invalid/future issue is held/classified |
| Expiry date | Cast zero-date classifier, then valid date rule | Zero is unknown/open-ended only if target supports it | Reversed/unrepresentable chronology is held; future expiry is not automatically invalid |
| `patients.Company` | Same patient plus exact normalized agreement to an already-valid provider candidate | Blank is not cash and not a provider | Corroboration/conflict only; never establishes membership |
| `patients.BillStatus` | Explicit six-value payer crosswalk | Blank is payer not evidenced; cash is not provider sentinel | Unknown/malformed category is classified; never establishes provider membership |
| Selected current membership/history | Patient crosswalk + provider crosswalk + compatible deterministic facts | No latest-PK/latest-expiry/default rule | At most one current target patient/provider row; every source row retained in protected history |

## Extraction contract input

**Technical specification candidate:**

1. Guard exact connection/database `legacy_uhms`/`uuhms`, D-101 least privilege, database compatibility, the approved 55-table/479-column fingerprint, installed nine-column insurance contract, and session read-only state; fail closed.
2. Extract `insurance` by explicit installed columns in `INS_ID ASC`, initial chunk 2,000, within one verified `REPEATABLE READ`, read-only coordinated snapshot. Use full ordered key/content comparison; `IssueDate` and `ExpiryDate` are business dates only and do not prove changes or deletions.
3. Use domain-separated protected HMAC purposes for row, member, patient/provider group and orphan chain. Retain key/canonicalization versions and typed length-prefixed inputs; emit only aggregates to repository artifacts.
4. Project `PAT_ID`, `Company`, `BillStatus` from the identical immutable Phase 2C `PATIENT-EXT-001` patient snapshot. Do not take a separately timed patient snapshot. This projection follows the same pattern as Phase 2D `PATIENT-CHILD-EXT-001`.
5. Consume the identical Phase 2A `EXTRACT-SETT_PRIVATE` provider snapshot and crosswalk version. A changed 38-row/33-key baseline or provider fingerprint is a stop requiring controlled re-profile.
6. Resume only within an unchanged snapshot using last fully emitted protected `INS_ID`; restart on snapshot identity change. A primary-key high-water mark proves later inserts only, never updates/deletes.
7. Reconcile source row, patient relationship, provider outcome, member-number outcome, both date outcomes, consolidation group and protected provenance partitions independently. Every difference must be zero.

## Confirmed findings, inferences and unresolved work

### Confirmed

- Complete installed nine-column insurance shape, 31,307 rows, primary key only, no FK or timestamp.
- Unique valid source keys and exact 30,991/316 matched/orphan patient split.
- Two complete safe insurance-type categories.
- Provider candidate, patient-company, BillStatus, member, scheme/plan, duplicate, date and text-quality aggregates above.
- All aggregate query bundles completed under exact `uuhms`, session read-only repeatable-read transactions and were rolled back.

### Inferred

- `patients.Company` is a mixed payer/sponsor/provider/historical field and cannot represent deterministic membership.
- `Scheme` is heterogeneous; `Plan` is sparse/conflicting secondary evidence.
- Known-date active-looking coverage is rare and cannot stand in for verified/current eligibility.

### Unresolved / lead-agent dependencies

- Installed target membership nullability/defaults, patient/provider uniqueness, scheme structure, verification/eligibility representation and existing-target collision state.
- Protected target provider IDs and type compatibility from the Phase 2A crosswalk; source candidate matches are not enough.
- Approved target representation for unknown issue/expiry date, blank member number and historical non-selected rows.
- Protected classifiers for multi-number member fields, decode failure and target overlength without exposing raw values.
- Patient-registration date comparison through the coordinated Phase 2C protected runtime.
- Query-manifest IDs/hashes and a committed rerunnable Phase 2E aggregate command belong in the consolidated specification package; this draft introduced no command or executable migration code.

There is no remaining Classic aggregate-query blocker for consolidation. These findings do not authorize an importer, target row, provider stub, eligibility verification, claim/billing processing or database write.
