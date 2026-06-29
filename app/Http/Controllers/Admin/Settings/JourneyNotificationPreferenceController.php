<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Enums\JourneyHandoffNotificationEvent;
use App\Http\Controllers\Controller;
use App\Services\Journey\JourneyNotificationPreferenceService;
use Illuminate\Http\Request;

/**
 * Phase 9.7 — per-user journey notification preferences. Any authenticated user may
 * manage their own. Channels disabled at the global config level are shown disabled.
 */
class JourneyNotificationPreferenceController extends Controller
{
    public function __construct(private JourneyNotificationPreferenceService $preferences) {}

    public function show(Request $request)
    {
        return view('admin.settings.journey-notifications', [
            'preferences' => $this->preferences->defaultsFor($request->user()),
            'events' => JourneyHandoffNotificationEvent::cases(),
            'emailEnabled' => (bool) config('journey.notifications.email', false),
            'smsEnabled' => (bool) config('journey.notifications.sms', false),
        ]);
    }

    public function update(Request $request)
    {
        $input = (array) $request->input('preferences', []);
        $prefs = [];
        foreach (JourneyHandoffNotificationEvent::cases() as $event) {
            $row = (array) ($input[$event->value] ?? []);
            $prefs[$event->value] = [
                'in_app' => (bool) ($row['in_app'] ?? false),
                'email' => (bool) ($row['email'] ?? false),
                'sms' => (bool) ($row['sms'] ?? false),
            ];
        }

        $this->preferences->update($request->user(), $prefs);

        return back()->with('success', __('journey.notification.preferences_saved'));
    }
}
