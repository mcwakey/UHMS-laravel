<?php

namespace App\Enums\FrontDesk;

enum VisitorContext: string
{
    case PATIENT = 'patient';
    case FACILITY = 'facility';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PATIENT => 'Patient Visitor',
            self::FACILITY => 'Facility / Department',
            self::OTHER => 'Other',
        };
    }

    public function translatedLabel(): string
    {
        return __('front_desk.visitor_context.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::PATIENT => 'primary',
            self::FACILITY => 'info',
            self::OTHER => 'secondary',
        };
    }

    /** @return array<int, self> */
    public static function options(): array
    {
        return self::cases();
    }
}
