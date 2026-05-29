# Visit Status and Patient Pathway Implementation Report

## Purpose

This report documents the implementation of the corrected UHMS visit, emergency, admission, billing, bed-count, consumable-count, and patient pathway workflow.

The central correction is that `visits.status` now represents the global clinical state of the visit. Department movement is tracked separately through sessions, requests, queues, invoice items, and the new pathway ledger.

## Previous Problems

- Patients with an active admission could still be registered for a normal outpatient visit without a clinical override trail.
- Creating an emergency case from an existing visit did not reliably create a linked Emergency Session.
- Department movement was mixed into the global visit status flow through statuses such as laboratory, pharmacy, billing, investigation, and procedure.
- A patient pathway could not show the real chronological parcours across consultation, emergency, investigations, pharmacy, procedures, billing, and admission.
- Previous-day outpatient sessions remained editable unless manually completed.
- Emergency bed occupancy and emergency daily consumables were not billed with the same daily lifecycle discipline as admissions.
- Emergency-to-admission handoff did not clearly end emergency bed/consumable counting and start admission bed/consumable counting.
- Admission billing could create billing lines outside the visit invoice lifecycle.

## Deprecated Global Status Usage

The following statuses remain in code only for backward compatibility and old data, but new workflow code no longer uses them as global visit movement targets:

- `WAITING_INVESTIGATION`
- `LAB`
- `PHARMACY`
- `BILLING`

New service-level guards reject these statuses as global visit transitions. Investigation, lab, pharmacy, billing, procedure, and theatre movement is represented by requests, workflow records, invoice items, sessions, and pathway events.

## New Visit Status Flow

The corrected status model keeps `visits.status` focused on the patient's overall care state:

- `REGISTERED`
- `WAITING_TRIAGE`
- `TRIAGE`
- `WAITING_CONSULTATION`
- `CONSULTING`
- `ACTIVE`
- `EMERGENCY`
- `ADMITTED`
- `COMPLETED`
- `CANCELLED`
- `DECEASED`

Department-level activity no longer moves the visit away from a valid clinical state. For example, a patient can be `CONSULTING`, go to investigation or pharmacy, and return to the consultation without the visit becoming a lab/pharmacy/billing visit.

## Patient Pathway Mechanism

A new `visit_pathway_events` table and `VisitPathwayService` provide the patient parcours ledger.

Each pathway event can link to:

- visit
- patient
- department
- route/session
- source model, such as emergency case, invoice, request, or admission
- creator
- metadata and notes

Visit Preview now includes pathway events so the clinical story can show actual movement without overloading the visit status column.

## Active Admission Guard

Visit creation now checks the patient's active admission state before creating a normal outpatient visit.

The guard blocks new OPD visits when the patient has an active admission, unless the user has `visits.create_while_admitted` and provides an override reason. The guard is wired through the visit service and exposed in the visit creation UI with a warning and reason field.

Active admissions include admitted and on-leave admissions that have not been completed, discharged, cancelled, transferred out, or marked deceased.

## Emergency Session Fix

Emergency cases can now be created from an existing visit while preserving the same visit and invoice lifecycle.

When an emergency case is created, the system creates or links an Emergency Session using the existing `visit_consultation_routes` session mechanism with:

- `session_type = EMERGENCY`
- `emergency_case_id`
- `visit_id`
- `patient_id`
- emergency department context

The Emergency Session is visible in the consultation session list and is included in the visit pathway/timeline context. Active emergency sessions remain editable for authorized emergency work; completed or locked sessions are read-only unless the user has correction permission.

## Direct Department Routing

Investigation, pharmacy, billing, procedure, and other department routing no longer requires a misleading global visit status.

Patients can be sent directly to service departments, or can leave and return to consultation, while the visit remains in its valid global clinical state. Requests, route records, billing selections, procedure records, and pathway events carry the movement details.

## Outpatient Auto-Lock

The new `OutpatientSessionAutoCloseService` completes and locks stale previous-day outpatient consultation sessions.

The scheduled command is:

```bash
php artisan outpatient-sessions:auto-complete
```

It excludes emergency/admission work, sets session completion and lock metadata, updates eligible visits to completed, and records pathway events. The command is registered in the scheduler.

## Locked Session Corrections

Locked consultation sessions are protected from normal editing. Users with `visits.reopen_locked_session` may perform correction workflows where allowed.

This keeps completed sessions stable while preserving an auditable clinical correction path.

## Emergency Bed and Consumable Billing

Emergency bay assignment now starts emergency bed and emergency daily consumable charge tracking.

Emergency release or physical transfer finalizes the active emergency charge records and bills them into the same visit invoice through `BillingService`.

Emergency daily billing lines use visit invoice source tracking so they can be traced back to the emergency charge records.

## Emergency-to-Admission Transition

Emergency disposition to admitted no longer prematurely releases the emergency bay. The emergency bed and emergency daily consumable charge remain active until ward admission bed assignment occurs.

When admission bed assignment is completed:

- emergency bed charge is finalized and billed
- emergency daily consumable charge is finalized and billed
- emergency bay is released
- admission bed charge starts
- admission daily consumable charge starts
- admission billing lines are written through `BillingService`
- the same visit invoice is reused

This preserves one visit, one billing lifecycle, and one invoice.

## One Visit, One Invoice

Admission and emergency charge services now add lines through `BillingService` instead of creating independent billing flows.

`BillingService::addItemToVisitInvoice()` supports controlled unit-price overrides for daily bed/consumable calculations while keeping invoice ownership at the visit level.

## Main Files Changed

### Schema and Models

- `database/migrations/2026_05_29_000001_add_visit_pathway_and_session_locking.php`
- `database/migrations/2026_05_29_000002_create_bed_and_daily_charge_tracking_tables.php`
- `app/Models/VisitPathwayEvent.php`
- `app/Models/EmergencyBedCharge.php`
- `app/Models/EmergencyDailyConsumableCharge.php`
- `app/Models/AdmissionBedCharge.php`
- `app/Models/AdmissionDailyConsumableCharge.php`
- `app/Models/Visit.php`
- `app/Models/VisitConsultationRoute.php`
- `app/Models/Patient.php`
- `app/Models/Admission.php`
- `app/Models/InvoiceItem.php`

### Workflow Services

- `app/Services/VisitStatusService.php`
- `app/Services/VisitGuardService.php`
- `app/Services/VisitPathwayService.php`
- `app/Services/VisitService.php`
- `app/Services/VisitWorkflowService.php`
- `app/Services/OutpatientSessionAutoCloseService.php`
- `app/Services/EmergencyCaseService.php`
- `app/Services/EmergencyDispositionService.php`
- `app/Services/EmergencyBayService.php`
- `app/Services/EmergencyBedBillingService.php`
- `app/Services/AdmissionService.php`
- `app/Services/AdmissionBedBillingService.php`
- `app/Services/BillingService.php`
- `app/Services/PaymentService.php`
- `app/Services/VisitPreviewService.php`

### Department Workflow Touchpoints

- `app/Services/InvestigationRequestService.php`
- `app/Services/LabService.php`
- `app/Services/PharmacyBillingSelectionService.php`
- `app/Services/PharmacyService.php`
- `app/Services/ProcedureRequestService.php`
- `app/Services/ProcedureWorkflowService.php`

### Controllers, Requests, UI, and Permissions

- `app/Http/Controllers/Admin/VisitController.php`
- `app/Http/Controllers/Admin/EmergencyCaseController.php`
- `app/Http/Controllers/Doctor/ConsultationController.php`
- `app/Http/Requests/StoreVisitRequest.php`
- `resources/views/visits/create.blade.php`
- `resources/views/visits/show.blade.php`
- `resources/views/visits/index.blade.php`
- `resources/views/emergency/create.blade.php`
- `resources/views/consultations/show.blade.php`
- `database/seeders/RoleSeeder.php`
- `bootstrap/app.php`

### Tests

- `tests/Feature/VisitStatusPatientPathwayWorkflowTest.php`

## Verification

Focused workflow tests were added for:

- active admission guard
- authorized override with reason
- existing-visit emergency case creating an Emergency Session
- outpatient session auto-lock
- registered visit direct admission
- emergency bed/daily consumable continuation until admission bed assignment
- admission bed service resolution after emergency bed billing
- one visit invoice across emergency and admission billing

Focused test command:

```bash
php artisan test --filter=VisitStatusPatientPathwayWorkflowTest
```

Result:

```text
6 passed, 28 assertions
```

## Remaining Risks and Follow-Up

- Historical rows using legacy movement statuses should be reviewed before any future data migration or status cleanup.
- Production service catalog configuration should include clear emergency and admission bed/day and consumable/day services to avoid ambiguous service resolution.
- The correction override UX can be expanded into a fuller audit review screen if clinical governance requires approval workflows.
- Broader regression testing is recommended across pharmacy, claims, theatre, and discharge after deployment because this change intentionally touches the central visit lifecycle.
