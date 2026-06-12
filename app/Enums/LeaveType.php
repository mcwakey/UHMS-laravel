<?php

namespace App\Enums;

enum LeaveType: string
{
    case ANNUAL = 'annual';
    case SICK = 'sick';
    case MATERNITY = 'maternity';
    case PATERNITY = 'paternity';
    case COMPASSIONATE = 'compassionate';
    case STUDY = 'study';
    case UNPAID = 'unpaid';

    public function label(): string
    {
        return match ($this) {
            self::ANNUAL => 'Annual Leave',
            self::SICK => 'Sick Leave',
            self::MATERNITY => 'Maternity Leave',
            self::PATERNITY => 'Paternity Leave',
            self::COMPASSIONATE => 'Compassionate Leave',
            self::STUDY => 'Study Leave',
            self::UNPAID => 'Unpaid Leave',
        };
    }

    public function translatedLabel(): string
    {
        return __('statuses.default.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::ANNUAL => 'primary',
            self::SICK => 'danger',
            self::MATERNITY => 'info',
            self::PATERNITY => 'info',
            self::COMPASSIONATE => 'warning',
            self::STUDY => 'success',
            self::UNPAID => 'secondary',
        };
    }
}
