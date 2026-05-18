<?php

namespace Database\Seeders;

use App\Models\AccountCategory;
use Illuminate\Database\Seeder;

class AccountCategorySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // ── Income ────────────────────────────────────────────────────────
            ['name' => 'Consultation Fees',      'type' => 'income',  'description' => 'OPD / specialist consultations'],
            ['name' => 'Laboratory Income',      'type' => 'income',  'description' => 'Pathology, microbiology, biochemistry'],
            ['name' => 'Radiology Income',       'type' => 'income',  'description' => 'X-ray, ultrasound, CT'],
            ['name' => 'Pharmacy Sales',         'type' => 'income',  'description' => 'Drug dispensing revenue'],
            ['name' => 'Procedure / Surgery',    'type' => 'income',  'description' => 'Theatre and minor procedure revenue'],
            ['name' => 'Admission Revenue',      'type' => 'income',  'description' => 'Bed charges, nursing fees'],
            ['name' => 'Insurance Reimbursement','type' => 'income',  'description' => 'NHIA / private insurance claim settlements'],
            ['name' => 'Other Income',           'type' => 'income',  'description' => 'Miscellaneous receipts (e.g. medical certificates, training fees)'],

            // ── Expense ───────────────────────────────────────────────────────
            ['name' => 'Salaries & Wages',       'type' => 'expense', 'description' => 'Staff payroll, allowances'],
            ['name' => 'Drug & Consumables Cost','type' => 'expense', 'description' => 'COGS for pharmacy and ward consumables'],
            ['name' => 'Lab Reagents',           'type' => 'expense', 'description' => 'Laboratory reagent and kit purchases'],
            ['name' => 'Utilities',              'type' => 'expense', 'description' => 'Electricity, water, internet, fuel'],
            ['name' => 'Rent & Facilities',      'type' => 'expense', 'description' => 'Building rent, maintenance, cleaning'],
            ['name' => 'Equipment Maintenance',  'type' => 'expense', 'description' => 'Repairs and servicing of medical equipment'],
            ['name' => 'Office Supplies',        'type' => 'expense', 'description' => 'Stationery, printing, office consumables'],
            ['name' => 'Transport & Logistics',  'type' => 'expense', 'description' => 'Ambulance fuel, courier, supply delivery'],
            ['name' => 'Bank Charges',           'type' => 'expense', 'description' => 'POS fees, transfer charges'],
            ['name' => 'Training & Development', 'type' => 'expense', 'description' => 'CPD, conferences, workshops'],
            ['name' => 'Miscellaneous Expense',  'type' => 'expense', 'description' => 'Other operating costs'],
        ];

        foreach ($rows as $row) {
            AccountCategory::updateOrCreate(
                ['name' => $row['name'], 'type' => $row['type']],
                array_merge($row, ['is_active' => true])
            );
        }
    }
}
