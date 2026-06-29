<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-user, per-event journey notification preference. Optional — the preference
 * service applies safe defaults when no row exists.
 */
class JourneyNotificationPreference extends Model
{
    protected $fillable = [
        'user_id', 'event', 'in_app_enabled', 'email_enabled', 'sms_enabled', 'digest_enabled',
    ];

    protected $casts = [
        'in_app_enabled' => 'boolean',
        'email_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'digest_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
