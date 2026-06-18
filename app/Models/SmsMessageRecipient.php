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
        'metadata_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
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
