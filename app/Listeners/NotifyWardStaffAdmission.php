<?php

namespace App\Listeners;

use App\Events\PatientAdmitted;
use App\Models\User;
use App\Notifications\AdmissionNotification;
use Illuminate\Support\Facades\Notification;

class NotifyWardStaffAdmission
{
    public function handle(PatientAdmitted $event): void
    {
        $users = User::role('Nurse')->where('status', 'active')->get();

        if ($users->isNotEmpty()) {
            Notification::send($users, new AdmissionNotification($event->admission, 'admitted'));
        }
    }
}
