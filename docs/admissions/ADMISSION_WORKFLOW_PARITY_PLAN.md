# Admission Workflow Parity Plan

## Objective

Bring Admission and Ward workflows closer to the maturity of Emergency while protecting existing admission, ward, medication, billing, and discharge behavior.

## Design Principles

- Add new workflow state beside existing tables first; do not replace working admission creation.
- Keep Emergency, Consultation, and Admission entry points compatible.
- Make bed assignment auditable and reversible.
- Make billing configuration visible before staff commit the admission.
- Use granular permissions before exposing new operational actions.
- Keep all new states localised in English and French.

## Target Admission Flow

1. A consultation, emergency case, or authorised staff member creates an admission request.
2. The request records source, patient, visit, diagnosis, priority, requested ward, requested bed type, requesting clinician, and clinical note.
3. Ward staff review the request.
4. Ward staff accept, reject, cancel, or mark bed pending.
5. Accepted requests can reserve a bed.
6. Admission creation converts the request into an admission, assigns a bed, records admission charges, links source workflow records, and writes a timeline event.
7. Inpatient care runs through ward board, admission workspace, vitals, rounds, medications, services, consumables, investigations, procedures, nursing notes, and tasks.
8. Transfer actions move the patient between beds/wards with history.
9. Discharge readiness tracks clinical, medication, billing, and bed release checks.
10. Final discharge closes admission, frees or marks the bed for cleaning, updates visit state, dispatches events, and writes audit logs.

## Proposed Workflow Records

### Admission Request

Recommended fields:

- patient_id
- visit_id
- source_type: consultation, emergency, direct, maternity, transfer
- source_id
- requested_by
- accepted_by
- cancelled_by
- requested_ward_id
- preferred_bed_type
- reserved_bed_id
- priority
- provisional_diagnosis
- clinical_summary
- status
- requested_at
- accepted_at
- rejected_at
- cancelled_at
- reason

Recommended request statuses:

- requested
- under_review
- accepted
- bed_pending
- reserved
- converted
- rejected
- cancelled

### Admission Timeline

Recommended events:

- request_created
- request_accepted
- request_rejected
- bed_reserved
- admitted
- bed_transferred
- ward_transferred
- round_recorded
- service_added
- consumable_used
- discharge_planning_started
- discharge_cleared
- discharged
- deceased
- cancelled

### Bed Location History

Recommended fields:

- admission_id
- from_ward_id
- from_bed_id
- to_ward_id
- to_bed_id
- moved_by
- reason
- moved_at

## Status Mapping

Existing `AdmissionStatus` values should remain valid:

- admitted
- on_leave
- discharged
- transferred
- deceased

Additive operational states can be layered through request status, discharge readiness, and bed status before changing `AdmissionStatus`. If admission status expansion becomes necessary, keep it additive and migrate existing rows to their current equivalents.

## Bed Status Target

Current bed statuses:

- available
- occupied
- maintenance
- reserved

Recommended future statuses:

- available
- reserved
- occupied
- cleaning
- maintenance
- blocked
- isolation

Each non-available state should carry a reason, actor, and timestamp.

## Billing Parity Target

Admission billing should support:

- Admission fee service.
- Bed or detention service.
- Daily consumable/nursing service.
- Optional ward package service.
- Procedures and investigations requested during admission.
- Consumables used in the ward.
- Medication administration charges where applicable.
- Maternity package mappings later.

Required safety behavior:

- Show missing service mapping warnings before admission.
- Prevent duplicate initial admission charges.
- Keep manual amount overrides visible in the admission timeline.
- Do not silently create historical charges when mappings change.
- Log billing setup at admission creation.

## Ward Workspace Parity Target

The admission page should grow into a ward-care workspace with:

- Patient banner with bed, ward, insurer, allergies, age, sex, admission source, and current status.
- Request and handover summary.
- Admission timeline.
- Bed and ward movement history.
- Vitals trends.
- Ward rounds and nursing notes.
- Medication administration board and MAR link.
- Pending investigations, procedures, tasks, services, and consumables.
- Billing readiness and unpaid balance panel.
- Discharge readiness checklist.
- Quick actions based on permissions.

## Ward Board Target

The ward board should show:

- Ward occupancy.
- Bed state.
- Patient name and admission age.
- Acuity or priority.
- Source: consultation, emergency, direct, maternity, transfer.
- Overdue vitals.
- Due medications.
- Pending labs/procedures.
- Discharge planned today.
- Billing or clearance blockers.
- Transfer pending.

## Permission Plan

Keep existing permissions and add granular permissions:

- `admission.requests.view`
- `admission.requests.create`
- `admission.requests.accept`
- `admission.requests.reject`
- `admission.requests.cancel`
- `admission.transfer`
- `admission.discharge.plan`
- `admission.discharge.clear`
- `admission.timeline.view`
- `admission.notes.create`
- `admission.billing.view`
- `admission.billing.configure`
- `beds.reserve`
- `beds.release`
- `beds.block`
- `beds.transfer`
- `ward.board.view`

## Localisation Plan

Add keys for:

- Request statuses and actions.
- Bed statuses and reasons.
- Ward board labels.
- Transfer labels.
- Billing readiness warnings.
- Discharge readiness checklist.
- Timeline event names.
- Maternity admission source labels.

## Implementation Phases

| Phase | Goal | Main Work | Tests |
| --- | --- | --- | --- |
| 1 | Fix admission discharge audit bug | Move discharge event/log after transaction result is captured | Discharge event/log regression test |
| 2 | Add admission request model | Migration, model, relationships, statuses, permissions | Request creation and listing tests |
| 3 | Wire consultation/emergency request creation | Create request instead of only setting visit admitting, keep old route compatible | Emergency disposition and consultation admit tests |
| 4 | Add request review UI | Requests board with accept/reject/cancel/bed pending | Permission and state transition tests |
| 5 | Convert accepted request to admission | Link request to admission and preserve existing direct admission path | Admission conversion tests |
| 6 | Add admission timeline | Timeline table/service/events for request/admit/discharge | Timeline assertions |
| 7 | Add bed reservations and history | Reservation, transfer, movement history, cleaning status | Bed transfer and occupancy tests |
| 8 | Improve admission workspace | Timeline, handover, billing readiness, discharge readiness panels | View smoke and permission tests |
| 9 | Improve ward board | Operational ward board with filters and alerts | Board filter tests |
| 10 | Strengthen billing safety | Mapping warnings, duplicate prevention, billing audit panel | Billing mapping and duplicate tests |
| 11 | Prepare maternity foundation | Maternity source support and package mapping hooks | Maternity admission seed/manual tests |

## Backward Compatibility

- Existing `admissions/create` and `admissions/store` should keep working.
- Existing `VisitStatus::ADMITTING` list should remain available until request conversion is fully adopted.
- Existing admission billing fields on `admissions` should remain supported.
- Existing medication board and MAR chart routes should not move.
- Existing ward and bed management should not be blocked by new bed history.

## Rollout Notes

- Start with narrow model/service tests before changing views.
- Keep new routes behind permissions.
- Seed permissions before exposing menu links.
- Add manual testing seed data only after the request lifecycle exists.
- Avoid bulk backfills except simple nullable links and safe status defaults.
