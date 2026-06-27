<?php

namespace Database\Seeders\ManualTesting;

use Database\Seeders\CashAndCarrySeeder;
use Database\Seeders\CountryLocationSeeder;
use Database\Seeders\GhanaPayeTaxTableSeeder;
use Database\Seeders\InsuranceProviderSeeder;
use Database\Seeders\LabCatalogSeeder;
use Database\Seeders\MedicationFrequencySeeder;
use Database\Seeders\ProductAndDrugSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\ServiceCatalogSeeder;
use Database\Seeders\VisitFlowSeeder;
use Database\Seeders\WardAndBedSeeder;

class ManualHospitalSeeder extends ManualTestingSeederBase
{
    public function run(): void
    {
        $this->safeCall(RoleSeeder::class, 'roles');
        $this->safeCall(VisitFlowSeeder::class, 'visit_statuses');
        $this->safeCall(CountryLocationSeeder::class, 'countries');
        $this->safeCall(GhanaPayeTaxTableSeeder::class, 'payroll_tax_tables');
        $this->safeCall(CashAndCarrySeeder::class, 'insurance_providers');
        $this->safeCall(InsuranceProviderSeeder::class, 'insurance_providers');
        $this->safeCall(ServiceCatalogSeeder::class, 'service_catalog');
        $this->safeCall(LabCatalogSeeder::class, 'lab_tests');
        $this->safeCall(ProductAndDrugSeeder::class, 'products');
        $this->safeCall(MedicationFrequencySeeder::class, 'medication_frequencies');
        $this->safeCall(WardAndBedSeeder::class, 'wards');
    }

    private function safeCall(string $class, string $requiredTable): void
    {
        if ($this->hasTable($requiredTable)) {
            $this->call($class);
        }
    }
}
