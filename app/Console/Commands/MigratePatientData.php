<?php

namespace App\Console\Commands;

use App\Models\EmergencyContact;
use App\Models\InsuranceProvider;
use App\Models\Patient;
use App\Models\PatientInsurance;
use Illuminate\Console\Command;

class MigratePatientData extends Command
{
    protected $signature = 'uhms:migrate-patient-data';
    protected $description = 'Migrate legacy emergency contact fields to emergency_contacts table and assign Cash & Carry insurance to all patients';

    public function handle(): int
    {
        $this->info('Starting patient data migration...');

        $this->migrateEmergencyContacts();
        $this->assignCashAndCarry();

        $this->info('Patient data migration complete!');
        return self::SUCCESS;
    }

    private function migrateEmergencyContacts(): void
    {
        $this->info('Migrating emergency contacts...');

        $patients = Patient::whereNotNull('emergency_contact_name')
            ->where('emergency_contact_name', '!=', '')
            ->get();

        $count = 0;
        foreach ($patients as $patient) {
            // Skip if already migrated (check if any emergency contact with same name exists)
            if ($patient->emergencyContacts()->where('name', $patient->emergency_contact_name)->exists()) {
                continue;
            }

            EmergencyContact::create([
                'patient_id' => $patient->id,
                'name' => $patient->emergency_contact_name,
                'phone' => $patient->emergency_contact_phone ?? '',
                'relationship' => $patient->emergency_contact_relationship,
                'is_primary' => true,
            ]);
            $count++;
        }

        $this->info("  Migrated {$count} emergency contacts.");
    }

    private function assignCashAndCarry(): void
    {
        $this->info('Assigning Cash & Carry insurance to all patients...');

        $cashAndCarry = InsuranceProvider::where('is_default', true)->first();

        if (!$cashAndCarry) {
            $this->warn('  Cash & Carry provider not found. Run CashAndCarrySeeder first.');
            return;
        }

        $patients = Patient::whereDoesntHave('insurances', fn($q) => $q->where('insurance_provider_id', $cashAndCarry->id))->get();

        $count = 0;
        foreach ($patients as $patient) {
            PatientInsurance::create([
                'patient_id' => $patient->id,
                'insurance_provider_id' => $cashAndCarry->id,
                'is_active' => true,
                'is_primary' => !$patient->insurances()->where('is_primary', true)->exists(),
            ]);
            $count++;
        }

        $this->info("  Assigned Cash & Carry to {$count} patients.");
    }
}
