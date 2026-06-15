<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollTaxCalculation extends Model
{
    protected $fillable = ['payroll_run_id', 'payroll_payslip_id', 'payroll_record_id', 'employee_id', 'payroll_tax_table_id', 'gross_taxable_income', 'pre_tax_deductions', 'reliefs_total', 'chargeable_income', 'tax_amount', 'calculation_snapshot_json', 'calculated_at', 'calculated_by'];
    protected $casts = ['calculation_snapshot_json' => 'array', 'calculated_at' => 'datetime'];
}
