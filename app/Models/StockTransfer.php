<?php

namespace App\Models;

use App\Enums\StockLocation;
use App\Enums\StockTransferStatus;
use App\Traits\GeneratesNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StockTransfer extends Model
{
    use HasFactory, GeneratesNumbers, LogsActivity;

    protected $fillable = [
        'transfer_number',
        'from_location',
        'to_location',
        'transferred_by',
        'approved_by',
        'transfer_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'from_location' => StockLocation::class,
        'to_location' => StockLocation::class,
        'status' => StockTransferStatus::class,
        'transfer_date' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public static function generateTransferNumber(): string
    {
        return self::generateNumber('TRF', 'stock_transfers', 'transfer_number');
    }

    // Relationships

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function transferredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where('transfer_number', 'like', "%{$term}%");
    }

    // Accessors

    public function getIsEditableAttribute(): bool
    {
        return $this->status === StockTransferStatus::PENDING;
    }
}
