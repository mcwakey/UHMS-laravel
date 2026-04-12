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
        'pay_period',
        'basic_salary',
        'allowances',
        'gross_pay',
        'ssnit_employee',
        'ssnit_employer',
        'tax',
        'other_deductions',
        'net_pay',
        'status',
        'processed_by',
        'paid_at',
    ];

    protected $casts = [
        'status' => PayrollStatus::class,
        'basic_salary' => 'decimal:2',
        'allowances' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'ssnit_employee' => 'decimal:2',
        'ssnit_employer' => 'decimal:2',
        'tax' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
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
