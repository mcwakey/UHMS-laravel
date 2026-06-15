<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollTaxTable;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class PayrollTaxCalculationService
{
    public function calculate(Employee $employee, float $grossTaxableIncome, float $preTaxDeductions = 0, float $reliefs = 0, ?CarbonInterface $date = null, string $basis = 'monthly'): array
    {
        $chargeable = max(0, $grossTaxableIncome - $preTaxDeductions - $reliefs);
        $residentType = $employee->paye_exempt ? 'exempt' : ($employee->tax_residency_status ?: 'resident');

        if ($residentType === 'exempt') {
            return $this->result(null, $residentType, $grossTaxableIncome, $preTaxDeductions, $reliefs, $chargeable, 0, []);
        }

        $table = $this->resolveTable($residentType, $date ?? now(), $basis);
        if ($residentType === 'non_resident') {
            $rate = (float) ($table->flat_rate_percent ?? 25);
            $tax = round($chargeable * ($rate / 100), 2);
            return $this->result($table, $residentType, $grossTaxableIncome, $preTaxDeductions, $reliefs, $chargeable, $tax, [[
                'label' => $table->name, 'taxable_amount' => $chargeable, 'rate_percent' => $rate, 'tax_amount' => $tax, 'cumulative_tax' => $tax,
            ]]);
        }

        $remaining = $chargeable;
        $tax = 0.0;
        $breakdown = [];
        foreach ($table->bands as $band) {
            if ($remaining <= 0) {
                break;
            }
            $taxable = $band->is_excess_band ? $remaining : min($remaining, (float) $band->band_amount);
            $bandTax = $taxable * ((float) $band->rate_percent / 100);
            $tax += $bandTax;
            $remaining -= $taxable;
            $breakdown[] = [
                'label' => $band->band_label,
                'taxable_amount' => round($taxable, 2),
                'rate_percent' => (float) $band->rate_percent,
                'tax_amount' => round($bandTax, 4),
                'cumulative_tax' => round($tax, 4),
            ];
        }

        return $this->result($table, $residentType, $grossTaxableIncome, $preTaxDeductions, $reliefs, $chargeable, round($tax, 2), $breakdown);
    }

    private function resolveTable(string $residentType, CarbonInterface $date, string $basis): PayrollTaxTable
    {
        $table = PayrollTaxTable::with('bands')
            ->where('country_code', 'GH')
            ->where('tax_type', 'paye')
            ->where('period_basis', $basis)
            ->where('resident_type', $residentType)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $date)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date))
            ->orderByDesc('effective_from')
            ->first();

        if (! $table) {
            throw ValidationException::withMessages(['tax_table' => "No effective Ghana PAYE table exists for {$residentType} {$basis} payroll."]);
        }

        return $table;
    }

    private function result(?PayrollTaxTable $table, string $residentType, float $gross, float $preTax, float $reliefs, float $chargeable, float $tax, array $breakdown): array
    {
        return [
            'tax_table_id' => $table?->id,
            'tax_table' => $table ? ['id' => $table->id, 'name' => $table->name, 'effective_from' => $table->effective_from?->toDateString(), 'period_basis' => $table->period_basis] : null,
            'resident_type' => $residentType,
            'gross_taxable_income' => round($gross, 2),
            'pre_tax_deductions' => round($preTax, 2),
            'reliefs_total' => round($reliefs, 2),
            'chargeable_income' => round($chargeable, 2),
            'tax_amount' => round($tax, 2),
            'breakdown' => $breakdown,
            'calculated_at' => now()->toIso8601String(),
        ];
    }
}
