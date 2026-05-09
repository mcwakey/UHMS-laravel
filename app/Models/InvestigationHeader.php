<?php

namespace App\Models;

use App\Models\InvestigationCriterion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvestigationHeader extends Model
{
    protected $fillable = [
        'service_id',
        'name',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceCatalog::class, 'service_id');
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(InvestigationCriterion::class, 'header_id')->orderBy('sort_order');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
