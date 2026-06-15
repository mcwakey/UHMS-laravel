<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(GhanaPayeTaxTableSeeder::class);
        $this->call([
            // ── Core access control & org structure ──────────────────────
            RoleSeeder::class,
            DepartmentSeeder::class,
            AdminUserSeeder::class,

            // ── Modules (feature flags) ───────────────────────────────────
            ModuleSeeder::class,
            BloodBankSeeder::class,

            // ── Billing / insurance / finance reference data ─────────────
            CashAndCarrySeeder::class,
            InsuranceProviderSeeder::class,
            SupplierSeeder::class,
            AccountCategorySeeder::class,
            AccountingChartSeeder::class,
            AccountingPostingTemplateSeeder::class,
            ServiceCatalogSeeder::class,
            InvestigationServiceCriteriaSeeder::class,

            // ── Pharmacy / stock spine ───────────────────────────────────
            DrugGenericNameSeeder::class,
            ProductAndDrugSeeder::class,
            InsurancePricingSeeder::class,
            MedicationFrequencySeeder::class,

            // ── Clinical reference data ──────────────────────────────────
            DesignationSeeder::class,
            SpecialtySeeder::class,
            ComplaintCatalogueSeeder::class,
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
