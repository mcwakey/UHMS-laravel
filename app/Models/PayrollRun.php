<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    protected $fillable = ['pay_period', 'period_start', 'period_end', 'status', 'generated_by', 'reviewed_by', 'reviewed_at', 'approved_by', 'approved_at'];
    protected $casts = ['period_start' => 'date', 'period_end' => 'date', 'reviewed_at' => 'datetime', 'approved_at' => 'datetime'];
    public function records(): HasMany { return $this->hasMany(PayrollRecord::class); }
    public function payslips(): HasMany { return $this->hasMany(PayrollPayslip::class); }
}
