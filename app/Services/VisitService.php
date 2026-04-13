<?php

namespace App\Services;

use App\Enums\VisitStatus;
use App\Models\Patient;
use App\Models\QueueEntry;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class VisitService
{
    public function __construct(
        protected QueueService $queueService,
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Visit::with(['patient', 'department', 'assignedDoctor', 'createdBy']);

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['visit_type'])) {
            $query->where('visit_type', $filters['visit_type']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['assigned_doctor_id'])) {
            $query->where('assigned_doctor_id', $filters['assigned_doctor_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('visit_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('visit_date', '<=', $filters['date_to']);
        }

        if (isset($filters['today']) && $filters['today']) {
            $query->today();
        }

        // Include scheduled visits in listing
        if (!empty($filters['include_scheduled'])) {
            $query->orWhere(function ($q) {
                $q->whereIn('status', [VisitStatus::SCHEDULED, VisitStatus::CONFIRMED]);
            });
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): Visit
    {
        $data['visit_number'] = Visit::generateVisitNumber();
        $data['visit_date'] = $data['visit_date'] ?? today();
        $data['created_by'] = auth()->id();

        // Determine if this is a scheduled visit (future date) or walk-in
        $visitDate = Carbon::parse($data['visit_date']);
        $isScheduled = $visitDate->isAfter(today());

        if ($isScheduled) {
            $data['status'] = VisitStatus::SCHEDULED->value;
        } else {
            $data['status'] = VisitStatus::REGISTERED->value;
        }

        // One visit per day per patient check
        if (Visit::patientHasVisitOnDate($data['patient_id'], $data['visit_date'])) {
            throw new \InvalidArgumentException('Patient already has a visit on this date.');
        }

        $visit = Visit::create($data);

        // Log the initial status
        $visit->statusLogs()->create([
            'from_status' => null,
            'to_status' => $visit->status->value,
            'changed_by' => auth()->id(),
            'notes' => $isScheduled ? 'Visit scheduled' : 'Visit created',
        ]);

        // Walk-in visits auto-transition to waiting
        if (!$isScheduled) {
            $visit->transitionTo(VisitStatus::WAITING);

            if ($visit->department_id) {
                $this->queueService->addToQueue($visit);
            }
        }

        return $visit->fresh(['patient', 'department', 'assignedDoctor']);
    }

    /**
     * Schedule a visit for a future date (replaces appointment creation).
     */
    public function schedule(array $data): Visit
    {
        $data['visit_date'] = $data['visit_date'] ?? $data['appointment_date'] ?? null;

        if (!$data['visit_date'] || !Carbon::parse($data['visit_date'])->isAfter(today())) {
            throw new \InvalidArgumentException('Scheduled visits must be for a future date.');
        }

        // Check for time slot conflicts if times provided
        if (!empty($data['start_time']) && !empty($data['end_time']) && !empty($data['assigned_doctor_id'])) {
            $conflict = Visit::where('assigned_doctor_id', $data['assigned_doctor_id'])
                ->whereDate('visit_date', $data['visit_date'])
                ->whereIn('status', [VisitStatus::SCHEDULED, VisitStatus::CONFIRMED])
                ->where(function ($q) use ($data) {
                    $q->where(function ($q2) use ($data) {
                        $q2->where('start_time', '<', $data['end_time'])
                           ->where('end_time', '>', $data['start_time']);
                    });
                })->exists();

            if ($conflict) {
                throw new \InvalidArgumentException('This time slot conflicts with an existing scheduled visit.');
            }
        }

        return $this->create($data);
    }

    /**
     * Confirm a scheduled visit.
     */
    public function confirm(Visit $visit, ?string $notes = null): Visit
    {
        if ($visit->status !== VisitStatus::SCHEDULED) {
            throw new \InvalidArgumentException('Only scheduled visits can be confirmed.');
        }

        $visit->transitionTo(VisitStatus::CONFIRMED, $notes);
        return $visit->fresh();
    }

    /**
     * Check in a scheduled/confirmed visit (patient arrives).
     */
    public function checkIn(Visit $visit): Visit
    {
        if (!in_array($visit->status, [VisitStatus::SCHEDULED, VisitStatus::CONFIRMED])) {
            throw new \InvalidArgumentException('Only scheduled or confirmed visits can be checked in.');
        }

        // One visit per day check
        if (Visit::where('patient_id', $visit->patient_id)
            ->whereDate('visit_date', today())
            ->whereNotIn('status', [VisitStatus::SCHEDULED, VisitStatus::CONFIRMED, VisitStatus::CANCELLED, VisitStatus::RESCHEDULED, VisitStatus::NO_SHOW])
            ->where('id', '!=', $visit->id)
            ->exists()) {
            throw new \InvalidArgumentException('Patient already has an active visit today.');
        }

        $visit->update(['visit_date' => today(), 'checked_in_at' => now()]);
        $visit->transitionTo(VisitStatus::REGISTERED);
        $visit->transitionTo(VisitStatus::WAITING);

        if ($visit->department_id) {
            $this->queueService->addToQueue($visit);
        }

        return $visit->fresh();
    }

    /**
     * Reschedule a visit to a new date/time.
     */
    public function reschedule(Visit $visit, array $data): Visit
    {
        if (!in_array($visit->status, [VisitStatus::SCHEDULED, VisitStatus::CONFIRMED])) {
            throw new \InvalidArgumentException('Only scheduled or confirmed visits can be rescheduled.');
        }

        // Mark old visit as rescheduled
        $visit->transitionTo(VisitStatus::RESCHEDULED, $data['reason'] ?? 'Rescheduled');

        // Create new visit with rescheduled_from reference
        $newData = $visit->only(['patient_id', 'visit_type', 'priority', 'department_id', 'assigned_doctor_id', 'chief_complaint', 'notes', 'visit_insurance_id', 'consultation_mode', 'meeting_link']);
        $newData['visit_date'] = $data['visit_date'];
        $newData['start_time'] = $data['start_time'] ?? $visit->start_time;
        $newData['end_time'] = $data['end_time'] ?? $visit->end_time;
        $newData['rescheduled_from_id'] = $visit->id;
        $newData['rescheduled_at'] = now();
        $newData['rescheduled_reason'] = $data['reason'] ?? null;

        return $this->schedule($newData);
    }

    /**
     * Mark a scheduled visit as no-show.
     */
    public function markNoShow(Visit $visit, ?string $notes = null): Visit
    {
        if (!in_array($visit->status, [VisitStatus::SCHEDULED, VisitStatus::CONFIRMED])) {
            throw new \InvalidArgumentException('Only scheduled or confirmed visits can be marked as no-show.');
        }

        $visit->transitionTo(VisitStatus::NO_SHOW, $notes ?? 'Patient did not show up');
        return $visit->fresh();
    }

    /**
     * Cancel a visit with reason.
     */
    public function cancel(Visit $visit, ?string $reason = null): Visit
    {
        $visit->update([
            'cancelled_by' => auth()->id(),
            'cancellation_reason' => $reason,
        ]);
        $visit->transitionTo(VisitStatus::CANCELLED, $reason);
        return $visit->fresh();
    }

    public function transition(Visit $visit, VisitStatus $newStatus, ?string $notes = null): Visit
    {
        if (!$visit->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from {$visit->status->label()} to {$newStatus->label()}"
            );
        }

        $visit->transitionTo($newStatus, $notes);

        // Complete queue entry when moving past waiting
        if ($visit->department_id && in_array($newStatus, [
            VisitStatus::TRIAGE,
            VisitStatus::CONSULTING,
        ])) {
            $this->queueService->completeCurrentEntry($visit);
        }

        return $visit->fresh();
    }

    public function update(Visit $visit, array $data): Visit
    {
        $visit->update($data);
        return $visit->fresh();
    }

    public function todayStats(): array
    {
        $today = Visit::today();

        return [
            'total' => (clone $today)->count(),
            'waiting' => (clone $today)->byStatus(VisitStatus::WAITING)->count(),
            'consulting' => (clone $today)->byStatus(VisitStatus::CONSULTING)->count(),
            'completed' => (clone $today)->byStatus(VisitStatus::COMPLETED)->count(),
            'cancelled' => (clone $today)->byStatus(VisitStatus::CANCELLED)->count(),
            'emergency' => (clone $today)->where('priority', 'emergency')->count(),
        ];
    }

    /**
     * Get upcoming scheduled visits for a patient.
     */
    public function upcomingForPatient(int $patientId): \Illuminate\Database\Eloquent\Collection
    {
        return Visit::where('patient_id', $patientId)
            ->whereIn('status', [VisitStatus::SCHEDULED, VisitStatus::CONFIRMED])
            ->where('visit_date', '>=', today())
            ->orderBy('visit_date')
            ->orderBy('start_time')
            ->with(['department', 'assignedDoctor'])
            ->get();
    }

    /**
     * Get calendar data for scheduled visits.
     */
    public function calendarData(array $filters = []): array
    {
        $query = Visit::whereIn('status', [
            VisitStatus::SCHEDULED,
            VisitStatus::CONFIRMED,
        ]);

        if (!empty($filters['doctor_id'])) {
            $query->where('assigned_doctor_id', $filters['doctor_id']);
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['start'])) {
            $query->whereDate('visit_date', '>=', $filters['start']);
        }

        if (!empty($filters['end'])) {
            $query->whereDate('visit_date', '<=', $filters['end']);
        }

        return $query->with(['patient', 'assignedDoctor', 'department'])
            ->get()
            ->map(fn(Visit $v) => [
                'id' => $v->id,
                'title' => $v->patient->full_name,
                'start' => $v->visit_date->format('Y-m-d') . ($v->start_time ? 'T' . $v->start_time : ''),
                'end' => $v->visit_date->format('Y-m-d') . ($v->end_time ? 'T' . $v->end_time : ''),
                'color' => $v->status->color(),
                'extendedProps' => [
                    'visit_id' => $v->id,
                    'patient_name' => $v->patient->full_name,
                    'doctor' => $v->assignedDoctor?->name,
                    'department' => $v->department?->name,
                    'status' => $v->status->label(),
                    'visit_type' => $v->visit_type?->label(),
                ],
            ])->toArray();
    }
}
