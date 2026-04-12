<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Traits\GeneratesNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visit extends Model
{
    use HasFactory, SoftDeletes, GeneratesNumbers;

    protected $fillable = [
        'visit_number',
        'patient_id',
        'visit_type',
        'visit_date',
        'status',
        'priority',
        'department_id',
        'assigned_doctor_id',
        'chief_complaint',
        'notes',
        'checked_in_at',
        'checked_out_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'visit_type' => VisitType::class,
            'status' => VisitStatus::class,
            'priority' => Priority::class,
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
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
            VisitStatus::CANCELLED->value,
        ]);
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
}
