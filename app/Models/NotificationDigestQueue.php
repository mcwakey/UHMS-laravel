<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationDigestQueue extends Model
{
    protected $table = 'notification_digest_queue';

    protected $fillable = [
        'user_id',
        'payload',
        'scheduled_for',
        'sent_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'scheduled_for' => 'datetime',
        'sent_at' => 'datetime',
    ];
}
