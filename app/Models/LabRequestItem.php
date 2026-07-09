<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LabRequestItem extends Model
{
    protected $fillable = [
        'lab_request_id',
        'sample_id',
        'lab_test_id',
        'service_id',
        'status',
        'name',
        'accepted_at',
        'accepted_by',
        'billed_at',
        'invoice_item_id',
        'unit_price',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'billed_at'   => 'datetime',
        'unit_price'  => 'decimal:2',
    ];

    public function labRequest(): BelongsTo
    {
        return $this->belongsTo(LabRequest::class);
    }

    public function sample(): BelongsTo
    {
        return $this->belongsTo(Sample::class);
    }

    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTest::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceCatalog::class, 'service_id');
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    /**
     * Whether this item's bill needs no further cash payment. Items with no
     * invoice line are considered settled. Paid, covered, waived, or fully
     * adjusted invoices can proceed to result entry.
     */
    public function isBillSettled(): bool
    {
        if (! $this->invoice_item_id) {
            return true;
        }

        $invoiceItem = $this->relationLoaded('invoiceItem')
            ? $this->invoiceItem
            : InvoiceItem::find($this->invoice_item_id);

        return $invoiceItem
            ? app(\App\Services\Billing\InvoiceItemSettlementService::class)
                ->canProceedWithoutCashPayment($invoiceItem)
            : true;
    }

    /**
     * Resolve the display name for the request item — preferring service, then lab test, then free-text name.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->relationLoaded('service') && $this->service) {
            return $this->service->name;
        }
        if ($this->service_id && $service = $this->service()->first()) {
            return $service->name;
        }
        if ($this->relationLoaded('labTest') && $this->labTest) {
            return $this->labTest->name;
        }
        if ($this->lab_test_id && $test = $this->labTest()->first()) {
            return $test->name;
        }
        return (string) ($this->name ?? '—');
    }

    public function result(): HasOne
    {
        return $this->hasOne(LabResult::class);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending'    => 'warning',
            'accepted'   => 'primary',
            'processing' => 'info',
            'completed'  => 'success',
            'verified'   => 'success',
            'cancelled'  => 'secondary',
            'rejected'   => 'danger',
            default      => 'secondary',
        };
    }

    public function isAccepted(): bool
    {
        return !is_null($this->accepted_at) || in_array($this->status, ['accepted', 'processing', 'completed', 'verified']);
    }

    public function hasResult(): bool
    {
        return $this->result()->exists();
    }

    /**
     * Whether result entry is blocked because this item's specimen has not yet
     * been received in the lab. Items with no linked sample are unaffected
     * (backward compatible with requests created before sample tracking).
     */
    public function isBlockedBySample(): bool
    {
        if (! $this->sample_id) {
            return false;
        }

        $sample = $this->relationLoaded('sample') ? $this->sample : $this->sample()->first();

        return $sample !== null && ! $sample->isReceived();
    }

    /**
     * Whether this item can still be deleted/cancelled by the requesting doctor.
     * Forbidden once a result exists or workflow has progressed past entry.
     */
    public function isDeletable(): bool
    {
        if ($this->hasResult()) {
            return false;
        }
        return in_array($this->status, ['pending', 'rejected', 'cancelled']);
    }
}
