# Admission Ward Operations Phase 5 Report

## Implemented

- Added reservation expiry automation through an Artisan command.
- Added configurable bed release behavior after discharge.
- Strengthened bed reservation, transfer, release, and status guardrails.
- Added deeper blocked, maintenance, isolation, and cleaning status handling.
- Improved the bed map into a ward capacity board with operational metrics and filters.
- Added admission workspace visibility for discharge bed-release behavior.
- Routed legacy bed status edits through the bed workflow service so reason/change metadata is consistently captured.

## Files Changed

- `app/Console/Commands/ExpireBedReservationsCommand.php`
- `app/Http/Controllers/Admin/AdmissionsWard/WardController.php`
- `app/Services/Admissions/BedWorkflowService.php`
- `app/Services/WardService.php`
- `config/admissions.php`
- `database/seeders/RoleSeeder.php`
- `lang/en/admissions.php`
- `lang/fr/admissions.php`
- `resources/views/admissions/show.blade.php`
- `resources/views/wards/bed-map.blade.php`
- `resources/views/wards/beds.blade.php`
- `routes/console.php`
- `routes/web.php`
- `tests/Feature/AdmissionBedWorkflowPhase5Test.php`

## New Command

`php artisan admissions:expire-bed-reservations`

The command checks active reservations whose `expires_at` is in the past and safely expires them.

Console summary includes:

- checked count
- expired count
- skipped count
- errors count

## Scheduler Behavior

Scheduler registration was added but disabled by default:

```php
ADMISSION_BED_RESERVATION_AUTO_EXPIRE=false
```

When enabled, the command runs every 15 minutes with `withoutOverlapping()` and `onOneServer()`.

## Configuration

New config file: `config/admissions.php`

Key options:

- `bed_release_after_discharge`: `available` by default, can be set to `cleaning`
- `reservations.default_expiry_hours`: default `6`
- `reservations.auto_expiry_schedule_enabled`: default `false`

Default discharge behavior remains compatible: beds become available after discharge unless configured otherwise.

## Bed Reservation Expiry Behavior

An active expired reservation is expired only when:

- the reservation is still active,
- `expires_at` is in the past,
- the bed is still reserved,
- no active admission occupies the bed,
- the reservation was not fulfilled, cancelled, released, or already expired.

When expired safely:

- reservation status becomes `expired`,
- bed becomes `available`,
- request `reserved_bed_id` is cleared,
- request status moves from `reserved` back to `bed_pending`,
- an activity log is written.

## Cleaning Workflow Behavior

Discharge bed release now respects config:

- `available`: bed becomes available after discharge.
- `cleaning`: bed is marked cleaning after discharge.

The admission workspace shows a preview of the configured behavior before discharge.

## Blocked, Maintenance, and Isolation Behavior

- Blocked beds cannot be reserved or transferred into.
- Cleaning beds cannot be reserved or transferred into.
- Maintenance beds cannot be reserved or transferred into.
- Occupied beds cannot be reserved or released from bed management.
- Blocked, maintenance, and isolation status changes require a reason.
- Bed status updates record changed by and changed at metadata.

## Ward Capacity Board

The existing bed map now shows:

- total beds
- available beds
- reserved beds
- occupied beds
- cleaning beds
- maintenance beds
- blocked beds
- isolation beds
- occupancy percentage
- active reservations
- reservations expiring within one hour
- transfers today

Filters:

- ward
- bed status
- bed type
- search by bed, ward, or patient

Bed tiles show:

- bed number
- bed type
- status
- occupied patient
- reserved patient/request
- reserved until
- status reason
- last status changed by/time

## Admission Workspace Changes

The admission location card now shows:

- current bed warning for cleaning, blocked, maintenance, or isolation statuses
- discharge bed release behavior preview
- existing transfer action and location history remain intact

## Permissions Added

- `beds.clean`
- `beds.status.manage`
- `beds.capacity.view`
- `bed.reservations.expire`

Existing Phase 4 permissions remain:

- `beds.reserve`
- `beds.release`
- `beds.block`
- `beds.transfer`

## Tests and Checks Run

- `php artisan test tests/Feature/AdmissionBedWorkflowPhase5Test.php`
- `php artisan test tests/Feature/AdmissionBedWorkflowPhase4Test.php`
- `php artisan test tests/Feature/AdmissionWorkflowFoundationTest.php`
- `php artisan test tests/Feature/WardAdmissionTest.php`
- `php artisan route:list --name=wards.bed-map`
- `php artisan route:list --name=wards.beds.status`
- `php artisan route:list --name=admissions.transfer-bed`
- `php artisan view:clear`
- `php artisan config:clear`
- `git diff --check`
- PHP syntax checks on new/changed core files

## Existing Workflows Protected

- Direct admission still works.
- Emergency-to-admission compatibility remains intact.
- Admission request reservation and conversion still work.
- Admission transfer still works.
- Discharge still works.
- Billing posting behavior was not changed.
- Existing admission, ward, and bed routes remain.

## Known Risks

- Scheduler is disabled by default and must be enabled in environment config.
- Cleaning workflow is status-based only; no housekeeping checklist exists yet.
- Isolation support records reasons and warnings but does not implement a clinical isolation policy engine.
- Capacity board is operational visibility, not a full ward staffing dashboard.

## Intentionally Deferred

- Full nursing care plan.
- Full discharge clearance engine.
- Maternity, ANC, labor, delivery, newborn, and postnatal workflows.
- Housekeeping task assignment/checklists.
- Advanced ward staffing or capacity forecasting.

## Next Recommended Phase

Phase 6: Nursing and Inpatient Care Layer.
