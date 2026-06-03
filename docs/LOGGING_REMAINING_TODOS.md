# Logging / Audit Trail — Remaining TODOs

The patient-timeline root cause is fixed and context now auto-attaches. What
remains is **breadth of coverage** — making sure every important action actually
calls `ActivityLogService` (or a service/observer that does).

## ✅ Done: stock-consumable usage → patient timeline

Patient-related consumable usage now writes a `STOCK / CONSUMABLE_USED` activity
log with explicit context (patient_id, visit_id, emergency_case_id,
investigation_request_id, procedure_request_id, product_id, stock_location_id,
quantity, source_type/source_id, invoice_item_id) and shows on the patient
profile. Wired at both write paths — `ConsumableUsageService::recordUsageForSource`
(investigation/procedure/ward) and `EmergencyConsumableService::useConsumable`
(emergency) — via `ConsumableUsage::toActivityContext()`. `buildProperties` now
persists these context keys. Generic stock movements (no patient) correctly do
**not** attach. Tests: `tests/Feature/ConsumableUsageAuditLogTest.php` (5).
Remaining stock-context follow-ups: MAR drug-administration stock-out (logged in
the MAR module — confirm patient/visit context), and any future non-central
consumable path.

## ✅ Done: consultation clinical entries → patient timeline

`MedicalRecordEntryLogService` now mirrors every clinical entry change
(complaint / HOPC / examination / diagnosis / treatment / prescription /
investigation — create/update/delete/correct) to the central activity log with
full context + old/new values, so they appear on the patient profile timeline.
Session lifecycle (`SESSION_STARTED/RESUMED/COMPLETED`) and `PATTERN_APPLIED` are
logged too. Tests: `ConsultationClinicalLogTest` (7) + a session assertion. See
`docs/LOGGING_CONSULTATION_BURN_DOWN_REPORT.md`. Follow-ups: individual
pattern-created non-complaint records, prescription delete, clinical tasks/
follow-up, note/summary, session lock/reopen/contributor.

## Burn-down order (next, module-by-module — verify each in global + patient logs)

1. ~~Consultation clinical entries~~ ✅ done.
2. **Investigations** (accept/reject, sample, result entered/updated/verified/
   rejected, printed) — **next up**; avoid duplicating consultation-entry logs.
3. MAR · 4. Pharmacy · 5. Procedures/Theatre · 6. Billing/Claims ·
7. Emergency/Admission · 8. Remaining stock/admin.

Do **not** wire all 75 flagged controllers at once; one module, with a test that
the action appears on both the global log and (where patient-related) the patient
timeline.

## Actions still likely unlogged

`php artisan logs:audit` flags **75 controllers** with mutating actions and no
logging marker. Many delegate to services that log; triage the list and add
explicit logs where genuinely missing. Priority order:

1. **Clinical create/update/delete** — consultation entries (complaint/HOPC/exam/
   diagnosis/treatment/note), clinical tasks, contributors. Prefer service-level
   logs so reason/context are captured (not just observers).
2. **Investigations** — accept/reject, sample collected, result entered/updated/
   verified/rejected, result printed.
3. **Procedures / theatre** — accept/schedule/room+team assign/pre-op/anaesthesia/
   operative/recovery notes/complete/cancel.
4. **MAR** — schedule generated, dose administered/held/missed/refused/skipped,
   reaction, stop, correction (some already logged — confirm patient/visit context).
5. **Pharmacy** — review, bill-selection, quantity-reduce, dispense, partial,
   cancel/correct, return.
6. **Stock** — purchase orders, transfers, requisitions, adjustments, returns,
   consumable usage (attach patient_id/visit_id when used for a patient).
7. **Service rendering** — started/rendered/not-rendered/cancelled.
8. **Admin** — role/permission/module/setting changes, admin password resets.

## Context still to attach

- Stock consumable usage tied to a patient should pass `patient_id`/`visit_id` so
  it lands on the patient timeline (the resolver can't infer it from a stock model
  alone).
- Add more subjects to `ActivityContextResolver` as needed (e.g. blood units →
  via request/visit).

## Modules needing deeper observer coverage

Consider observers for `Patient`, `Visit`, `Admission`, `EmergencyCase` create/
update **in addition to** service logs (observers catch out-of-workflow edits).
Do not rely on observers alone for workflow actions (no reason/context).

## Audit command limitations

`logs:audit` is a heuristic: it inspects controller source only, so it
false-positives when logging happens in a delegated service and false-negatives
when a controller merely references the service without logging a given branch. It
does not (yet) verify patient/visit context is attached per action.

## Severity / filter caveat (MariaDB 10.1)

`getPatientTimeline()` filters by module/action/user/date via real columns.
**Severity** lives in `properties` (longText) and can't be filtered in SQL on
MariaDB 10.1 — filter in PHP, or promote `severity` to a column later.

## Future CI / logging gate

- Add `composer logs:audit` and a non-blocking CI step (mirror `ui:audit`).
- Once the 75-controller list is burned down, enable `logs:audit --fail` for new
  controllers.
- Add a tiny test per high-risk workflow asserting it writes an activity log with
  patient/visit context (a few exist; extend to consultation/MAR/lab/theatre/blood).

## Deploy checklist

1. `php artisan migrate` (adds `patient_id`/`visit_id` columns).
2. `php artisan logs:backfill-context` (one-time; recovers historical context).
3. `php artisan db:seed --class=RoleSeeder` (or sync) for the new `logs.*`
   permissions.
