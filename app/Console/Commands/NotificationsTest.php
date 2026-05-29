<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class NotificationsTest extends Command
{
    protected $signature = 'notifications:test
        {--user= : User id or email to notify}
        {--message=UHMS test notification : Message body}
        {--module=SYSTEM : Notification module}
        {--priority=NORMAL : Notification priority}';

    protected $description = 'Send a test database notification to a user (dev/admin only).';

    public function handle(NotificationService $service): int
    {
        $userRef = $this->option('user');
        if (! $userRef) {
            $this->error('--user is required (id or email).');
            return self::FAILURE;
        }

        $user = is_numeric($userRef)
            ? User::find((int) $userRef)
            : User::where('email', $userRef)->first();

        if (! $user) {
            $this->error("User '{$userRef}' not found.");
            return self::FAILURE;
        }

        $sent = $service->notifyUser($user, [
            'title' => 'UHMS Test',
            'message' => (string) $this->option('message'),
            'module' => $this->option('module'),
            'priority' => $this->option('priority'),
            'source_type' => 'cli_test',
            'source_id' => now()->timestamp,
            'action_url' => route('admin.notifications.index'),
        ], dedupeMinutes: 0);

        $this->info($sent
            ? "Notification sent to {$user->email}."
            : 'Notification suppressed (dedupe or send failure).');

        return self::SUCCESS;
    }
}
