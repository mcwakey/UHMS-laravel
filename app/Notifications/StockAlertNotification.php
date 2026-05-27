<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StockAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $drugName,
        public int $currentQuantity,
        public int $reorderLevel
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'stock_alert',
            'action' => 'low_stock',
            'message' => "Low stock alert: {$this->drugName} — {$this->currentQuantity} remaining (reorder level: {$this->reorderLevel})",
            'drug_name' => $this->drugName,
            'current_quantity' => $this->currentQuantity,
            'reorder_level' => $this->reorderLevel,
            'url' => route('admin.pharmacy.drugs.index'),
            'icon' => 'ti-alert-triangle',
            'color' => 'danger',
        ];
    }
}
