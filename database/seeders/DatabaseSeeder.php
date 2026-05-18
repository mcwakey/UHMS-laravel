<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // ── Core access control & org structure ──────────────────────
            RoleSeeder::class,
            DepartmentSeeder::class,
            AdminUserSeeder::class,

            // ── Billing / insurance / finance reference data ─────────────
            CashAndCarrySeeder::class,
            InsuranceProviderSeeder::class,
            SupplierSeeder::class,
            AccountCategorySeeder::class,
            ServiceCatalogSeeder::class,

            // ── Pharmacy / stock spine ───────────────────────────────────
            DrugGenericNameSeeder::class,
            ProductAndDrugSeeder::class,

            // ── Clinical reference data ──────────────────────────────────
            DesignationSeeder::class,
            SpecialtySeeder::class,
            IcdCodeSeeder::class,

            // ── Demo users (doctors, nurses, lab, pharmacy …) ────────────
            DemoUserSeeder::class,

            // ── Wards / Lab / Analyzers / Patterns ───────────────────────
            WardAndBedSeeder::class,
            LabCatalogSeeder::class,
            AnalyzerSeeder::class,
            MedicalPatternSeeder::class,

            // ── Patients & appointments (depend on users + departments) ──
            PatientSeeder::class,
            AppointmentSeeder::class,
        ]);
    }
}
