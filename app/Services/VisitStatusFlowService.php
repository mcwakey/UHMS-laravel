<?php

namespace App\Services;

use App\Enums\VisitStatus;
use App\Models\Patient;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Canonical engine for the dynamic visit-status flow. Responsible for:
 *   - determining attendance_class (statistical category)
 *   - resolving the initial visit_status for a new visit
 *   - validating + applying status transitions (with privileged override)
 *   - walking a freshly-created visit forward into the triage queue
 *
 * Workflow status stays a typed enum (App\Enums\VisitStatus); visit_source and
 * attendance_class are stored as lookup "code" strings on the visit.
 *
 * Extend the flow later by: adding enum cases + transition edges in VisitStatus,
 * adding rows to the visit_sources / attendance_classes lookup tables, and
 * (if needed) extending {@see resolveInitialStatus()} / {@see advanceToQueue()}.
 */
class VisitStatusFlowService
{
    /** Visit statuses that do NOT count as an actual attendance. */
    private const NON_ATTENDANCE = [
        'cancelled',
        'no_show',
        'rescheduled',
    ];

    public function __construct(
        protected VisitPathwayService $pathway,
    ) {}

    /**
     * Determine the statistical attendance category for a patient on a given
     * visit date. Uses the visit's YEAR (not the server's), per spec.
     */
    public function determineAttendanceClass(Patient $patient, Carbon $visitDate, ?int $excludeVisitId = null): string
    {
        $base = Visit::query()
            ->where('patient_id', $patient->id)
            ->when($excludeVisitId, fn ($q) => $q->where('id', '!=', $excludeVisitId))
            ->whereNotIn('status', self::NON_ATTENDANCE);

        if (! (clone $base)->exists()) {
            return 'first_ever';
        }

        $attendedThisYear = (clone $base)
            ->whereYear('visit_date', $visitDate->year)
            ->exists();

        return $attendedThisYear ? 'subsequent_attendance' : 'first_attendance_of_year';
    }

    /**
     * Resolve the initial visit_status for a new visit. The attendance class
     * drives the first status for BOTH direct and appointment visits:
     *
     *  - first_ever (direct OR appointment)        → CREATED
     *  - first_attendance_of_year (direct OR appt) → REGISTERED
     *  - subsequent + appointment                  → SCHEDULED
     *  - subsequent + direct walk-in               → WALKED_IN
     *  - future-scheduled direct visit             → SCHEDULED (any class)
     */
    public function resolveInitialStatus(string $source, string $attendanceClass, bool $isScheduled = false): VisitStatus
    {
        // A direct visit booked for a future date is just a schedule.
        if ($isScheduled && $source !== 'appointment') {
            return VisitStatus::SCHEDULED;
        }

        return match ($attendanceClass) {
            'first_ever' => VisitStatus::CREATED,
            'first_attendance_of_year' => VisitStatus::REGISTERED,
            default => $source === 'appointment' ? VisitStatus::SCHEDULED : VisitStatus::WALKED_IN,
        };
    }

    /**
     * Stamp visit_source + attendance_class on a freshly-created visit and set
     * its initial status, writing the null → initial transition log.
     */
    public function classifyAndInitialize(Visit $visit, string $source, bool $isScheduled = false): Visit
    {
        $visitDate = $visit->visit_date instanceof Carbon
            ? $visit->visit_date
            : Carbon::parse($visit->visit_date);

        $attendanceClass = $this->determineAttendanceClass($visit->patient, $visitDate, $visit->id);
        $initial = $this->resolveInitialStatus($source, $attendanceClass, $isScheduled);

        $visit->forceFill([
            'visit_source' => $source,
            'attendance_class' => $attendanceClass,
            'status' => $initial->value,
        ]);

        if (in_array($initial, [VisitStatus::WALKED_IN, VisitStatus::CHECKED_IN], true)) {
            $visit->arrived_at = $visit->arrived_at ?? now();
            $visit->checked_in_at = $visit->checked_in_at ?? now();
        }

        $visit->save();

        $visit->statusLogs()->create([
            'from_status' => null,
            'to_status' => $initial->value,
            'changed_by' => Auth::id(),
            'notes' => $this->initialNote($source, $attendanceClass, $isScheduled),
        ]);

        return $visit->fresh();
    }

    /**
     * Validate and apply a status transition.
     *
     * Invalid transitions are rejected unless $force is set AND the current user
     * holds the `visits.override_transition` permission.
     */
    public function transition(Visit $visit, VisitStatus $to, ?string $notes = null, bool $force = false): Visit
    {
        if ($to->isDepartmentMovementStatus()) {
            throw new \InvalidArgumentException("{$to->label()} is tracked through pathway events, not visit.status.");
        }

        if ($visit->status === $to) {
            return $visit;
        }

        if (! $visit->canTransitionTo($to)) {
            if (! ($force && Auth::user()?->can('visits.override_transition'))) {
                throw new \InvalidArgumentException(
                    "Cannot transition from {$visit->status->label()} to {$to->label()}"
                    . ($force ? ' — override permission required.' : '.')
                );
            }
        }

        // Visit::transitionTo updates the status, writes the status log, and sets
        // the relevant workflow timestamps (arrived_at, checked_in_at, cancelled_at, …).
        $visit->transitionTo($to, $notes);

        $this->pathway->record($visit->fresh(), 'VISIT_STATUS_CHANGED', [
            'status' => $to->value,
            'title' => 'Visit status changed',
            'description' => $notes,
        ]);

        return $visit->fresh();
    }

    /**
     * The "patient present" arrival state for a visit, by source:
     * appointment → CHECKED_IN, otherwise (direct/walk-in) → WALKED_IN.
     */
    public function arrivalStatusFor(Visit $visit): VisitStatus
    {
        return $visit->visit_source === 'appointment'
            ? VisitStatus::CHECKED_IN
            : VisitStatus::WALKED_IN;
    }

    /**
     * The next hop toward the queue from the visit's current status. Routing is
     * source-aware: direct visits pass through WALKED_IN, appointment visits
     * through CHECKED_IN. Returns null once there is no further automatic hop.
     */
    private function nextHop(Visit $visit): ?VisitStatus
    {
        return match ($visit->status) {
            VisitStatus::CREATED, VisitStatus::REGISTERED => $this->arrivalStatusFor($visit),
            VisitStatus::SCHEDULED, VisitStatus::CONFIRMED => VisitStatus::CHECKED_IN,
            VisitStatus::WALKED_IN, VisitStatus::CHECKED_IN => VisitStatus::QUEUED,
            default => null,
        };
    }

    /**
     * Walk a visit forward, hop by hop, until it reaches $stopAt (or runs out of
     * automatic hops). Used to advance to the arrival state or all the way to the
     * triage queue.
     */
    private function advanceTo(Visit $visit, VisitStatus $stopAt): Visit
    {
        $guard = 0;
        while (
            $visit->status !== $stopAt
            && ($next = $this->nextHop($visit)) !== null
            && $guard++ < 8
        ) {
            $visit = $this->transition($visit, $next, 'Advancing visit status');
        }

        return $visit;
    }

    /**
     * Walk a freshly-created visit to its arrival state (walked_in / checked_in).
     */
    public function advanceToArrival(Visit $visit): Visit
    {
        return $this->advanceTo($visit, $this->arrivalStatusFor($visit));
    }

    /**
     * Walk a visit all the way to the triage QUEUED state.
     * Direct path: created/registered → walked_in → queued.
     * Appointment path: created/registered/scheduled → checked_in → queued.
     *
     * Does NOT create the queue entry — that stays the caller's responsibility
     * (see VisitWorkflowService::queueForTriage).
     */
    public function advanceToQueue(Visit $visit): Visit
    {
        return $this->advanceTo($visit, VisitStatus::QUEUED);
    }

    private function initialNote(string $source, string $attendanceClass, bool $isScheduled): string
    {
        if ($isScheduled && $source !== 'appointment') {
            return 'Visit scheduled';
        }

        return match ($attendanceClass) {
            'first_ever' => 'First-ever attendance — visit created',
            'first_attendance_of_year' => 'First attendance of the year — registered',
            default => $source === 'appointment' ? 'Appointment scheduled' : 'Returning patient — walked in',
        };
    }
}
