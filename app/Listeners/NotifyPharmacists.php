<?php

namespace App\Listeners;

use App\Events\PrescriptionCreated;
use App\Models\User;
use App\Notifications\PrescriptionNotification;
use Illuminate\Support\Facades\Notification;

class NotifyPharmacists
{
    public function handle(PrescriptionCreated $event): void
    {
        $users = User::role('Pharmacist')->where('status', 'active')->get();

        if ($users->isNotEmpty()) {
            Notification::send($users, new PrescriptionNotification($event->prescription, 'created'));
        }
    }
}
