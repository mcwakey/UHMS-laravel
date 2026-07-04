<?php

namespace App\Enums;

enum AdmissionCareFlag: string
{
    case CRITICAL = 'critical';
    case HIGH_PRIORITY = 'high_priority';
    case FALL_RISK = 'fall_risk';
    case ISOLATION_REQUIRED = 'isolation_required';
    case OXYGEN_REQUIRED = 'oxygen_required';
    case NIL_BY_MOUTH = 'nil_by_mouth';
    case POST_OP = 'post_op';
    case OBSERVATION_REQUIRED = 'observation_required';
    case DISCHARGE_PLANNING = 'discharge_planning';

    public function label(): string
    {
        return __('admissions.care_flags.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::CRITICAL, self::ISOLATION_REQUIRED => 'danger',
            self::HIGH_PRIORITY, self::FALL_RISK, self::OXYGEN_REQUIRED, self::NIL_BY_MOUTH => 'warning',
            self::POST_OP, self::OBSERVATION_REQUIRED => 'info',
            self::DISCHARGE_PLANNING => 'success',
        };
    }
}
