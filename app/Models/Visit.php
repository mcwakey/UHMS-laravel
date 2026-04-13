<?php

namespace App\Models;

use App\Enums\ConsultationMode;
use App\Enums\Priority;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Traits\GeneratesNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Visit extends Model
{
    use HasFactory, SoftDeletes, GeneratesNumbers, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'visit_type', 'priority', 'assigned_doctor_id', 'department_id'])
            ->logOnlyDirty()
            ->useLogName('visits')
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'visit_number',
        'patient_id',
        'visit_type',
        'visit_date',
        'start_time',
        'end_time',
        'status',
        'priority',
        'department_id',
        'assigned_doctor_id',
        'chief_complaint',
        'notes',
        'checked_in_at',
        'checked_out_at',
        'created_by',
        'visit_insurance_id',
        'cancelled_by',
        'cancellation_reason',
        'rescheduled_from_id',
        'rescheduled_at',
        'rescheduled_reason',
        'consultation_mode',
        'meeting_link',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'visit_type' => VisitType::class,
            'status' => VisitStatus::class,
            'priority' => Priority::class,
            'consultation_mode' => ConsultationMode::class,
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'rescheduled_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function assignedDoctor()
    {
        return $this->belongsTo(User::class, 'assigned_doctor_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusLogs()
    {
        return $this->hasMany(VisitStatusLog::class)->orderBy('timestamp');
    }

    public function queueEntries()
    {
        return $this->hasMany(QueueEntry::class);
    }

    public function latestQueue()
    {
        return $this->hasOne(QueueEntry::class)->latestOfMany();
    }

    public function medicalRecord()
    {
        return $this->hasOne(MedicalRecord::class);
    }

    public function vitals()
    {
        return $this->hasMany(Vital::class);
    }

    public function latestVitals()
    {
        return $this->hasOne(Vital::class)->latestOfMany();
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function labRequests()
    {
        return $this->hasMany(LabRequest::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function latestInvoice()
    {
        return $this->hasOne(Invoice::class)->latestOfMany();
    }

    public function admission()
    {
        return $this->hasOne(Admission::class);
    }

    public function visitInsurance()
    {
        return $this->belongsTo(PatientInsurance::class, 'visit_insurance_id');
    }

    public function cancelledByUser()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function rescheduledFrom()
    {
        return $this->belongsTo(self::class, 'rescheduled_from_id');
    }

    public function rescheduledTo()
    {
        return $this->hasOne(self::class, 'rescheduled_from_id');
    }

    public function visitServices()
    {
        return $this->hasMany(VisitServiceItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeToday($query)
    {
        return $query->whereDate('visit_date', today());
    }

    public function scopeByStatus($query, VisitStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [
            VisitStatus::COMPLETED->value,
            VisitStatus::DISCHARGED->value,
            VisitStatus::CANCELLED->value,
            VisitStatus::RESCHEDULED->value,
            VisitStatus::NO_SHOW->value,
        ]);
    }

    public function scopeScheduled($query)
    {
        return $query->whereIn('status', [
            VisitStatus::SCHEDULED->value,
            VisitStatus::CONFIRMED->value,
        ]);
    }

    public function scopeUpcoming($query)
    {
        return $query->scheduled()->where('visit_date', '>=', today());
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('visit_number', 'like', "%{$term}%")
              ->orWhere('chief_complaint', 'like', "%{$term}%")
              ->orWhereHas('patient', function ($pq) use ($term) {
                  $pq->where('first_name', 'like', "%{$term}%")
                     ->orWhere('last_name', 'like', "%{$term}%")
                     ->orWhere('patient_number', 'like', "%{$term}%")
                     ->orWhere('phone', 'like', "%{$term}%");
              });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Status Transitions
    |--------------------------------------------------------------------------
    */

    public function canTransitionTo(VisitStatus $target): bool
    {
        return $this->status->canTransitionTo($target);
    }

    public function transitionTo(VisitStatus $target, ?string $notes = null): void
    {
        $from = $this->status;

        $this->update(['status' => $target]);

        $this->statusLogs()->create([
            'from_status' => $from->value,
            'to_status' => $target->value,
            'changed_by' => auth()->id(),
            'notes' => $notes,
        ]);

        // Auto-set timestamps
        if ($target === VisitStatus::WAITING && !$this->checked_in_at) {
            $this->update(['checked_in_at' => now()]);
        }

        if (in_array($target, [VisitStatus::COMPLETED, VisitStatus::CANCELLED])) {
            $this->update(['checked_out_at' => now()]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public static function generateVisitNumber(): string
    {
        return static::generateNumber('VST', 'visits', 'visit_number');
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->checked_in_at) return null;

        $end = $this->checked_out_at ?? now();
        $diff = $this->checked_in_at->diff($end);

        if ($diff->h > 0) {
            return $diff->h . 'h ' . $diff->i . 'm';
        }
        return $diff->i . 'm';
    }

    /**
     * Check if the visit is a scheduled appointment (future-dated).
     */
    public function getIsScheduledAttribute(): bool
    {
        return in_array($this->status, [VisitStatus::SCHEDULED, VisitStatus::CONFIRMED]);
    }

    /**
     * Check for scheduling conflicts with a doctor.
     */
    public function hasConflict(): bool
    {
        if (!$this->assigned_doctor_id || !$this->start_time) return false;

        return self::where('assigned_doctor_id', $this->assigned_doctor_id)
            ->where('visit_date', $this->visit_date)
            ->where('id', '!=', $this->id ?? 0)
            ->whereNotIn('status', [
                VisitStatus::CANCELLED->value,
                VisitStatus::NO_SHOW->value,
                VisitStatus::RESCHEDULED->value,
            ])
            ->whereNotNull('start_time')
            ->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->where('start_time', '<', $this->end_time ?? date('H:i', strtotime($this->start_time . ' +30 minutes')))
                          ->where('end_time', '>', $this->start_time);
                });
            })
            ->exists();
    }

    /**
     * Check if patient already has a visit on this date.
     */
    public static function patientHasVisitOnDate(int $patientId, string $date, ?int $excludeId = null): bool
    {
        return self::where('patient_id', $patientId)
            ->whereDate('visit_date', $date)
            ->whereNotIn('status', [
                VisitStatus::CANCELLED->value,
                VisitStatus::RESCHEDULED->value,
                VisitStatus::NO_SHOW->value,
            ])
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }
}
