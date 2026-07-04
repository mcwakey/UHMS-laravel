<?php

namespace App\Models;

use App\Enums\BedStatus;
use App\Enums\BedType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Bed extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['bed_number', 'status', 'bed_type', 'daily_rate'])
            ->logOnlyDirty()
            ->useLogName('beds')
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'ward_id',
        'bed_number',
        'bed_type',
        'status',
        'reserved_until',
        'status_reason',
        'status_changed_by',
        'status_changed_at',
        'daily_rate',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'bed_type' => BedType::class,
            'status' => BedStatus::class,
            'reserved_until' => 'datetime',
            'status_changed_at' => 'datetime',
            'daily_rate' => 'decimal:2',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function admissions()
    {
        return $this->hasMany(Admission::class);
    }

    public function admissionRequests()
    {
        return $this->hasMany(AdmissionRequest::class, 'reserved_bed_id');
    }

    public function reservations()
    {
        return $this->hasMany(BedReservation::class);
    }

    public function activeReservation()
    {
        return $this->hasOne(BedReservation::class)->where('status', 'active')->latestOfMany();
    }

    public function statusChangedBy()
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }

    public function currentAdmission()
    {
        return $this->hasOne(Admission::class)->where('status', 'admitted')->latestOfMany();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeAvailable($query)
    {
        return $query->where('status', BedStatus::AVAILABLE);
    }

    public function scopeByWard($query, $wardId)
    {
        return $query->where('ward_id', $wardId);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function markOccupied(?int $userId = null, ?string $reason = null): void
    {
        $this->updateStatus(BedStatus::OCCUPIED, $userId, $reason);
    }

    public function markAvailable(?int $userId = null, ?string $reason = null): void
    {
        $this->updateStatus(BedStatus::AVAILABLE, $userId, $reason);
    }

    public function markReserved(?int $userId = null, ?string $reason = null, $reservedUntil = null): void
    {
        $this->updateStatus(BedStatus::RESERVED, $userId, $reason, $reservedUntil);
    }

    public function updateStatus(BedStatus $status, ?int $userId = null, ?string $reason = null, $reservedUntil = null): void
    {
        $this->update([
            'status' => $status,
            'reserved_until' => $status === BedStatus::RESERVED ? $reservedUntil : null,
            'status_reason' => $reason,
            'status_changed_by' => $userId,
            'status_changed_at' => now(),
        ]);
    }

    public function getIsAvailableAttribute(): bool
    {
        return $this->status === BedStatus::AVAILABLE;
    }
}
