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

    public function transaction()
    {
        return $this->belongsTo(PaymentProviderTransaction::class, 'payment_provider_transaction_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
