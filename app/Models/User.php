<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * @method bool hasAnyRole(array|string $roles, ?string $guard = null)
 */
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

    public function getNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
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

    public function specialties(): BelongsToMany
    {
        return $this->belongsToMany(Specialty::class, 'doctor_specialty', 'user_id', 'specialty_id')
            ->withTimestamps();
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

    /* ── Role helpers ─────────────────────────────────── */

    /**
     * Roles that are treated as "consultation-type" clinicians.
     * Stored as a constant so seeders / policies stay in sync.
     */
    public const CONSULTATION_ROLES = [
        'Doctor',
        'Consultant',
        'Specialist',
        'Physician Assistant',
    ];

    public function isAdminUser(): bool
    {
        return $this->hasAnyRole(['Super Admin', 'Admin']);
    }

    public function isDoctor(): bool
    {
        return $this->hasRole('Doctor');
    }

    /**
     * A consultation-type user works the clinical consultation workflow.
     * Identified by clinical role, department type, or explicit permission.
     * Never hardcoded to a single role.
     */
    public function isConsultationUser(): bool
    {
        if ($this->hasAnyRole(self::CONSULTATION_ROLES)) {
            return true;
        }

        $departmentType = optional($this->department)->type;
        if ($departmentType instanceof \BackedEnum) {
            if ($departmentType->value === 'consultation') {
                return true;
            }
        } elseif (is_string($departmentType) && $departmentType === 'consultation') {
            return true;
        }

        try {
            return $this->can('consultation.access');
        } catch (\Throwable $e) {
            return false;
        }
    }
}
