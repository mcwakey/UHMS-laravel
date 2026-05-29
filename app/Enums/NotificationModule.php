<?php

namespace App\Enums;

enum NotificationModule: string
{
    case EMERGENCY = 'EMERGENCY';
    case ADMISSION = 'ADMISSION';
    case CONSULTATION = 'CONSULTATION';
    case MAR = 'MAR';
    case CLINICAL_TASKS = 'CLINICAL_TASKS';
    case INVESTIGATION = 'INVESTIGATION';
    case PROCEDURE = 'PROCEDURE';
    case THEATRE = 'THEATRE';
    case PHARMACY = 'PHARMACY';
    case BILLING = 'BILLING';
    case CLAIMS = 'CLAIMS';
    case STOCK = 'STOCK';
    case PATIENTS = 'PATIENTS';
    case SYSTEM = 'SYSTEM';

    public function label(): string
    {
        return ucwords(strtolower(str_replace('_', ' ', $this->value)));
    }

    public function icon(): string
    {
        return match ($this) {
            self::EMERGENCY => 'ti-emergency-bed',
            self::ADMISSION => 'ti-bed',
            self::CONSULTATION => 'ti-stethoscope',
            self::MAR => 'ti-pill',
            self::CLINICAL_TASKS => 'ti-checklist',
            self::INVESTIGATION => 'ti-test-pipe',
            self::PROCEDURE => 'ti-medical-cross',
            self::THEATRE => 'ti-door',
            self::PHARMACY => 'ti-prescription',
            self::BILLING => 'ti-receipt',
            self::CLAIMS => 'ti-file-invoice',
            self::STOCK => 'ti-packages',
            self::PATIENTS => 'ti-user-circle',
            self::SYSTEM => 'ti-settings',
        };
    }
}
