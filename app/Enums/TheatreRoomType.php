<?php

namespace App\Enums;

enum TheatreRoomType: string
{
    case MAJOR_THEATRE = 'MAJOR_THEATRE';
    case MINOR_THEATRE = 'MINOR_THEATRE';
    case EMERGENCY_THEATRE = 'EMERGENCY_THEATRE';
    case MATERNITY_THEATRE = 'MATERNITY_THEATRE';
    case ENDOSCOPY_ROOM = 'ENDOSCOPY_ROOM';
    case PROCEDURE_ROOM = 'PROCEDURE_ROOM';
    case RECOVERY_ROOM = 'RECOVERY_ROOM';
    case DENTAL_PROCEDURE_ROOM = 'DENTAL_PROCEDURE_ROOM';
    case EYE_THEATRE = 'EYE_THEATRE';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::MAJOR_THEATRE => 'Major Theatre',
            self::MINOR_THEATRE => 'Minor Theatre',
            self::EMERGENCY_THEATRE => 'Emergency Theatre',
            self::MATERNITY_THEATRE => 'Maternity Theatre',
            self::ENDOSCOPY_ROOM => 'Endoscopy Room',
            self::PROCEDURE_ROOM => 'Procedure Room',
            self::RECOVERY_ROOM => 'Recovery Room',
            self::DENTAL_PROCEDURE_ROOM => 'Dental Procedure Room',
            self::EYE_THEATRE => 'Eye Theatre',
            self::OTHER => 'Other',
        };
    }
}