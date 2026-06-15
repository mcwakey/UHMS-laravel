<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceException extends Model
{
    protected $fillable = ['employee_attendance_id', 'exception_type', 'message', 'status', 'resolved_by', 'resolved_at'];
    protected $casts = ['resolved_at' => 'datetime'];
    public function attendance(): BelongsTo { return $this->belongsTo(EmployeeAttendance::class, 'employee_attendance_id'); }
}
