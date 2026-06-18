<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsMessage extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_PARTIALLY_SENT = 'partially_sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_UNDELIVERED = 'undelivered';

    protected $fillable = [
        'message_uuid',
        'provider_id',
        'template_id',
        'sender_id',
        'message_body',
        'message_type',
        'status',
        'scheduled_at',
        'sent_at',
        'completed_at',
        'failed_at',
        'provider_batch_reference',
        'error_code',
        'error_message',
        'metadata_snapshot',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
            'metadata_snapshot' => 'array',
        ];
    }

    public function provider()
    {
        return $this->belongsTo(IntegrationProvider::class, 'provider_id');
    }

    public function template()
    {
        return $this->belongsTo(SmsTemplate::class, 'template_id');
    }

    public function recipients()
    {
        return $this->hasMany(SmsMessageRecipient::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
