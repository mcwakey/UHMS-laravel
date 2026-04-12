<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use App\Enums\Gender;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_number',
        'user_id',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'phone',
        'email',
        'address',
        'department_id',
        'position',
        'hire_date',
        'basic_salary',
        'bank_name',
        'bank_account',
        'bank_branch',
        'ssnit_number',
        'tin_number',
        'emergency_contact_name',
        'emergency_contact_phone',
        'status',
    ];

    protected $casts = [
        'status' => EmployeeStatus::class,
        'gender' => Gender::class,
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'basic_salary' => 'decimal:2',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(EmployeeAttendance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function payrollRecords(): HasMany
    {
        return $this->hasMany(PayrollRecord::class);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', EmployeeStatus::ACTIVE);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeSearch($query, ?string $search)
    {
        if (!$search) return $query;
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('employee_number', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }
}
