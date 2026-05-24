<?php

namespace App\Services;

use App\Models\QueueEntry;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class QueueService
{
    public function addToQueue(Visit $visit): void
    {
        // No-op: queue entries are now created explicitly per stage
        // (triage via addTriageEntry, departments via addForDepartment)
    }

    /**
     * Create a triage queue entry (no specific department).
     */
    public function addTriageEntry(Visit $visit): QueueEntry
    {
        $queueNumber = QueueEntry::nextQueueNumber(null);

        return QueueEntry::create([
            'visit_id'      => $visit->id,
            'department_id' => null,
            'queue_number'  => $queueNumber,
            'priority'      => $visit->priority->value,
            'status'        => 'waiting',
        ]);
    }

    /**
     * Create a queue entry for a specific department.
     */
    public function addForDepartment(Visit $visit, int $departmentId): QueueEntry
    {
        $queueNumber = QueueEntry::nextQueueNumber($departmentId);

        return QueueEntry::create([
            'visit_id'      => $visit->id,
            'department_id' => $departmentId,
            'queue_number'  => $queueNumber,
            'priority'      => $visit->priority->value,
            'status'        => 'waiting',
        ]);
    }

    public function getDepartmentQueue(int $departmentId): Collection
    {
        return QueueEntry::with([
            'visit.patient',
            'visit.activeConsultationRoute.doctor',
            'visit.pendingConsultationRoutes.doctor',
            'servedBy',
        ])
            ->forDepartment($departmentId)
            ->today()
            ->waiting()
            ->orderByRaw("FIELD(priority, 'emergency', 'urgent', 'normal')")
            ->orderBy('queue_number')
            ->get();
    }

    public function getAllQueues(): Collection
    {
        return QueueEntry::with(['visit.patient', 'department'])
            ->today()
            ->waiting()
            ->orderByRaw("FIELD(priority, 'emergency', 'urgent', 'normal')")
            ->orderBy('queue_number')
            ->get();
    }

    public function callNext(int $departmentId): ?QueueEntry
    {
        $entry = QueueEntry::forDepartment($departmentId)
            ->today()
            ->waiting()
            ->orderByRaw("FIELD(priority, 'emergency', 'urgent', 'normal')")
            ->orderBy('queue_number')
            ->first();

        if ($entry) {
            $entry->update([
                'status' => 'serving',
                'called_at' => now(),
                'served_by' => Auth::id(),
            ]);
        }

        return $entry?->fresh(['visit.patient', 'department']);
    }

    public function markServing(QueueEntry $entry): QueueEntry
    {
        $entry->update([
            'status' => 'serving',
            'served_at' => now(),
            'served_by' => Auth::id(),
        ]);

        return $entry->fresh();
    }

    public function markCompleted(QueueEntry $entry): QueueEntry
    {
        $entry->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return $entry->fresh();
    }

    public function skip(QueueEntry $entry): QueueEntry
    {
        $entry->update(['status' => 'skipped']);
        return $entry->fresh();
    }

    public function requeue(QueueEntry $entry): QueueEntry
    {
        $newNumber = QueueEntry::nextQueueNumber($entry->department_id);

        $entry->update([
            'status' => 'waiting',
            'queue_number' => $newNumber,
            'called_at' => null,
            'served_at' => null,
            'served_by' => null,
        ]);

        return $entry->fresh();
    }

    public function completeCurrentEntry(Visit $visit): void
    {
        $entry = $visit->queueEntries()
            ->whereIn('status', ['waiting', 'serving'])
            ->latest()
            ->first();

        if ($entry) {
            $this->markCompleted($entry);
        }
    }

    public function boardData(): array
    {
        $queues = QueueEntry::with(['visit.patient', 'department'])
            ->today()
            ->whereIn('status', ['waiting', 'serving'])
            ->orderByRaw("FIELD(priority, 'emergency', 'urgent', 'normal')")
            ->orderBy('queue_number')
            ->get()
            ->groupBy('department_id');

        return $queues->toArray();
    }
}
