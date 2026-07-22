# Patient dependency-chain contract

## Purpose and boundary

This contract defines how a patient-link exception propagates through Classic UHMS records and how a future controlled process may release those records. It does not map the child domains, implement a quarantine ledger, authorize persistence, or permit source/target writes. Child-domain validity remains the responsibility of its later mapping phase.

The contract consumes the five direct patient relationships and all registered attendance/claim descendants in `PATIENT_RELATIONSHIP_REGISTER.md`, Phase 2A reference crosswalks and `EXTRACT-BEDS`, Phase 2B actor contracts including `TARGET-ACTOR-054` and `STAFF-EXT-*`, and authoritative D-207, D-209, D-213, Q-006, Q-007 and Q-008.

## Chain roots and opaque identity

Raw Classic keys are protected data. The future operational crosswalk/quarantine store may retain them under access control, but routine logs, reports, checkpoints and reconciliation exports must use an opaque chain token.

Every token must be `HMAC-SHA-256` with an environment-controlled secret key, a recorded key version, a recorded canonicalization version, and an explicit domain. Plain SHA-256 is prohibited for Classic keys and other low-entropy patient values. The canonical HMAC message must use unambiguous length-prefixed UTF-8 fields with type and null markers; delimiter concatenation is prohibited.

| Root class | HMAC domain | Canonical protected input | Created when |
|---|---|---|---|
| Patient-rooted | `PATIENT-PRIV-007` / `legacy-patient-chain-v1` | schema, table, typed `patients.PAT_ID` | A source patient exists, whether eligible or quarantined |
| Attendance-rooted orphan | `PATIENT-PRIV-008` / `legacy-attendance-chain-v1` | schema, table, typed `attendance.ATT_ID` | `attendance.PAT_ID` is null, zero or a nonzero orphan |
| Insurance-rooted orphan | `PATIENT-PRIV-009` / `legacy-insurance-chain-v1` | schema, table, typed `insurance.INS_ID` | `insurance.PAT_ID` is unresolved/orphaned |
| Bed-occupancy-rooted | `PATIENT-PRIV-010` / `legacy-bed-occupancy-chain-v1` | schema, table, typed `beds.BED_ID` | The patient-specific occupancy link is unresolved; the bed master is not included |
| Claim-rooted subchain | `PATIENT-PRIV-011` / `legacy-claim-chain-v1` | schema, table, typed `claims.CLAIM_ID` | A claim cannot resolve its attendance, or later release requires a separately held claim subchain |
| Other child subchain | `PATIENT-PRIV-012` / `legacy-patient-child-subchain-v1` | qualified child table, primary-key type and value | A child has an unresolved direct parent or conflicting secondary path after its higher root is known |

Tokens are stable only within the declared domain, key version and canonicalization version. Cross-domain comparison and cross-environment comparison are prohibited. A missing key/version, ambiguous canonicalization or token collision stops release. Tokens are diagnostics and correlation handles; they do not replace the protected operational crosswalk.

## Traversal invariants

1. A direct `PAT_ID` predicate is authoritative for Classic patient-chain membership where present. Names, phone, OPD, email, national identifier, insurance number and similarity scores may not replace it.
2. An attendance child derives its root only through `child.ATT_ID = attendance.ATT_ID`, followed by `attendance.PAT_ID = patients.PAT_ID` where that second edge is valid.
3. A claim child derives its root only through `child.CLAIM_ID = claims.CLAIM_ID`, then `claims.ATT_ID = attendance.ATT_ID`, then the valid attendance-to-patient edge.
4. `consult_prescriptions.BILL_ID`, `serv_results.SCL_ID` and `attendance.BED_ID` are secondary consistency links. They may hold a subchain when conflicting, but may never establish or override the patient root.
5. If two evidenced paths resolve to different attendances or patients, quarantine the affected subchain under the original root, record a cross-link conflict and stop. Do not select a preferred path, merge tokens, or union chains.
6. Reverse traversal through shared reference data is prohibited. In particular, never traverse bed to every attendance, product to every prescription, service/catalogue to every encounter, provider to every membership, or user to every patient.
7. Reference masters may be mapped independently. Only the patient-specific use, membership or occupancy edge is held with the patient chain.
8. Every source row has one primary root/disposition. Duplicate-alias and suspected-duplicate statuses are secondary flags and never create a second chain.
9. Never create an artificial patient, attendance, claim, provider, catalogue record, actor or bed to make a chain traversable.

## Chain topology

```text
patient
  -> attendance / visit candidate
       -> appointment
       -> billing
       -> claim
            -> claim diagnosis
            -> claim prescription
            -> claim procedure
            -> claim investigation
       -> consultation grouping / medical record
            -> complaint, history, diagnosis
            -> prescription, procedure, service/order, treatment plan
            -> result, nursing note, vitals, administered treatment
       -> admission candidate
            -> patient-specific bed/occupancy evidence
  -> insurance membership candidate
  -> patient-specific bed occupancy snapshot
```

This topology records dependency order only. It does not assert that Classic has a visit, medical-record, consultation-route or admission entity. Later phases must create those aggregates only from approved evidence.

## Blocking scopes

### Patient-root blockers

The following block the patient row and every patient-rooted dependent record:

- missing, invalid or unrepresentable required patient identity;
- unresolved source remediation;
- ambiguous or conflicting existing-target crosswalk/identity decision;
- patient extraction identity, schema/fingerprint, provenance or privacy failure;
- target constraint/uniqueness failure that prevents the patient mapping.

Dependent records remain associated with the original opaque patient-chain token. They may not be reassigned to another Classic or target patient.

### Non-chain patient flags

A duplicate OPD alias alone blocks only that alias. The otherwise eligible patient and eligible children remain candidates. A suspected duplicate alone is a protected review flag; it causes no automatic link, merge, patient block or chain coalescence.

### Attendance-root blockers

Null, zero or nonzero-orphan `attendance.PAT_ID` creates an attendance-rooted quarantine. It blocks the attendance plus appointment, clinical, billing, claim, admission and medication descendants reached through registered exact predicates. It is never attached to a generic, first, current, system or guessed patient.

An orphan child `ATT_ID` holds that child/subchain even when some other field resembles an encounter. An orphan claim `ATT_ID` holds the claim and every claim descendant. An orphan child `CLAIM_ID` holds that claim-child subchain.

### Insurance and occupancy blockers

An orphan `insurance.PAT_ID` holds that insurance row and any evidenced descendants only. It does not select a patient using company, membership number or demographic similarity.

`beds.PAT_ID = 0` means occupancy relationship not evidenced under the approved Phase 2A field rule. It blocks no bed reference master and creates no patient. A matched numeric occupancy link remains unreleasable as admission evidence until the later admission contract corroborates attendance, chronology, ward/bed and actor evidence.

### Domain-local blockers

After the patient edge is repaired, a child may remain held for its own actor, department, service/catalogue, chronology, content, target constraint, insurance, billing or claim-reconciliation failure. Repairing a patient link never waives:

- Phase 2B verified action-role semantics or prohibited actor fallback;
- Phase 2A target reference crosswalks;
- D-214/Q-011 consultation grouping evidence;
- D-206/Q-302 admission evidence and chronology;
- D-107/Q-101 billing decimal reconciliation;
- Q-102 claim component reconciliation;
- target unique, foreign-key and service-only invariants;
- historical side-effect isolation.

## Topological release and no-partial-release rule

No descendant may release while its required ancestor relationship is unresolved. “No partial release” means zero descendants escape an unresolved patient, attendance or claim root. It does not mean that fixing a root automatically releases every child.

Release proceeds only in this order:

1. Verify the same source/schema snapshot, chain token domain/key/canonicalization versions, complete edge classifications and protected provenance.
2. Establish a valid patient relationship through evidenced remediation or a controlled identity decision; persist/resolve the protected patient crosswalk first.
3. Reconcile and release a direct patient edge: attendance, insurance membership link, or patient-specific occupancy link.
4. For attendance, establish the target visit/encounter candidate and required actor/reference/date contracts.
5. Establish a consultation/medical-record, claim, billing or admission aggregate only when that domain's evidence and constraints pass.
6. Release descendants only after their immediate parent has released and their own actor/reference/content/chronology/financial/target rules pass.
7. Reconcile the original root token, every traversed edge and all held/released/failed descendant counts before advancing the checkpoint.

For claim descendants the fixed order is `patient -> attendance -> claim -> claim child`. For clinical descendants the fixed order is `patient -> attendance -> evidence-backed consultation/medical-record aggregate -> clinical child -> reference/result`. For admission candidates it is `patient -> attendance -> verified admitted actor -> corroborated admission chronology -> mapped ward/bed -> admission candidate`. For insurance it is `patient -> insurance patient edge -> provider crosswalk -> member/date/status/consolidation rules -> membership candidate`.

Release is atomic at each immediate parent/child edge outcome. A released parent and locally quarantined child is permitted and remains explicit; a released child with an unresolved parent is prohibited. Failure or rollback may not move a child to a different root or lose its prior classification.

## Domain contracts

### Attendance and clinical history

The attendance edge is the sole principal Classic encounter-to-patient bridge. Phase 2B `STAFF-EXT-002` co-snapshots `attendance.USER_ID`, but a matching Classic user proves only a parent record, not the exact target creator/clinician role. D-214/Q-011 allows one historical consultation grouping per attendance only when one clinician and compatible department/service context are evidenced. Ambiguous grouping holds the consultation aggregate and descendants.

Results are rooted by `serv_results.ATT_ID`. The sparse `SCL_ID` relationship is consistency evidence only. Empty/malformed result content is not synthesized, and no result may be reinterpreted as normal, negative, pending, cancelled or completed.

### Appointments

Classic `appointement` has no direct patient, department or creator field. It may inherit a patient only through a valid attendance edge. Target patient, department, creator and date/time requirements all remain mandatory. Phase 2B prohibits `Legacy Actor Unknown` for the required appointment creator, so a valid patient chain alone is insufficient.

### Billing and claims

Billing and claims are attendance descendants. `billing.USER_ID` consumes `STAFF-EXT-004`; claims actor text consumes `STAFF-EXT-011`. Neither is an automatic target actor mapping. Billing must preserve `Bill`, `Discount`, `Paid` and reported `Balance` and pass the 0.01 D-107/Q-101 equation before operational opening treatment. Claims preserve `ClaimTotal` and independent service/investigation/pharmacy components and pass the 0.01 Q-102 equation before posting. Patient-link remediation changes none of these facts.

### Admissions and beds

Classic has no admission table. Attendance dates/status/`BED_ID` and mutable `beds` occupancy are evidence candidates only. Equal admission/discharge dates do not prove admission. The bed master may migrate independently through Phase 2A; historical patient data must not occupy or release a renewed bed, and current occupancy is reconciled only at controlled cutover.

### Patient-linked medication and stock

Only `consult_prescriptions -> attendance -> patient`, `claims_prescriptions -> claims -> attendance -> patient`, and `treatment -> attendance -> patient` are evidenced medication paths. `med_pharm` and `med_store` are stock/price snapshots with no patient or attendance predicate. `billing.BillDesc` and other text must not be mined to invent one. A medication child also requires the Phase 2A product map; migration history must trigger zero stock movement.

### Notifications and dormant maternity

`notifications` has no patient/attendance predicate. Its content is excluded under D-209/Q-303 and may not be inspected for patient inference or imported into operational tables. `mat_family` and `mat_obstetrics` have direct but zero-cardinality patient predicates and are excluded at the captured baseline under D-209/Q-305. Any nonzero row or fingerprint change is a stop.

## Chain reconciliation

For each registered edge:

`child_rows = null + field_approved_sentinel_or_chain_not_evidenced + matched_parent + orphan_parent + failed_extraction`

For every future dry-run and release event, record aggregate counts for:

- patient-root chains held, released and failed;
- attendance-root orphan chains separated by null, zero and nonzero orphan reason;
- insurance-root and occupancy-root chains held/released;
- descendant counts per opaque chain token in the protected ledger, never in repository artifacts;
- each domain's held, released and failed partitions;
- prescription/bill, result/order and attendance/bed cross-link conflicts;
- rows that resolve to more than one root;
- before/after target counts and protected hashes;
- financial reconciliation independently from patient-link reconciliation.

Mandatory zero assertions are:

- descendants released before an unresolved parent = 0;
- guessed/reassigned patient links = 0;
- artificial parents = 0;
- cross-chain unions = 0;
- records without exactly one primary disposition = 0;
- descendants omitted without a classified outcome = 0;
- automatic patient merges or chain coalescence = 0;
- historical notifications, stock movements, bed operations, billing/claim postings or operational activity triggered = 0.

Any nonzero reconciliation difference, token/version mismatch, unexplained count/hash change, cross-link disagreement or partial-release breach stops the affected release.

## Evidence gaps and release blockers

Phase 3 foundation and later domain work must close these gaps before affected chains can release:

1. Privacy-safe conditional descendant counts for matched, quarantined and orphan attendance roots, including overlap proof.
2. Same-attendance/patient consistency for the six `serv_results.SCL_ID` order matches.
3. Same-attendance/patient consistency for matched prescription-to-billing links.
4. Same-patient consistency for the 120 matched attendance-to-bed links; bed traversal remains prohibited regardless.
5. A protected chain/quarantine ledger, crosswalk, exception ledger, checkpoints and release audit with exactly-once state transitions.
6. Target-side relationship validation for the installed patient-shaped columns without foreign keys.
7. Later-domain approval of child-field semantic sentinels where Phase 2C records only “relationship not evidenced for chain traversal.”
8. A consistent freeze/snapshot coordinate; the source has no general change/delete journal, so stale parent/child snapshots cannot be released.

SLA expiry never releases a chain, invents a value, defaults an actor, chooses a patient, or discards a record.
