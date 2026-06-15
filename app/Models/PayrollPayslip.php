<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollPayslip extends Model
{
    protected $fillable = ['payroll_run_id', 'payroll_record_id', 'employee_id', 'status', 'generated_at', 'generated_by', 'released_at', 'released_by', 'snapshot_json'];
    protected $casts = ['generated_at' => 'datetime', 'released_at' => 'datetime', 'snapshot_json' => 'array'];
    public function record(): BelongsTo { return $this->belongsTo(PayrollRecord::class, 'payroll_record_id'); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
