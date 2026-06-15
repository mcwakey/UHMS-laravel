<?php

namespace App\Models;

use App\Enums\PayrollStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'payroll_run_id',
        'pay_period',
        'basic_salary',
        'overtime_pay',
        'allowances',
        'taxable_allowances',
        'non_taxable_allowances',
        'gross_pay',
        'gross_taxable_income',
        'ssnit_employee',
        'ssnit_employer',
        'other_pre_tax_deductions',
        'tax_reliefs',
        'chargeable_income',
        'tax',
        'other_deductions',
        'attendance_deductions',
        'total_deductions',
        'net_pay',
        'calculation_snapshot',
        'status',
        'processed_by',
        'paid_at',
    ];

    protected $casts = [
        'status' => PayrollStatus::class,
        'basic_salary' => 'decimal:2',
        'allowances' => 'decimal:2',
        'taxable_allowances' => 'decimal:2',
        'non_taxable_allowances' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'gross_taxable_income' => 'decimal:2',
        'ssnit_employee' => 'decimal:2',
        'ssnit_employer' => 'decimal:2',
        'tax' => 'decimal:2',
        'other_pre_tax_deductions' => 'decimal:2',
        'tax_reliefs' => 'decimal:2',
        'chargeable_income' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'attendance_deductions' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'calculation_snapshot' => 'array',
        'paid_at' => 'datetime',
    ];

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function taxCalculation()
    {
        return $this->hasOne(PayrollTaxCalculation::class);
    }

    public function processedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Scopes
    public function scopeByPeriod($query, string $period)
    {
        return $query->where('pay_period', $period);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Accessors
    public function getTotalDeductionsAttribute(): float
    {
        return $this->ssnit_employee + $this->tax + $this->other_deductions;
    }
}
