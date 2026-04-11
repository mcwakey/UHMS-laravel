<?php

namespace App\Services;

use App\Enums\VisitStatus;
use App\Models\Patient;
use App\Models\QueueEntry;
use App\Models\Visit;
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

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): Visit
    {
        $data['visit_number'] = Visit::generateVisitNumber();
        $data['visit_date'] = $data['visit_date'] ?? today();
        $data['status'] = VisitStatus::REGISTERED->value;
        $data['created_by'] = auth()->id();

        $visit = Visit::create($data);

        // Log the initial status
        $visit->statusLogs()->create([
            'from_status' => null,
            'to_status' => VisitStatus::REGISTERED->value,
            'changed_by' => auth()->id(),
            'notes' => 'Visit created',
        ]);

        // Auto-transition to waiting and create queue entry
        $visit->transitionTo(VisitStatus::WAITING);

        if ($visit->department_id) {
            $this->queueService->addToQueue($visit);
        }

        return $visit->fresh(['patient', 'department', 'assignedDoctor']);
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
}
