<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAttendance extends Model
{
    use HasFactory;

    protected $table = 'employee_attendance';

    protected $fillable = [
        'employee_id',
        'date',
        'clock_in',
        'clock_out',
        'hours_worked',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'hours_worked' => 'decimal:2',
    ];

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // Scopes
    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeDateRange($query, $from = null, $to = null)
    {
        return $query
            ->when($from, fn ($q) => $q->where('date', '>=', $from))
            ->when($to, fn ($q) => $q->where('date', '<=', $to));
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
