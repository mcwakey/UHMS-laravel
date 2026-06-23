<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CountryTown extends Model
{
    protected $fillable = [
        'country_city_id',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(CountryCity::class, 'country_city_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
