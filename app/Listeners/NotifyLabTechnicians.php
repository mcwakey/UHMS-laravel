<?php

namespace App\Listeners;

use App\Events\LabRequestCreated;
use App\Models\User;
use App\Notifications\LabRequestNotification;
use Illuminate\Support\Facades\Notification;

class NotifyLabTechnicians
{
    public function handle(LabRequestCreated $event): void
    {
        $users = User::role('Lab Technician')->where('status', 'active')->get();

        if ($users->isNotEmpty()) {
            Notification::send($users, new LabRequestNotification($event->labRequest, 'created'));
        }
    }
}
