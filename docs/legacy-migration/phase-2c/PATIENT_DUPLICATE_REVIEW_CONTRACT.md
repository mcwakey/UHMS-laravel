# Patient duplicate review contract

Status: Phase 2C specification only. It defines review handoff and safety invariants; it authorizes no automatic match, merge request, merge execution, UI change, target write or crosswalk decision.

## Authority and boundary

Q-002 and D-005 require separate eligible Classic patient rows to remain separate patient candidates and prohibit automatic merging. D-201/D-211 separate OPD alias uniqueness from patient identity. D-213/Q-007 prohibit using a suspected duplicate to fill another patient's missing identity or to reassign dependent records. Q-006 keeps each patient/encounter dependency chain traceable.

This contract distinguishes three concepts:

1. **source duplicate-alias group**: a deterministic collision under the approved OPD comparison rule; every alias is withheld, but the patient entity need not be blocked;
2. **suspected duplicate patient**: a secondary review signal; it is neither a match nor a merge decision and does not by itself block an eligible patient;
3. **reviewed identity determination**: an authorized, protected human decision that may approve an existing-target crosswalk or a post-migration merge workflow. It is never inferred by the migration runtime.

## Evidence

Confirmed source aggregates are diagnostic only:

- `UPPER(TRIM(PatientName))`: 1,001 duplicate groups covering 2,278 rows;
- normalized name plus DOB plus normalized sex: 230 groups/466 rows;
- the same group plus trimmed phone: 190 groups/384 rows;
- exact trimmed phone duplicate groups: 935 groups/2,075 rows;
- canonical source OPD duplicate groups: 275 groups/1,259 rows after trim, whitespace removal and uppercase; runtime Unicode/NFC parity and target collision checks remain mandatory.

These aggregates expose review workload, not duplicate truth. Classic provides one combined name, no separate name components, and no national identifier/email field. Phone and OPD quality are incomplete. Classic `PAT_ID` proves only source-row linkage.

The installed target has patient merge requests/logs, aliases and merge-state columns. Its confidence helper scores demographic equality and its merge service mutates folders/children and creates present-time records. That operational workflow is not a migration deduplication API, does not cover every installed patient dependency, and must not be called automatically by migration.

## Prohibited automatic decisions

The following may create a protected review signal but can never independently or in a weighted/scored combination establish a source-source merge, existing-target match or dependent-row reassignment:

- name or normalized name;
- phone or normalized phone;
- OPD or alias equality;
- email;
- DOB or age;
- gender/sex;
- address, occupation, company/payer or emergency-contact data;
- Ghana Card or another national identifier alone;
- any similarity/fuzzy/phonetic comparison;
- any weighted confidence score, including target merge-confidence output;
- a shared child, bed, billing text, clinician text or other weak/inconsistent relationship;
- Classic primary-key proximity, registration chronology or gaps.

The runtime must never select a “best” patient, “main” folder or alias owner. It must never call `getFinalPatient()` to redirect a Classic mapping, create a merge request/log, execute `PatientMergeService`, overwrite demographics, coalesce patient chains, or assign children to another candidate.

## Classification and blocking behavior

| Condition | Primary patient outcome | Secondary flag | Alias outcome | Chain behavior |
|---|---|---|---|---|
| Suspected duplicate only, all entity rules valid | Remains eligible new-patient candidate | `PATIENT_SUSPECTED_DUPLICATE_REVIEW` | Independently classified | Separate patient and child chain remains eligible. |
| Duplicate OPD only, all entity rules valid | Remains eligible new-patient candidate | Duplicate-alias review | `duplicate_withheld` | Only the alias is withheld. |
| Missing/invalid required identity plus duplicate signal | Missing/invalid identity quarantine | Duplicate-review flag | Independently classified | Entire patient-root chain remains with that source patient. |
| Existing-target ambiguity | Existing-target ambiguity quarantine | Any diagnostic flags | Independently classified | No target link; patient-root chain held. |
| Explicit approved existing-target link plus suspected duplicate signal | Existing-target link | Review flag if still applicable | Independently classified | Link is immutable and children follow only the approved crosswalk. |
| Orphan encounter resembles a patient | Encounter-root quarantine | Optional diagnostic only | Not applicable | Never attach to the resembling patient. |

Duplicate alias and suspected duplicate are secondary classifications and must not add rows to the mutually exclusive patient-entity partition. A suspected duplicate never cures or worsens a required-field result; each rule is evaluated independently.

## Review candidate construction

The future dry-run may emit a protected duplicate-review candidate only when a versioned deterministic signal rule fires. The protected payload must contain:

- opaque source-patient token(s), never raw Classic keys;
- rule ID/version and signal classes that fired;
- domain-separated HMAC tokens for each normalized evidence class, never raw name/phone/OPD/identifier;
- source snapshot/content-hash identity and target fingerprint/state coordinate;
- patient entity, alias and chain outcomes;
- explicit statement that no match/merge/alias ownership has been decided;
- reviewer, status, timestamps, evidence class, decision reason, approvals and revocation history when later reviewed.

Each evidence family uses a separate HMAC domain. A name token cannot be compared with an OPD or phone token. Review reports and repository artifacts contain aggregate candidate/group counts only; row-level tokens stay in the protected review ledger.

## Controlled post-migration workflow

An otherwise eligible patient may be created and mapped separately before duplicate review. Review occurs after migration only when the complete child graphs and identity-review controls are available.

Required review sequence:

1. Patient Identity Governance accepts the protected case; SLA objective is five business days, with immediate escalation for contradictory authoritative evidence, privacy failure or active patient-safety risk.
2. Reviewer obtains authoritative evidence beyond weak demographic similarity and records its issuer, validity and scope without exporting raw evidence to repository artifacts.
3. Reviewer chooses one explicit outcome: `NOT_DUPLICATE`, `REVIEW_INCONCLUSIVE`, `APPROVE_EXISTING_TARGET_CROSSWALK`, `APPROVE_POST_MIGRATION_MERGE`, or `ALIAS_OWNERSHIP_DECISION`. No timeout-derived outcome exists.
4. Existing-target approval must satisfy `EXISTING_TARGET_PATIENT_CONTRACT.md`; it changes no target fields.
5. A merge approval is executed only through a separately authorized renewed identity/merge workflow after complete dependency coverage, actor authorization, four-eyes/segregation controls, privacy/audit controls and rollback/recovery review are proven. Migration never executes it.
6. Alias ownership is separately revalidated under `LEGACY_OPD_ALIAS_CONTRACT.md`; a merge decision does not silently release or transfer an alias.
7. Every disposition preserves the original source-patient and chain tokens, decision history and reconciliation category.

One reviewer must not silently turn a diagnostic score into identity truth. Where the target workflow permits request/approval/execution by insufficiently separated actors, the controlled migration review process must add independently reviewed segregation before use.

## No-merge and chain invariants

- Separate eligible Classic rows create separate new-patient candidates unless an explicit existing-target crosswalk already resolves a row.
- Every source patient has at most one patient-root chain; suspected duplicate chains are never unioned.
- Dependent rows retain their exact source patient/attendance chain. No dependent is moved to satisfy a duplicate theory.
- An unresolved patient quarantine holds its complete chain. A suspected duplicate flag alone does not quarantine the chain.
- A post-migration merge, if separately approved, must account for every installed target FK and application-level patient reference. The current service's table list is not proof of completeness.
- Existing target patients and successfully mapped new patients remain immutable during migration review; only the later authorized operational action may mutate merge state.

## Reconciliation

Contract ID: `PATIENT-REC-DUPLICATE-010`. The patient-entity equation it audits is `PATIENT-REC-ENTITY-001`; duplicate flags never add terms to that entity equation.

Patient entity reconciliation continues to use exactly one primary outcome:

`Classic patient rows = explicit existing-target links + eligible new-patient candidates + source-remediation pending + quarantined missing/invalid identity + quarantined existing-target ambiguity + failed extraction/constraint + excluded`

Suspected-duplicate and duplicate-OPD counts are secondary measures outside this sum.

For every versioned duplicate-review signal:

`signal candidates = queued for protected review + already reviewed under same evidence/version + superseded by evidenced rule-version change + failed handoff`

The outcomes are mutually exclusive and difference must be zero. A rule-version change never auto-closes a case; it produces an auditable supersession and re-evaluation.

Zero assertions:

- automatic source-source merges created = 0;
- automatic target merge requests/logs/executions = 0;
- name-only matches accepted = 0;
- phone-only matches accepted = 0;
- OPD-only matches accepted = 0;
- email/DOB/gender/address/national-ID-only matches accepted = 0;
- fuzzy, phonetic, confidence or weighted-score matches accepted = 0;
- duplicate OPD aliases automatically assigned = 0;
- patients chosen as a “best” duplicate or alias owner = 0;
- patient dependency chains coalesced/reassigned = 0;
- Classic keys reused as renewed identity = 0;
- review SLA expiries producing a default decision = 0.

Any nonzero result is a critical identity-safety failure and stops the affected run/release.

## Classified exceptions and manual ownership

| Code | Use in this contract |
|---|---|
| `LEGACY-PATIENT-DUPLICATE-027` | Suspected duplicate signal/review handoff, inconclusive or contradictory evidence, and review-state/SLA tracking without a default decision. |
| `LEGACY-PATIENT-DUPLICATE-028` | Any prohibited automatic match/merge/main-folder decision, unsafe merge workflow/dependency coverage, or cross-chain reassignment attempt. |
| `LEGACY-PATIENT-ALIAS-004` | Deterministic duplicate OPD alias withholding; one occurrence per affected source patient. |
| `LEGACY-PATIENT-PRIVACY-036` | Missing protected review provenance/HMAC version or disclosure of raw identity evidence. |

Duplicate flags do not replace the field-specific reason codes for missing names, DOB, gender, phone or other entity blockers. One primary entity disposition can carry several secondary duplicate-review codes without double-counting.

Patient Identity Governance owns ordinary duplicate review. The Privacy/Security Owner and Migration Technical Owner jointly handle privacy failures, unauthorized automation and chain integrity. Clinical/financial owners must approve any later merge effect on their records. SLA expiry never permits auto-merge, auto-match, alias assignment, invented fields, discarded cases or child reassignment.

## Privacy

Names, phones, OPDs, emails, national identifiers, addresses, crosswalks and duplicate-group membership are protected data.

- Use domain-separated HMAC-SHA-256 with versioned domains such as `patient-name-comparison-v1`, `patient-phone-comparison-v1`, `legacy-opd-comparison-v1` and `patient-source-key-v1`.
- Never use plain SHA-256 for low-entropy identity values and never place raw values or row-level HMACs in documentation, screenshots, ordinary logs, fixtures or source control.
- Canonical input is unambiguous length-prefixed UTF-8 with explicit field/type/null markers. Store algorithm, purpose, key version and canonicalization version.
- Review access is least-privilege and audited in the protected migration review ledger. HMAC signals are routing evidence, not proof of identity.
- Key loss, unversioned normalization, cross-environment token comparison or raw-PHI disclosure is an immediate stop.

## Stop conditions and prerequisites

Stop duplicate handoff or the affected migration release when:

- a rule or operator attempts automatic matching, merging, main-folder selection, alias ownership or child reassignment;
- a similarity score is treated as deterministic identity evidence;
- source/target fingerprints, signal rule versions, HMAC key versions or chain identities are absent/drifted;
- one source resolves to multiple target patients, or unapproved multiple sources collapse to one target;
- a merge candidate lacks complete installed dependency coverage or approved segregation;
- patient entity and alias partitions are conflated or any reconciliation difference is nonzero;
- target data changes during migration review, or raw protected evidence leaves the approved ledger.

Before implementation, Phase 3 must provide the protected review ledger, role/segregation/access contract, versioned HMAC canonicalizers and key management, immutable source/target snapshots, complete target dependency coverage, review-status lifecycle and audit/reconciliation outputs. None is implemented here.
