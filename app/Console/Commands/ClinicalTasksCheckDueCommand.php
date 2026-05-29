<?php

namespace App\Console\Commands;

use App\Enums\NotificationModule;
use App\Enums\NotificationPriority;
use App\Models\ClinicalTask;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class ClinicalTasksCheckDueCommand extends Command
{
    protected $signature = 'clinical-tasks:check-due {--lookahead=15 : Minutes before due time to alert}';
    protected $description = 'Notify assignees about clinical tasks due within the lookahead window.';

    public function handle(NotificationService $notifier): int
    {
        $lookahead = (int) $this->option('lookahead');
        $now = now();
        $cutoff = $now->copy()->addMinutes($lookahead);

        $tasks = ClinicalTask::query()
            ->whereIn('status', ['SCHEDULED', 'DUE'])
            ->whereBetween('due_at', [$now, $cutoff])
            ->limit(500)
            ->get();

        $count = 0;
        foreach ($tasks as $task) {
            $payload = [
                'module' => NotificationModule::CLINICAL_TASKS,
                'priority' => NotificationPriority::HIGH,
                'title' => $task->title ?? 'Clinical task due',
                'message' => sprintf('Task due at %s', optional($task->due_at)->format('H:i')),
                'source_type' => 'clinical_task',
                'source_id' => $task->id,
                'url' => url("/admin/clinical-tasks/{$task->id}"),
            ];

            if ($task->assigned_to) {
                $user = \App\Models\User::find($task->assigned_to);
                if ($user && $notifier->notifyUser($user, $payload, 30)) {
                    $count++;
                }
            } elseif ($task->assigned_role) {
                $count += $notifier->notifyRole($task->assigned_role, $payload, 30);
            } elseif ($task->assigned_department_id) {
                $count += $notifier->notifyDepartment((int) $task->assigned_department_id, $payload, 30);
            }
        }

        $this->info("Clinical task due notifications dispatched: {$count}");
        return self::SUCCESS;
    }
}
