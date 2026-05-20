<?php

namespace App\Models;

use App\Models\StockBalance;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'department_id',
        'is_active',
        'is_main',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_main'   => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function productMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function productBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function scopeMain($q)
    {
        return $q->where('is_main', true);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
