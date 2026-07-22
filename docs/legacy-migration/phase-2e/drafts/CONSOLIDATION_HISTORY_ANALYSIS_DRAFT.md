# Phase 2E consolidation and protected-history analysis draft

Status: read-only analysis for lead-agent consolidation. This draft is not a normative contract, importer authority, persistence design, or database-write authorization.

## Evidence and authority boundary

### Confirmed evidence

- The approved Classic database is exactly `uuhms`; this analysis used repository evidence only and made no database connection.
- `uuhms.insurance` has 31,307 rows and nine installed columns: `INS_ID`, `PAT_ID`, `InsType`, `Scheme`, `MemberNo`, `Company`, `IssueDate`, `ExpiryDate`, and `Plan`.
- `INS_ID` is the only index: a non-null auto-increment primary key. No source foreign key is declared.
- `insurance.PAT_ID = patients.PAT_ID` partitions as `31,307 = 0 null + 0 zero + 30,991 matched nonzero + 316 orphan nonzero + 0 failed` in the captured evidence.
- The current provider-name candidate predicate partitions the same source rows as 25,042 blank/not-evidenced, 2,248 having at least one Classic `sett_private` name/short-name candidate, and 4,017 unmatched nonblank. It does not prove unique provider resolution.
- Phase 2A found 38 `sett_private` rows, 33 normalized `(PrivateName, PrivateType)` keys, and five duplicate groups covering ten rows. `NK-004` is candidate-only; its specification explicitly disallows automatic matching and requires an approved duplicate group for many-source-to-one mapping.
- Classic insurance has no installed status/current flag, registration/modification timestamp, or change journal. `IssueDate` and `ExpiryDate` are business dates only and cannot prove extraction chronology.
- The captured date aggregates are: 17,091 zero issue dates, 15,717 zero expiry dates, 66 expiry-before-issue rows, 19 future issue dates, and 54 future expiry dates. The future predicates used `UTC_DATE()` during the 2026-07-21 evidence capture.
- The captured identity aggregates are: 17,029 blank member numbers; 1,019 duplicated normalized nonblank member-number groups covering 2,555 rows; and 36 duplicate normalized `(patient, insurance type, company, scheme)` groups covering 88 rows. These aggregates do not disclose overlap or establish the number of target patient/provider groups.
- Installed `patient_insurances` has a unique constraint on `(patient_id, insurance_provider_id)`. Its `membership_number`, `policy_number`, `start_date`, `expiry_date`, `insurance_tier_id`, `card_holder_insurance_id`, `ccc_code`, and timestamps are nullable. `member_type`, `is_primary`, and `is_active` are non-null with defaults of `holder`, `false`, and `true` respectively. Those defaults are not migration evidence.
- The installed target has no soft-delete column on `patient_insurances`. Provider and patient foreign keys are required. The table therefore cannot preserve multiple operational rows for the same patient/provider pair.
- The existing Phase 1B capture reports no reusable source transaction coordinate. Its structural fingerprint and count-bearing snapshot fingerprint are not an ordered insurance-row content snapshot.

### Approved policy consumed unchanged

- D-212/Q-009: at most one current membership per target patient/provider pair; create it only from deterministic patient, provider, and current-state facts; retain every source row in protected provenance; quarantine conflicting current candidates.
- D-205/Q-004: every categorical mapping is explicit and unknown values are exceptions, never defaults.
- D-206/Q-301: valid source dates are preserved; field-specific unknown representations are allowed only where supported; dates are never guessed, swapped, or replaced.
- D-207/Q-005: sentinel rules are field/relationship specific; no artificial patient, provider, scheme, or membership parent is created.
- Phase 2A provider contract: exact approved crosswalk precedence and `NK-004` constraints are consumed without fuzzy or punctuation-insensitive matching, source duplicate collapse, provider stub creation, or `OTHER` fallback.
- Phase 2C contracts `PATIENT-REL-003`, `PATIENT-SENT-003`, `PATIENT-PRIV-007`, `PATIENT-PRIV-009`, `PATIENT-TARGET-009`, and `PATIENT-CHAIN-*` remain authoritative for patient linkage, quarantine topology, provenance, and existing-target immutability.
- Phase 2D does not alter the insurance parent contract. Its child-sequencing rule reinforces that patient mapping commits first, every child has an independent outcome, and existing target children remain immutable.

### Inferences that must not be treated as facts

- `patients.Company` may be insurer, employer, sponsor, default payer, historical text, or a mixture. It is corroborating/conflict evidence only and never creates a provider or membership.
- `patients.BillStatus` is payer-category evidence, not a patient status and not a provider identity.
- Source `Plan` and `Scheme` may contain overlapping plan/scheme concepts, but equality, precedence, or merge semantics are not established by the installed schema.
- A duplicate normalized member number does not prove the same patient, provider, membership, or household.
- A greater `INS_ID`, later expiry date, nonblank member number, matching `patients.Company`, or target default does not prove that a row is current.

## Deterministic patient/provider grouping

Recommended normative IDs: `INSURANCE-CONSOLIDATION-001..014`.

1. Classify the source row and its patient edge before attempting a group. A row with extraction/provenance failure, an orphan patient, or a quarantined patient cannot enter a releasable target group.
2. Resolve the target patient only through the Phase 2C protected patient crosswalk. No insurance field may create, select, merge, redirect, or repair a patient.
3. Resolve the provider only through a version-pinned Phase 2A provider crosswalk. A name candidate, normalized name, provider type, `patients.Company`, insurance type, popularity, first target row, or `OTHER` value is insufficient.
4. Create the consolidation-group identity only after both parents resolve uniquely. Its logical key is exactly `(target patient identity, target insurance-provider identity)` because that is the installed target uniqueness boundary.
5. Do not add insurance type, scheme, plan, member number, dates, or source key to the target uniqueness key. Those values classify agreement/conflict inside one patient/provider group; they cannot split the group to evade uniqueness.
6. Use a protected domain-separated group token, not raw source/target IDs, in routine execution and reporting. Suggested HMAC domain: `patient-insurance-group-v1`.
7. Rows with provider blank/unmatched/ambiguous/type-conflicting evidence remain individually represented and outside a resolved patient/provider group until the exact provider dependency is repaired. They are never grouped under an artificial placeholder provider.
8. One resolved group has exactly one group outcome: selected-current, history-only, conflicting-current, existing-target-immutable, or failed. Secondary row exceptions do not create a second group disposition.

### Required group-key record

The future protected group ledger needs the patient crosswalk contract/version, provider crosswalk contract/version, opaque patient and provider target tokens, group HMAC domain/key/canonicalization versions, source snapshot identity, target snapshot identity, evaluation coordinate, ordered source-row token set, group classification, selected target-representation fingerprint if any, exception set, and reconciliation status. It must not expose raw identifiers in repository artifacts.

## Row normalization and membership equivalence

Normalization is for matching and classification; protected provenance retains the original typed source representation.

| Component | Comparison rule | Prohibited shortcut |
|---|---|---|
| Patient | Exact Phase 2C crosswalk result | Member number, name, OPD, provider, scheme, or `patients.Company` |
| Provider | Exact Phase 2A crosswalk result and compatible mapped provider type | Fuzzy/punctuation-insensitive name, popularity, `OTHER`, first/current target |
| Insurance type | Explicit Phase 2E value crosswalk | Default to private, NHIS, cash, or provider default |
| Scheme and plan | Independently decoded, NFC-normalized, outer-trimmed, internal whitespace preserved unless the final field rule explicitly approves collapse | Blind concatenation; plan creation from free text; scheme equality as patient identity |
| Member number | Decode Classic `latin1`, reject undecodable/control characters, Unicode NFC, trim outer whitespace for comparison, preserve internal whitespace, punctuation, case, and leading zeros unless an explicit provider-specific contract says otherwise | Numeric cast, punctuation stripping, low-entropy hash, patient match |
| Dates | Exact typed date plus separate raw lexical provenance and field classification | Zero-to-runtime-date conversion, swapping, `INS_ID` order |

An output-equivalence tuple should contain: mapped patient, mapped provider, mapped insurance type, resolved scheme/plan representation, normalized member-number representation including explicit blank/null state, valid issue/start date representation, valid expiry date representation, and every source-evidenced target field. If an input has no approved target representation, it remains in protected provenance and may still make two current candidates incompatible.

## Exact, canonical-equivalent, historical, and conflicting duplicates

Recommended row classes: `INSURANCE_EXACT_DUPLICATE_SOURCE`, `INSURANCE_CANONICAL_EQUIVALENT_SOURCE`, `INSURANCE_COMPATIBLE_HISTORICAL_SOURCE`, and `INSURANCE_CONFLICTING_CURRENT_CANDIDATES`.

| Class | Required evidence | Current representation | History/provenance |
|---|---|---|---|
| Exact source duplicate | Different valid `INS_ID` values but every other installed source field has identical typed/raw representation | At most one target representation if the shared facts independently qualify | One protected history record per source row; never collapse provenance |
| Canonical-equivalent duplicate | Raw representation differs only through an explicitly approved, loss-accounted transformation and the complete output-equivalence tuple is identical | At most one target representation if independently current | Preserve each raw representation and transformation trace; do not label it exact |
| Compatible historical version | Same resolved group; source facts are representable and temporally non-conflicting; at most one equivalence class is current at the pinned coordinate | Select the unique current class only | Every non-selected row remains protected history with its own temporal class |
| Conflicting current candidates | More than one independently active-looking, target-representable equivalence class remains, or active rows disagree on member/type/scheme/plan/dates in a way not eliminated by an approved exact transformation | None; quarantine the group | Preserve all rows and conflict dimensions |
| Diagnostic member duplicate | Same normalized member number inside or outside the group without complete tuple equivalence | No selection effect by itself | Protected review flag only; never patient identity evidence |

Important distinctions:

- The baseline 36 duplicate `(patient, type, company, scheme)` groups are diagnostic candidates only. They do not establish target provider resolution, exact duplication, compatible chronology, or current selection.
- Two concurrently active rows with different date bounds are conflicting unless their complete target representation is identical under approved rules. Containment, later expiry, later issue, or larger `INS_ID` is not a tie-breaker.
- One uniquely active valid class may be selected while expired compatible rows remain history. A future-looking or date-unknown row is not a second current candidate, but it remains separately classified and may carry an exception.
- An active row and a future-looking row may coexist in protected history; the future row cannot overwrite or reserve the unique operational target row without a later approved scheduled-coverage design.
- Differences hidden by a target-null field are not silently discarded. For example, two active rows with different unresolved schemes conflict even if `insurance_tier_id` could be left null.

## Pinned issue/expiry/current evaluation coordinate

Recommended coordinate ID: `INSURANCE-DATE-EVALUATION-001`; rule version: `insurance-current-state-v1`.

The coordinate is a four-part immutable value:

1. `evaluation_instant_utc` and derived `evaluation_date`;
2. declared timezone used to interpret Classic date-only fields;
3. current-state rule version;
4. exact coordinated source snapshot identity, including approved database, schema fingerprint, query/tool versions, and ordered insurance-row content hash.

For the current Phase 1B baseline, only `2026-07-21` under the query's `UTC_DATE()` predicate and the structural/count evidence are confirmed. Because `CLASSIC_QUERY_MANIFEST.json` states that no reusable source coordinate exists, that capture cannot authorize row-level consolidation. Phase 2E should record it as aggregate baseline evidence and require a newly coordinated ordered insurance/patient snapshot before any dry-run classification.

All rows in one run and all reconciliation reports must use the same coordinate. Replaying later must use the stored coordinate or produce a new versioned classification and explain every delta; reading the runtime clock again is prohibited.

### Field-specific row state

Treat a zero source date as unknown/not evidenced, retaining the raw source lexical value only in protected provenance. Null is not observed in the installed non-null columns; a future null is schema/data drift and a distinct extraction exception.

| State | Deterministic predicate at the pinned evaluation date |
|---|---|
| `chronology_invalid` | Both dates are known and expiry is before issue; never swap them |
| `future_looking` | Chronology is not invalid and the known issue date is after the evaluation date; scheduled-coverage semantics are not assumed |
| `active_looking` | Both dates are known, chronology is valid, issue is on/before and expiry is on/after the evaluation date |
| `expired_looking` | Both dates are known, chronology is valid, and expiry is before the evaluation date |
| `date_unknown` | One or both fields are unknown and no invalid known chronology can be proven |
| `date_unrepresentable` | Decoding, calendar validity, range, target representation, or rule-version validation fails |

Equal issue/expiry dates are a valid one-day period; they are active-looking only on that date. A future expiry is not invalid and can be active-looking when the known issue date is on/before the coordinate. A future issue is not treated as current without a separate approved scheduled-coverage specification.

`active_looking` is date-derived membership evidence, not verified eligibility. `date_unknown` is never promoted to active-looking from `patients.Company`, `patients.BillStatus`, target defaults, or row order.

## Deterministic current-membership selection

For each resolved patient/provider group:

1. Retain and independently classify every source row before selection.
2. Exclude patient-held/orphan, provider-unresolved, invalid chronology, unrepresentable, and extraction-failed rows from current candidacy, but not from provenance.
3. Require explicit insurance-type compatibility and an approved scheme/plan disposition. Unresolved values do not disappear merely because the target permits null.
4. Form equivalence classes using the complete output-equivalence tuple. Exact duplicates and approved canonical equivalents remain separately traceable inside the class.
5. A current candidate class must be `active_looking`, target-representable, and free of blocking field exceptions.
6. If there is exactly one current candidate class, select one target representation for the class. Selection is from the class, not from a preferred source row; all contributing row tokens are recorded.
7. If there are no current candidate classes, create no current target membership. Preserve all rows as history/evidence/quarantine outcomes.
8. If there is more than one current candidate class, quarantine the entire patient/provider group as conflicting current candidates. Create no target row and do not choose by primary key, date maximum, nonblank richness, company agreement, or provider popularity.
9. `patients.Company` may corroborate the selected provider only when it agrees with an already valid candidate. `patients.BillStatus` may corroborate payer/type compatibility only through an explicit crosswalk. Neither creates a candidate or resolves a tie.
10. The group yields at most one target membership; every source row yields exactly one source-row outcome and one protected history record.

### Target-required/defaulted fields

- `membership_number` is nullable. A blank source `MemberNo` may be represented as target null only when every other current-selection rule passes and the final normative contract explicitly approves null as “not evidenced.” It never becomes a fabricated placeholder. Blankness remains a classified exception/diagnostic.
- Classic does not distinguish `membership_number` from `policy_number`; `MemberNo` must not be copied to both. The field name supports a membership-number candidate, while `policy_number` remains null/not evidenced unless stronger evidence is approved.
- Classic has no holder/beneficiary relationship evidence. The installed `member_type='holder'` default is prohibited as automatic authority. Either approve a non-assertive target initialization separately or record a Phase 3 representation prerequisite; do not let the database silently fill it.
- `insurance_tier_id` may be populated only from the final scheme/plan mapping. It remains null/not evidenced when the approved target representation permits that outcome; no tier is fabricated.
- `card_holder_insurance_id` and `ccc_code` remain null/not evidenced. No target self-parent or CCC verification is fabricated.
- `is_active=true` may be source-derived only for the single selected `active_looking` class at the pinned coordinate. It is not an eligibility result. No row is inserted merely to set inactive history.
- `is_primary` is not sourced by Classic insurance, `patients.Company`, or `patients.BillStatus`. The safe technical initialization is false only if the lead contract explicitly classifies it as a non-assertive approved initialization; otherwise persistence remains blocked. It must never default true or be inferred from row order.
- `created_at`/`updated_at` are migration execution metadata if future persistence requires them, never Classic membership dates or historical operational events. Their handling belongs to migration audit/persistence, not source fact mapping.

## Member-number conflict handling

Recommended rule family: `INSURANCE-MEMBER-001..012`.

- Preserve raw `MemberNo` only in protected provenance. Repository artifacts contain aggregate counts and domain-separated HMACs only; plain hashes of low-entropy numbers are prohibited.
- Do not numeric-cast, remove leading zeros, strip punctuation, remove internal whitespace, or casefold for storage. A comparison form may trim outer whitespace and NFC-normalize only; any broader provider-specific normalization requires explicit approval and versioning.
- A blank value is an explicit `unknown/not evidenced` state, not provider absence and not a reason to invent a number.
- A duplicate within the same patient/provider group is classified by complete tuple equality: exact/canonical equivalent, compatible history, or conflicting current candidate. Member equality alone never resolves the class.
- A duplicate across different patients is a protected identity-review signal, never proof that patients should merge or that insurance rows should move.
- A duplicate across providers may be legitimate provider-scoped reuse or dirty data; retain the diagnostic and do not create a cross-provider identity rule.
- Multiple apparent numbers in one field, control characters, undecodable bytes, or values that exceed the target representation are unrepresentable exceptions. Do not split a field into invented memberships.
- A collision with an existing target number is comparison evidence only. It cannot link the patient, switch the provider, mutate the target, or override the patient/provider unique key.

## Protected historical provenance

Recommended normative IDs: `INSURANCE-HISTORY-001..012`.

Every Classic row, including exact duplicates, conflicts, invalid rows, orphans, and non-selected history, must have one protected history/provenance record. If the renewed application has no suitable historical membership model, this is a Phase 3 foundation prerequisite; operational `patient_insurances` rows are not replayed/overwritten to simulate history.

Minimum protected row record:

- approved database/schema/table and source snapshot identity;
- exact protected source key plus `patient-insurance-row-v1` row token;
- protected full typed source payload or encrypted equivalent sufficient for lossless evidence retention;
- row-content HMAC and canonicalization/key versions;
- Phase 2C patient-root or `legacy-insurance-chain-v1` orphan token;
- patient and provider crosswalk versions/outcomes without routine raw IDs;
- protected member-number token in `patient-insurance-member-v1` plus explicit blank/invalid class;
- raw and normalized field provenance, transformation rule IDs, date lexical evidence, date classes, and pinned evaluation coordinate;
- exact/canonical/historical/conflict class, group token, selected/non-selected state, exception codes, target-comparison outcome, and reconciliation disposition;
- migration run/checkpoint/audit reference and release/hold state.

The protected group ledger records equivalence classes and selection reasoning but never replaces row provenance. A source row selected into the current representation still remains a history record. No history record is an operational verification, eligibility, notification, claim, payment, receivable, or activity event.

## Patient-parent and orphan-chain behavior

1. A matched source patient that is Phase 2C-quarantined holds every matched insurance row under the same `PATIENT-PRIV-007` patient-root token. Provider or date validity cannot bypass the parent hold.
2. A nonzero orphan `insurance.PAT_ID` creates its own insurance-rooted chain using `PATIENT-PRIV-009` / `legacy-insurance-chain-v1` and the source insurance identity. It is not added to another orphan row merely because member/provider/scheme evidence matches.
3. No member number, `Company`, `BillStatus`, insurance type, scheme, plan, date, or target search can redirect a row to another patient.
4. A row with a valid patient but an unresolved provider remains in that patient's chain as a domain-local provider hold. It does not create a provider-rooted replacement patient chain.
5. Parent release is topological and idempotent: patient crosswalk first, then insurance patient edge, then provider/type/scheme/date/member/consolidation gates, then at most one target membership. Repairing a parent never waives a local insurance exception.
6. A missing/duplicate/unusable `INS_ID` is a source identity/provenance failure. Do not create an alternative row identity from member number or descriptive fields; stop/hold the affected extraction scope.
7. Required zero assertions include artificial patients/providers/schemes/memberships, reassigned insurance rows, cross-chain unions, children released before parent, and rows omitted from provenance.

## Existing-target membership immutability

Recommended rule family: `INSURANCE-TARGET-001..012`; comparison-token domain: `patient-insurance-target-comparison-v1`.

The target collision snapshot must query all `patient_insurances` rows directly and bind to the target schema/data fingerprint and migration coordinate. The installed table has no soft delete, but a future schema drift adding one must fail closed until the contract is reviewed.

For a Phase 2C-approved existing-target patient:

| Collision state | Deterministic outcome |
|---|---|
| No target row for the resolved patient/provider pair | Preserve source comparison/provenance only. Do not create enrichment without a separate protected enrichment decision. |
| Exactly one target row and protected prior-migration provenance proves the same group, rule versions, source-row set, and target fingerprint | Idempotent no-op; any fingerprint delta is target-state drift, not an update instruction. |
| Exactly one target row is field-compatible but has no prior-migration authority | Existing-target immutable evidence; no update, ownership claim, or silent adoption. Review if later enrichment/linkage is desired. |
| Exactly one target row conflicts in provider, member/policy number, scheme/tier, dates, holder relationship, primary/active state, CCC/verification context, or other protected field | Quarantine the group as an existing-target conflict; preserve both sides under protected comparison only. |
| More than one target row for the pair or uniqueness/index fingerprint missing | Target-state/constraint drift; stop the affected scope. |

All migration-caused changes to an existing target membership must reconcile to zero: inserts through the existing-target path, updates, deletes, provider changes, member/policy changes, scheme/tier changes, date changes, holder/self-parent changes, active/primary changes, CCC changes, and verification/eligibility changes. A target blank is not permission to fill it.

## Eligibility and verification boundary

Recommended IDs: `INSURANCE-ELIGIBILITY-001..010`.

Classic evidence can establish only:

1. that a protected source membership row exists; and
2. a versioned date-derived state (`active_looking`, `expired_looking`, `future_looking`, `date_unknown`, or invalid) at the pinned coordinate.

It cannot establish live verified eligibility, current online eligibility, NHIS CCC validity, authorization, benefit limits, remaining coverage, provider-contract eligibility, claim admissibility, or payer acceptance. `active_looking` and a target `is_active` value are not synonyms for eligible/verified.

- Create zero `insurance_verifications` rows and zero eligibility events.
- Leave verification status/source/reference/time/actor and CCC evidence null/not evidenced where the target permits it.
- Never assign current user, importer, administrator, first user, Phase 2B historical identity, or `Legacy Actor Unknown` as verifier.
- Do not call provider/NHIS/CCC, claims, payment, billing, pricing, notification, or queue services.
- If a future target constraint requires a verification state or actor, stop and record a Phase 3 representation prerequisite; do not use a default.
- Migration execution identity belongs only in the separate migration audit.

## Consolidation reconciliation

The lead contract should require all of the following simultaneously:

1. **Source-row outcome:** all 31,307 source rows have exactly one primary row outcome and exactly one protected history record; difference zero.
2. **Patient edge:** `31,307 = 0 null + 0 approved zero sentinel + 30,991 matched + 316 orphan + 0 failed` at the captured baseline; refresh only in the coordinated patient/insurance snapshot.
3. **Resolved group coverage:** every provider-resolved, patient-released row belongs to exactly one protected patient/provider group; unresolved rows belong to none and have an explicit hold.
4. **Per-group source coverage:** `source rows = selected-class contributing rows + compatible history + exact/canonical duplicates + conflicts/invalid/held + failed`, with categories made mutually exclusive in the final machine specification.
5. **Current uniqueness:** selected current target representations are 0 or 1 per group; any group with more than one is a hard failure.
6. **History preservation:** source rows without protected history = 0; duplicate rows lost by consolidation = 0.
7. **Immutability:** migration-caused existing-target membership mutations = 0.
8. **No invention:** invented member numbers/dates/provider/patient/scheme/eligibility/verifier facts = 0.

Secondary diagnostics such as blank member number, duplicate member number, Company/BillStatus disagreement, future issue, and future expiry must be subsets of primary outcomes, never extra source-row counts.

## Evidence gaps and Phase 3 prerequisites

These gaps are not stakeholder-policy reopenings:

1. A privacy-safe coordinated Phase 2E snapshot must establish exact provider uniqueness/ambiguity, exact versus normalized duplicate overlap, complete normalized membership-equivalence groups, current-state groups at the pinned coordinate, and target collision aggregates. The Phase 1B aggregates are insufficient for row/group selection.
2. The source timezone/business-date interpretation must be fixed. The existing baseline used UTC only for aggregate future predicates.
3. Scheme/plan semantics and the target tier representation must be finalized without creating free-text plan masters.
4. A non-fabricating `member_type` representation is required because the target column is non-null and Classic supplies no holder/beneficiary fact.
5. The final contract must explicitly approve or block the non-assertive `is_primary=false` initialization; the database default alone is not authority.
6. Protected source-row history, consolidation ledger, crosswalk, quarantine, target comparison, HMAC key management, idempotency, resume/rollback, and migration-audit stores do not yet exist.
7. A refreshed target collision snapshot must verify patient/provider uniqueness, relevant defaults, current rows, and schema fingerprint immediately before any later dry-run/commit.
8. Phase 3 must implement migration-specific persistence and complete side-effect isolation before target writes. This draft authorizes neither.

## Lead-agent consolidation recommendations

- Make the date-state classifier and group-selection trace machine-readable and pure: the same input snapshot and coordinate must produce the same result.
- Treat a source row outcome, historical-provenance outcome, patient outcome, provider outcome, member-number outcome, two date outcomes, and group outcome as independent reconciliation dimensions.
- Keep `exact source duplicate` narrower than `canonical equivalent`; never hide a raw disagreement behind normalization.
- Require a unique `active_looking` equivalence class for current selection. Unknown-date rows remain history/exception evidence; Company/BillStatus only corroborate a valid candidate.
- Make existing-target no-row, prior-migration exact no-op, immutable-compatible evidence, conflict, and target drift distinct states.
- Preserve all nine Classic insurance fields losslessly in protected provenance, while source control retains only aggregate evidence and rules.
- Carry forward the Phase 2A and 2C IDs above as normative cross-references; do not mint substitute provider or patient contracts.

