<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use App\Traits\GeneratesNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes, GeneratesNumbers, LogsActivity;

    protected $fillable = [
        'po_number',
        'supplier_id',
        'order_date',
        'expected_date',
        'received_date',
        'total_amount',
        'status',
        'notes',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'status' => PurchaseOrderStatus::class,
        'order_date' => 'date',
        'expected_date' => 'date',
        'received_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public static function generatePONumber(): string
    {
        return self::generateNumber('PO', 'purchase_orders', 'po_number');
    }

    // Relationships

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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

    public function scopeBySupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeDateRange($query, ?string $from, ?string $to)
    {
        return $query->when($from, fn ($q, $d) => $q->where('order_date', '>=', $d))
                     ->when($to, fn ($q, $d) => $q->where('order_date', '<=', $d));
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('po_number', 'like', "%{$term}%")
              ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$term}%"));
        });
    }

    // Accessors

    public function getIsEditableAttribute(): bool
    {
        return in_array($this->status, [PurchaseOrderStatus::DRAFT]);
    }

    public function getIsReceivableAttribute(): bool
    {
        return in_array($this->status, [
            PurchaseOrderStatus::APPROVED,
            PurchaseOrderStatus::PARTIALLY_RECEIVED,
        ]);
    }

    // Helpers

    public function recalculateTotal(): void
    {
        $this->update([
            'total_amount' => $this->items()->sum('total_cost'),
        ]);
    }
}
