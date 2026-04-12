<?php

namespace App\Listeners;

use App\Events\PaymentRecorded;
use App\Models\User;
use App\Notifications\PaymentNotification;
use Illuminate\Support\Facades\Notification;

class NotifyAccountants
{
    public function handle(PaymentRecorded $event): void
    {
        $users = User::role('Accountant')->where('status', 'active')->get();

        if ($users->isNotEmpty()) {
            Notification::send($users, new PaymentNotification($event->payment));
        }
    }
}
