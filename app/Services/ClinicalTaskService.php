<?php

namespace App\Services;

use App\Models\ClinicalTask;
use App\Models\MedicationAdministrationSchedule;
use App\Models\User;

class ClinicalTaskService
{
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
