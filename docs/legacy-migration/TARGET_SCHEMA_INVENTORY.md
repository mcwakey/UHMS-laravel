# Renewed UHMS target schema inventory

## Evidence boundary

This inventory is derived from repository migrations, models, services, form requests, enums, seeders, observers, listeners, and schedulers. It did not inspect or modify a renewed production database.

**Phase 1B actual-target closure:** local non-production `uhms_clean` was inspected read-only on 2026-07-21. It contains 335 tables and 5,347 columns with structural/ledger fingerprint `12e3a4c6ca80a0c1a54ea9267d9c4935453b7a36d27c7f8ad900062c1c12bff0`. The full installed catalogue and drift analysis are in `TARGET_INSTALLED_SCHEMA_INVENTORY.md` and `TARGET_SCHEMA_DRIFT_REPORT.md`; they supersede static estimates where they conflict.

**Confirmed:** the repository had 306 migration files, approximately 322 statically created table names, 315 models, 467 services, 75 form requests, and 156 enum classes at discovery time. `database/schema/mysql-schema.sql` contains only 106 tables and its migration ledger ends at `2026_05_13_000001_create_theatre_procedure_tables`; repository migrations continue through 2026-07-20. At least 216 statically created tables are absent from the dump.

Seventy-three migrations use conditional `Schema::hasTable()`/`hasColumn()` logic (481 references), and some raw-DDL migrations catch failures. The installed schema can therefore vary by database history and driver. The Phase 1B preflight confirmed one missing permission-data migration and one failed/fallback unique-index outcome; preflight remains mandatory for every later environment.

## Target domains

| Domain | Representative target structures | Source coverage assessment |
|---|---|---|
| Identity/access | users, roles, permissions, departments, designations, user-department/module assignments | Partial: Classic users/departments, but not renewed authorization model |
| Patient identity | patients, contacts, aliases, insurance, merges, privacy, archives, number sequences | Partial: patient/insurance/contact snapshots; no merge/privacy workflows |
| Encounters | appointments, visits, queues, status/department/pathway history, consultation routes | Partial: attendance/appointments and clinical rows; no equivalent route/queue event model |
| Core clinical | medical records, complaints/HOPC, exams, diagnoses, treatments, prescriptions, tasks | Partial to strong but relationships/content are inconsistent |
| Investigation/lab | catalogues, criteria, orders, samples, results, analyzers | Partial: services/criteria/orders/results; no analyzer integration history in `uuhms` |
| Admissions | wards, beds, reservations, admissions, location history | Partial: attendance/bed fields and limited nursing/treatment data |
| Emergency cases | emergency workflow/cases/bays | No approved source rule; triage/status labels alone are insufficient evidence |
| Maternity/newborn | maternity, labour, delivery, newborn, postnatal | No populated source: both Classic maternity tables empty |
| Blood bank | donors, inventory, requests, crossmatch/transfusion | No known Classic source |
| Billing/AR | invoices/items/discounts, renderings, receivables, sponsor/corporate, payment policy | Partial: billing snapshots and payer categories; target model is much richer |
| Payments/claims | payments/allocations/refunds/credits, claims/items/status/payments | Partial: paid/balance snapshots and claim graph; allocation/refund history absent |
| Inventory/procurement | suppliers, purchase/receipt/return/payables, products, stock ledger/balances/batches/requisitions | Partial: catalogues, snapshots, batches, requests; no complete movement ledger |
| Accounting/finance | chart, fiscal periods, journals, posting/reconciliation, budgets, bank, assets, tax/payroll | Minimal: small accounts/bank/petty/supplier snapshots; no renewed-style ledger graph |
| Human resources | employees, shifts, staff attendance, leave, payroll foundations | No Classic HR source; Classic `users` supports attribution only |
| Audit/compliance | activity logs, retention, privacy overrides | No general Classic audit/change journal |
| Integrations/notifications | providers, webhooks, SMS, notifications/digests/preferences | Classic has operational notifications only; no compatible integration configuration |
| Journey/analytics | predictions, handoffs, events, snapshots | No known Classic source |
| Front desk operational logs | workspace/front-desk activity | No approved Classic source |

## Seeded target reference order

`database/seeders/DatabaseSeeder.php` confirms that renewed setup includes roles, visit vocabularies, locations, departments, modules, insurance, suppliers, accounting charts/templates, service catalogue, investigation criteria, product/drug spine, pricing, payment policies, clinical catalogues, wards/beds, lab/analyzers, and medical patterns. Legacy reference records must be reconciled against destination seeds by approved natural keys; target IDs must remain target-generated.

`DatabaseSeeder` is evidence of ordering only and is prohibited as a migration bootstrap: it also invokes `DemoUserSeeder`, `PatientSeeder`, and `AppointmentSeeder`. Phase 1B must define an explicit allow-list of approved reference seeders so synthetic people and encounters cannot contaminate reconciliation.

## Schema integrity caveats

- Many aggregate tables soft-delete; source inactive/cancelled state is not automatically `deleted_at`.
- Numerous clinical foreign keys cascade from patients, visits, medical records, or users. Idempotency cannot delete/recreate parents.
- Enum-backed model casts can fail on unknown stored strings.
- Money is commonly decimal(12,2); stock quantities can be decimal(14,4).
- Some MySQL/MariaDB route/medical-record link migrations lack full foreign-key enforcement.
- One medical record per consultation route and one active admission per patient/visit are service-enforced, not fully database-enforced.
- The active-invoice migration can fall back from a generated-column unique index to a non-unique index, leaving a service-only invariant.

## Destination domains with no known legacy source

These require a later **seed/configure/derive/start-empty/exclude** decision, not invented migration data:

- roles/permissions/modules and renewed authorization assignments;
- employee, shift, staff-attendance, leave, and payroll operations;
- privacy directives/overrides, patient merge workflows, archive/financial-risk history;
- payment timing/financial clearance and visit payment arrangements;
- journey predictions, handoffs, and analytics;
- service-rendering operational workflow and department work queues;
- integration provider credentials, callbacks, and events;
- notification preferences/digest configuration;
- blood bank;
- populated maternity/newborn/postnatal records;
- emergency-case workflow unless a source-evidenced rule is approved;
- detailed receivable workbench, refunds/credit notes, and allocation history;
- complete accounting, budgets, tax, payroll, assets, and bank reconciliation;
- complete procurement/GRN/return/payables workflow;
- front-desk operational/audit logs;
- analyzer integration data.

## Required later preflight

The Phase 1B command compares actual non-production target tables, columns, indexes, foreign keys, generated columns, triggers, scheduled events, stored routines, migration ledger, engine/version/settings, and conditional-migration outcomes against the proposed contract. The current installed target has zero triggers, zero scheduled events and zero stored routines. Future runs must fail closed on drift; they must not auto-migrate production as part of data import.
