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
        'invoice_item_id',
        'visit_id',
        'patient_id',
        'service_id',
        'product_id',
        'department_id',
        'service_name',
        'service_type',
        'description',
        'item_type',
        'quantity',
        'unit_price',
        'cash_price',
        'insurance_price',
        'selected_price',
        'claim_amount',
        'total_price',
        'approved_amount',
        'rejected_amount',
        'status',
        'rejection_reason',
        'metadata',
    ];

    protected $casts = [
        'service_type' => ServiceType::class,
        'status' => ClaimItemStatus::class,
        'unit_price' => 'decimal:2',
        'cash_price' => 'decimal:2',
        'insurance_price' => 'decimal:2',
        'selected_price' => 'decimal:2',
        'claim_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'rejected_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    // ── Relationships ────────────────────────────────
    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceCatalog::class, 'service_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
