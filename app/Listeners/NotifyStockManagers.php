<?php

namespace App\Listeners;

use App\Events\StockLow;
use App\Models\User;
use App\Notifications\StockAlertNotification;
use Illuminate\Support\Facades\Notification;

class NotifyStockManagers
{
    public function handle(StockLow $event): void
    {
        $users = User::role(['Pharmacist', 'Store Keeper'])->where('status', 'active')->get();

        if ($users->isNotEmpty()) {
            Notification::send(
                $users,
                new StockAlertNotification($event->drugName, $event->currentQuantity, $event->reorderLevel)
            );
        }
    }
}
