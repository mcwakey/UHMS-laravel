<?php

namespace App\Console\Commands;

use App\Enums\NotificationModule;
use App\Enums\NotificationPriority;
use App\Enums\NursingTaskStatus;
use App\Models\NursingTask;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class NursingTasksCheckDueCommand extends Command
{
    protected $signature = 'nursing-tasks:check-due
        {--lookahead=15 : Minutes before due time to alert}
        {--dedupe=60 : Minutes before the same user can receive the same task reminder again}';

    protected $description = 'Notify nursing staff about admission nursing tasks that are due or overdue.';

    public function handle(NotificationService $notifier): int
    {
        $lookahead = max(0, (int) $this->option('lookahead'));
        $dedupe = max(0, (int) $this->option('dedupe'));
        $now = now();
        $cutoff = $now->copy()->addMinutes($lookahead);

        $tasks = NursingTask::query()
            ->with([
                'admission.bed.ward.department',
                'assignedTo',
                'createdBy',
                'patient',
            ])
            ->whereIn('status', [
                NursingTaskStatus::OPEN->value,
                NursingTaskStatus::IN_PROGRESS->value,
            ])
            ->whereNotNull('due_at')
            ->where('due_at', '<=', $cutoff)
            ->orderBy('due_at')
            ->limit(500)
            ->get();

        $sent = 0;

        foreach ($tasks as $task) {
            $isOverdue = $task->due_at?->lt($now) ?? false;
            $patientName = $task->patient?->full_name ?: 'patient';
            $dueLabel = $task->due_at?->format('d M H:i') ?? 'now';

            $payload = [
                'module' => NotificationModule::ADMISSION,
                'priority' => $isOverdue ? NotificationPriority::URGENT : NotificationPriority::HIGH,
                'title' => $isOverdue ? 'Nursing task overdue' : 'Nursing task due',
                'message' => sprintf('%s is due for %s at %s.', $patientName, $task->title, $dueLabel),
                'source_type' => 'nursing_task_due',
                'source_id' => $task->id,
                'patient_id' => $task->patient_id,
                'admission_id' => $task->admission_id,
                'url' => url("/admin/admissions/{$task->admission_id}#tab-tasks"),
            ];

            if ($task->assignedTo) {
                if ($notifier->notifyUser($task->assignedTo, $payload, $dedupe)) {
                    $sent++;
                }

                continue;
            }

            $department = $task->admission?->bed?->ward?->department;
            if ($department) {
                $sent += $notifier->notifyDepartment($department, $payload, $dedupe);
                continue;
            }

            $sent += $notifier->notifyRole(['Ward Nurse', 'Nurse'], $payload, $dedupe);
        }

        $this->info("Nursing task due notifications dispatched: {$sent}");

        return self::SUCCESS;
    }
}
