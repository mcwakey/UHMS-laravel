<?php

namespace App\Http\Controllers\Nursing;

use App\Enums\LogModule;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Nursing\Concerns\ResolvesNursingDepartment;
use App\Models\ClinicalTask;
use App\Services\ActivityLogService;
use App\Services\Nursing\NursingOpdService;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    use ResolvesNursingDepartment;

    public function index(Request $request, NursingOpdService $opd)
    {
        $department = $this->nursingDepartment($request);
        $tasks = $opd->tasks($department)->with(['patient:id,patient_number,first_name,last_name', 'visit:id,visit_number,status', 'assignedUser:id,name'])
            ->orderByRaw('due_at IS NULL')->orderBy('due_at')->paginate(20)->withQueryString();

        return view('nursing.tasks.index', compact('department', 'tasks'));
    }

    public function show(Request $request, ClinicalTask $task, NursingOpdService $opd)
    {
        $department = $this->nursingDepartment($request);
        abort_unless($opd->tasks($department)->whereKey($task->id)->exists(), 404);
        $task->load(['patient', 'visit', 'assignedUser', 'completedBy']);

        return view('nursing.tasks.show', compact('task'));
    }

    public function update(Request $request, ClinicalTask $task, NursingOpdService $opd, ActivityLogService $activity)
    {
        $department = $this->nursingDepartment($request);
        abort_unless($opd->tasks($department)->whereKey($task->id)->exists(), 404);
        $task->loadMissing('visit');
        abort_if(! $task->visit?->visit_date?->isToday(), 422, __('nursing.tasks.closed_visit'));

        $data = $request->validate([
            'action' => ['required', 'in:claim,start,complete'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        abort_if($task->status === ClinicalTask::STATUS_COMPLETED, 409, __('nursing.tasks.already_completed'));
        abort_if($data['action'] === 'claim' && $task->assigned_to && $task->assigned_to !== $request->user()->id, 409, __('nursing.tasks.already_claimed'));

        $event = match ($data['action']) {
            'claim' => 'NURSING_TASK_CLAIMED',
            'start' => 'NURSING_TASK_STARTED',
            'complete' => 'NURSING_TASK_COMPLETED',
        };
        $changes = match ($data['action']) {
            'claim' => ['assigned_to' => $request->user()->id, 'status' => ClinicalTask::STATUS_IN_PROGRESS],
            'start' => ['status' => ClinicalTask::STATUS_IN_PROGRESS],
            'complete' => ['status' => ClinicalTask::STATUS_COMPLETED, 'completed_by' => $request->user()->id, 'completed_at' => now()],
        };
        if (array_key_exists('notes', $data)) {
            $changes['notes'] = $data['notes'];
        }

        $task->update($changes);
        $activity->log(LogModule::CLINICAL_TASKS, $event, [
            'patient_id' => $task->patient_id,
            'visit_id' => $task->visit_id,
            'department_id' => $department->id,
        ], $task, __('nursing.tasks.updated'));

        return redirect()->route('nursing.tasks.show', $task)->with('success', __('nursing.tasks.updated'));
    }
}
