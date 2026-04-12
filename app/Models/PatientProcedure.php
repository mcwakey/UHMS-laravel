<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientProcedure extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id',
        'patient_id',
        'procedure_id',
        'performed_by',
        'scheduled_date',
        'performed_date',
        'status',
        'notes',
        'outcome',
        'consent_signed',
    ];

    protected $casts = [
        'scheduled_date' => 'datetime',
        'performed_date' => 'datetime',
        'consent_signed' => 'boolean',
    ];

    /* ── Relationships ────────────────────────────────── */

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    public function performedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /* ── Scopes ───────────────────────────────────────── */

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByVisit($query, int $visitId)
    {
        return $query->where('visit_id', $visitId);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /* ── Accessors ────────────────────────────────────── */

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'scheduled' => 'info',
            'in_progress' => 'warning',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'scheduled' => 'Scheduled',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }
}
