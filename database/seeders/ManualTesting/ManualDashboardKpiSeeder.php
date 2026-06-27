<?php

namespace Database\Seeders\ManualTesting;

class ManualDashboardKpiSeeder extends ManualTestingSeederBase
{
    public function run(): void
    {
        // Dashboard KPIs are intentionally source-data driven. The preceding
        // manual seeders create visits, queues, lab requests, prescriptions,
        // admissions, invoices, stock locations, HR, payroll, and audit rows.
    }
}
