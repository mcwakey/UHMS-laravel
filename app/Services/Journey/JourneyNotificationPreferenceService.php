<?php

namespace App\Services\Journey;

use App\Enums\JourneyHandoffNotificationEvent;
use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\JourneyNotificationPreference;
use App\Models\User;
use App\Services\ActivityLogService;

/**
 * Per-user, per-event journey notification preferences. Works with NO row present
 * (safe defaults), gates on the global channel config, and caches per request so a
 * batch of recipients costs at most one query per distinct user.
 */
class JourneyNotificationPreferenceService
{
    /** In-app defaults when the user has no saved row. */
    private const IN_APP_DEFAULTS = [
        'handoff_assigned' => true,
        'handoff_claimed' => false,
        'handoff_acknowledged' => true,
        'handoff_resolved' => true,
        'handoff_escalated' => true,
        'handoff_critical' => true,
        'handoff_stale_dismissed' => true,
        'handoff_unassigned_near_breach' => false,
        'handoff_unassigned_breached' => true,
    ];

    /** @var array<int, array<string, JourneyNotificationPreference>> */
    private array $cache = [];

    public function __construct(private ActivityLogService $activity) {}

    public function allows(User $user, JourneyHandoffNotificationEvent $event, string $channel = 'in_app'): bool
    {
        // Global channel gate (in_app on by default; email/sms off unless configured).
        if (! (bool) config('journey.notifications.'.$channel, $channel === 'in_app')) {
            return false;
        }

        $preference = $this->preferenceFor($user, $event->value);
        if ($preference !== null) {
            return (bool) $preference->{$channel.'_enabled'};
        }

        return $channel === 'in_app'
            ? (self::IN_APP_DEFAULTS[$event->value] ?? true)
            : false;
    }

    /** @return array<string, array{in_app:bool,email:bool,sms:bool,digest:bool}> for the settings UI. */
    public function defaultsFor(User $user): array
    {
        $rows = JourneyNotificationPreference::where('user_id', $user->id)->get()->keyBy('event');
        $out = [];
        foreach (JourneyHandoffNotificationEvent::cases() as $event) {
            $row = $rows->get($event->value);
            $out[$event->value] = [
                'in_app' => $row ? (bool) $row->in_app_enabled : (self::IN_APP_DEFAULTS[$event->value] ?? true),
                'email' => $row ? (bool) $row->email_enabled : false,
                'sms' => $row ? (bool) $row->sms_enabled : false,
                'digest' => $row ? (bool) $row->digest_enabled : false,
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, array<string, bool>>  $preferences  event => [in_app, email, sms, digest]
     */
    public function update(User $user, array $preferences): void
    {
        foreach ($preferences as $event => $channels) {
            if (JourneyHandoffNotificationEvent::tryFrom((string) $event) === null) {
                continue;
            }
            JourneyNotificationPreference::updateOrCreate(
                ['user_id' => $user->id, 'event' => $event],
                [
                    'in_app_enabled' => (bool) ($channels['in_app'] ?? false),
                    'email_enabled' => (bool) ($channels['email'] ?? false),
                    'sms_enabled' => (bool) ($channels['sms'] ?? false),
                    'digest_enabled' => (bool) ($channels['digest'] ?? false),
                ],
            );
        }

        unset($this->cache[$user->id]);

        $this->activity->log(LogModule::CLINICAL_TASKS, 'JOURNEY_NOTIFICATION_PREFERENCES_UPDATED', [
            'severity' => LogSeverity::INFO,
            'target_user_id' => $user->id,
            'preference_new' => $preferences,
        ], $user, 'Journey notification preferences updated');
    }

    private function preferenceFor(User $user, string $event): ?JourneyNotificationPreference
    {
        if (! isset($this->cache[$user->id])) {
            $this->cache[$user->id] = JourneyNotificationPreference::where('user_id', $user->id)
                ->get()
                ->keyBy('event')
                ->all();
        }

        return $this->cache[$user->id][$event] ?? null;
    }
}
