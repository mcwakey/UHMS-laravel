<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A secure payment request reference (UUID) that can be sent by SMS or shown on
 * an invoice page. A link NEVER marks an invoice paid — it only seeds provider
 * payment initiation, which is then verified before any UHMS payment is created.
 */
class PaymentRequestLink extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INITIATED = 'initiated';
    public const STATUS_USED = 'used';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'link_uuid',
        'public_token',
        'token_hash',
        'invoice_id',
        'visit_id',
        'patient_id',
        'payer_type',
        'payer_id',
        'amount',
        'currency',
        'status',
        'initiated_count',
        'last_initiated_at',
        'last_viewed_at',
        'expires_at',
        'used_at',
        'cancelled_by',
        'cancelled_at',
        'cancel_reason',
        'success_redirect_url',
        'failure_redirect_url',
        'payment_provider_transaction_id',
        'created_by',
        'metadata_snapshot',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'initiated_count' => 'integer',
            'last_initiated_at' => 'datetime',
            'last_viewed_at' => 'datetime',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'metadata_snapshot' => 'array',
        ];
    }


    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function transaction()
    {
        return $this->belongsTo(PaymentProviderTransaction::class, 'payment_provider_transaction_id');
    }

    public function isUsable(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }
        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || ($this->expires_at !== null && $this->expires_at->isPast());
    }
}
