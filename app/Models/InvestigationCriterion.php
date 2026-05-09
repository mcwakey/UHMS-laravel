<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestigationCriterion extends Model
{
    protected $table = 'investigation_criteria';

    protected $fillable = [
        'service_id',
        'header_id',
        'name',
        'unit',
        'reference_range',
        'default_value',
        'input_type',
        'options',
        'sort_order',
        'is_required',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'options'     => 'array',
            'is_required' => 'boolean',
            'is_active'   => 'boolean',
            'sort_order'  => 'integer',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceCatalog::class, 'service_id');
    }

    public function header(): BelongsTo
    {
        return $this->belongsTo(InvestigationHeader::class, 'header_id');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
