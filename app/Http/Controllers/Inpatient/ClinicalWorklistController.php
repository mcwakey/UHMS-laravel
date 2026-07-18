<?php

namespace App\Http\Controllers\Inpatient;

use App\Enums\LogModule;
use App\Http\Controllers\Controller;
use App\Models\ClinicalTask;
use App\Models\Treatment;
use App\Models\Visit;
use App\Services\ActivityLogService;
use App\Services\ClinicalTaskService;
use App\Services\Department\DepartmentContextSwitcherService;
use App\Services\InpatientWorkspaceScope;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClinicalWorklistController extends Controller
{
    public function __construct(
        private InpatientWorkspaceScope $scope,
        private DepartmentContextSwitcherService $departments,
    ) {}

    public function tasks(Request $request)
    {
        $query = ClinicalTask::query()
            ->whereNotNull('admission_id')
            ->whereHas('admission.bed.ward', fn ($ward) => $ward->where('department_id', $this->scope->departmentId()))
            ->with(['patient:id,patient_number,first_name,last_name', 'visit:id,visit_number,status', 'admission:id,admission_number', 'assignedUser:id,name']);

        $query->when($request->status, fn ($tasks, $status) => $tasks->where('status', $status));
        $query->when($request->task_type, fn ($tasks, $type) => $tasks->where('task_type', $type));
        $query->when($request->boolean('assigned_to_me'), fn ($tasks) => $tasks->where('assigned_to', $request->user()->id));

        $tasks = $query->orderByRaw('due_at IS NULL')->orderBy('due_at')->paginate(20)->withQueryString();
        $department = $this->departments->currentDepartment($request->user(), $request);

        return view('nursing.tasks.index', compact('department', 'tasks'));
    }

    public function task(ClinicalTask $task)
    {
        $this->assertTaskScope($task);
        $task->load(['patient', 'visit', 'admission', 'assignedUser', 'completedBy']);

        return view('nursing.tasks.show', compact('task'));
    }

    public function updateTask(Request $request, ClinicalTask $task, ClinicalTaskService $tasks, ActivityLogService $activity)
    {
        $this->assertTaskScope($task);
        $data = $request->validate([
            'action' => ['required', Rule::in(['claim', 'start', 'complete'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        abort_if($task->status === ClinicalTask::STATUS_COMPLETED, 409, __('nursing.tasks.already_completed'));
        abort_if($data['action'] === 'claim' && $task->assigned_to && $task->assigned_to !== $request->user()->id, 409, __('nursing.tasks.already_claimed'));

        if ($data['action'] === 'complete') {
            $tasks->completeTask($task, $request->user(), notes: $data['notes'] ?? null);
        } else {
            $task->update(array_filter([
                'assigned_to' => $data['action'] === 'claim' ? $request->user()->id : $task->assigned_to,
                'status' => ClinicalTask::STATUS_IN_PROGRESS,
                'notes' => $data['notes'] ?? null,
            ], fn ($value) => $value !== null));
        }

        $event = match ($data['action']) {
            'claim' => 'INPATIENT_TASK_CLAIMED',
            'start' => 'INPATIENT_TASK_STARTED',
            'complete' => 'INPATIENT_TASK_COMPLETED',
        };

        $activity->log(LogModule::CLINICAL_TASKS, $event, [
            'patient_id' => $task->patient_id,
            'visit_id' => $task->visit_id,
            'admission_id' => $task->admission_id,
            'department_id' => $this->scope->departmentId(),
        ], $task->fresh(), 'Inpatient clinical task updated');

        return redirect()->route('inpatient.tasks.show', $task)->with('success', __('nursing.tasks.updated'));
    }

    public function treatments()
    {
        $treatments = Treatment::query()
            ->whereHas('visit.admission.bed.ward', fn ($ward) => $ward->where('department_id', $this->scope->departmentId()))
            ->with(['patient:id,patient_number,first_name,last_name', 'visit:id,visit_number,status', 'doctor:id,first_name,last_name'])
            ->latest()->paginate(20)->withQueryString();

        return view('nursing.treatments.index', compact('treatments'));
    }

    public function treatment(Visit $visit)
    {
        abort_unless($this->scope->contains($visit), 404);
        $visit->load('patient');
        $treatments = Treatment::query()->where('visit_id', $visit->id)->with('doctor')->latest()->get();

        return view('nursing.treatments.show', compact('visit', 'treatments'));
    }

    private function assertTaskScope(ClinicalTask $task): void
    {
        abort_unless($task->admission_id && $task->admission()
            ->whereHas('bed.ward', fn ($ward) => $ward->where('department_id', $this->scope->departmentId()))
            ->exists(), 404);
    }
}
