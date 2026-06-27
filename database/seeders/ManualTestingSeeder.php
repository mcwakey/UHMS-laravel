<?php

namespace Database\Seeders;

use Database\Seeders\ManualTesting\ManualAccountingSeeder;
use Database\Seeders\ManualTesting\ManualAppointmentSeeder;
use Database\Seeders\ManualTesting\ManualAuditSeeder;
use Database\Seeders\ManualTesting\ManualBillingSeeder;
use Database\Seeders\ManualTesting\ManualClinicalSeeder;
use Database\Seeders\ManualTesting\ManualDashboardKpiSeeder;
use Database\Seeders\ManualTesting\ManualDepartmentSeeder;
use Database\Seeders\ManualTesting\ManualEmergencySeeder;
use Database\Seeders\ManualTesting\ManualHRSeeder;
use Database\Seeders\ManualTesting\ManualHospitalSeeder;
use Database\Seeders\ManualTesting\ManualInsuranceSeeder;
use Database\Seeders\ManualTesting\ManualIntegrationSeeder;
use Database\Seeders\ManualTesting\ManualInventorySeeder;
use Database\Seeders\ManualTesting\ManualInvestigationSeeder;
use Database\Seeders\ManualTesting\ManualPatientSeeder;
use Database\Seeders\ManualTesting\ManualPayrollSeeder;
use Database\Seeders\ManualTesting\ManualPharmacySeeder;
use Database\Seeders\ManualTesting\ManualProcedureSeeder;
use Database\Seeders\ManualTesting\ManualRadiologySeeder;
use Database\Seeders\ManualTesting\ManualTreatmentSeeder;
use Database\Seeders\ManualTesting\ManualUserSeeder;
use Database\Seeders\ManualTesting\ManualVisitSeeder;
use Illuminate\Database\Seeder;

class ManualTestingSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Manual testing seeders are disabled in production.');
        }

        $allowed = filter_var(env('UHMS_ALLOW_MANUAL_TEST_SEED', false), FILTER_VALIDATE_BOOL)
            || (bool) config('uhms.manual_testing.allow', false);

        if (! $allowed) {
            throw new \RuntimeException('Set UHMS_ALLOW_MANUAL_TEST_SEED=true before running ManualTestingSeeder.');
        }

        $this->call([
            ManualHospitalSeeder::class,
            ManualDepartmentSeeder::class,
            ManualUserSeeder::class,
            ManualInsuranceSeeder::class,
            ManualPatientSeeder::class,
            ManualAppointmentSeeder::class,
            ManualVisitSeeder::class,
            ManualEmergencySeeder::class,
            ManualInvestigationSeeder::class,
            ManualRadiologySeeder::class,
            ManualProcedureSeeder::class,
            ManualTreatmentSeeder::class,
            ManualPharmacySeeder::class,
            ManualClinicalSeeder::class,
            ManualBillingSeeder::class,
            ManualAccountingSeeder::class,
            ManualInventorySeeder::class,
            ManualHRSeeder::class,
            ManualPayrollSeeder::class,
            ManualIntegrationSeeder::class,
            ManualAuditSeeder::class,
            ManualDashboardKpiSeeder::class,
        ]);
    }
}
