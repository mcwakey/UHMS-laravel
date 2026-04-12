<?php

namespace App\Models;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leave_type',
        'start_date',
        'end_date',
        'days',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'leave_type' => LeaveType::class,
        'status' => LeaveStatus::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', LeaveStatus::PENDING);
    }

    public function scopeDateRange($query, $from = null, $to = null)
    {
        return $query
            ->when($from, fn ($q) => $q->where('start_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('end_date', '<=', $to));
    }
}
