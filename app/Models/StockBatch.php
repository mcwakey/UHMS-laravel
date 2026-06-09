<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A group of manual stock movements recorded together — one batch per
 * adjustment / return / transfer submission. Its lines are the StockMovements
 * that point back at it.
 */
class StockBatch extends Model
{
    use HasFactory;

    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_RETURN = 'return';
    public const TYPE_TRANSFER = 'transfer';

    protected $fillable = [
        'batch_number',
        'type',
        'source_location_id',
        'dest_location_id',
        'reason',
        'notes',
        'created_by',
    ];

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function sourceLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'source_location_id');
    }

    public function destLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'dest_location_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_ADJUSTMENT => 'Adjustment',
            self::TYPE_RETURN => 'Return',
            self::TYPE_TRANSFER => 'Transfer',
            default => ucfirst((string) $this->type),
        };
    }

    public static function prefixFor(string $type): string
    {
        return match ($type) {
            self::TYPE_ADJUSTMENT => 'ADJ',
            self::TYPE_RETURN => 'RET',
            self::TYPE_TRANSFER => 'TRF',
            default => 'BAT',
        };
    }

    /**
     * Assign a readable, unique batch number derived from the id.
     */
    public function assignNumber(): self
    {
        if (! $this->batch_number) {
            $this->forceFill([
                'batch_number' => self::prefixFor((string) $this->type).'-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT),
            ])->save();
        }

        return $this;
    }
}
