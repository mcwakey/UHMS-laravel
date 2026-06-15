<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeShiftAssignment extends Model
{
    protected $fillable = ['employee_id', 'department_id', 'shift_id', 'assignment_type', 'effective_from', 'effective_to', 'priority', 'is_active'];

    protected $casts = ['effective_from' => 'date', 'effective_to' => 'date', 'is_active' => 'boolean'];

    public function shift(): BelongsTo { return $this->belongsTo(HrShift::class, 'shift_id'); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
