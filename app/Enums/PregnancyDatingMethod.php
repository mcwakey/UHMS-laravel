<?php

namespace App\Enums;

/**
 * How a pregnancy's gestational age / EDD was dated.
 *
 * Phase 14R.3 — approved source-of-truth decision R2 makes the dating method
 * Pregnancy-Profile-owned rather than a consultation specialty field.
 */
enum PregnancyDatingMethod: string
{
    case LMP = 'lmp';
    case EARLY_ULTRASOUND = 'early_ultrasound';
    case LATE_ULTRASOUND = 'late_ultrasound';
    case ASSISTED_REPRODUCTION = 'assisted_reproduction';
    case CLINICAL_ESTIMATE = 'clinical_estimate';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return __('consultation_maternity.dating_methods.'.$this->value);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
