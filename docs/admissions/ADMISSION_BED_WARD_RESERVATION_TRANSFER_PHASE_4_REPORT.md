# Admission Bed, Ward, Reservation, Transfer, and Location History Phase 4 Report

## Implemented

- Added durable bed reservation records.
- Added admission location history records.
- Added bed status metadata: reserved-until, status reason, changed by, changed at.
- Added new bed statuses for cleaning, blocked, and isolation.
- Added a bed workflow service for reservation, reservation fulfilment, transfer, discharge release, and status updates.
- Wired admission request bed reservation into the new reservation table.
- Wired admission conversion to fulfil active bed reservations.
- Wired direct admission to write an admitted location-history event.
- Wired discharge to release the bed and write a discharge location-history event.
- Added admission bed transfer route/action and UI card.
- Added location history display on the admission workspace.
- Added bed management visibility for active reservations and status reasons.
- Added bed status update route/action for operational bed states.

## Files Changed

- `app/Enums/AdmissionLocationEvent.php`
- `app/Enums/BedReservationStatus.php`
- `app/Enums/BedStatus.php`
- `app/Http/Controllers/Admin/AdmissionsWard/AdmissionBedWorkflowController.php`
- `app/Http/Controllers/Admin/AdmissionsWard/AdmissionController.php`
- `app/Http/Controllers/Admin/AdmissionsWard/AdmissionRequestController.php`
- `app/Models/Admission.php`
- `app/Models/AdmissionLocationHistory.php`
- `app/Models/AdmissionRequest.php`
- `app/Models/Bed.php`
- `app/Models/BedReservation.php`
- `app/Models/Patient.php`
- `app/Models/Visit.php`
- `app/Services/AdmissionService.php`
- `app/Services/Admissions/AdmissionRequestService.php`
- `app/Services/Admissions/BedWorkflowService.php`
- `app/Services/WardService.php`
- `database/migrations/2026_07_04_000002_create_bed_reservations_and_location_history.php`
- `database/seeders/RoleSeeder.php`
- `lang/en/admissions.php`
- `lang/en/statuses.php`
- `lang/fr/admissions.php`
- `lang/fr/statuses.php`
- `resources/views/admissions/request-show.blade.php`
- `resources/views/admissions/show.blade.php`
- `resources/views/wards/beds.blade.php`
- `routes/web.php`
- `tests/Feature/AdmissionBedWorkflowPhase4Test.php`

## New Tables and Columns

New table: `bed_reservations`

- admission_request_id
- admission_id
- patient_id
- visit_id
- bed_id
- status
- reserved_by
- released_by
- reserved_at
- expires_at
- released_at
- reason

New table: `admission_location_histories`

- admission_id
- patient_id
- visit_id
- event_type
- from_ward_id
- from_bed_id
- to_ward_id
- to_bed_id
- moved_by
- moved_at
- reason
- metadata

New `beds` columns:

- reserved_until
- status_reason
- status_changed_by
- status_changed_at

## New Statuses

Bed reservation statuses:

- active
- fulfilled
- cancelled
- expired
- released

Location event types:

- admitted
- bed_reserved
- bed_released
- bed_transferred
- ward_transferred
- discharged

Additional bed statuses:

- cleaning
- blocked
- isolation

## Routes Added

- `POST admin/admissions/{admission}/transfer-bed`
- `PATCH admin/beds/{bed}/status`

## Permissions Added

- `beds.reserve`
- `beds.release`
- `beds.block`
- `beds.transfer`

Existing permissions remain unchanged:

- `ward.view`
- `ward.manage`
- `ward.admit`
- `ward.discharge`
- `beds.view`
- `beds.manage`
- `admission.requests.*`

## Safety Rules

- A request can reserve only an available bed.
- Reserving a new bed for the same request releases the previous active reservation.
- Rejected, cancelled, or converted requests still cannot convert.
- Conversion accepts a reserved bed only when the reservation belongs to that request.
- Transfers can only move an active admission to a different available bed.
- Transfer releases the old bed and occupies the destination bed in one transaction.
- Bed management cannot release a bed that has an active admission.
- Discharge releases the bed through the bed workflow service and records location history.

## UI Updates

- Admission show page now displays:
  - current location card
  - transfer-to-bed form for users with `beds.transfer`
  - recent location history
- Admission request detail page now displays active reservation metadata.
- Bed management page now displays active reservation patient/request and bed status reasons.
- Bed management page now has a status modal for users with `beds.block`.

## Existing Workflows Protected

- Existing direct admission still works.
- Existing admission request conversion still works.
- Existing emergency-to-admission compatibility remains intact.
- Existing ward rounds, vitals, services, medication board, MAR chart, and discharge remain in place.
- Billing behavior is unchanged; reservation does not post billing.

## Tests and Checks Run

- `php artisan test tests/Feature/AdmissionBedWorkflowPhase4Test.php`
- `php artisan test tests/Feature/AdmissionWorkflowFoundationTest.php`
- `php artisan test tests/Feature/WardAdmissionTest.php`
- `php artisan route:list --name=admissions.transfer-bed`
- `php artisan route:list --name=wards.beds.status`
- `php artisan view:clear`
- `php artisan config:clear`
- `git diff --check`
- PHP syntax checks on new/changed core files

## Known Risks

- Reservation expiry is stored but not yet enforced by a scheduled command.
- Bed cleaning/blocked/isolation statuses are supported, but no cleaning checklist workflow exists yet.
- Transfer history is admission-focused; there is no separate ward-capacity dashboard yet.
- The bed status modal is intentionally simple and does not replace full ward operations.

## Intentionally Deferred

- Automatic reservation expiry job.
- Bed cleaning checklist.
- Blocked/isolation reason taxonomy.
- Ward transfer approval flow.
- Advanced ward board capacity planning.
- Maternity/labor/postnatal workflows.

## Next Recommended Phase

Phase 5 should add reservation expiry automation, cleaning/blocked bed workflow depth, and richer ward capacity/bed-map operations.
