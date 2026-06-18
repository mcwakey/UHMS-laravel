<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsMessageRecipient extends Model
{
    use HasFactory;

    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';
    public const STATUS_UNDELIVERED = 'undelivered';

    protected $fillable = [
        'sms_message_id',
        'recipient_type',
        'recipient_id',
        'phone_number',
        'normalized_phone_number',
        'recipient_name',
        'status',
        'provider_message_id',
        'provider_status',
        'sent_at',
        'delivered_at',
        'failed_at',
        'error_code',
        'error_message',
        'retry_count',
        'last_retry_at',
        'next_retry_at',
        'provider_status_checked_at',
        'metadata_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
            'last_retry_at' => 'datetime',
            'next_retry_at' => 'datetime',
            'provider_status_checked_at' => 'datetime',
            'retry_count' => 'integer',
            'metadata_snapshot' => 'array',
        ];
    }

    public function message()
    {
        return $this->belongsTo(SmsMessage::class, 'sms_message_id');
    }

    public function deliveryReports()
    {
        return $this->hasMany(SmsDeliveryReport::class);
    }
}
