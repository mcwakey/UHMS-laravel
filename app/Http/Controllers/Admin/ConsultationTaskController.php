<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsultationTask;
use App\Models\Visit;
use App\Services\ConsultationContributorService;
use App\Services\ConsultationService;
use App\Services\MedicalRecordEntryLogService;
use App\Services\MedicalRecordEntryPermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConsultationTaskController extends Controller
{
    public function __construct(
        protected ConsultationService $consultationService,
        protected ConsultationContributorService $contributors,
        protected MedicalRecordEntryLogService $entryLogs,
        protected MedicalRecordEntryPermissionService $permissions,
    ) {}

    public function store(Request $request, Visit $visit)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'priority' => ['nullable', 'string', 'in:low,medium,high'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
        ]);

        $data['created_by'] = Auth::id();
        $data['status'] = 'pending';

        $medicalRecord = $this->consultationService->getOrCreateRecord(
            $visit,
            $request->integer('consultation_route_id') ?: null,
        );
        $task = $medicalRecord->tasks()->create(array_merge($data, [
            'consultation_route_id' => $medicalRecord->consultation_route_id,
            'visit_id' => $medicalRecord->visit_id,
            'patient_id' => $medicalRecord->patient_id,
            'department_id' => $medicalRecord->department_id,
        ]));

        if ($user = Auth::user()) {
            $this->contributors->recordContribution($medicalRecord, $user, 'Task');
            $this->entryLogs->created($task, $user);
        }

        if ($request->ajax() && ! $request->header('X-Inertia')) {
            return response()->json(['success' => true, 'task' => $task->load(['assignedUser', 'creator'])]);
        }

        return back()->with('success', 'Task created.');
    }

    public function update(Request $request, ConsultationTask $task)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'priority' => ['nullable', 'string', 'in:low,medium,high'],
            'status' => ['nullable', 'string', 'in:pending,in_progress,completed,cancelled'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
        ]);

        abort_unless(Auth::user() && $this->permissions->canEdit(Auth::user(), $task), 403);

        if (($data['status'] ?? null) === 'completed' && $task->status !== 'completed') {
            $data['completed_at'] = now();
            $data['completed_by'] = Auth::id();
        }

        $old = $task->getOriginal();
        $task->update($data);
        $this->entryLogs->updated($task, $old, Auth::user());

        if ($request->ajax() && ! $request->header('X-Inertia')) {
            return response()->json(['success' => true, 'task' => $task->fresh('assignedUser')]);
        }

        return back()->with('success', 'Task updated.');
    }

    public function toggleComplete(ConsultationTask $task)
    {
        abort_unless(Auth::user() && $this->permissions->canEdit(Auth::user(), $task), 403);

        $old = $task->getOriginal();
        if ($task->status === 'completed') {
            $task->update(['status' => 'pending', 'completed_at' => null, 'completed_by' => null]);
        } else {
            $task->markCompleted(Auth::id());
        }
        $this->entryLogs->updated($task, $old, Auth::user());

        if (request()->ajax() && ! request()->header('X-Inertia')) {
            return response()->json(['success' => true, 'task' => $task->fresh()]);
        }

        return back()->with('success', 'Task status toggled.');
    }

    public function destroy(ConsultationTask $task)
    {
        abort_unless(Auth::user() && $this->permissions->canDelete(Auth::user(), $task), 403);
        $this->entryLogs->deleted($task, Auth::user());
        $task->delete();

        if (request()->ajax() && ! request()->header('X-Inertia')) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Task deleted.');
    }
}
