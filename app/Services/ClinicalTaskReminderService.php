<?php

namespace App\Services;

use App\Models\ClinicalTask;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ClinicalTaskReminderService
{
    public function syncMedicationTaskStatuses(?Builder $scope = null): int
    {
        $now = now();
        $overdueAfter = (int) Setting::getValue('medication', 'medication_task_overdue_after_minutes', 15);
        $firstEscalation = (int) Setting::getValue('medication', 'medication_task_escalate_after_minutes', 30);
        $secondEscalation = (int) Setting::getValue('medication', 'medication_task_second_escalation_after_minutes', 60);

        $query = ($scope ?: ClinicalTask::query())
            ->where('task_type', ClinicalTask::TYPE_MEDICATION_ADMINISTRATION)
            ->whereNotIn('status', [
                ClinicalTask::STATUS_COMPLETED,
                ClinicalTask::STATUS_MISSED,
                ClinicalTask::STATUS_HELD,
                ClinicalTask::STATUS_REFUSED,
                ClinicalTask::STATUS_SKIPPED,
                ClinicalTask::STATUS_CANCELLED,
            ]);

        $updated = 0;

        $query->chunkById(200, function (Collection $tasks) use ($now, $overdueAfter, $firstEscalation, $secondEscalation, &$updated) {
            foreach ($tasks as $task) {
                if (! $task->due_at) {
                    continue;
                }

                $minutesLate = $task->due_at->diffInMinutes($now, false);
                $status = $task->status;
                $escalation = (int) $task->escalation_level;

                if ($minutesLate >= $overdueAfter) {
                    $status = ClinicalTask::STATUS_OVERDUE;
                } elseif ($minutesLate >= 0) {
                    $status = ClinicalTask::STATUS_DUE;
                }

                if ($minutesLate >= $secondEscalation) {
                    $escalation = max($escalation, 2);
                } elseif ($minutesLate >= $firstEscalation) {
                    $escalation = max($escalation, 1);
                }

                if ($status !== $task->status || $escalation !== (int) $task->escalation_level) {
                    $task->update([
                        'status' => $status,
                        'escalation_level' => $escalation,
                        'last_reminded_at' => $status === ClinicalTask::STATUS_OVERDUE ? now() : $task->last_reminded_at,
                    ]);
                    $updated++;
                }
            }
        });

        return $updated;
    }

    public function countsForAdmission(int $admissionId): array
    {
        $this->syncMedicationTaskStatuses(ClinicalTask::query()->where('admission_id', $admissionId));

        return $this->counts(ClinicalTask::query()->where('admission_id', $admissionId));
    }

    public function countsForEmergency(): array
    {
        $scope = ClinicalTask::query()
            ->whereNull('admission_id')
            ->whereNotNull('emergency_case_id');

        $this->syncMedicationTaskStatuses(clone $scope);

        return $this->counts($scope);
    }

    public function counts(Builder $query): array
    {
        $base = $query->where('task_type', ClinicalTask::TYPE_MEDICATION_ADMINISTRATION);

        return [
            'upcoming' => (clone $base)->where('status', ClinicalTask::STATUS_SCHEDULED)->whereBetween('due_at', [now(), now()->addMinutes(30)])->count(),
            'due_now' => (clone $base)->where('status', ClinicalTask::STATUS_DUE)->count(),
            'overdue' => (clone $base)->where('status', ClinicalTask::STATUS_OVERDUE)->count(),
            'completed_today' => (clone $base)->where('status', ClinicalTask::STATUS_COMPLETED)->whereDate('completed_at', today())->count(),
            'missed' => (clone $base)->whereIn('status', [
                ClinicalTask::STATUS_MISSED,
                ClinicalTask::STATUS_HELD,
                ClinicalTask::STATUS_REFUSED,
                ClinicalTask::STATUS_SKIPPED,
            ])->count(),
            'escalated' => (clone $base)->where('escalation_level', '>', 0)->where('status', ClinicalTask::STATUS_OVERDUE)->count(),
        ];
    }
}
