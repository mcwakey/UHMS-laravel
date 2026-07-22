# Preliminary migration dependencies

## Dependency principles

- Target reference/master records are reconciled before transactional children.
- Source IDs enter an explicit mapping registry; target IDs remain target-owned.
- Historical facts load without operational transitions or generated side effects.
- Financial, claims, stock, and accounting graphs cannot be partially committed as “successful.”
- Forward/polymorphic links are backfilled after both endpoints exist.
- Current operational state is reconstructed only after history is complete and reconciled.

## Approved Phase 2 detailed-mapping sequence

1. Organisation and reference crosswalks
2. Staff identity and historical attribution
3. Patient identity and registration
4. Patient aliases, contacts and demographic children
5. Patient insurance
6. Patient pilot mapping contract
7. Visits and workflow
8. Clinical history
9. Admissions
10. Billing and claims
11. Pharmacy and stock
12. Incremental synchronization and cutover

This order authorizes mapping specifications only. The later implementation order below remains conditional on completed transformations, exception codes, reconciliation contracts, extraction rules, and a reviewed migration foundation.

## Later implementation dependency order

| Stage | Domains | Preconditions | Reconciliation/gate |
|---:|---|---|---|
| 0 | Safety preflight | Dedicated Classic SELECT-only account; `uuhms` fingerprint; approved non-production target | Prove rejected schemas/writes and target drift fail closed |
| 1 | Allow-listed renewed reference seeds/config | Actual target verified; seed ownership approved; `DatabaseSeeder` prohibited | Natural-key catalogue/config inventory with no demo users, patients, or appointments |
| 2 | Departments, staff actors, specialties, providers | Accepted identity/actor policy plus completed Phase 2 crosswalk | Every required actor/reference has mapping or explicit exception |
| 3 | Clinical/service/diagnosis/procedure catalogues | Deduplication/version rules | Counts, duplicates, rejected/orphan references |
| 4 | Products/drugs, suppliers, stock locations, wards/beds, accounting references | Stock/finance scope direction | Natural-key maps and precision verified |
| 5 | Patients | Accepted patient policy plus completed number/identity/required-field specification | Count, unique identifiers, field exceptions, rerun/resume |
| 6 | Contacts/aliases/insurance | Patient maps, provider maps, date and target-uniqueness policies | Resolve duplicate normalized OPD aliases and multiple source insurance rows per patient/provider |
| 7 | Approved appointments without forward links | Patient/department/user maps plus inheritance/quarantine rule | Patient/department/creator/time evidence; 67 unmatched links classified |
| 8 | Visits/attendance core | Patient/actor/department/status maps | Visit counts, dates, duplicate-day policy, orphans |
| 9 | Visit history/context | Visit maps | Status/pathway/insurance/payment context, no live queues |
| 10 | Consultation routes and medical records | Visits/services/departments/doctors plus approved grouping/clinician rules | Logical one-record/route rules and no cross-encounter/clinician misattachment |
| 11 | Complaints/history/diagnoses/treatments | Medical-record and catalogue maps | Clinical child counts and text precedence |
| 12 | Prescriptions/procedures/investigations/results/vitals | Products/services/criteria/actors | Order/result links, parsing, empty-result exceptions |
| 13 | Admissions/nursing/discharge | Visits/patients/beds/actors; date policy | Episode chronology and terminal/current state |
| 14 | Invoice/items/renderings/usages | Visits/services/payers; approved finance strategy | Exact item/header equations; no GL/notifications |
| 15 | Receivables/claims/items/status history | Invoice/patient/visit/provider/clinical maps | Claim totals and link reconciliation |
| 16 | Source-evidenced settlement facts; renewed allocations/refunds/credits start empty unless evidenced | Invoice item/AR maps and approved representation | No invented payment-detail history; supported facts/opening position exact |
| 17 | Source-evidenced procurement/stock facts and approved opening position | Product/supplier/location maps; approved stock strategy | No claim of a complete Classic movement ledger; quantity/value/batch reconciliation |
| 18 | Source-evidenced finance facts and approved opening position; renewed journals/subledgers start empty unless evidenced | Approved finance strategy, periods/accounts | No invented double-entry history and no double counting |
| 19 | Deferred links | All endpoint maps | No unresolved approved forward/polymorphic links |
| 20 | Cutover state | Final delta complete | Sequences, active beds/queues, appointments/reminders, invoice/AR/GL, stock, claims |

## Cycles and deferred relationships

- Appointment may point forward to a visit, consultation route, and medical record: load appointment first and backfill.
- Visits and consultation routes can create billing, while invoices reference visit/service sources: load historical clinical graph without billing side effects, then approved financial graph, then source links.
- Admission/bed state is cyclic operationally: load bed identities and historical admissions first; derive one approved current occupancy state at cutover.
- Claim depends on visit/patient/provider and may depend on invoice; claim children additionally depend on clinical/product catalogues.
- Target stock movement can originate in procurement/dispensing/clinical usage and post accounting, but Classic has no complete movement ledger. Only source-evidenced facts or a clearly labelled approved opening position may be persisted; operational source/GL histories are not synthesized.

## Incremental dependency

The final synchronization cannot rely only on timestamps. Twenty-three source tables lack timestamps; nine use mutable `ON UPDATE` fields. The accepted policy requires a Phase 2 per-table strategy: reliable `(timestamp, PK)` high-water marks, overlap/reconciliation for mutable timestamps, ordered full snapshot plus deterministic content hash where no reliable watermark exists, or an approved freeze/snapshot/CDC approach for high-risk mutable tables. Deletions cannot be inferred without evidence. The keyless `serv_options_cri` and `serv_options_out` tables require snapshot identity/order and cannot use a simple last-key checkpoint.

## Current written handoff

Phase 1B produced the source/target catalogues, query manifest, installed non-production target inspection, and nine governance packs. The project owner accepted all pack outcomes directly on 2026-07-21; no workshop occurred. Every domain may now enter detailed mapping in the approved sequence above. No implementation is authorized: importers remain blocked until column maps, transformations, exceptions, reconciliations, extraction contracts, and the reviewed migration foundation exist.

## Stop conditions

Stop a domain/run on schema fingerprint change, unexpected enum, missing required approved parent, duplicate target natural/unique key, financial/stock mismatch, prohibited side-effect mutation, checkpoint inconsistency, or target production detection. Record a classified failure; never continue with a convenient default.
