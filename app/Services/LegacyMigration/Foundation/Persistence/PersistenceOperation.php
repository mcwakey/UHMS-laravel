<?php

namespace App\Services\LegacyMigration\Foundation\Persistence;

enum PersistenceOperation: string
{
    case PatientEntity = 'patient_entity';
    case ExistingTargetLink = 'existing_target_link';
    case Alias = 'alias';
    case EmergencyContact = 'emergency_contact';
    case InsuranceHistory = 'insurance_history';
    case CurrentMembership = 'current_membership';
}
