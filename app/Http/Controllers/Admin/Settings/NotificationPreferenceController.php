<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Enums\NotificationModule;
use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationPreferenceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $prefs = NotificationPreference::where('user_id', $user->id)
            ->get()->keyBy('module');

        return view('settings.notification-preferences', [
            'user' => $user,
            'modules' => NotificationModule::cases(),
            'preferences' => $prefs,
            'channels' => NotificationPreference::ALL_CHANNELS,
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'preferences' => 'array',
            'preferences.*.module' => 'required|string|max:64',
            'preferences.*.channels' => 'array',
            'preferences.*.channels.*' => 'in:database,broadcast,mail,sms',
            'preferences.*.digest_enabled' => 'sometimes|boolean',
            'preferences.*.quiet_hours_start' => 'nullable|date_format:H:i',
            'preferences.*.quiet_hours_end' => 'nullable|date_format:H:i',
        ]);

        foreach ($data['preferences'] ?? [] as $row) {
            NotificationPreference::updateOrCreate(
                ['user_id' => $user->id, 'module' => $row['module']],
                [
                    'channels' => $row['channels'] ?? ['database'],
                    'digest_enabled' => (bool) ($row['digest_enabled'] ?? false),
                    'quiet_hours_start' => $row['quiet_hours_start'] ?? null,
                    'quiet_hours_end' => $row['quiet_hours_end'] ?? null,
                ]
            );
        }

        return back()->with('success', __('messages.notification_preferences.saved'));
    }
}
