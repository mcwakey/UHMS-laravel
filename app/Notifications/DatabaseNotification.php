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
        $channels = $this->payload['_channels'] ?? ['database'];
        $allowed = ['database', 'broadcast', 'mail', 'sms'];
        $channels = array_values(array_intersect($allowed, (array) $channels));
        return $channels ?: ['database'];
    }

    public function toMail(object $notifiable)
    {
        $title = (string) ($this->payload['title'] ?? 'Notification');
        $message = (string) ($this->payload['message'] ?? $title);
        $mail = (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject($title)
            ->line($message);
        if (! empty($this->payload['action_url'])) {
            $mail->action('View details', $this->payload['action_url']);
        }
        return $mail;
    }

    public function toBroadcast(object $notifiable): \Illuminate\Notifications\Messages\BroadcastMessage
    {
        return new \Illuminate\Notifications\Messages\BroadcastMessage($this->toArray($notifiable));
    }

    public function toSms(object $notifiable): array
    {
        // Placeholder channel — wire to your SMS provider notification channel.
        return [
            'to' => $notifiable->phone ?? null,
            'message' => (string) ($this->payload['message'] ?? $this->payload['title'] ?? 'Notification'),
        ];
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
