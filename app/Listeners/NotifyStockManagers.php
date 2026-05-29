<?php

namespace App\Listeners;

use App\Enums\NotificationModule;
use App\Enums\NotificationPriority;
use App\Events\StockLow;
use App\Services\NotificationService;

class NotifyStockManagers
{
    public function __construct(protected NotificationService $notifier) {}

    public function handle(StockLow $event): void
    {
        $payload = [
            'module' => NotificationModule::STOCK,
            'priority' => NotificationPriority::HIGH,
            'title' => 'Low stock alert',
            'message' => sprintf(
                '%s low: %d remaining (reorder level %d).',
                $event->drugName,
                $event->currentQuantity,
                $event->reorderLevel,
            ),
            'url' => url('/admin/inventory'),
            'source_type' => 'stock_low',
            'source_id' => null,
            'metadata' => [
                'drug' => $event->drugName,
                'current' => $event->currentQuantity,
                'reorder' => $event->reorderLevel,
            ],
        ];

        $sent = $this->notifier->notifyPermission('inventory.manage', $payload);
        if ($sent === 0) {
            $this->notifier->notifyRole(['Pharmacist', 'Store Keeper'], $payload);
        }
    }
}
