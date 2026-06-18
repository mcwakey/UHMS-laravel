<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentProviderAttempt extends Model
{
    use HasFactory;

    public const TYPE_INITIATE = 'initiate';
    public const TYPE_VERIFY = 'verify';
    public const TYPE_STATUS_CHECK = 'status_check';
    public const TYPE_CALLBACK_PROCESS = 'callback_process';
    public const TYPE_REFUND = 'refund';
    public const TYPE_CANCEL = 'cancel';

    public const STATUS_STARTED = 'started';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'payment_provider_transaction_id',
        'provider_id',
        'attempt_type',
        'status',
        'request_payload_snapshot',
        'response_payload_snapshot',
        'http_status',
        'error_code',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'request_payload_snapshot' => 'array',
            'response_payload_snapshot' => 'array',
            'http_status' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function transaction()
    {
        return $this->belongsTo(PaymentProviderTransaction::class, 'payment_provider_transaction_id');
    }
}
