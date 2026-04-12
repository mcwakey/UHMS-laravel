<?php

namespace App\Listeners;

use App\Events\PatientDischarged;
use App\Models\User;
use App\Notifications\AdmissionNotification;
use Illuminate\Support\Facades\Notification;

class NotifyAccountantsDischarge
{
    public function handle(PatientDischarged $event): void
    {
        // Notify accountants for final billing
        $users = User::role('Accountant')->where('status', 'active')->get();

        if ($users->isNotEmpty()) {
            Notification::send($users, new AdmissionNotification($event->admission, 'discharged'));
        }
    }
}
