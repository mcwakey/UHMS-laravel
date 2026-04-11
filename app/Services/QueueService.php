<?php

namespace App\Services;

use App\Models\QueueEntry;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Collection;

class QueueService
{
    public function addToQueue(Visit $visit): QueueEntry
    {
        $queueNumber = QueueEntry::nextQueueNumber($visit->department_id);

        return QueueEntry::create([
            'visit_id' => $visit->id,
            'department_id' => $visit->department_id,
            'queue_number' => $queueNumber,
            'priority' => $visit->priority->value,
            'status' => 'waiting',
        ]);
    }

    public function getDepartmentQueue(int $departmentId): Collection
    {
        return QueueEntry::with(['visit.patient', 'visit.assignedDoctor', 'servedBy'])
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
                'served_by' => auth()->id(),
            ]);
        }

        return $entry?->fresh(['visit.patient', 'department']);
    }

    public function markServing(QueueEntry $entry): QueueEntry
    {
        $entry->update([
            'status' => 'serving',
            'served_at' => now(),
            'served_by' => auth()->id(),
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
