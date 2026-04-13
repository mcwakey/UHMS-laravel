<?php

namespace App\Enums;

enum ConsultationMode: string
{
    case IN_PERSON = 'in_person';
    case TELEHEALTH = 'telehealth';
    case VIRTUAL = 'virtual';

    public function label(): string
    {
        return match ($this) {
            self::IN_PERSON => 'In Person',
            self::TELEHEALTH => 'Telehealth',
            self::VIRTUAL => 'Virtual',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::IN_PERSON => 'success',
            self::TELEHEALTH => 'info',
            self::VIRTUAL => 'primary',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::IN_PERSON => 'ti-user',
            self::TELEHEALTH => 'ti-phone',
            self::VIRTUAL => 'ti-video',
        };
    }
}
