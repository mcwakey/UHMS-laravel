# Admission and Maternity Manual Testing Plan

## Scope

This plan covers manual testing for the current Emergency-to-Admission workflow and future Admission, Ward, Maternity, ANC, Labor, Delivery, Newborn, and Postnatal phases.

## Current Baseline Tests

### Emergency Case Creation

- Create emergency case from an existing patient.
- Create emergency case for an unknown/temporary patient.
- Confirm visit status becomes emergency.
- Confirm emergency consultation billing is created when configured.
- Confirm case appears on Emergency Board.

### Emergency Clinical Workflow

- Record triage with danger signs.
- Assign bay.
- Record vitals.
- Add emergency note.
- Request investigation.
- Request procedure.
- Administer medication.
- Use consumables.
- Add and complete a clinical task.
- Confirm case detail page remains usable after each action.

### Emergency to Admission

- Set emergency disposition to admitted.
- Confirm visit appears in Admission Requests.
- Create admission from the admitting visit.
- Select ward and available bed.
- Confirm bed becomes occupied.
- Confirm emergency bay is released.
- Confirm admission links back to the emergency source.
- Confirm initial admission charges are created when service mappings exist.

### Ward and Admission Care

- Open admissions index.
- Filter admissions by search, status, and ward.
- Open admission detail.
- Record ward round.
- Record vitals.
- Add a service to the visit from the admission page.
- Open admission medication board.
- Open MAR chart.
- Process discharge.
- Confirm bed is released or marked appropriately by the current workflow.

### Ward and Bed Management

- Create ward.
- Update ward.
- Toggle ward active state.
- Create bed.
- Update bed.
- Confirm bed map displays ward and bed status.

## Future Admission Request Tests

- Create request from consultation.
- Create request from emergency disposition.
- Create direct admission request.
- Accept request.
- Reject request with reason.
- Cancel request with reason.
- Mark request bed pending.
- Reserve bed for accepted request.
- Convert accepted request into admission.
- Confirm rejected/cancelled requests cannot be admitted.
- Confirm old direct admission path still works during rollout.

## Future Bed and Transfer Tests

- Reserve an available bed.
- Prevent double reservation.
- Admit into reserved bed.
- Transfer patient to another bed in same ward.
- Transfer patient to another ward.
- Mark bed cleaning after discharge.
- Mark bed available after cleaning.
- Block bed with reason.
- Confirm bed/location history is visible.

## Future Admission Billing Tests

- Admit with complete service mappings.
- Admit with missing admission fee mapping and confirm warning.
- Admit with missing bed/detention mapping and confirm warning.
- Admit with manual amount override.
- Confirm duplicate initial charges are prevented.
- Confirm billing readiness panel reflects unpaid balances.

## Future Discharge Readiness Tests

- Start discharge planning.
- Add clinical clearance.
- Add medication clearance.
- Add billing clearance.
- Add bed release/cleaning step.
- Block discharge when mandatory clearance is missing.
- Complete discharge after clearance.
- Confirm discharge event and activity log are recorded.

## Maternity Foundation Tests

- Create pregnancy profile for a patient.
- Update gravida, para, LMP, EDD, and risk flags.
- Confirm maternity dashboard lists active pregnancy profile counts.
- Confirm non-maternity admission does not require pregnancy fields.
- Confirm permissions hide maternity actions from unauthorised users.

## ANC Tests

- Record ANC visit.
- Capture vitals, gestational age, fundal height, fetal heart rate, presentation, and danger signs.
- Request lab test from ANC.
- Request ultrasound from ANC.
- Record supplement or medication.
- Schedule next ANC appointment.
- Flag high-risk pregnancy.
- Refer high-risk ANC patient to emergency or admission.

## Labor and Delivery Tests

- Start labor episode from maternity admission.
- Record labor observations.
- Record partograph-style progress data.
- Trigger risk escalation.
- Refer to theatre.
- Record normal delivery.
- Record assisted delivery.
- Record caesarean handoff outcome.
- Record delivery complications.
- Create newborn record.
- Create multiple newborn records for twins.
- Capture APGAR scores and birth weight.

## Postnatal Tests

- Record maternal postnatal observation.
- Record newborn postnatal observation.
- Flag maternal danger sign.
- Flag newborn danger sign.
- Record breastfeeding and counselling.
- Clear mother for discharge.
- Clear newborn for discharge.
- Discharge mother and newborn together.
- Refer newborn for further care.

## Manual Seed Data Recommendations

Add dedicated manual seeders only after the related implementation phase exists:

- Emergency patient admitted to ward.
- Consultation patient awaiting admission.
- Occupied, reserved, cleaning, blocked, and available beds.
- Maternity ward and delivery room.
- Pregnant patient with low-risk ANC profile.
- Pregnant patient with high-risk ANC profile.
- Maternity admission in labor.
- Postnatal mother and newborn.

## Acceptance Checklist

- Existing emergency workflow still works.
- Existing admission creation still works.
- Existing ward rounds, vitals, services, medication board, and MAR chart still work.
- Existing discharge workflow still works or has a documented narrow fix.
- New admission request lifecycle is permission-protected.
- Bed occupancy cannot become inconsistent during reservation, admission, transfer, or discharge.
- Maternity workflows remain optional and do not disrupt general patients.
- All new user-facing text has English and French keys.
- Manual test users have the right roles and permissions.
