<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodStorageLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'location_type',
        'temperature_min',
        'temperature_max',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'temperature_min' => 'decimal:2',
        'temperature_max' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function units()
    {
        return $this->hasMany(BloodUnit::class, 'storage_location_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
