<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicationFrequency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'times_per_day',
        'interval_hours',
        'default_times',
        'requires_schedule',
        'is_prn',
        'is_stat',
        'is_active',
    ];

    protected $casts = [
        'default_times' => 'array',
        'requires_schedule' => 'boolean',
        'is_prn' => 'boolean',
        'is_stat' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function medicationOrders()
    {
        return $this->hasMany(MedicationOrder::class, 'frequency_id');
    }
}
