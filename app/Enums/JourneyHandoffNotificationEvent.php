<?php

namespace App\Enums;

/**
 * Phase 9.6 — journey handoff coordination notification events. Each maps to a
 * translatable title, an icon and a notification priority. No patient data is
 * embedded here; messages are built (and gated) by the notification service.
 */
enum JourneyHandoffNotificationEvent: string
{
    case ASSIGNED = 'handoff_assigned';
    case CLAIMED = 'handoff_claimed';
    case ACKNOWLEDGED = 'handoff_acknowledged';
    case RESOLVED = 'handoff_resolved';
    case ESCALATED = 'handoff_escalated';
    case CRITICAL = 'handoff_critical';
    case STALE_DISMISSED = 'handoff_stale_dismissed';
    case UNASSIGNED_NEAR_BREACH = 'handoff_unassigned_near_breach';
    case UNASSIGNED_BREACHED = 'handoff_unassigned_breached';

    public function translatedLabel(): string
    {
        return __('journey.notification.'.$this->value);
    }

    public function icon(): string
    {
        return match ($this) {
            self::ASSIGNED, self::CLAIMED => 'ti-user-check',
            self::ACKNOWLEDGED => 'ti-checkbox',
            self::RESOLVED => 'ti-circle-check',
            self::ESCALATED, self::UNASSIGNED_NEAR_BREACH => 'ti-arrow-up-circle',
            self::CRITICAL, self::UNASSIGNED_BREACHED => 'ti-alert-triangle',
            self::STALE_DISMISSED => 'ti-trash',
        };
    }

    public function priority(): NotificationPriority
    {
        return match ($this) {
            self::CRITICAL, self::UNASSIGNED_BREACHED => NotificationPriority::CRITICAL,
            self::ESCALATED, self::UNASSIGNED_NEAR_BREACH => NotificationPriority::URGENT,
            self::ASSIGNED, self::CLAIMED => NotificationPriority::HIGH,
            default => NotificationPriority::NORMAL,
        };
    }
}
