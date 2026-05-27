<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PharmacyBillingSelection extends Model
{
    public const STATUS_SELECTED = 'SELECTED';

    public const STATUS_BILLED = 'BILLED';

    public const STATUS_PARTIALLY_DISPENSED = 'PARTIALLY_DISPENSED';

    public const STATUS_DISPENSED = 'DISPENSED';

    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'visit_id',
        'patient_id',
        'prescription_id',
        'prescription_item_id',
        'product_id',
        'prescribed_quantity',
        'selected_quantity',
        'billed_quantity',
        'dispensed_quantity',
        'invoice_item_id',
        'selected_by',
        'billed_by',
        'dispensed_by',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'prescribed_quantity' => 'decimal:4',
            'selected_quantity' => 'decimal:4',
            'billed_quantity' => 'decimal:4',
            'dispensed_quantity' => 'decimal:4',
        ];
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    public function prescriptionItem()
    {
        return $this->belongsTo(PrescriptionItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function invoiceItem()
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function selectedBy()
    {
        return $this->belongsTo(User::class, 'selected_by');
    }

    public function billedBy()
    {
        return $this->belongsTo(User::class, 'billed_by');
    }

    public function dispensedBy()
    {
        return $this->belongsTo(User::class, 'dispensed_by');
    }

    public function getRemainingQuantityAttribute(): float
    {
        return max(0.0, (float) $this->billed_quantity - (float) $this->dispensed_quantity);
    }
}