<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalPattern extends Model
{
    protected $fillable = [
        'name',
        'doctor_id',
        'usage_count',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'usage_count' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MedicalPatternItem::class)->orderBy('sort_order');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDoctor($query, int $doctorId)
    {
        return $query->where(function ($q) use ($doctorId) {
            $q->where('doctor_id', $doctorId)
              ->orWhereNull('doctor_id');
        });
    }

    public function scopeSystemWide($query)
    {
        return $query->whereNull('doctor_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function getItemsByType(string $type)
    {
        return $this->items->where('type', $type);
    }

    public function getIsSystemAttribute(): bool
    {
        return is_null($this->doctor_id);
    }
}
