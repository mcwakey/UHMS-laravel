<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DrugStock extends Model
{
    protected $table = 'drug_stock';

    protected $fillable = [
        'drug_id',
        'batch_number',
        'quantity',
        'unit_cost',
        'selling_price',
        'expiry_date',
        'supplier',
        'received_date',
        'received_by',
        'reorder_level',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'expiry_date' => 'date',
        'received_date' => 'date',
    ];

    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function dispensingRecords(): HasMany
    {
        return $this->hasMany(DispensingRecord::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('quantity', '>', 0)
                     ->where('expiry_date', '>', now());
    }

    public function scopeExpiringSoon($query, int $days = 90)
    {
        return $query->where('quantity', '>', 0)
                     ->where('expiry_date', '<=', now()->addDays($days))
                     ->where('expiry_date', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<=', now());
    }

    public function scopeLowStock($query)
    {
        return $query->where('quantity', '>', 0)
                     ->whereColumn('quantity', '<=', 'reorder_level');
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date->isPast();
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        return !$this->is_expired && $this->expiry_date->lte(now()->addDays(90));
    }

    public function getExpiryStatusAttribute(): string
    {
        if ($this->is_expired) return 'expired';
        if ($this->is_expiring_soon) return 'expiring';
        return 'ok';
    }

    public function getExpiryColorAttribute(): string
    {
        return match ($this->expiry_status) {
            'expired' => 'danger',
            'expiring' => 'warning',
            default => 'success',
        };
    }
}
