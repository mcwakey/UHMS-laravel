# Downstream actor dependency matrix

The complete contract is [downstream_actor_dependencies.json](specifications/downstream_actor_dependencies.json): 17 domain summaries, all 53 installed non-nullable user-FK fields, and 46 relevant nullable/non-FK actor fields. Every field states nullability, FK enforcement, Classic candidate evidence, action-role semantic status, verified-actor requirement, unknown/null rule, blocking scope, exception and reconciliation.

| Domain | Target actor requirement | Classic evidence | Unknown actor | Missing-actor effect |
|---|---|---|---|---|
| Patients | `registered_by` nullable | none | conditional | preserve null/evidence |
| Appointments | `created_by` required; doctor optional | no source actor | prohibited for creator | row blocks |
| Visits | `created_by` required | `attendance.USER_ID` | conditional only by field approval | row/chain may block |
| Consultation routes/medical records | evidence-backed clinician logically/physically required | attendance plus Doctor text | prohibited | consultation aggregate blocks |
| Clinical children | mixed doctor/creator nullability | Doctor text and selected USER_ID | only non-privilege descriptive fields if approved | child or aggregate |
| Investigations/results | requester/performer required | all-zero USER_ID plus mixed Doctor text | prohibited | child/aggregate blocks |
| Prescriptions | doctor required | USER_ID plus Doctor text | prohibited for doctor | prescription/aggregate blocks |
| Admissions | admitted_by required | indirect attendance actor only | prohibited | admission chain blocks |
| Discharge | nullable actor fields | no dedicated source fields | conditional | attribution or invariant-specific |
| Billing | invoice creator required | `billing.USER_ID` | prohibited | billing/opening aggregate blocks |
| Claims | creator required; assigned/approved optional | Doctor text only | prohibited for creator/doctor | claim quarantined from posting |
| Payments/accounting | payment receiver required | unsupported history | prohibited | unsupported histories remain empty |
| Pharmacy/stock | operational actor optional/required by operation | batch/request USER_ID snapshots | prohibited for invented movements | no movement history invented |
| Procurement | purchase creator required | request USER_ID all zero | prohibited | no purchase history invented |
| Migration audit | actual migration executor | migration runtime identity | prohibited | run blocks |
| Operational activity log | causer nullable | actor evidence | no fabricated causer | operational Classic activity remains uncreated |

No universal fallback exists. Nullability never overrides domain policy, and a required FK never justifies an invented actor.

Candidate links such as `attendance.USER_ID` to `visits.created_by`, `billing.USER_ID` to `invoices.created_by`, or `consult_prescriptions.USER_ID` to a prescription actor are **not approved mappings**. They prove at most a Classic user parent. The corresponding field rules keep mapping blocked until the downstream detailed-mapping phase corroborates the exact action-role semantics.

Required fields explicitly include `vitals.recorded_by`, `medication_administrations.administered_by`, `dispensing_records.dispensed_by`, `triages.triaged_by`, `visit_status_logs.changed_by`, clinical/procedure actors, finance actors and stock/procurement actors. No omitted required FK can inherit a default.
