<?php

namespace App\Models;

use App\Enums\StockRequisitionStatus;
use App\Models\StockRequisitionItem;
use App\Traits\GeneratesNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockRequisition extends Model
{
    use HasFactory, GeneratesNumbers;

    protected $fillable = [
        'requisition_number',
        'department_id',
        'requested_by',
        'approved_by',
        'issued_by',
        'acknowledged_by',
        'requested_at',
        'approved_at',
        'issued_at',
        'acknowledged_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'issued_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'status' => StockRequisitionStatus::class,
    ];

    public static function generateRequisitionNumber(): string
    {
        return self::generateNumber('SR', 'stock_requisitions', 'requisition_number');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockRequisitionItem::class);
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function issuedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function acknowledgedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function getIsEditableAttribute(): bool
    {
        return $this->status === StockRequisitionStatus::DRAFT;
    }
}
