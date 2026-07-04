# Admission and Emergency Gap Analysis

## Scope

This report audits the current Emergency, Admission, Ward, Bed, and Maternity-related surfaces. It is documentation only; no workflow implementation was changed.

## Audited Areas

- Emergency routes, controllers, services, models, migrations, views, localisation, permissions, manual seeders, and feature tests.
- Admission and ward routes, controllers, services, models, requests, migrations, views, localisation, permissions, and feature tests.
- Department dashboard routing for inpatient and maternity department types.
- Maternity, antenatal, labor/labour, postnatal, newborn, pregnancy, and obstetric references across app, database, resources, tests, lang, and routes.

## Current Emergency Workflow

Emergency is a mature workflow with a dedicated operational spine:

- Entry from existing visits or temporary/unknown patient registration.
- Dedicated `EmergencyCase`, `EmergencySession`, bay assignment, case logs, notes, vitals, triage, investigations, procedures, medication, consumables, tasks, disposition, and reports.
- Automatic emergency visit state handling through `VisitStatus::EMERGENCY`.
- Automatic emergency consultation billing with safe failure handling.
- Rich emergency board with triage, status, bay, wait time, team, and alert filters.
- Case detail page acts as a clinical command workspace.
- Emergency-to-admission disposition marks the visit as admitting and links the emergency case to the eventual admission.
- Granular emergency permissions and stronger feature coverage than admission.

## Current Admission and Ward Workflow

Admission exists and is usable, but is much thinner than emergency:

- Entry is mainly from visits already in `VisitStatus::ADMITTING`.
- Admission creation directly assigns a bed and marks the visit admitted.
- Ward and bed CRUD exist with available, occupied, maintenance, and reserved bed states.
- Admission detail supports ward rounds, vitals, medication administration board, MAR chart, task visibility, attached visit services, consultation preview, and discharge form.
- Admission billing creates initial admission, detention, and daily consumable charges through configurable services.
- Emergency bed release is linked during admission if the source visit came from an admitted emergency case.
- Admission permissions are broad: `ward.view`, `ward.manage`, `ward.admit`, `ward.discharge`, `beds.view`, `beds.manage`, plus medication/MAR board view permissions.

## Parity Comparison

| Area | Emergency | Admission / Ward | Gap |
| --- | --- | --- | --- |
| Entry points | New emergency case, existing visit, temporary patient | Visits waiting for admission | Admission cannot yet manage requests as first-class records. |
| Unknown patient flow | Supported | Not supported | Admission depends on an existing patient/visit. |
| Lifecycle model | Dedicated case and session | Admission row only | Admission lacks a request/admission session lifecycle. |
| Status depth | Arrival, triage, care, observation, disposition | Admitted, on leave, discharged, transferred, deceased | Admission has limited operational statuses. |
| Queue/board | Rich emergency board with clinical filters | Admission list and admission requests list | Admission lacks a true ward board with acuity, bed state, nursing alerts, and discharge readiness. |
| Bed/location handling | Bay assignment and release tracking | Bed assignment and basic occupancy | No transfer history, cleaning state, blocking reason, or reservation expiry. |
| Clinical workspace | Large command page with tabs, modals, alerts, timeline and billing groups | Admission show page with vitals, rounds, tasks, medication, services, discharge | Admission UI needs parity for timeline, location history, billing flags, nursing checklist, and clinical handover. |
| Billing | Emergency consultation, bed, consumables, services | Admission fee, detention/bed, consumables, attached services | Admission billing mapping is useful but needs warnings, auditability, and safer service configuration visibility. |
| Emergency handoff | Disposition marks visit as admitting | Admission consumes admitting visits and links emergency case | Handoff lacks approval, accepting ward/team, request note, bed pending state, and transfer checklist. |
| Audit trail | Emergency case logs and activity logging | Basic activity logging on admit; discharge logging appears unreachable after transaction return | Admission needs first-class event/timeline logging. |
| Permissions | Granular emergency permissions | Broad ward/admission permissions | Admission needs finer permissions for request approval, transfer, bed control, nursing care, and discharge clearance. |
| Localisation | Dedicated emergency lang files | Admission and ward lang files exist | Admission will need new keys for request lifecycle, transfer, bed board, clearances, maternity integration. |
| Tests | Broad emergency feature coverage | Thin ward/admission feature coverage | Admission needs workflow, billing, transfer, bed, discharge, and permission tests. |
| Manual seed data | Emergency manual seeder exists | Ward/bed manual seeder exists | Admission and maternity need richer manual testing data. |

## Emergency-to-Admission Flow

Current flow:

1. Emergency disposition is set to admitted.
2. The visit is marked `ADMITTING` and changed to inpatient type.
3. The Admissions Requests screen lists visits waiting for admission.
4. Staff creates an admission and assigns an available bed.
5. Admission service marks the bed occupied, marks the visit admitted, creates initial charges, links the emergency case, and releases the emergency bay.

Gaps:

- No first-class admission request record with source, priority, diagnosis, requested ward, requested by, accepted by, rejection reason, or cancellation reason.
- No bed-pending or accepted-pending-placement state.
- No accepting ward/team acknowledgment.
- No clinical transfer checklist from emergency to ward.
- No separate emergency handover summary snapshot.
- No notification or alert path for admission request acceptance.
- No structured ward capacity decision trail.

## Ward and Bed Gaps

- Bed statuses do not include cleaning, blocked, isolation, or ready-for-nurse-check.
- No bed reservation expiry.
- No transfer history from bed to bed, ward to ward, or emergency bay to ward bed.
- No occupancy timeline for reporting.
- No bed blocking reason or release checklist.
- No ward board showing bed state, nursing status, overdue vitals, pending medications, discharge readiness, and pending bills.
- No clear split between ward management actions and clinical admission actions.

## Admission Billing Gaps

- Admission billing has configurable service IDs and amount overrides, which is good.
- Missing service mappings can silently reduce expected billing coverage if staff do not notice configuration gaps.
- There is no admission billing readiness panel.
- There is no ward-level service mapping audit screen.
- No explicit separation between admission fee, detention/bed fee, nursing consumables, procedure, medication, and investigation charge health.
- No dedicated tests covering missing mappings, duplicate prevention, and emergency-to-admission charge continuity.

## Admission Discharge Gap

`AdmissionService::discharge()` appears to return from the database transaction before the discharge event and activity log statements. That means discharge event dispatch and discharge activity logging may be unreachable. This should be fixed in an implementation phase with a narrow regression test.

## Maternity Current State

Maternity exists as:

- A department type.
- A dashboard type routed to the admission/inpatient dashboard resolver.
- A ward type for routing and journey worklists.
- A manual testing department seed entry.
- A few clinical terms in complaints, ICD codes, blood bank screening, and emergency triage pregnancy flags.

No first-class maternity, antenatal, labor/labour, delivery, postnatal, or newborn workflow module was found.

## Priority Gaps

| Priority | Gap | Reason |
| --- | --- | --- |
| P0 | Admission request lifecycle | Needed before safe emergency-to-ward and consultation-to-ward parity. |
| P0 | Admission timeline/event logging | Needed for auditability and clinical traceability. |
| P0 | Discharge event/log bug | Current code likely skips discharge event/logging. |
| P1 | Ward board and bed/location history | Needed for practical inpatient operations. |
| P1 | Admission billing readiness and service mapping visibility | Needed to avoid missed billing. |
| P1 | Admission permission granularity | Needed before expanding workflow actions. |
| P2 | Maternity module foundation | Needed after admission/ward spine is stable. |
| P2 | ANC/labor/postnatal/newborn workflows | Should build on the admission and maternity foundation. |

## Safe Direction

Admission should not be rewritten around emergency. Instead, add an admission request and ward-care spine beside the existing admission flow, then migrate screens and actions incrementally. Existing admission creation, ward rounds, vitals, medication boards, and discharge should remain usable throughout the rollout.
