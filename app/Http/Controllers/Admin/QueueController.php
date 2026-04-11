<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\QueueEntry;
use App\Services\QueueService;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    public function __construct(
        protected QueueService $queueService,
    ) {}

    public function manage(Request $request)
    {
        $departments = Department::active()->orderBy('name')->get();
        $selectedDepartment = $request->get('department_id');

        $queue = collect();
        $serving = collect();

        if ($selectedDepartment) {
            $queue = $this->queueService->getDepartmentQueue((int) $selectedDepartment);
            $serving = QueueEntry::with(['visit.patient', 'servedBy'])
                ->forDepartment((int) $selectedDepartment)
                ->today()
                ->where('status', 'serving')
                ->get();
        }

        return view('queue.manage', compact('departments', 'selectedDepartment', 'queue', 'serving'));
    }

    public function board()
    {
        $departments = Department::active()->orderBy('name')->get();

        $queues = QueueEntry::with(['visit.patient', 'department'])
            ->today()
            ->whereIn('status', ['waiting', 'serving'])
            ->orderByRaw("FIELD(priority, 'emergency', 'urgent', 'normal')")
            ->orderBy('queue_number')
            ->get()
            ->groupBy('department_id');

        return view('queue.board', compact('departments', 'queues'));
    }

    public function callNext(Request $request)
    {
        $request->validate(['department_id' => 'required|exists:departments,id']);

        $entry = $this->queueService->callNext((int) $request->department_id);

        if (!$entry) {
            return back()->with('info', 'No patients waiting in this department queue.');
        }

        return back()->with('success', "Now serving #{$entry->queue_number} — {$entry->visit->patient->full_name}");
    }

    public function complete(QueueEntry $queueEntry)
    {
        $this->queueService->markCompleted($queueEntry);
        return back()->with('success', "Queue #{$queueEntry->queue_number} marked as completed.");
    }

    public function skip(QueueEntry $queueEntry)
    {
        $this->queueService->skip($queueEntry);
        return back()->with('success', "Queue #{$queueEntry->queue_number} skipped.");
    }

    public function requeue(QueueEntry $queueEntry)
    {
        $entry = $this->queueService->requeue($queueEntry);
        return back()->with('success', "Patient re-queued as #{$entry->queue_number}.");
    }
}
