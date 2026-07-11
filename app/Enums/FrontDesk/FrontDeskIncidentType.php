<?php

namespace App\Enums\FrontDesk;

enum FrontDeskIncidentType: string
{
    case AGGRESSIVE_VISITOR = 'aggressive_visitor';
    case UNAUTHORIZED_ACCESS = 'unauthorized_access';
    case LOST_PROPERTY = 'lost_property';
    case QUEUE_DISPUTE = 'queue_dispute';
    case FACILITY_DAMAGE = 'facility_damage';
    case SECURITY_CONCERN = 'security_concern';
    case MISSING_ITEM = 'missing_item';
    case COURIER_ISSUE = 'courier_issue';
    case CALL_COMPLAINT = 'call_complaint';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::AGGRESSIVE_VISITOR => 'Aggressive Visitor',
            self::UNAUTHORIZED_ACCESS => 'Unauthorized Access',
            self::LOST_PROPERTY => 'Lost Property',
            self::QUEUE_DISPUTE => 'Queue Dispute',
            self::FACILITY_DAMAGE => 'Facility Damage',
            self::SECURITY_CONCERN => 'Security Concern',
            self::MISSING_ITEM => 'Missing Item',
            self::COURIER_ISSUE => 'Courier Issue',
            self::CALL_COMPLAINT => 'Call Complaint',
            self::OTHER => 'Other',
        };
    }

    public function translatedLabel(): string
    {
        return __('front_desk.incident_type.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::AGGRESSIVE_VISITOR, self::UNAUTHORIZED_ACCESS, self::SECURITY_CONCERN => 'danger',
            self::FACILITY_DAMAGE, self::MISSING_ITEM => 'warning',
            self::QUEUE_DISPUTE, self::CALL_COMPLAINT, self::COURIER_ISSUE => 'info',
            default => 'secondary',
        };
    }

    /** @return array<int, self> */
    public static function options(): array
    {
        return self::cases();
    }
}
