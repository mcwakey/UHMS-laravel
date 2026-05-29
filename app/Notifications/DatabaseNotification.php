<?php

namespace App\Notifications;

use App\Enums\NotificationModule;
use App\Enums\NotificationPriority;
use Illuminate\Notifications\Notification;

class DatabaseNotification extends Notification
{
    public function __construct(public array $payload)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $module = $this->payload['module'] ?? NotificationModule::SYSTEM->value;
        $priority = $this->payload['priority'] ?? NotificationPriority::NORMAL->value;
        $moduleEnum = NotificationModule::tryFrom((string) $module) ?? NotificationModule::SYSTEM;
        $priorityEnum = NotificationPriority::tryFrom((string) $priority) ?? NotificationPriority::NORMAL;

        return array_merge([
            'icon' => $moduleEnum->icon(),
            'color' => $priorityEnum->color(),
            'type' => strtolower($moduleEnum->value),
        ], $this->payload, [
            'module' => $moduleEnum->value,
            'priority' => $priorityEnum->value,
        ]);
    }
}
