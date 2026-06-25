<?php

namespace App\Http\Controllers\Admin\Appointments;

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

    public function board(Request $request)
    {
        $departments = Department::active()->orderBy('name')->get();

        $entries = QueueEntry::with(['visit.patient', 'department'])
            ->today()
            ->whereIn('status', ['waiting', 'serving'])
            ->orderBy('queue_number')
            ->orderBy('created_at')
            ->get();

        $triageQueue = $entries
            ->filter(fn (QueueEntry $entry) => $entry->department_id === null)
            ->values();

        $queues = $entries
            ->filter(fn (QueueEntry $entry) => $entry->department_id !== null)
            ->groupBy('department_id');

        $viewData = compact('departments', 'queues', 'triageQueue');

        if ($request->boolean('embedded')) {
            return view('queue.partials.board-content', $viewData);
        }

        return view('queue.board', $viewData);
    }

    public function callNext(Request $request)
    {
        $request->validate(['department_id' => 'required|exists:departments,id']);

        $entry = $this->queueService->callNext((int) $request->department_id);

        if (!$entry) {
            return back()->with('info', __('messages.queue.none_waiting'));
        }

        return back()->with('success', __('messages.queue.now_serving', ['number' => $entry->queue_number, 'name' => $entry->visit->patient->full_name]));
    }

    public function complete(QueueEntry $queueEntry)
    {
        $this->queueService->markCompleted($queueEntry);
        return back()->with('success', __('messages.queue.completed', ['number' => $queueEntry->queue_number]));
    }

    public function skip(QueueEntry $queueEntry)
    {
        $this->queueService->skip($queueEntry);
        return back()->with('success', __('messages.queue.skipped', ['number' => $queueEntry->queue_number]));
    }

    public function requeue(QueueEntry $queueEntry)
    {
        $entry = $this->queueService->requeue($queueEntry);
        return back()->with('success', __('messages.queue.requeued', ['number' => $entry->queue_number]));
    }
}
