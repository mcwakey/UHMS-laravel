<?php

namespace App\Models;

use App\Enums\StockLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestigationItemStock extends Model
{
    protected $table = 'investigation_item_stock';

    protected $fillable = [
        'investigation_item_id',
        'location',
        'batch_number',
        'quantity',
        'unit_cost',
        'expiry_date',
        'supplier',
        'supplier_id',
        'received_date',
        'received_by',
        'reorder_level',
    ];

    protected $casts = [
        'location'     => StockLocation::class,
        'unit_cost'    => 'decimal:2',
        'expiry_date'  => 'date',
        'received_date'=> 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function item(): BelongsTo
    {
        return $this->belongsTo(InvestigationItem::class, 'investigation_item_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function supplierRecord(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeAvailable($query)
    {
        return $query->where('quantity', '>', 0)
                     ->where(function ($q) {
                         $q->whereNull('expiry_date')
                           ->orWhere('expiry_date', '>', now());
                     });
    }

    public function scopeAtLocation($query, string $location)
    {
        return $query->where('location', $location);
    }

    public function scopeExpiringSoon($query, int $days = 90)
    {
        return $query->where('quantity', '>', 0)
                     ->whereNotNull('expiry_date')
                     ->where('expiry_date', '<=', now()->addDays($days))
                     ->where('expiry_date', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
                     ->where('expiry_date', '<=', now());
    }

    public function scopeLowStock($query)
    {
        return $query->where('quantity', '>', 0)
                     ->whereColumn('quantity', '<=', 'reorder_level');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        return !$this->is_expired
            && $this->expiry_date !== null
            && $this->expiry_date->lte(now()->addDays(90));
    }

    public function getExpiryStatusAttribute(): string
    {
        if ($this->expiry_date === null) return 'ok';
        if ($this->is_expired) return 'expired';
        if ($this->is_expiring_soon) return 'expiring';
        return 'ok';
    }
}
