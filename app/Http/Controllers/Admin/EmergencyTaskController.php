<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LogModule;
use App\Http\Controllers\Controller;
use App\Models\ClinicalTask;
use App\Models\EmergencyCase;
use App\Services\ActivityLogService;
use App\Services\ClinicalTaskService;
use App\Services\EmergencySessionService;
use Illuminate\Http\Request;

/**
 * Lets clinicians add and complete monitoring / nursing tasks directly on the
 * emergency case view (reuses the shared ClinicalTaskService — no parallel task
 * system). Medication-administration tasks are owned by the MAR and are not
 * toggled here.
 */
class EmergencyTaskController extends Controller
{
    public function __construct(
        private ClinicalTaskService $tasks,
        private EmergencySessionService $sessions,
        private ActivityLogService $logger,
    ) {}

    public function store(Request $request, EmergencyCase $emergencyCase)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'priority' => ['nullable', 'in:low,normal,high,critical'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $session = $this->sessions->getOrCreateForCase($emergencyCase, $request->user());
        $scheduledAt = $data['scheduled_at'] ?? now();

        $task = $this->tasks->createTask([
            'visit_id' => $emergencyCase->visit_id,
            'emergency_case_id' => $emergencyCase->id,
            'emergency_session_id' => $session->id,
            'medical_record_id' => $session->medical_record_id,
            'consultation_route_id' => $session->consultation_route_id,
            'patient_id' => $emergencyCase->patient_id,
            'task_type' => ClinicalTask::TYPE_NURSING_OBSERVATION,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'scheduled_at' => $scheduledAt,
            'due_at' => $scheduledAt,
            'status' => ClinicalTask::STATUS_SCHEDULED,
            'priority' => $data['priority'] ?? 'normal',
            'assigned_to' => $data['assigned_to'] ?? null,
            'assigned_role' => empty($data['assigned_to']) ? 'Emergency Nurse' : null,
        ]);

        $this->logger->logClinicalAction($task, LogModule::EMERGENCY, 'CREATED', [
            'emergency_case_id' => $emergencyCase->id,
            'title' => $task->title,
        ]);

        return back()->with('success', __('messages.emergency.task_added'));
    }

    public function complete(Request $request, EmergencyCase $emergencyCase, ClinicalTask $task)
    {
        abort_unless((int) $task->emergency_case_id === (int) $emergencyCase->id, 404);

        // Medication tasks are completed via the MAR, not here.
        abort_if($task->task_type === ClinicalTask::TYPE_MEDICATION_ADMINISTRATION, 422, 'Administer medication tasks from the MAR.');

        if (in_array($task->status, [ClinicalTask::STATUS_COMPLETED, ClinicalTask::STATUS_CANCELLED], true)) {
            $task->update(['status' => ClinicalTask::STATUS_SCHEDULED, 'completed_by' => null, 'completed_at' => null]);
            $this->logger->logClinicalAction($task, LogModule::EMERGENCY, 'REOPENED', ['emergency_case_id' => $emergencyCase->id]);
        } else {
            $this->tasks->completeTask($task, $request->user());
            $this->logger->logClinicalAction($task, LogModule::EMERGENCY, 'COMPLETED', ['emergency_case_id' => $emergencyCase->id]);
        }

        return back()->with('success', __('messages.emergency.task_updated'));
    }
}
