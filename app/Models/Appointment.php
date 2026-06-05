<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\VisitType;
use App\Traits\GeneratesNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\ServiceCatalog;
use App\Models\PatientInsurance;

class Appointment extends Model
{
    use HasFactory, SoftDeletes, GeneratesNumbers, LogsActivity;

    protected $fillable = [
        'appointment_number',
        'patient_id',
        'doctor_id',
        'department_id',
        'appointment_date',
        'start_time',
        'end_time',
        'visit_type',
        'priority',
        'chief_complaint',
        'reason',
        'notes',
        'consultation_mode',
        'visit_insurance_id',
        'status',
        'visit_id',
        'created_by',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'status' => AppointmentStatus::class,
        'visit_type' => VisitType::class,
    ];

    // ── Activity Log ─────────────────────────────────
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'appointment_date', 'start_time', 'doctor_id', 'cancelled_by'])
            ->logOnlyDirty()
            ->useLogName('appointments');
    }

    // ── Relationships ────────────────────────────────
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function services(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(ServiceCatalog::class, 'appointment_services')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function visitInsurance(): BelongsTo
    {
        return $this->belongsTo(PatientInsurance::class, 'visit_insurance_id');
    }

    // ── Scopes ───────────────────────────────────────
    public function scopeToday($query)
    {
        return $query->whereDate('appointment_date', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('appointment_date', '>=', today())
            ->whereNotIn('status', [
                AppointmentStatus::COMPLETED->value,
                AppointmentStatus::CANCELLED->value,
                AppointmentStatus::NO_SHOW->value,
            ]);
    }

    public function scopeByStatus($query, AppointmentStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDoctor($query, int $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeByDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeDateRange($query, ?string $from, ?string $to)
    {
        if ($from) {
            $query->where('appointment_date', '>=', $from);
        }
        if ($to) {
            $query->where('appointment_date', '<=', $to);
        }
        return $query;
    }

    public function scopeSearch($query, ?string $search)
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('appointment_number', 'like', "%{$search}%")
              ->orWhereHas('patient', function ($pq) use ($search) {
                  $pq->where('first_name', 'like', "%{$search}%")
                     ->orWhere('last_name', 'like', "%{$search}%")
                     ->orWhere('patient_number', 'like', "%{$search}%")
                     ->orWhere('phone', 'like', "%{$search}%")
                     ->orWhere('phone_secondary', 'like', "%{$search}%");
              });
        });
    }

    // ── Helpers ──────────────────────────────────────
    public static function generateAppointmentNumber(): string
    {
        return self::generateNumber('APT', 'appointments', 'appointment_number');
    }

    public function getIsActiveAttribute(): bool
    {
        return ! in_array($this->status, [
            AppointmentStatus::COMPLETED,
            AppointmentStatus::CANCELLED,
            AppointmentStatus::NO_SHOW,
        ]);
    }

    public function hasConflict(): bool
    {
        return self::where('doctor_id', $this->doctor_id)
            ->where('appointment_date', $this->appointment_date)
            ->where('id', '!=', $this->id ?? 0)
            ->whereNotIn('status', [
                AppointmentStatus::CANCELLED->value,
                AppointmentStatus::NO_SHOW->value,
            ])
            ->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->where('start_time', '<=', $this->start_time)
                          ->where('end_time', '>', $this->start_time);
                })->orWhere(function ($inner) {
                    $inner->where('start_time', '<', $this->end_time)
                          ->where('end_time', '>=', $this->end_time);
                })->orWhere(function ($inner) {
                    $inner->where('start_time', '>=', $this->start_time)
                          ->where('end_time', '<=', $this->end_time);
                });
            })
            ->exists();
    }
}
