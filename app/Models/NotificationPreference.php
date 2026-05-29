<?php

namespace App\Models;

use App\Enums\NotificationModule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    use HasFactory;

    public const CHANNEL_DATABASE = 'database';
    public const CHANNEL_BROADCAST = 'broadcast';
    public const CHANNEL_MAIL = 'mail';
    public const CHANNEL_SMS = 'sms';

    public const ALL_CHANNELS = [
        self::CHANNEL_DATABASE,
        self::CHANNEL_BROADCAST,
        self::CHANNEL_MAIL,
        self::CHANNEL_SMS,
    ];

    protected $fillable = [
        'user_id',
        'module',
        'channels',
        'digest_enabled',
        'quiet_hours_start',
        'quiet_hours_end',
    ];

    protected $casts = [
        'channels' => 'array',
        'digest_enabled' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function forUser(int $userId, ?string $module): ?self
    {
        if (! $module) {
            return null;
        }
        return static::query()
            ->where('user_id', $userId)
            ->where('module', $module)
            ->first();
    }
}
