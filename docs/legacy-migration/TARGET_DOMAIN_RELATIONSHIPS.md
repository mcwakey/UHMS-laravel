# Target domain relationships

## Core target graph

```text
users/departments/reference catalogues
  -> patients -> contacts, aliases, insurances, privacy/merge metadata
  -> patients + users -> visits -> histories, queues, insurance/payment context
  -> visits + departments/services/users -> consultation routes
  -> routes + visits + patients + doctors -> medical records -> clinical children
  -> visits + patients + beds + users -> admissions -> location/nursing/discharge
  -> visits + patients + services -> invoices/items/renderings/insurance usages
  -> invoice items -> receivables/claims -> payments/allocations/claim payments
  -> products + stock locations -> movement ledger -> stock balances/batches
  -> financial source graphs -> journal entries/lines -> reconciliation
```

This is the renewed target dependency graph, not evidence that Classic contains every node. In particular, renewed payment allocations/refunds/credits, double-entry journals/subledgers, and a complete stock-movement history start empty unless directly source-evidenced; approved opening positions must be clearly labelled.

## Confirmed required relationships and invariants

| Aggregate | Required links/invariant | Enforcement caveat |
|---|---|---|
| Patient | unique patient number; registrar nullable | Ghana Card nullable-unique; sequence must be reconciled |
| Visit | patient, creator, unique visit number, date/type | patient/creator delete cascades; service rejects duplicate patient/date |
| Appointment | patient, department, creator, date/start time | historical dates rejected by web validation; forward links nullable |
| Medical record | visit, patient, doctor | one-per-route relationship is not DB-unique |
| Consultation route | visit, department/service/doctor associations | activation mutates other routes, queues, visit, medical record, billing |
| Admission | visit, patient, bed, admitting user, date | one-active admission is service-only; bed occupancy is operational |
| Claim | provider, patient, visit, creator, dates/amount | invoice/doctor nullable; service derives status history/current context |
| Invoice item | invoice and pricing/payer/source snapshots | conditional DDL; totals/receivables are derived and mutative |
| Payment allocation | payment, invoice item, decimal amount | exact line allocations and payer constraints apply |
| Stock movement | product, location, type/direction, quantity/date | ledger is canonical; balances rebuild from movements |
| Journal | fiscal year/period, date, description, balanced lines | posting requires open period and current actor/time |

## Deferred-link strategy

- Load appointments initially without visit/consultation-route/medical-record links; backfill after those target maps exist.
- Create all parent/reference maps before child clinical rows.
- Resolve polymorphic billing/rendering/source links after source and destination aggregates exist.
- Load financial headers/items before receivables, claims, payments, allocations, and posting links.
- Reconstruct active bed/queue states only after historical graphs are complete and approved.

## Deletion and idempotency risk

Many target relationships cascade on deletion, and major aggregates soft-delete. A rerun must locate mapped target records and verify/update according to transformation version; it must never delete a patient/visit and recreate it as a shortcut. Any correction path needs immutable run/record maps and child-aware reconciliation.

## Logical invariants the importer must validate explicitly

- At most one approved active invoice per visit under the installed-schema rules.
- At most one active admission per patient/visit and consistent bed state.
- One logical medical record per consultation route where applicable.
- Valid target enum and workflow values without invoking transitions.
- Actor, department, service, insurance, and catalogue links are authorized/valid.
- Invoice/item/receivable/payment equations reconcile exactly.
- Journal debit equals credit and fiscal references exist.
- Stock balances equal approved movement ledger or approved opening records, never both.
