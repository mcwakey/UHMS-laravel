<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyBay extends Model
{
    use HasFactory;

    public const TYPE_RESUSCITATION = 'RESUSCITATION';

    public const TYPE_OBSERVATION = 'OBSERVATION';

    public const TYPE_TREATMENT = 'TREATMENT';

    public const TYPE_MINOR_PROCEDURE = 'MINOR_PROCEDURE';

    public const TYPE_ISOLATION = 'ISOLATION';

    public const TYPE_WAITING_AREA = 'WAITING_AREA';

    public const TYPE_EMERGENCY_WARD = 'EMERGENCY_WARD';

    public const STATUS_AVAILABLE = 'AVAILABLE';

    public const STATUS_OCCUPIED = 'OCCUPIED';

    public const STATUS_CLEANING = 'CLEANING';

    public const STATUS_OUT_OF_SERVICE = 'OUT_OF_SERVICE';

    public const STATUS_RESERVED = 'RESERVED';

    protected $fillable = [
        'name',
        'code',
        'bay_type',
        'department_id',
        'ward_id',
        'bed_id',
        'status',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function bed()
    {
        return $this->belongsTo(Bed::class);
    }

    public function emergencyCases()
    {
        return $this->hasMany(EmergencyCase::class);
    }

    public function activeCase()
    {
        return $this->hasOne(EmergencyCase::class)
            ->whereNotIn('emergency_status', [
                EmergencyCase::STATUS_DISPOSED,
                EmergencyCase::STATUS_CANCELLED,
            ])
            ->latestOfMany();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->active()->where('status', self::STATUS_AVAILABLE);
    }

    public function markOccupied(): void
    {
        $this->update(['status' => self::STATUS_OCCUPIED]);
    }

    public function markAvailable(): void
    {
        $this->update(['status' => self::STATUS_AVAILABLE]);
    }
}
