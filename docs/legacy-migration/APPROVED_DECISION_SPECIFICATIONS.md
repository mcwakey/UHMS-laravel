# Approved migration decision specifications

## Authority record

| Field | Approved record |
|---|---|
| Approval method | Direct project-owner decision |
| Approving authority | Project Owner and Final Decision Authority |
| Approving stakeholder name | mcwakey (established repository identity; no legal name is asserted) |
| Approval date | 2026-07-21 |
| Signature/reference | Project-owner directive recorded in the migration decision register |
| Decision status | Accepted |
| Workshop status | Superseded by final-authority decision |
| Additional approval | None required |

No physical or virtual workshop occurred. This specification is the canonical policy interpretation of the owner directive. It authorizes Phase 2 detailed mapping only; it does not authorize an importer, target write, production test, schema change, migration-state implementation, UI, synchronization runtime, or cutover.

## 1. Patient identity and registration

### D-201 / Q-001 — Patient numbering

- Generate renewed patient numbers with the renewed numbering mechanism.
- Preserve each unique, valid Classic OPD number as a typed legacy alias.
- Preserve the Classic patient primary key only in the protected migration crosswalk; never reuse it as a renewed primary key.
- Blank and duplicate OPD values become neither renewed patient numbers nor automatically assigned aliases.

### D-211 / Q-008 — Duplicate OPD aliases

- Do not assign duplicate OPD values automatically. Preserve each original value in the protected crosswalk and create a classified exception for every affected patient.
- Alias ownership requires controlled manual identity review. Do not merge distinct records to satisfy alias uniqueness.

### Q-002 — Duplicate patients

- Never merge patients automatically during migration. Use the Classic patient primary key only for source linkage and import separate eligible source patients separately.
- Send suspected duplicates to the renewed identity-review/patient-merge workflow after migration. Names alone never establish a merge.

### D-213 / Q-007 — Missing or invalid required fields

- Never invent names, dates of birth, gender, phone numbers, or other identity values.
- Apply only reliable, evidenced source remediation. Quarantine unresolved patients with explicit reason codes and retain all dependent rows in the same source chain without reassignment.
- Any nullable or explicit-unknown target representation needs a separate controlled schema specification.

### Q-006 — Orphan encounters

- Never attach an orphan encounter to a generic, first, current, or guessed patient.
- Quarantine the encounter and all dependent clinical, billing, claim, admission, and related records as one traceable chain. Release it only after approval of a valid patient relationship.

## 2. Staff attribution and security

### D-202 / Q-003 — Missing or obsolete actors

- Map verified Classic staff to disabled, non-login historical identities with no renewed permissions or operational access.
- Use one clearly labelled `Legacy Actor Unknown` identity only when no valid actor is determinable. Preserve the original actor reference in the protected crosswalk.
- Never attribute history to the current user, first user, administrator, or importer account.

### Q-304 and D-008 — Staff crosswalk, credentials, and permissions

- Match staff with verified employment identity, never username alone. Duplicate usernames cannot match automatically.
- Provision renewed credentials, roles, and permissions independently. Permanently exclude Classic passwords and permission flags.

## 3. Visits, appointments, and workflow

### D-205 / Q-004 — Enum and status mapping

- Create explicit source-value-to-target-value tables. Preserve exact semantic matches and transform variants only when the specification explicitly approves them.
- Blank, unknown, and out-of-domain values are classified exceptions; convenient defaults are prohibited.

### D-206 / Q-301 and Q-302 — Dates and admission evidence

- Preserve valid historical dates. Never replace dates with migration time, the current date, or guessed values.
- Treat zero or approved placeholder dates as unknown/not evidenced only when the field-specific target design supports it. Quarantine impossible, reversed, or implausible chronology that cannot be represented safely.
- Equal admission and discharge dates alone do not prove an admission. Require corroborating inpatient, bed, ward, or other approved evidence and quarantine ambiguous candidates.

### D-207 / Q-005 — Sentinel values

- Define sentinel handling per relationship. A zero sentinel in one column establishes no global zero-is-null rule.
- Preserve unresolved relationship facts in exception metadata and never create artificial parents.

### Q-010 — Appointments

- Import only appointments with evidence-backed patient, department, creator, and date/time derivations.
- Generate renewed appointment numbers, preserve Classic keys only in the crosswalk, and quarantine unmatched or ambiguous rows.
- Bypass present-day validation only inside the migration persistence boundary.

### D-214 / Q-203 — Migration persistence boundary

- Use migration-specific persistence, not normal operational services, for historical creation.
- Enforce foreign keys, enum validity, uniqueness, precision, and approved logical invariants. Suppress queue, notification, billing, stock, accounting, bed, pathway, and audit side effects.
- Do not weaken normal production validation globally.

## 4. Clinical history and consultation grouping

### D-214 / Q-011 — Grouping and clinician attribution

- Create one historical consultation route/medical record per attendance only when one evidence-backed clinician and compatible department/service context are determinable.
- Quarantine ambiguous grouping or attribution; never infer clinicians from name similarity. Explicitly validate logical one-record-per-route behavior because the database does not fully enforce it.

### D-208 / Q-201 — Plain text, RTF, and catalogue values

- Preserve source representations without silent overwrite. Sanitize RTF before rendering, derive safe display text, retain protected provenance, and report conflicts.
- Never concatenate blindly or execute embedded markup.

### Q-202 — Empty results

- Preserve a valid source-evidenced order/request and represent its result as not evidenced through a migration-specific state/exception.
- Never reinterpret an empty result as normal, negative, pending, cancelled, or completed, and never synthesize a result.

### Q-204 — Historical workflow and audit

- Import only source-evidenced facts and timestamps; do not simulate renewed workflow transitions.
- Record execution, mappings, transformations, failures, approvals, and reconciliation in a separate migration audit. Do not create contemporaneous-looking operational activity for Classic events.

## 5. Insurance and claims

### D-212 / Q-009 — Insurance consolidation

- Create one current patient/provider membership only when deterministic. Preserve every Classic insurance row in protected provenance/history.
- Quarantine conflicting current-membership candidates and never violate target patient/provider uniqueness.

### D-206 / Q-301 and D-205 / Q-004 — Dates and statuses

- Apply field-specific date rules: approved unknown/not-evidenced representation where supported, protected raw evidence, and quarantine for impossible chronology. Never coerce invalid dates.
- Use explicit approved payer/claim status crosswalks. Unknowns are exceptions; `ARCHIVES` never automatically means paid, closed, rejected, or denied, and payer mapping must not invent coverage.

### Q-102 — Claim reconciliation authority

- Preserve Classic `ClaimTotal` as the reported source total and preserve service, investigation, and pharmacy components independently.
- Calculate the component equation with decimal arithmetic. Do not overwrite the reported total.
- Quarantine differences beyond 0.01 from receivable, opening-balance, and GL posting until reviewed. Never invent claim payments or allocations.

## 6. Billing, payments, receivables, and accounting

### D-203 / Q-103 — Financial migration scope

- Preserve approved Classic billing/claim facts with migration provenance and create only clearly labelled, policy-approved opening balances.
- Unsupported allocations, refunds, credit notes, receivable histories, GL postings, and journal histories start empty.
- Never call normal payment, allocation, or accounting-posting services for history; never double count source facts and openings.

### D-107 / Q-101 — Billing equation

- Preserve source `Bill`, `Discount`, `Paid`, and `Balance` independently. Calculate `Bill - Discount - Paid` with decimal arithmetic and treat stored `Balance` as a reported fact, never an overwrite target.
- Rows outside the 0.01 tolerance are reconciliation exceptions and cannot contribute to operational opening AR or GL until reviewed. Preserve mismatch counts and amounts.

The Q-102 claim policy above applies identically in claims and finance.

## 7. Pharmacy, inventory, and stock

### D-204 / Q-104 — Opening stock

- Preserve pharmacy, store, batch, and request snapshots as source evidence; do not reconstruct historical movements.
- Create only clearly labelled opening movements per approved product/location after explicit product, unit, location, and duplicate mapping.
- Negative quantities remain exceptions and are unavailable operationally until reviewed. Do not fabricate valuation or accounting history.

### Q-105 — Expired batches

- Use expiry dates as medication-safety gates. Do not trust the Classic active/expired flag when it contradicts expiry.
- Exclude expired quantities from available opening stock, retain quarantined/expired evidence, and require controlled pharmacist disposition.
- Do not treat batch number alone as unique identity.

## 8. Privacy, retention, and historical audit

### D-209 / Q-303 — Notifications

- Exclude Classic notification content from renewed operational tables. Retain only non-identifying aggregate reconciliation unless a later explicit legal-retention decision is made.
- Never trigger historical notifications.

### Q-305 — Empty/dormant modules

- Exclude the currently empty maternity and occupation tables. A changed pre-cutover fingerprint or nonzero row count is a stop condition requiring scope review.

### D-106 / Q-204 and D-008 — Provenance, audit, and security

- Maintain a separate labelled migration audit for run identity, mappings, transformations, failures, approvals, and reconciliation. Import only evidenced facts and never create false operational audit events.
- Exclude all Classic credentials and permission flags. Provision renewed authentication and authorization separately.

## 9. Incremental synchronization and cutover

### D-210 / Q-401 and Q-402 — Extraction strategies

- Use ordered full snapshots and deterministic row/content hashes for small/master tables without reliable timestamps. Use an approved freeze, snapshot, or CDC strategy for high-volume/high-risk mutable tables where supported. Never infer deletion without evidence.
- Use `(timestamp, primary key)` high-water marks where reliable, overlap plus reconciliation for mutable `ON UPDATE` timestamps, and snapshot/hash comparison where no reliable watermark exists.
- Define the exact strategy per table during Phase 2.

### Q-403 — Freeze and cutover

- Use a staged freeze followed by a final read-only delta with explicit go/no-go thresholds.
- Do not activate while critical reconciliation differences remain unresolved. The project owner is final go/no-go authority; clinical and financial exceptions remain visible and classified.

### Q-404 — Runtime isolation

- Use an environment-isolated runtime. Pause or isolate queues, schedulers, SMS, email, notification dispatch, audit forwarding, billing/accounting posting, stock operations, queue/pathway mutation, and bed-state actions.
- `Model::withoutEvents()` alone is insufficient. Restore integrations only after reconciliation and cutover checks pass.

### D-101 and D-102 — Classic account and guards

- Require a Classic account restricted to `SELECT` and metadata on exactly `uuhms`; the broad-privilege discovery account is not approved for migration execution.
- Every run verifies connection name, exact database name `uuhms`, approved schema fingerprint, expected 55-table/479-column shape, and expected database version/compatibility. Any mismatch fails closed.

## Shared-decision reconciliation

- D-205 uses separate explicit crosswalks for visits, triage, payer, and claim statuses.
- D-206 uses field-specific date rules; no global coercion rule exists.
- D-214 approves migration-specific validated persistence and evidence-based clinical grouping, but does not authorize importer implementation.
- Q-102 preserves source-reported `ClaimTotal` plus independent components; differences over 0.01 are quarantined from posting.
- Q-204 permits only source-evidenced historical facts and a separate migration audit.

## Phase 2 technical obligations

The business policies above are resolved. Phase 2 must still create column-level maps, transformation specifications, exception codes, reconciliation contracts, per-table extraction contracts, and reviewed persistence/isolation designs. Manual-review owners and SLAs must be assigned in those exception specifications. Importer implementation remains blocked until those artefacts and the migration foundation are completed and reviewed.
