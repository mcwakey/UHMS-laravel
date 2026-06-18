<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Records each automatic SMS event trigger so the same source/event is not
 * messaged twice, and so skips (toggle off, no phone, no provider) are auditable.
 */
class SmsNotificationEvent extends Model
{
    use HasFactory;

    public const TYPE_INVOICE_PAYMENT_REQUEST = 'invoice_payment_request';
    public const TYPE_PAYMENT_RECEIPT = 'payment_receipt';
    public const TYPE_APPOINTMENT_REMINDER = 'appointment_reminder';
    public const TYPE_QUEUE_NOTIFICATION = 'queue_notification';
    public const TYPE_MANUAL_CUSTOM = 'manual_custom';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'event_type',
        'source_type',
        'source_id',
        'template_id',
        'sms_message_id',
        'recipient_phone',
        'status',
        'triggered_by',
        'triggered_at',
        'sent_at',
        'failed_at',
        'error_message',
        'metadata_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'triggered_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
            'metadata_snapshot' => 'array',
        ];
    }

    public function message()
    {
        return $this->belongsTo(SmsMessage::class, 'sms_message_id');
    }

    public function template()
    {
        return $this->belongsTo(SmsTemplate::class, 'template_id');
    }
}
