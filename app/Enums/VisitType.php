<?php

namespace App\Enums;

enum VisitType: string
{
    case OUTPATIENT = 'outpatient';
    case INPATIENT = 'inpatient';
    case EMERGENCY = 'emergency';

    public function label(): string
    {
        return match ($this) {
            self::OUTPATIENT => 'Outpatient',
            self::INPATIENT => 'Inpatient',
            self::EMERGENCY => 'Emergency',
        };
    }

    public function translatedLabel(): string
    {
        return __('visits.' . $this->value);
    }
}
