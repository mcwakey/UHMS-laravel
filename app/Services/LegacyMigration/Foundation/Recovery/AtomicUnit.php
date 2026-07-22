<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

enum AtomicUnit: string
{
    case PatientCore = 'A_PATIENT_CORE';
    case ExistingTargetLink = 'A_EXISTING_TARGET_LINK';
    case OpdAlias = 'B_OPD_ALIAS';
    case EmergencyContact = 'C_EMERGENCY_CONTACT';
    case InsuranceHistory = 'D_INSURANCE_HISTORY';
    case InsuranceCurrent = 'D_INSURANCE_CURRENT';
}
