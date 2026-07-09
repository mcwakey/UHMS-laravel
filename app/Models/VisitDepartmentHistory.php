<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitDepartmentHistory extends Model
{
    protected $table = 'visit_department_history';

    protected $fillable = [
        'visit_id',
        'department_id',
        'type',
        'status',
        'assigned_by',
        'notes',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at'   => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Type constants
    |--------------------------------------------------------------------------
    */

    const TYPE_TRIAGE       = 'triage';
    const TYPE_CONSULTATION = 'consultation';
    const TYPE_REFERRAL     = 'referral';
    const TYPE_INVESTIGATION = 'investigation';
    const TYPE_PHARMACY = 'pharmacy';
    const TYPE_PROCEDURE = 'procedure';

    const STATUS_WAITING     = 'waiting';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED   = 'completed';
    const STATUS_CANCELLED   = 'cancelled';

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeConsultations($query)
    {
        return $query->whereIn('type', [self::TYPE_CONSULTATION, self::TYPE_REFERRAL]);
    }

    public function scopeInvestigations($query)
    {
        return $query->where('type', self::TYPE_INVESTIGATION);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_WAITING, self::STATUS_IN_PROGRESS]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_TRIAGE        => 'Triage',
            self::TYPE_CONSULTATION  => 'Consultation',
            self::TYPE_REFERRAL      => 'Referral',
            self::TYPE_INVESTIGATION => 'Investigation',
            self::TYPE_PHARMACY      => 'Pharmacy',
            self::TYPE_PROCEDURE     => 'Procedure',
            default                  => ucfirst($this->type),
        };
    }

    public function translatedTypeLabel(): string
    {
        return __("consultations.history_type.{$this->type}");
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_WAITING     => 'Waiting',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED   => 'Completed',
            self::STATUS_CANCELLED   => 'Cancelled',
            default                  => ucfirst($this->status),
        };
    }

    public function translatedStatusLabel(): string
    {
        return __("consultations.history_status.{$this->status}");
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_WAITING     => 'warning',
            self::STATUS_IN_PROGRESS => 'primary',
            self::STATUS_COMPLETED   => 'success',
            self::STATUS_CANCELLED   => 'danger',
            default                  => 'secondary',
        };
    }

    public function typeColor(): string
    {
        return match ($this->type) {
            self::TYPE_TRIAGE        => 'info',
            self::TYPE_CONSULTATION  => 'primary',
            self::TYPE_REFERRAL      => 'indigo',
            self::TYPE_INVESTIGATION => 'purple',
            self::TYPE_PHARMACY      => 'orange',
            self::TYPE_PROCEDURE     => 'warning',
            default                  => 'secondary',
        };
    }
}
