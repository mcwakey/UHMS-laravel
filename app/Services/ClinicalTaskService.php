<?php

namespace App\Services;

use App\Models\ClinicalTask;
use App\Models\MedicationAdministrationSchedule;
use App\Models\User;

class ClinicalTaskService
{
    public function createTask(array $data): ClinicalTask
    {
        return ClinicalTask::create([
            'visit_id' => $data['visit_id'] ?? null,
            'admission_id' => $data['admission_id'] ?? null,
            'emergency_case_id' => $data['emergency_case_id'] ?? null,
            'emergency_session_id' => $data['emergency_session_id'] ?? null,
            'medical_record_id' => $data['medical_record_id'] ?? null,
            'consultation_route_id' => $data['consultation_route_id'] ?? null,
            'patient_id' => $data['patient_id'] ?? null,
            'task_type' => $data['task_type'] ?? ClinicalTask::TYPE_OTHER,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'scheduled_at' => $data['scheduled_at'] ?? $data['due_at'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'status' => $data['status'] ?? ClinicalTask::STATUS_SCHEDULED,
            'priority' => $data['priority'] ?? 'normal',
            'assigned_to' => $data['assigned_to'] ?? null,
            'assigned_role' => $data['assigned_role'] ?? null,
            'assigned_department_id' => $data['assigned_department_id'] ?? null,
            'source_type' => $data['source_type'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function createMedicationTaskForSchedule(MedicationAdministrationSchedule $schedule): ClinicalTask
    {
        $order = $schedule->medicationOrder()->with(['prescriber', 'admission.bed.ward'])->first();

        $task = ClinicalTask::updateOrCreate(
            [
                'task_type' => ClinicalTask::TYPE_MEDICATION_ADMINISTRATION,
                'source_type' => MedicationAdministrationSchedule::class,
                'source_id' => $schedule->id,
            ],
            [
                'visit_id' => $schedule->visit_id,
                'admission_id' => $schedule->admission_id,
                'emergency_case_id' => $schedule->emergency_case_id,
                'emergency_session_id' => $schedule->emergency_session_id,
                'medical_record_id' => $schedule->medical_record_id,
                'consultation_route_id' => $schedule->consultation_route_id,
                'patient_id' => $schedule->patient_id,
                'title' => 'Administer '.$order->display_name,
                'description' => trim(sprintf(
                    'Dose %d/%d: %s %s %s',
                    $schedule->sequence_number,
                    max(1, (int) $order->total_doses),
                    $schedule->dose ?: $order->dose ?: '',
                    $schedule->dose_unit ?: $order->dose_unit ?: '',
                    $schedule->route ?: $order->route ?: '',
                )),
                'scheduled_at' => $schedule->scheduled_at,
                'due_at' => $schedule->scheduled_at,
                'status' => ClinicalTask::STATUS_SCHEDULED,
                'priority' => $order->frequency_code === 'STAT' ? 'critical' : 'normal',
                'assigned_role' => $schedule->admission_id ? 'Ward Nurse' : 'Emergency Nurse',
                'assigned_department_id' => $order->admission?->bed?->ward?->department_id,
            ],
        );

        $schedule->update(['clinical_task_id' => $task->id]);

        return $task;
    }

    /**
     * Generic task completion (monitoring / nursing tasks, etc.).
     */
    public function completeTask(ClinicalTask $task, User $user, string $status = ClinicalTask::STATUS_COMPLETED, ?string $notes = null): ClinicalTask
    {
        $task->update([
            'status' => $status,
            'completed_by' => $user->id,
            'completed_at' => now(),
            'notes' => $notes ?? $task->notes,
        ]);

        return $task;
    }

    public function completeMedicationTask(ClinicalTask $task, User $user, string $status = ClinicalTask::STATUS_COMPLETED, ?string $notes = null): ClinicalTask
    {
        $task->update([
            'status' => $status,
            'completed_by' => $user->id,
            'completed_at' => now(),
            'notes' => $notes ?? $task->notes,
        ]);

        return $task;
    }

    public function cancelTask(ClinicalTask $task, User $user, ?string $reason = null): ClinicalTask
    {
        $task->update([
            'status' => ClinicalTask::STATUS_CANCELLED,
            'completed_by' => $user->id,
            'completed_at' => now(),
            'notes' => $reason,
        ]);

        return $task;
    }
}
