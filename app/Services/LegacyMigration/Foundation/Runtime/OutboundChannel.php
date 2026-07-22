<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

enum OutboundChannel: string
{
    case Mail = 'mail';
    case Sms = 'sms';
    case Http = 'http';
    case Webhook = 'webhook';
    case Queue = 'queue';
    case Broadcast = 'broadcast';

    public function subsystem(): ProhibitedSubsystem
    {
        return match ($this) {
            self::Mail => ProhibitedSubsystem::Mail,
            self::Sms => ProhibitedSubsystem::Sms,
            self::Http => ProhibitedSubsystem::ExternalIntegrations,
            self::Webhook => ProhibitedSubsystem::Webhooks,
            self::Queue => ProhibitedSubsystem::QueueDispatch,
            self::Broadcast => ProhibitedSubsystem::Notifications,
        };
    }
}
