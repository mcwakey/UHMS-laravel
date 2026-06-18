<?php

namespace App\Enums;

enum LogModule: string
{
    case EMERGENCY = 'EMERGENCY';
    case ADMISSION = 'ADMISSION';
    case CONSULTATION = 'CONSULTATION';
    case SERVICE_RENDERING = 'SERVICE_RENDERING';
    case MAR = 'MAR';
    case CLINICAL_TASKS = 'CLINICAL_TASKS';
    case INVESTIGATION = 'INVESTIGATION';
    case PROCEDURE = 'PROCEDURE';
    case THEATRE = 'THEATRE';
    case PHARMACY = 'PHARMACY';
    case BILLING = 'BILLING';
    case PAYMENTS = 'PAYMENTS';
    case ACCOUNTING = 'ACCOUNTING';
    case CLAIMS = 'CLAIMS';
    case STOCK = 'STOCK';
    case BLOOD_BANK = 'BLOOD_BANK';
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
    case INTEGRATIONS = 'INTEGRATIONS';
    case AUTH = 'AUTH';
    case SYSTEM = 'SYSTEM';

    public function label(): string
    {
        return ucwords(strtolower(str_replace('_', ' ', $this->value)));
    }

    public function translatedLabel(): string
    {
        return __('statuses.default.' . strtolower($this->value));
    }

    public function color(): string
    {
        return match ($this) {
            self::EMERGENCY, self::AUTH => 'danger',
            self::BILLING, self::PAYMENTS, self::ACCOUNTING, self::CLAIMS, self::SUPPLIER_LEDGER, self::PURCHASE_ORDERS => 'warning',
            self::SERVICE_RENDERING => 'success',
            self::STOCK, self::PHARMACY => 'info',
            self::BLOOD_BANK => 'danger',
            self::USERS, self::ROLES, self::PERMISSIONS, self::SETTINGS => 'dark',
            self::INTEGRATIONS => 'info',
            self::PATIENT_MERGE => 'danger',
            default => 'secondary',
        };
    }
}
