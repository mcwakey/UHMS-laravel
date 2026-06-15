<?php

namespace Database\Seeders;

use App\Models\HrPolicySetting;
use App\Models\PayrollTaxTable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GhanaPayeTaxTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $monthly = PayrollTaxTable::updateOrCreate(
                ['country_code' => 'GH', 'tax_type' => 'paye', 'period_basis' => 'monthly', 'resident_type' => 'resident', 'effective_from' => '2024-01-01'],
                ['name' => 'Ghana PAYE Resident Monthly 2024', 'currency' => 'GHS', 'is_active' => true, 'notes' => 'GRA rates effective 1 January 2024.']
            );
            $this->bands($monthly, [
                ['First 490.00', 490, 0, false],
                ['Next 110.00', 110, 5, false],
                ['Next 130.00', 130, 10, false],
                ['Next 3,166.67', 3166.67, 17.5, false],
                ['Next 16,000.00', 16000, 25, false],
                ['Next 30,520.00', 30520, 30, false],
                ['Excess', null, 35, true],
            ]);

            $annual = PayrollTaxTable::updateOrCreate(
                ['country_code' => 'GH', 'tax_type' => 'paye', 'period_basis' => 'annual', 'resident_type' => 'resident', 'effective_from' => '2024-01-01'],
                ['name' => 'Ghana PAYE Resident Annual 2024', 'currency' => 'GHS', 'is_active' => true, 'notes' => 'GRA rates effective 1 January 2024.']
            );
            $this->bands($annual, [
                ['First 5,880.00', 5880, 0, false],
                ['Next 1,320.00', 1320, 5, false],
                ['Next 1,560.00', 1560, 10, false],
                ['Next 38,000.00', 38000, 17.5, false],
                ['Next 192,000.00', 192000, 25, false],
                ['Next 366,240.00', 366240, 30, false],
                ['Excess', null, 35, true],
            ]);

            PayrollTaxTable::updateOrCreate(
                ['country_code' => 'GH', 'tax_type' => 'paye', 'period_basis' => 'monthly', 'resident_type' => 'non_resident', 'effective_from' => '2024-01-01'],
                ['name' => 'Ghana PAYE Non-Resident Monthly', 'currency' => 'GHS', 'flat_rate_percent' => 25, 'is_active' => true]
            );

            foreach ([
                ['attendance.grace_minutes', '15', 'integer'],
                ['attendance.overtime_after_minutes', '0', 'integer'],
                ['attendance.weekends_are_off_days', 'true', 'boolean'],
                ['payroll.non_resident_tax_rate', '25', 'decimal'],
                ['payroll.money_precision', '2', 'integer'],
                ['payroll.unpaid_absence_daily_rate_basis', '30', 'integer'],
            ] as [$key, $value, $type]) {
                HrPolicySetting::firstOrCreate(['key' => $key], ['value' => $value, 'value_type' => $type, 'is_active' => true]);
            }
        });
    }

    private function bands(PayrollTaxTable $table, array $bands): void
    {
        $lower = 0;
        $tax = 0;
        foreach ($bands as $index => [$label, $amount, $rate, $excess]) {
            $table->bands()->updateOrCreate(['band_order' => $index + 1], [
                'band_label' => $label,
                'lower_bound' => $lower,
                'upper_bound' => $amount === null ? null : $lower + $amount,
                'band_amount' => $amount,
                'rate_percent' => $rate,
                'fixed_tax_amount' => 0,
                'cumulative_tax' => $tax,
                'is_excess_band' => $excess,
            ]);
            if ($amount !== null) {
                $tax += $amount * ($rate / 100);
                $lower += $amount;
            }
        }
    }
}
