<?php

namespace App\Models;

use App\Enums\ClaimItemStatus;
use App\Enums\ServiceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'claim_id',
        'service_name',
        'service_type',
        'quantity',
        'unit_price',
        'total_price',
        'approved_amount',
        'status',
        'rejection_reason',
    ];

    protected $casts = [
        'service_type' => ServiceType::class,
        'status' => ClaimItemStatus::class,
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'approved_amount' => 'decimal:2',
    ];

    // ── Relationships ────────────────────────────────
    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }
}
