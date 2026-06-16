<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDepreciationLine extends Model
{
    protected $fillable = [
        'asset_depreciation_run_id', 'fixed_asset_id', 'depreciable_amount',
        'depreciation_amount', 'accumulated_after',
    ];

    protected function casts(): array
    {
        return [
            'depreciable_amount' => 'decimal:2',
            'depreciation_amount' => 'decimal:2',
            'accumulated_after' => 'decimal:2',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }
}
