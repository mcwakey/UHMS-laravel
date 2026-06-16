<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCategory extends Model
{
    protected $fillable = [
        'name', 'code', 'depreciation_method', 'useful_life_months',
        'default_residual_rate', 'asset_cost_account_id',
        'accumulated_depreciation_account_id', 'depreciation_expense_account_id',
        'disposal_gain_account_id', 'disposal_loss_account_id', 'is_active',
    ];

    protected function casts(): array
    {
        return ['default_residual_rate' => 'decimal:4', 'is_active' => 'boolean'];
    }

    public function assets(): HasMany
    {
        return $this->hasMany(FixedAsset::class);
    }

    public function costAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'asset_cost_account_id');
    }

    public function accumulatedDepreciationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accumulated_depreciation_account_id');
    }

    public function depreciationExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'depreciation_expense_account_id');
    }
}
