<?php

namespace App\Http\Controllers\Admin\Appointments;

use App\Http\Controllers\Controller;
use App\Models\ConsultationTask;
use App\Models\Visit;
use App\Services\ClinicalFrequencyOptionService;
use App\Services\Consultation\ConsultationActionException;
use App\Services\Consultation\ConsultationActionGuard;
use App\Services\Consultation\ConsultationIdempotencyService;
use App\Services\Consultation\ConsultationTaskFrequencyExpansionService;
use App\Services\ConsultationContributorService;
use App\Services\ConsultationService;
use App\Services\MedicalRecordEntryLogService;
use App\Services\MedicalRecordEntryPermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ConsultationTaskController extends Controller
{
    public function __construct(
        protected ConsultationService $consultationService,
        protected ConsultationContributorService $contributors,
        protected MedicalRecordEntryLogService $entryLogs,
        protected MedicalRecordEntryPermissionService $permissions,
        protected ConsultationActionGuard $actionGuard,
        protected ConsultationIdempotencyService $idempotency,
        protected ClinicalFrequencyOptionService $frequencyOptions,
        protected ConsultationTaskFrequencyExpansionService $taskExpansion,
    ) {}

    public function store(Request $request, Visit $visit)
    {
        $allowedFrequencies = $this->frequencyOptions->values();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'priority' => ['nullable', 'string', 'in:low,medium,high'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'start_at' => ['nullable', 'date'],
            'frequency' => ['required', 'string', Rule::in($allowedFrequencies)],
        ]);

        try {
            $context = $this->actionGuard->editable(
                $visit,
                $request->integer('consultation_route_id') ?: null,
                Auth::user(),
                'task.create',
                'consultations.create',
            );
            $result = $this->idempotency->run(
                $request,
                'task.create',
                $visit,
                $context->route,
                $request->all(),
                fn () => $this->taskExpansion->createTasks($context->medicalRecord, $context->route, $data, Auth::user()),
            );
            $tasks = $result instanceof \Illuminate\Support\Collection
                ? $result
                : collect($result ? [$result] : []);
        } catch (ConsultationActionException $e) {
            if (($request->ajax() || $request->expectsJson()) && ! $request->header('X-Inertia')) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }

        if (($request->ajax() || $request->expectsJson()) && ! $request->header('X-Inertia')) {
            $tasks->each(fn ($task) => $task instanceof ConsultationTask ? $task->load(['assignedUser', 'creator']) : null);

            return response()->json([
                'success' => true,
                'count' => $tasks->count(),
                'tasks' => $tasks->values(),
                'message' => trans_choice('messages.consultation_tasks.created_count', max(1, $tasks->count()), ['count' => max(1, $tasks->count())]),
            ]);
        }

        return back()->with('success', __('messages.consultation_tasks.created'));
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
            'scheduled_at' => ['nullable', 'date'],
            'frequency' => ['nullable', 'string', Rule::in($this->frequencyOptions->values())],
        ]);

        abort_unless(Auth::user() && $this->permissions->canEdit(Auth::user(), $task), 403);

        if (($data['status'] ?? null) === 'completed' && $task->status !== 'completed') {
            $data['completed_at'] = now();
            $data['completed_by'] = Auth::id();
        }

        $old = $task->getOriginal();
        $task->update($data);
        $this->entryLogs->updated($task, $old, Auth::user());

        if (($request->ajax() || $request->expectsJson()) && ! $request->header('X-Inertia')) {
            return response()->json(['success' => true, 'task' => $task->fresh(['assignedUser', 'creator', 'completedBy'])]);
        }

        return back()->with('success', __('messages.consultation_tasks.updated'));
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

        if ((request()->ajax() || request()->expectsJson()) && ! request()->header('X-Inertia')) {
            return response()->json(['success' => true, 'task' => $task->fresh(['assignedUser', 'creator', 'completedBy'])]);
        }

        return back()->with('success', __('messages.consultation_tasks.toggled'));
    }

    public function destroy(ConsultationTask $task)
    {
        abort_unless(Auth::user() && $this->permissions->canDelete(Auth::user(), $task), 403);
        $this->entryLogs->deleted($task, Auth::user());
        $task->delete();

        if ((request()->ajax() || request()->expectsJson()) && ! request()->header('X-Inertia')) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', __('messages.consultation_tasks.deleted'));
    }
}
