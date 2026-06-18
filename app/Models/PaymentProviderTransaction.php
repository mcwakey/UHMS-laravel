<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An online / mobile-money payment request handled by a payment provider.
 *
 * This is NOT a UHMS payment. Only a verified, amount- and currency-matched
 * transaction may create/attach a UHMS Payment (see PaymentGatewayService).
 */
class PaymentProviderTransaction extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_INITIATED = 'initiated';
    public const STATUS_PENDING = 'pending';
    public const STATUS_REQUIRES_CUSTOMER_ACTION = 'requires_customer_action';
    public const STATUS_AUTHORIZED = 'authorized';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_RECONCILED = 'reconciled';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

    /** Statuses that represent a confirmed successful collection. */
    public const SUCCESS_STATUSES = [self::STATUS_PAID, self::STATUS_VERIFIED, self::STATUS_RECONCILED];

    protected $fillable = [
        'transaction_uuid',
        'provider_id',
        'provider_code',
        'payment_reference',
        'provider_transaction_id',
        'external_reference',
        'invoice_id',
        'visit_id',
        'patient_id',
        'payer_name',
        'payer_phone',
        'payer_email',
        'amount',
        'currency',
        'payment_method',
        'status',
        'provider_status',
        'initiated_at',
        'authorized_at',
        'paid_at',
        'failed_at',
        'cancelled_at',
        'expired_at',
        'verified_at',
        'uhms_payment_id',
        'accounting_posting_attempt_id',
        'error_code',
        'error_message',
        'metadata_snapshot',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'initiated_at' => 'datetime',
            'authorized_at' => 'datetime',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'expired_at' => 'datetime',
            'verified_at' => 'datetime',
            'metadata_snapshot' => 'array',
        ];
    }

    public function provider()
    {
        return $this->belongsTo(IntegrationProvider::class, 'provider_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'uhms_payment_id');
    }

    public function attempts()
    {
        return $this->hasMany(PaymentProviderAttempt::class);
    }

    public function refunds()
    {
        return $this->hasMany(PaymentProviderRefund::class);
    }

    public function isSuccessful(): bool
    {
        return in_array($this->status, self::SUCCESS_STATUSES, true);
    }

    public function hasUhmsPayment(): bool
    {
        return ! empty($this->uhms_payment_id);
    }
}
