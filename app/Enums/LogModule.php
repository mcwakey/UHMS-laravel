<?php

namespace App\Enums;

enum LogModule: string
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
    case PAYMENTS = 'PAYMENTS';
    case CLAIMS = 'CLAIMS';
    case STOCK = 'STOCK';
    case PURCHASE_ORDERS = 'PURCHASE_ORDERS';
    case SUPPLIER_LEDGER = 'SUPPLIER_LEDGER';
    case PATIENTS = 'PATIENTS';
    case PATIENT_MERGE = 'PATIENT_MERGE';
    case INSURANCE = 'INSURANCE';
    case USERS = 'USERS';
    case ROLES = 'ROLES';
    case PERMISSIONS = 'PERMISSIONS';
    case SETTINGS = 'SETTINGS';
    case NOTIFICATIONS = 'NOTIFICATIONS';
    case AUTH = 'AUTH';
    case SYSTEM = 'SYSTEM';

    public function label(): string
    {
        return ucwords(strtolower(str_replace('_', ' ', $this->value)));
    }

    public function color(): string
    {
        return match ($this) {
            self::EMERGENCY, self::AUTH => 'danger',
            self::BILLING, self::PAYMENTS, self::CLAIMS, self::SUPPLIER_LEDGER, self::PURCHASE_ORDERS => 'warning',
            self::STOCK, self::PHARMACY => 'info',
            self::USERS, self::ROLES, self::PERMISSIONS, self::SETTINGS => 'dark',
            self::PATIENT_MERGE => 'danger',
            default => 'secondary',
        };
    }
}
