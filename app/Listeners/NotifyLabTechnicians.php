<?php

namespace App\Listeners;

use App\Events\LabRequestCreated;
use App\Models\User;
use App\Notifications\LabRequestNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

class NotifyLabTechnicians
{
    public function handle(LabRequestCreated $event): void
    {
        if (! Role::where('name', 'Lab Technician')->where('guard_name', 'web')->exists()) {
            return;
        }

        $users = User::role('Lab Technician')->where('status', 'active')->get();

        if ($users->isNotEmpty()) {
            Notification::send($users, new LabRequestNotification($event->labRequest, 'created'));
        }
    }
}
