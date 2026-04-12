<?php

namespace App\Enums;

enum EmployeeStatus: string
{
    case ACTIVE = 'active';
    case ON_LEAVE = 'on_leave';
    case TERMINATED = 'terminated';
    case RETIRED = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::ON_LEAVE => 'On Leave',
            self::TERMINATED => 'Terminated',
            self::RETIRED => 'Retired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::ON_LEAVE => 'warning',
            self::TERMINATED => 'danger',
            self::RETIRED => 'secondary',
        };
    }
}
