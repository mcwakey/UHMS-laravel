<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsDeliveryReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'sms_message_recipient_id',
        'provider_id',
        'provider_message_id',
        'provider_status',
        'status',
        'reported_at',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }

    public function recipient()
    {
        return $this->belongsTo(SmsMessageRecipient::class, 'sms_message_recipient_id');
    }

    public function provider()
    {
        return $this->belongsTo(IntegrationProvider::class, 'provider_id');
    }
}
