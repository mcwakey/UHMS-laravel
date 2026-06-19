<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Refund foundation (Phase 1). Records intent + provider result; the actual
 * UHMS refund/credit-note workflow is wired in a later phase.
 */
class PaymentProviderRefund extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    // Phase 3 — provider/credit-note lifecycle
    public const STATUS_PROVIDER_PENDING = 'provider_pending';
    public const STATUS_PROVIDER_REFUNDED = 'provider_refunded';
    public const STATUS_PROVIDER_FAILED = 'provider_failed';
    public const STATUS_PROVIDER_UNSUPPORTED = 'provider_unsupported';
    public const STATUS_MANUAL_REQUIRED = 'manual_required';

    protected $fillable = [
        'payment_provider_transaction_id',
        'provider_id',
        'refund_reference',
        'provider_refund_id',
        'amount',
        'currency',
        'status',
        'reason',
        'requested_by',
        'requested_at',
        'processed_at',
        'failed_at',
        'uhms_refund_id',
        'uhms_credit_note_id',
        'metadata_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'requested_at' => 'datetime',
            'processed_at' => 'datetime',
            'failed_at' => 'datetime',
            'metadata_snapshot' => 'array',
        ];
    }

    public function creditNote()
    {
        return $this->belongsTo(CreditNote::class, 'uhms_credit_note_id');
    }

    public function transaction()
    {
        return $this->belongsTo(PaymentProviderTransaction::class, 'payment_provider_transaction_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
