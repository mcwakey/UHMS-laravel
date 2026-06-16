<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAsset extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_DISPOSED = 'disposed';

    protected $fillable = [
        'asset_number', 'asset_category_id', 'asset_location_id', 'custodian_id',
        'name', 'description', 'acquisition_date', 'placed_in_service_date',
        'cost', 'residual_value', 'useful_life_months', 'depreciation_method',
        'accumulated_depreciation', 'impairment_amount', 'status',
        'capitalization_journal_entry_id', 'capitalized_at', 'capitalized_by',
        'disposed_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_date' => 'date',
            'placed_in_service_date' => 'date',
            'disposed_at' => 'date',
            'capitalized_at' => 'datetime',
            'cost' => 'decimal:2',
            'residual_value' => 'decimal:2',
            'accumulated_depreciation' => 'decimal:2',
            'impairment_amount' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(AssetLocation::class, 'asset_location_id');
    }

    public function custodian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'custodian_id');
    }

    public function depreciationLines(): HasMany
    {
        return $this->hasMany(AssetDepreciationLine::class);
    }

    public function assetAcquisitions(): HasMany
    {
        return $this->hasMany(AssetAcquisition::class);
    }

    public function assetTransfers(): HasMany
    {
        return $this->hasMany(AssetTransfer::class);
    }

    public function assetVerifications(): HasMany
    {
        return $this->hasMany(AssetVerification::class);
    }

    public function disposals(): HasMany
    {
        return $this->hasMany(AssetDisposal::class);
    }

    public function getCarryingAmountAttribute(): float
    {
        return round((float) $this->cost - (float) $this->accumulated_depreciation - (float) $this->impairment_amount, 2);
    }
}
