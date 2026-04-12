<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['first_name', 'last_name', 'email', 'phone', 'status'])
            ->logOnlyDirty()
            ->useLogName('users')
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'avatar',
        'gender',
        'date_of_birth',
        'status',
        'employee_id',
        'department_id',
        'designation_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'gender' => Gender::class,
            'status' => UserStatus::class,
        ];
    }

    /* ── Accessors ────────────────────────────────────── */

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /* ── Relationships ────────────────────────────────── */

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /* ── Scopes ───────────────────────────────────────── */

    public function scopeActive($query)
    {
        return $query->where('status', UserStatus::ACTIVE);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->role($role);
    }
}
