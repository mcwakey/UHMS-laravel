# Consultation Next Appointment and Queue Report

## Existing Systems Found

- Appointment workflow already exists through `Appointment`, `AppointmentService`, `AppointmentController`, appointment statuses, and `appointment_services`.
- Consultation queue/session workflow already exists through `VisitConsultationRoute`, `VisitConsultationRouteService`, `ConsultationSessionService`, `QueueEntry`, and `QueueService`.
- OPD payment-gate behavior already exists through `BillingPolicyService`; emergency and admission contexts are allowed as running-bill care.
- Activity logging already exists through `ActivityLogService` and the patient timeline reads central activity logs.

No parallel appointment or queue system was added.

## Implementation Approach

- Added `consultation_route_id` and `medical_record_id` links to `appointments`.
- Added reusable follow-up service: `ConsultationFollowUpService`.
- Added queue-aware next-patient service: `ConsultationNextPatientService`.
- Reused `AppointmentService` for create/update/cancel.
- Reused `ConsultationRouteService::activateRoute()` for opening the next patient, preserving existing session start, queue, medical-record, and logging behavior.
- Used row locks around the selected queue entry and route during next-patient opening.

## UI Changes

- Consultation page now has a `Next Appointment` tab with:
  - appointment date
  - optional time
  - consultation department
  - optional service
  - optional doctor
  - reason
  - clinical instruction/note
  - priority
  - notify-patient intent checkbox
- Consultation sidebar now shows `Next Patient in Line`.
- Next-patient card displays patient identity, visit number, waiting time, queue number, priority, department, services, doctor assignment, and payment readiness.
- Added `Open Next Patient` and `Complete & Open Next` actions.
- Follow-up appears in:
  - consultation summary
  - visit preview
  - patient profile upcoming follow-up appointments

## Backend Changes

- Added follow-up routes under existing consultation routes:
  - `admin.consultations.routes.follow-up.store`
  - `admin.consultations.routes.follow-up.update`
  - `admin.consultations.routes.follow-up.cancel`
- Added next-patient routes:
  - `admin.consultations.routes.next-patient.open`
  - `admin.consultations.routes.next-patient.complete-open`
- Backend validates follow-up date, department, service, doctor, priority, reason, and notes.
- Locked/completed consultation sessions require correction/reopen-style permission before follow-up changes.
- Next-patient opening rechecks queue availability, route state, doctor assignment, and payment readiness inside a transaction.

## Permissions Added

- `consultation.followup.create`
- `consultation.followup.update`
- `consultation.followup.cancel`
- `appointments.update`
- `appointments.cancel`

The new consultation follow-up routes enforce backend permissions. `appointments.update` and `appointments.cancel` are documented compatibility aliases for the existing appointment edit flow.

## Logging Added

- `FOLLOW_UP_APPOINTMENT_CREATED`
- `FOLLOW_UP_APPOINTMENT_UPDATED`
- `FOLLOW_UP_APPOINTMENT_CANCELLED`
- `NEXT_PATIENT_OPENED`

Follow-up logs carry patient, visit, consultation route, medical record, appointment, department, doctor, old values, new values, and reason/metadata where applicable.

## Tests Run

- `php -l` on changed PHP services, controller, models, and the new feature test.
- `php artisan test tests\Feature\ConsultationFollowUpAndNextPatientTest.php`

Focused feature result: 6 passed, 30 assertions.

## Remaining TODOs

- Notification delivery is intentionally hook-only for now: `notify_patient` is recorded in log metadata, but SMS/WhatsApp/email reminder delivery should use the notification provider once configured.
- Appointment/follow-up reports can be expanded later for doctor-created follow-ups, missed follow-ups, and completion conversion.
- Queue analytics can later expose waiting-time reports from `QueueEntry` and consultation route logs.
- If multiple database engines are supported in production, keep the transactional route/queue lock behavior covered by integration tests for that engine.
