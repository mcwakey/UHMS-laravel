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
        'daily_rate',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'bed_type' => BedType::class,
            'status' => BedStatus::class,
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

    public function markOccupied(): void
    {
        $this->update(['status' => BedStatus::OCCUPIED]);
    }

    public function markAvailable(): void
    {
        $this->update(['status' => BedStatus::AVAILABLE]);
    }

    public function getIsAvailableAttribute(): bool
    {
        return $this->status === BedStatus::AVAILABLE;
    }
}
