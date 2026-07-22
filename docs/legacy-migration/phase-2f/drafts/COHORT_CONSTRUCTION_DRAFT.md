# Phase 2F patient-pilot cohort construction draft

Status: read-only discovery draft for lead-agent consolidation. It is not an importer, a cohort execution result, a persistence design, or authorization to write either database.

## Scope and evidence boundary

This draft composes, without changing, the approved Phase 2A reference-parent contracts, Phase 2B registration-attribution and security exclusions, Phase 2C patient identity/alias/remediation/quarantine contracts, Phase 2D demographic/NOK/child contracts, and Phase 2E insurance/provenance contracts. The only Classic schema in scope is exactly `uuhms`. No renewed-target inspection was performed for this workstream.

No fresh row-level source data was read or emitted. The Classic evidence used here is the existing aggregate-only, hash-bound package captured on 2026-07-21 under read-only, repeatable-read transactions that were rolled back. Raw patient keys, names, OPDs, phones, addresses, NOK data, member numbers, row tokens and HMAC values are absent from this document.

Evidence labels used below:

- **Confirmed evidence**: an installed Classic schema fact or sanitized aggregate already recorded in the approved Phase 1/2 evidence package.
- **Inference**: a diagnostic interpretation not sufficient to create, match, merge or repair a patient.
- **Technical specification**: a deterministic Phase 2F contract proposed for consolidation; it is not Classic evidence and does not authorize persistence.
- **Blocker**: a prerequisite that must remain fail-closed before a later commit pilot.

## Authoritative inputs consumed

| Domain | Contracts consumed unchanged | Cohort consequence |
|---|---|---|
| Phase 2A | `NK-004`, `EXTRACT-SETT_PRIVATE`, provider/reconciliation/exception contracts | A source insurance row never creates or guesses a provider. Cohort membership uses only a protected, approved source-provider-to-target-provider crosswalk result. |
| Phase 2B | `TARGET-ACTOR-054`, downstream actor dependencies, security exclusion contract | Patient registration projects `registered_by = null` plus protected absence provenance. Current/importer/admin/first-user and `Legacy Actor Unknown` registrar fallbacks are prohibited. |
| Phase 2C | patient identity classes, `PATIENT-EXT-001`, required-field, alias, duplicate-review, existing-target, chain, privacy and reconciliation contracts | Source identity is rooted only in protected `patients.PAT_ID`; combined names do not prove components; no automatic match/merge; duplicate OPD is alias-only; patient quarantine holds its dependency chain. |
| Phase 2D | `PATIENT-CHILD-EXT-001`, child classes, NOK tuple, optional demographic, existing-target immutability and privacy contracts | Child projection shares the exact patient snapshot. Invalid optional child data withholds that child/field but normally does not invalidate an otherwise valid new-patient candidate. |
| Phase 2E | `INS-EXT-001..004`, provider/type/member/date/consolidation/history/eligibility/immutability contracts | Every selected insurance row remains represented in protected provenance; at most one current candidate per resolved patient/provider; unknown/invalid/conflicting evidence never becomes invented active coverage. |

## Confirmed Classic coverage evidence

### Patient master and required identity

- `uuhms.patients` has 16,950 rows, 24 columns, `PRIMARY(PAT_ID)`, no secondary index and no declared foreign key. The approved schema fingerprint is `150fcf4783fcb8bdc25f7e17fe0ece5050955f0c68e7dd03651ee8bd58498977`.
- `PAT_ID` is unique and non-null at the evidence snapshot. It remains protected source linkage only.
- Classic has one combined `PatientName` field and no separately evidenced first/last-name components. 211 rows are blank; 134 populated rows are single-token; 1,001 normalized-name duplicate groups cover 2,278 rows. None of these aggregates authorizes a split, match or merge.
- The provisional source-only intersection of populated combined name, recognized gender, conservative DOB and preliminary phone shape is 11,732 rows. **This is not a commit-ready count** because every Classic row still lacks independently evidenced first/last components and target collision/state checks.
- Gender partitions exactly as 16,784 recognized + 151 blank + 15 malformed/out-of-domain = 16,950.
- DOB has 16,931 conservative passes; one pre-1900 row and 18 rows after registration are known defect signals. No date is repaired by default.
- Phone partitions exactly as 11,737 target-compatible local shapes + 5,050 blank + 163 invalid nonblank = 16,950.

### Alias, demographic and NOK coverage

- Source OPD classification is 16,950 = 141 blank + 15,550 unique nonblank candidates + 1,259 duplicate-withheld rows in 275 groups. “Candidate” is not runtime-valid until the approved canonicalizer, semantic grammar and target collision snapshot pass.
- NOK presence partitions exactly: complete 14,578; name+phone 213; name+relationship 586; phone+relationship 49; name only 68; phone only 16; relationship only 69; all blank 1,371; difference zero.
- NOK phone has 14,577 conservative valid populated shapes. Validation diagnostics overlap and do not authorize repair.
- `Work` is blank in 6,440 rows and populated in 10,510. `Address` is blank in 982 and populated in 15,968.
- Religion is Christianity 12,438; Muslim 2,749; Other 112; blank 1,651; unrecognized zero.
- Marital status is Single 8,240; Married 6,976; Widow(er) 353; blank 1,381; unrecognized zero.

### Patient relationships and insurance coverage

- Attendance relationship evidence is 51,927 = 0 null + 155 zero + 48,589 matched + 3,183 nonzero orphan. A missing patient relationship roots an attendance-chain quarantine; it never selects another patient.
- Insurance relationship evidence is 31,307 = 0 null + 0 zero + 30,991 matched + 316 orphan.
- `uuhms.insurance` has 31,307 rows and nine columns, with a unique non-null `INS_ID` primary key and no reliable extraction timestamp.
- Insurance type is exactly 15,654 Private Insurance + 15,653 NHIS.
- Company/provider diagnostic is 25,042 blank + 2,247 one Classic provider candidate + one Classic-source ambiguity + 4,017 without a Classic provider candidate. These are not target mappings.
- Member-number primary classification is 17,029 blank + 2,516 cross-patient duplicate + 18 cross-provider/same-patient duplicate + 16 same-patient/provider duplicate + 5 placeholder + 11,723 valid single-scope = 31,307.
- Insurance duplicate diagnostics include 32 exact-complete groups/80 rows and four conflicting tuple groups/eight rows.
- Date chronology is 31,307 = 15,683 both unknown + 34 issue-known/expiry-unknown + 1,408 issue-unknown/expiry-known + 66 reversed + 28 equal-known + 0 future-issue after precedence + 51 future-expiry/nonfuture-issue + 14,037 valid ordered nonfuture.
- Current-state diagnostics are 17,125 date-unknown + 66 chronology-invalid + 0 future-looking + 14,065 expired-looking + 51 active-looking = 31,307. “Active-looking” does not evidence eligibility.

## Eligibility-state vocabulary

These states are mutually distinguishable and must not be collapsed:

| State | Meaning | May Phase 2F assign it? | Later persistence effect |
|---|---|---:|---|
| `MAPPING_CLASSIFIABLE` | The protected source root and its selected fields can be read and assigned deterministic dry-run outcomes. | Yes | None. It is not a creation claim. |
| `REMEDIATION_REQUIRED` | One or more required fields lack valid independent evidence; an approved protected remediation input could resolve them. | Yes | Patient and dependent creation remain blocked. |
| `REMEDIATION_ACCEPTED_CANDIDATE` | A synthetic case, or future protected source record, has a valid approved remediation record under the Phase 2F interface. | Synthetic expectation only; no real record is asserted | Still not commit-ready until all target/foundation gates pass. |
| `FUTURE_COMMIT_CANDIDATE` | Identity, remediation, references, target-state, collision and exception gates all project cleanly at one pinned coordinate. | Synthetic expectation only | Eligible to be reconsidered by a later implementation preflight. |
| `FUTURE_COMMIT_READY` | Phase 3 foundation exists; fresh source/target snapshots and all fail-closed preflight, isolation, allocator and storage checks pass. | **No** | Only this future state can enter commit mode. |
| `EXISTING_TARGET_LINK_CANDIDATE` | A protected approved crosswalk points uniquely to a live, unmerged target and all immutable-state checks agree. | Dry-run classification only | Future link/mapping transaction only; zero existing-target mutation. |
| `QUARANTINED` | A root identity, relationship, collision, required field, conflict or provenance condition is unresolved. | Yes | Zero patient/alias/child/insurance creation; chain remains attached to its root. |
| `RUN_STOPPED` | Environment, fingerprint, privacy, key, query, side-effect or invariant failure invalidates the run. | Yes | No candidate is reported as successful. |

**Confirmed evidence:** no aggregate proves any unremediated Classic patient is commit-ready. **Technical specification:** Cohort B can measure mapping classification and remediation demand, but its `FUTURE_COMMIT_READY` count must be zero throughout Phase 2F.

## Cohort A — synthetic/anonymised contract fixtures

All fixture values must be generated independently from Classic data and carry the literal fixture namespace `SYNTHETIC-ONLY-P2F`. Repository scenarios use only symbolic classes such as `SYNTH_FIRST_TOKEN`, `SYNTH_LAST_TOKEN`, `SYNTH_OPD_UNIQUE_TOKEN`, `SYNTH_MEMBER_TOKEN`, `SYNTH_PHONE_VALID_TOKEN` and `SYNTH_ADDRESS_TOKEN`; concrete fixture values belong only in a later isolated test-fixture implementation. Synthetic execution must be blocked from notification, SMS, email and external verification. These tokens are not anonymised copies or perturbations of source values.

Outcome notation used in the matrix:

- Patient: `P+` = future new-patient candidate, conditional on all Phase 3 gates; `P=` = existing-target protected link with immutable target; `P!` = patient/root quarantine; `P0` = no valid patient parent; `STOP` = whole run fails closed.
- Alias: `A+` = one future `legacy_opd` candidate; `A0` = no alias by classified absence/withholding; `A!` = collision or parent hold; `A=` = preserve existing aliases, no mutation.
- Child: `C+` = approved inline demographic/contact projection; `C0` = blank/invalid optional child withheld; `C!` = parent-held; `C=` = existing target children immutable.
- Insurance: `I+` = at most one future membership candidate for the patient/provider group plus all rows in protected history; `IH` = history/provenance only; `I!` = insurance/group quarantine; `I=` = existing target membership immutable; `I0` = no source insurance.
- Provenance: `Z+` = protected root, field decisions and source-row history are present with domain/key/canonicalization versions; `Z!` = provenance/privacy invalid, so no success outcome.
- Reconciliation: `R0` = every applicable partition difference and prohibited-side-effect count is exactly zero; expected classified exceptions may be nonzero and are explicitly named. `RF` = an unexplained difference or safety-zero breach stops the run.

`I+` always includes `LEGACY-INSURANCE-ELIGIBILITY-028` as a classified “eligibility not evidenced” absence and creates zero verification rows. The notation describes expected dry-run projections, never writes.

| ID | Synthetic scenario/input distinction | Patient | Alias | Child | Insurance | Exact expected exception/provenance/reconciliation outcome |
|---|---|---|---|---|---|---|
| A-001 | Complete remediated new-patient fixture, no OPD or insurance | `P+` | `A0` | `C+` | `I0` | `LEGACY-PATIENT-ALIAS-003`; `Z+`; `R0`. |
| A-002 | Complete fixture with symbolic unique valid `SYNTH_OPD_UNIQUE_TOKEN` | `P+` | `A+` | `C+` | `I0` | No alias exception; `Z+`; `R0`, exactly one alias candidate. |
| A-003 | Two separate fixture patients share `SYN-DUP-OPD-01` | `P+` each | `A0` each | independently classified | independently classified | `LEGACY-PATIENT-ALIAS-004` for both; no merge; `Z+`; `R0`. |
| A-004 | Blank OPD | `P+` | `A0` | independently classified | independently classified | `LEGACY-PATIENT-ALIAS-003`; `Z+`; `R0`. |
| A-005 | Invalid/control/overlength synthetic OPD | `P+` | `A0` | independently classified | independently classified | `LEGACY-PATIENT-ALIAS-005`; `Z+`; `R0`. |
| A-006 | Unique source OPD collides with protected target alias namespace | `P+` or `P=` per prior identity branch | `A!` | independent | independent | `LEGACY-PATIENT-ALIAS-006`; zero reassignment; `Z+`; `R0`. |
| A-007 | Future allocator proposes a patient number already reserved | `P!` | `A!` | `C!` | `I!` | `LEGACY-PATIENT-NUMBER-037`; no alternate number selected in same attempt; `Z+`; `R0`. |
| A-008 | Combined name blank | `P!` | `A!` | `C!` | `I!` | `LEGACY-PATIENT-NAME-008`, `-009`, `-010`, `LEGACY-PATIENT-REMEDIATION-040`, chain hold; `Z+`; `R0`. |
| A-009 | Single-token/ambiguous combined name | `P!` | `A!` | `C!` | `I!` | `LEGACY-PATIENT-NAME-009`, `-010`, `-011`, `LEGACY-PATIENT-REMEDIATION-040`; `Z+`; `R0`. |
| A-010 | Independently evidenced and approved `SYNTH-FIRST-010`/`SYNTH-LAST-010` remediation | `P+` | independent | independent | independent | Remediation consumed under its version; no copying/order guess; source combined-name provenance retained; `Z+`; `R0`. |
| A-011 | Missing DOB | `P!` | `A!` | `C!` | `I!` | `LEGACY-PATIENT-DOB-013` or `-016` per representation, `LEGACY-PATIENT-REMEDIATION-040`; `Z+`; `R0`. |
| A-012 | Pre-1900/implausible synthetic DOB | `P!` | `A!` | `C!` | `I!` | `LEGACY-PATIENT-DOB-014`, remediation pending; `Z+`; `R0`. |
| A-013 | DOB after synthetic registration date | `P!` | `A!` | `C!` | `I!` | `LEGACY-PATIENT-DOB-015`, remediation pending; `Z+`; `R0`. |
| A-014 | Blank gender | `P!` | `A!` | `C!` | `I!` | `LEGACY-PATIENT-GENDER-017`, remediation pending; `Z+`; `R0`. |
| A-015 | Out-of-domain synthetic gender | `P!` | `A!` | `C!` | `I!` | `LEGACY-PATIENT-GENDER-018`, no default; `Z+`; `R0`. |
| A-016 | Blank phone | `P!` | `A!` | `C!` | `I!` | `LEGACY-PATIENT-PHONE-019`, remediation pending; `Z+`; `R0`. |
| A-017 | Invalid synthetic phone | `P!` | `A!` | `C!` | `I!` | `LEGACY-PATIENT-PHONE-020`, no repair from NOK; `Z+`; `R0`. |
| A-018 | Complete valid synthetic NOK tuple | `P+` | independent | `C+` one emergency-contact candidate | independent | No NOK exception; one non-primary contact candidate under Phase 2D; `Z+`; `R0`. |
| A-019 | Partial NOK tuple | `P+` | independent | `C0` | independent | Exact applicable `LEGACY-PATIENT-CHILD-CONTACT-002/005/008/011`; patient remains eligible; `Z+`; `R0`. |
| A-020 | Entire NOK tuple blank | `P+` | independent | `C0` | independent | `LEGACY-PATIENT-CHILD-CONTACT-001`; no contact; `Z+`; `R0`. |
| A-021 | Complete tuple except invalid NOK phone | `P+` | independent | `C0` | independent | `LEGACY-PATIENT-CHILD-CONTACT-006`; no phone repair or partial contact; `Z+`; `R0`. |
| A-022 | Valid synthetic occupation | `P+` | independent | `C+` inline occupation | independent | No occupation exception; `Z+`; `R0`. |
| A-023 | Blank occupation | `P+` | independent | `C0` for occupation only | independent | `LEGACY-PATIENT-CHILD-OCC-015`; `Z+`; `R0`. |
| A-024 | Valid unstructured synthetic address | `P+` | independent | `C+` inline address only | independent | No inferred city/region/postcode; `Z+`; `R0`. |
| A-025 | Blank address | `P+` | independent | `C0` for address only | independent | `LEGACY-PATIENT-CHILD-ADDR-018`; `Z+`; `R0`. |
| A-026 | Religion `CHRISTIANITY` | `P+` | independent | `C+` approved religion value | independent | Explicit Phase 2D crosswalk; `Z+`; `R0`. |
| A-027 | Religion `MUSLIM` | `P+` | independent | `C+` approved religion value | independent | Explicit Phase 2D crosswalk; `Z+`; `R0`. |
| A-028 | Religion `OTHER` | `P+` | independent | `C+` approved religion value | independent | Explicit Phase 2D crosswalk; `Z+`; `R0`. |
| A-029 | Religion blank | `P+` | independent | `C0` for religion only | independent | `LEGACY-PATIENT-CHILD-RELIGION-021`; `Z+`; `R0`. |
| A-030 | Marital status `SINGLE` | `P+` | independent | `C+` mapped `single` | independent | Explicit Phase 2D crosswalk; `Z+`; `R0`. |
| A-031 | Marital status `MARRIED` | `P+` | independent | `C+` mapped `married` | independent | Explicit Phase 2D crosswalk; `Z+`; `R0`. |
| A-032 | Marital status `WIDOW(ER)` | `P+` | independent | `C+` mapped `widowed` | independent | Explicit Phase 2D crosswalk; `Z+`; `R0`. |
| A-033 | Marital status blank | `P+` | independent | `C0` for marital status only | independent | `LEGACY-PATIENT-CHILD-MARITAL-025`; `Z+`; `R0`. |
| A-034 | Approved suspected-duplicate signal | `P+` as a separate patient | independent | independent | independent | Secondary `LEGACY-PATIENT-DUPLICATE-027`; zero automatic match/merge; `Z+`; `R0`. |
| A-035 | Unique approved existing-target crosswalk to live/unmerged target | `P=` | independently apply alias rule only | `C=` | `I=` comparison only | `PATIENT-REC-EXISTING-030`; zero target mutation; `Z+`; `R0`. |
| A-036 | Multiple/malformed existing-target candidates | `P!` | `A!` | `C!` | `I!` | `LEGACY-PATIENT-TARGET-023`; no fall-through creation; `Z+`; `R0`. |
| A-037 | Approved target is soft-deleted | `P!` | `A!` | `C!` | `I!` | `LEGACY-PATIENT-TARGET-026`; no restore/replacement; `Z+`; `R0`. |
| A-038 | Approved target is merged/redirected | `P!` | `A!` | `C!` | `I!` | `LEGACY-PATIENT-TARGET-026`; no merge-pointer following; `Z+`; `R0`. |
| A-039 | Required patient identity unresolved | `P!` | `A!` | `C!` | `I!` | `LEGACY-PATIENT-CHAIN-030` plus root cause; all dependants retained under one chain token; `Z+`; `R0`. |
| A-040 | Attendance references no valid patient | `P0` | `A0` | `C0` | `I0` | `LEGACY-PATIENT-RELATIONSHIP-029`, `-CHAIN-030`; entire attendance descendant chain quarantined; `Z+`; `R0`. |
| A-041 | Valid patient with zero insurance rows | `P+` | independent | independent | `I0` | Insurance source count zero is a successful classified outcome, not self-pay invention; `Z+`; `R0`. |
| A-042 | Insurance row references no Classic patient | `P0` for that insurance root | `A0` | `C0` | `I!` | `LEGACY-INSURANCE-PATIENT-005`; protected insurance-chain history only; `Z+`; `R0`. |
| A-043 | Nonblank source provider has no approved provider crosswalk | `P+` | independent | independent | `I!`/`IH` | `LEGACY-INSURANCE-PROVIDER-007` or `-039`; no provider/tier creation; every row in history; `Z+`; `R0`. |
| A-044 | Provider projection has multiple candidates/type conflict | `P+` | independent | independent | `I!`/`IH` | `LEGACY-INSURANCE-PROVIDER-008/009`; group withheld; `Z+`; `R0`. |
| A-045 | `PRIVATE INSURANCE` with unique approved provider crosswalk | `P+` | independent | independent | `I+` | Explicit type crosswalk; `LEGACY-INSURANCE-ELIGIBILITY-028`; `Z+`; `R0`. |
| A-046 | `NHIS` with unique approved provider crosswalk | `P+` | independent | independent | `I+` | Explicit type crosswalk; `LEGACY-INSURANCE-ELIGIBILITY-028`; `Z+`; `R0`. |
| A-047 | Blank member number, otherwise deterministic group | `P+` | independent | independent | `I+` only if approved nullable initialization; otherwise `IH` | `LEGACY-INSURANCE-MEMBER-015` and `-ELIGIBILITY-028`; member remains null, never invented; `Z+`; `R0`. |
| A-048 | Duplicate member within same patient/provider | `P+` | independent | independent | `I!`/`IH` | `LEGACY-INSURANCE-MEMBER-017`; no arbitrary winner; all rows retained; `Z+`; `R0`. |
| A-049 | Same member value across different patients | patients remain separate | independent | independent | `I!`/`IH` for affected groups | `LEGACY-INSURANCE-MEMBER-018`; never a patient match/merge; `Z+`; `R0`. |
| A-050 | Both insurance dates unknown | `P+` | independent | independent | `IH`, no current membership | `LEGACY-INSURANCE-DATE-020/023`, `-ELIGIBILITY-028`; null dates in history; `Z+`; `R0`. |
| A-051 | Valid expired historical insurance | `P+` | independent | independent | `IH` | Dates preserved; `LEGACY-INSURANCE-ELIGIBILITY-028`; no active/current claim; `Z+`; `R0`. |
| A-052 | Valid active-looking date window | `P+` | independent | independent | `I+` only after all non-date gates | `LEGACY-INSURANCE-ELIGIBILITY-028`; active-looking is not verified eligibility; `Z+`; `R0`. |
| A-053 | Future issue date | `P+` | independent | independent | `I!`/`IH` | `LEGACY-INSURANCE-DATE-022`; no current candidate; raw fact retained; `Z+`; `R0`. |
| A-054 | Nonfuture issue and future expiry | `P+` | independent | independent | `I+` only after all other gates | Preserve dates; `LEGACY-INSURANCE-ELIGIBILITY-028`; no date coercion; `Z+`; `R0`. |
| A-055 | Expiry before issue | `P+` | independent | independent | `I!`/`IH` | `LEGACY-INSURANCE-DATE-025`; never swap dates; `Z+`; `R0`. |
| A-056 | Exact duplicate insurance source rows | `P+` | independent | independent | At most one `I+`; every row in history | Exact duplicate classification and `INS-CONS-*`; no row discarded; `Z+`; row/group `R0`. |
| A-057 | Conflicting current candidates in one patient/provider group | `P+` | independent | independent | `I!`/`IH` | `LEGACY-INSURANCE-CONSOLIDATION-026/027`; no arbitrary winner; `Z+`; `R0`. |
| A-058 | Existing target patient/provider membership differs or collides | `P=` or `P+` per prior branch | independent | existing patient immutable | `I=` | `LEGACY-INSURANCE-TARGET-030/032/040`; zero target membership mutation; `Z+`; `R0`. |
| A-059 | All membership facts present but eligibility unverified | `P+` | independent | independent | `I+` without verification | `LEGACY-INSURANCE-ELIGIBILITY-028`; verification rows/actors/statuses = 0; `Z+`; `R0`. |
| A-060 | Attempt to use current/importer/admin/unknown registrar or verifier | `STOP` | none | none | none | `LEGACY-PATIENT-ACTOR-032` and/or `LEGACY-INSURANCE-ELIGIBILITY-029`; `Z+`; `RF`, zero writes. |
| A-061 | Raw identifier/token leakage or missing provenance/key version | `STOP` | none | none | none | `LEGACY-PATIENT-PRIVACY-036`, child `-PRIVACY-034/035/041`, insurance `-PRIVACY-036/037`; `Z!`; containment/rotation required; `RF`. |
| A-062 | Source fingerprint/shape differs from pinned `uuhms` coordinate | `STOP` | none | none | none | `LEGACY-PATIENT-DRIFT-033`, child `-SOURCE-032`, insurance `-SOURCE-034`; `Z+`; `RF`, zero writes. |
| A-063 | Target fingerprint/constraint/collision snapshot drifts | `STOP` | none | none | none | `LEGACY-PATIENT-TARGET-035`, child `-TARGET-031`, insurance `-TARGET-031`; `Z+`; `RF`, zero writes. |
| A-064 | Exact rerun with same contract/snapshot/idempotency inputs | Same prior outcome | no duplicate alias | no duplicate child | no duplicate membership/history | Same protected lineage; created-delta projection zero on rerun; `Z+`; `R0`. |
| A-065 | Resume after interruption at a completed root/stage boundary | Previously complete outcomes unchanged | resume next incomplete stage | same | same | Same snapshot/manifest/contract versions; no skipped or duplicated root; `Z+`; `R0`. |
| A-066 | Future child-stage failure after patient-core success | Patient core remains only if its atomic unit committed; otherwise none | later stages not advanced | failed child unit rolled back/compensated | not advanced | Classified unit failure, checkpoint remains before failed unit, no universal patient delete or sequence rewind; `Z+`; `R0` after compensation. |

The consolidated Phase 2F package should generate machine-readable fixture rows for all 66 IDs. Every row must expand “independent” outcomes into explicit empty/success/withheld values, list the exact applicable reconciliation IDs, and assert source writes, target writes and prohibited side effects equal zero during Phase 2F.

## Cohort B — protected source-derived dry-run cohort

### Selection units

Two protected root types are allowed:

1. `PATIENT_ROOT`: one exact `uuhms.patients.PAT_ID`, represented only by the Phase 2C `patient-source-key-v1` token. All same-row patient/child projections and all exact-key matched insurance rows share that root.
2. `ORPHAN_CHAIN_ROOT`: one attendance or insurance source key whose patient predicate is missing/sentinel/orphan under the authoritative field rule, represented only by its domain-separated attendance- or insurance-chain token. It never becomes a patient root.

Raw keys and the rank HMAC are operational-only. Repository outputs contain stratum IDs, quotas, selected aggregate counts, underfill counts and nonsecret contract hashes.

### Deterministic selection algorithm

Technical specification `PILOT-COHORT-SELECTION-V1`:

1. Fail unless the connection is exactly `legacy_uhms`, `DATABASE()` is exact `uuhms`, the transaction is `READ ONLY`, the approved database/version/55-table/479-column/fingerprint guards pass, and the D-101 account restriction is proven.
2. Pin one immutable source snapshot identity and the exact Phase 2A–2F contract bundle hashes. The patient projection and every relationship/insurance projection must use this same coordinate.
3. Derive protected root tokens with HMAC-SHA-256, domain `phase2f-patient-root-v1`, pinned key version, and canonical payload containing explicit schema/table/key-type/null markers. Derive orphan-chain roots with their Phase 2C domains. Never rank a name, phone, OPD or member number.
4. For each stratum in the precedence table below, calculate eligibility from approved normative rule IDs. A diagnostic may not silently become a mapping rule.
5. Exclude any root already assigned to an earlier stratum. This makes strata mutually exclusive for quota arithmetic while retaining all secondary coverage flags in the protected manifest.
6. Rank remaining roots by `HMAC-SHA-256(K_selection_version, length-prefix("phase2f-cohort-rank-v1", source_snapshot_id, stratum_id, protected_root_token))`, then by protected root token as a deterministic tie-breaker. No random seed, SQL `RAND()`, sampling clock or operator choice is allowed.
7. Select the first `min(quota, available-unassigned)` roots. Do not silently refill from a different stratum. Record underfill and its evidence gap.
8. Store row-level selection only in the protected manifest. Publish aggregates and nonsecret hashes only.

Exact bounded size formula:

`N_B = sum over ordered strata s of min(q_s, A_s_after_prior_assignment)`

where each `A_s_after_prior_assignment` is measured under the pinned source snapshot. The declared maximum is `Q_B = 148`, so `0 <= N_B <= 148`. The exact `N_B`, per-stratum selected counts and underfill must be frozen in the protected manifest before dry-run classification. A missing required stratum is a coverage failure, not permission to select an unclassified substitute.

### Ordered strata and quotas

| Order / ID | Unit and protected predicate owner | Quota | Evidence/capacity status | Expected readiness class |
|---:|---|---:|---|---|
| 1 / B-INS-CONFLICT | Patient roots supporting Phase 2E conflicting patient/provider tuple groups | 4 | Confirmed four groups/eight rows; distinct patient-root capacity is not published | `QUARANTINED` for membership; patient classified independently |
| 2 / B-INS-EXACT-DUP | Patient roots supporting exact-complete insurance duplicate groups | 6 | Confirmed 32 groups/80 rows; distinct roots not published | Membership consolidation dry-run; never discard history |
| 3 / B-INS-ORPHAN | Insurance orphan-chain roots | 6 | Confirmed 316 rows | `QUARANTINED`, no patient candidate |
| 4 / B-ATT-ORPHAN | Attendance zero/orphan-chain roots | 6 | Confirmed 155 zero + 3,183 nonzero orphan rows; sentinel approval is field-specific | `QUARANTINED`, no patient candidate |
| 5 / B-ALIAS-DUP | Patient roots in duplicate final-normalized OPD groups | 8 | Confirmed 1,259 rows/275 groups | Patient class independent; alias withheld |
| 6 / B-NAME-BLANK | Patient roots with blank combined name | 6 | Confirmed 211 | `REMEDIATION_REQUIRED` |
| 7 / B-NAME-AMBIG | Populated single-token/otherwise unresolved name-separation candidates | 6 | Confirmed 134 single-token; all source-only rows still need independent components | `REMEDIATION_REQUIRED` |
| 8 / B-DOB-INVALID | Patient roots failing conservative DOB rules | 6 | Confirmed at least 19 defect rows; overlap must be measured | `REMEDIATION_REQUIRED` |
| 9 / B-GENDER-INVALID | Patient roots with missing or out-of-domain gender | 6 | Confirmed 166 rows | `REMEDIATION_REQUIRED` |
| 10 / B-PHONE-INVALID | Patient roots with blank or invalid phone | 6 | Confirmed 5,213 rows | `REMEDIATION_REQUIRED` |
| 11 / B-DUP-REVIEW | Patient roots carrying approved suspected-duplicate review signals | 6 | Aggregate weak-signal groups exist; the final approved row-level review predicate/capacity is not yet frozen | Patient remains separate; secondary review flag |
| 12 / B-NOK-COMPLETE | Patient roots with complete structurally valid NOK tuple | 6 | 14,578 complete; valid-phone intersection recorded as 14,348 | Mapping classifiable; contact candidate after parent |
| 13 / B-NOK-PARTIAL | Patient roots in any nonblank incomplete NOK presence class | 6 | Confirmed 1,001 rows across six partial classes | Child withheld; patient independent |
| 14 / B-NOK-BLANK | Patient roots with all NOK fields blank | 6 | Confirmed 1,371 | No contact; patient independent |
| 15 / B-DEMO-VALUE | Patient roots with nonblank Work/Address plus approved religion/marital values | 6 | Marginals confirmed; joint capacity not published | Optional fields mapped independently |
| 16 / B-DEMO-BLANK | Patient roots with one or more blank optional demographic fields | 6 | Marginals confirmed; joint capacity not published | Blank field withheld only |
| 17 / B-INS-MULTI | Patient roots with multiple matched insurance rows | 6 | Required coverage; distinct patient count not in baseline | Insurance group classification/consolidation |
| 18 / B-INS-ONE | Patient roots with exactly one matched insurance row | 6 | Required coverage; distinct patient count not in baseline | Insurance row classification |
| 19 / B-INS-ZERO | Patient roots with zero matched insurance rows | 6 | Required coverage; distinct patient count not in baseline | `I0`; never infer cash/self-pay membership |
| 20 / B-INS-UNKNOWN-DATE | Patient roots supporting insurance rows in unknown-date classes | 6 | 17,125 date-unknown rows; distinct roots not published | History-only/current withheld per Phase 2E |
| 21 / B-INS-PROVIDER-UNRESOLVED | Patient roots supporting blank, unmatched or source-ambiguous provider evidence | 6 | Row marginals confirmed; distinct roots and approved target-crosswalk runtime result not published | Membership withheld/history retained |
| 22 / B-ALIAS-BLANK | Patient roots with blank OPD | 6 | Confirmed 141 | Alias absence only |
| 23 / B-ALIAS-UNIQUE | Patient roots with one unique nonblank source alias candidate | 8 | Confirmed 15,550 candidates; runtime grammar/target collisions pending | Alias candidate, never identity match |
| 24 / B-STRAIGHTFORWARD | Patient roots passing source-only required checks and not previously assigned | 8 | Provisional 11,732 intersection; does not prove separate names or commit eligibility | `MAPPING_CLASSIFIABLE`, still `REMEDIATION_REQUIRED` for names unless protected remediation exists |

The table totals 148 quota slots. Secondary flags retained for every selected root must include alias class, required-field class, NOK class, each optional demographic class, insurance row-count class, provider/member/date/consolidation classes and relationship/quarantine class. Assignment to one primary stratum must not erase overlapping coverage.

### Cohort B outcome gates

For each selected patient root, record all four separately:

1. **Mapping dry-run eligibility:** exact root and source snapshot are valid, extraction succeeds, and fields can be classified. This may be true even when patient creation is blocked.
2. **Protected remediation eligibility:** the defect is one that an independently evidenced, approved remediation record is allowed to address. A duplicate-review signal is never itself remediation evidence.
3. **Future commit candidacy:** all required identity values are valid after approved remediation, reference and target-collision checks are unique, target-state and insurance initialization policies are approved, and no root/chain exception blocks.
4. **Future commit readiness:** additionally requires the entire Phase 3 foundation, fresh pinned snapshots, migration-safe allocator, protected storage, runtime isolation, idempotency/resume/rollback controls and zero reconciliation differences. Phase 2F must record this as false for every source-derived row.

An unremediated combined name can be mapping-classifiable but cannot be a future commit candidate. Missing/invalid DOB, gender or phone behaves the same. A parent/root quarantine holds aliases, contacts and memberships without reassigning them. Optional child withholding alone does not make a valid patient ineligible.

## Cohort manifest interface

The protected operational manifest must carry the following fields; this repository may contain only field names, scenario/stratum IDs, aggregate counts and nonsecret schema/contract hashes.

| Field | Contract |
|---|---|
| `pilot_contract_version` | Exact immutable Phase 2F bundle version/hash. |
| `run_candidate_id` | Non-PHI run/candidate UUID generated by the foundation; not a patient identifier. |
| `source_snapshot_id` | Signed/hash-bound exact `uuhms` snapshot coordinate and source fingerprint. |
| `target_snapshot_id` | Protected non-production target collision coordinate supplied by the target workstream. |
| `cohort_type` | `A_SYNTHETIC`, `B_SOURCE_PROTECTED` or `C_TARGET_PROTECTED`. |
| `scenario_stratum_id` | Stable A/B/C identifier from the approved contract. |
| `protected_source_token` | Domain-separated source root HMAC; null only for purely synthetic/target-only cases. Never publish. |
| `protected_dependency_chain_token` | Patient/attendance/insurance chain token with domain/key/canonicalization versions. Never publish. |
| `expected_mapping_class` | One explicit class from the Phase 2C/2D/2E composition plus Phase 2F readiness vocabulary. |
| `required_remediation_class` | `NONE`, `NAME_COMPONENTS`, `DOB`, `GENDER`, `PHONE`, `MULTI_FIELD`, `NOT_REMEDIABLE_BY_PATIENT_INPUT`, or exact versioned extension. |
| `expected_exception_set` | Sorted stable exception-code set; absence represented explicitly as empty set. |
| `expected_reconciliation_results` | Contract IDs, expected terms and exact zero differences/safety counts. |
| `expected_target_collision_branch` | `NEW_CLEAR`, `EXPLICIT_EXISTING_IMMUTABLE`, `MISSING`, `AMBIGUOUS`, `SOFT_DELETED`, `MERGED`, patient-number/alias/contact/insurance collision, or drift stop. |
| `inclusion_reason` | Stable rule/stratum reference only; no free-text PHI. |
| `exclusion_reason` | Stable exception/reason code, or explicit null. |
| `selection_algorithm_version` | Exactly `PILOT-COHORT-SELECTION-V1` for Cohort B. |
| `selection_rank` | Protected ordinal within stratum; never publish row token/rank HMAC. |
| `hmac_algorithm` / `hmac_key_version` | `HMAC-SHA-256` and pinned protected key version; keys remain outside repository. |
| `canonicalization_version` | Exact typed, length-prefixed payload version; includes field/null markers. |
| `approval_state` | `DRAFT`, `SELECTED`, `REMEDIATION_PENDING`, `DRY_RUN_CLASSIFIED`, `QUARANTINED`, `EXCLUDED`, or later approved state. Never infer approval from document existence. |
| `retention_classification` | Restricted migration identity/provenance retention class and purge/hold rule. |
| `contract_dependency_hashes` | Exact Phase 2A–2F normative bundle hashes. |
| `manifest_record_hash` | Keyed integrity value over the canonical manifest record. Plain SHA-256 of patient facts is prohibited. |

Cardinality/integrity rules:

- one manifest record has exactly one cohort type and primary stratum/scenario;
- one selected protected root appears once as the primary Cohort B root, with secondary flags attached rather than duplicate selections;
- one source snapshot and one target snapshot apply to the whole run candidate;
- exception sets and expected reconciliation terms are sorted and versioned;
- a changed contract, source/target snapshot, HMAC key version or canonicalization version creates a new candidate manifest; it never mutates a prior signed manifest silently;
- repository projection is aggregate-only and must prove zero raw identifiers and zero row-level tokens.

## Protected remediation interface consumed by Cohort B

The future protected record is keyed only by the `patient-source-key-v1` token and must include independently evidenced first name, last name, optional other names, corrected DOB/gender/phone where applicable, evidence type, evidence issuer/source, reviewer, approval state/time, validity interval, revocation state/time, rule version and conflict flags. Raw remediation values remain outside repository artifacts.

Validation precedence is: token/key/version integrity -> approval and nonrevocation -> evidence independence/authority -> field semantic/format validity -> conflicts -> validity at pinned snapshot -> accepted field projection. Failure retains `REMEDIATION_REQUIRED`/quarantine and never falls back.

Prohibited uses remain exact: copy one name into both components; guess name order; borrow another patient's fact; treat duplicate-review evidence as remediation; use insurance or a child contact to repair patient identity automatically; turn remediation into a general target match; retain values after revocation; publish values or row tokens.

## Aggregate-only Cohort B output

Allowed repository/report fields:

- contract/source/target snapshot hashes and nonsecret versions;
- `N_B`, the maximum 148, and selected/available/underfill count per stratum;
- counts by mapping, remediation, quarantine, alias, child, insurance, collision and readiness class;
- count of roots with each expected exception code;
- all reconciliation terms/differences and safety-zero counts;
- privacy scan result and artifact manifest hash.

Forbidden output includes raw/source IDs, min/max IDs, patient values, example source values, row hashes, HMAC tokens, HMAC rank, member/provider text, dates tied to a root, small-cell target values and protected remediation evidence.

## Aggregate evidence dependencies and closure gaps

| Dependency/gap | Current state | Required closure before protected Cohort B selection |
|---|---|---|
| Exact source fingerprint/schema/database/version | Confirmed baseline | Revalidate with D-101 account at the selection snapshot; fail closed on difference. |
| Required patient marginals and source-only intersection | Confirmed | Recompute through versioned aggregate queries at the pinned snapshot. Do not call 11,732 commit-ready. |
| Alias blank/unique/duplicate source partition | Confirmed aggregate | Freeze final Unicode/semantic grammar and protected runtime parity; target collisions come from separate Cohort C snapshot. |
| Final suspected-duplicate row-level predicate | Unresolved | Approve/freeze the deterministic signal set for review-only selection. It must never match/merge. |
| NOK presence and child marginals | Confirmed | Compute protected joint flags on the exact shared `PATIENT-EXT-001` snapshot. |
| Zero/one/multiple matched insurance rows per patient | Not present in published baseline | Add sanitized aggregate query and result/hash; preserve patient-orphan rows separately. |
| Distinct patient-root capacities for insurance duplicate/conflict/date/provider strata | Not published | Add aggregate distinct-root capacities without IDs/values and freeze query/tool hashes. |
| Phase 2A approved provider-crosswalk runtime outcomes | Not executed/available in repository | Supply protected, version-matched crosswalk results; Classic name candidate counts are not target mappings. |
| Protected name/required-field remediation population | Not implemented or counted | Provide signed protected inputs outside repository; Phase 2F reports zero source commit-ready rows without them. |
| Target collision/immutability population | Separate target workstream | Pin Cohort C snapshot and join only through protected authorized crosswalk/collision tokens. |
| HMAC key management | Blocker | Provision separated root, selection, comparison and integrity keys with versions/rotation/audit before row-level manifest creation. |
| Protected manifest/quarantine/provenance/reconciliation storage | Blocker | Phase 3 must implement and review storage before any real Cohort B row-level selection is retained. |

## Inferences that must not become policy

- A patient in the 11,732 source-only intersection may be operationally cleaner than other rows, but still lacks evidenced first/last components and is not commit-ready.
- A unique nonblank OPD may become an alias candidate, but uniqueness alone does not prove grammar validity or absence of a target collision.
- A complete NOK tuple may yield one emergency-contact candidate after parent success; it does not repair patient identity or prove a relationship enum.
- One Classic provider-name candidate is diagnostic only; it does not prove a target provider.
- Active-looking insurance dates describe a date window only and do not prove eligibility, active target state or primary membership.
- Repeated names, phones, OPDs or member numbers can support review/exception strata; none authorizes patient matching or merging.

## Mandatory blockers carried forward

The cohort specification can complete while future commit mode remains blocked by:

1. a dedicated D-101 SELECT/metadata-only account on exact `uuhms`;
2. protected independently evidenced first/last-name and other required-field remediation;
3. approved coherent target patient-state initialization;
4. approved insurance `member_type`, active/current/primary and other initialization representation;
5. approved, version-matched Phase 2A provider crosswalk runtime inputs;
6. protected crosswalk, provenance/history, quarantine, manifest and reconciliation storage;
7. HMAC key generation, separation, rotation, revocation and recovery controls;
8. a migration-safe patient-number allocator;
9. migration-specific validated persistence and full side-effect isolation;
10. fresh protected target collision/immutability snapshot;
11. deterministic idempotency, checkpoint, resume and rollback/compensation infrastructure;
12. hash-bound aggregate queries closing the Cohort B joint-capacity gaps above.

No blocker is permission to default, discard, invent, merge, reassign or expose a record.

## Draft acceptance checks for lead consolidation

- Cohort A has exactly 66 synthetic scenarios and covers every mandatory scenario in the Phase 2F prompt.
- Cohort B is deterministic and bounded by 148 primary roots; exact size is the stated underfill formula, not an unproved promise.
- Every Cohort B root is ranked only through a domain-separated HMAC over a protected source/chain token at one pinned snapshot.
- Source-derived rows without accepted protected first/last-name remediation are never labelled future commit-ready.
- Parent identity precedes alias, child and insurance classification; no later stage changes an earlier identity outcome.
- Every selected insurance source row remains represented in protected history; current membership is at most one per resolved patient/provider.
- Existing-target patient/child/membership mutation remains zero.
- Expected classified exceptions can be nonzero, but every partition difference, unexplained loss and prohibited side-effect count must be zero.
- Repository artifacts contain no raw identifier, patient value, row token, HMAC, secret or remediation value.
- Phase 2F database writes, importer code, state/schema/UI/seeder/synchronization implementation and operational eligibility verification remain zero.

## Workstream handoff

**Confirmed:** the baseline provides sufficient sanitized marginal evidence to define a representative, fail-closed selection contract and synthetic coverage suite. It does not provide joint capacities for every source-derived stratum or any evidence that an unremediated Classic patient is commit-ready.

**Technical specification:** consolidate the 66-case Cohort A matrix, the maximum-148 deterministic Cohort B formula, the protected manifest/remediation interfaces, the readiness vocabulary and the listed aggregate-query closure requirements into the normative Phase 2F package.

**Blockers:** do not claim a fixed realized Cohort B size until the versioned joint-capacity queries run at the pinned read-only snapshot. Do not claim future commit readiness until protected remediation, target-state/insurance initialization, target collision data and the Phase 3 foundation all exist and pass.
