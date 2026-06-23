<?php

namespace App\Http\Controllers\Admin\Reporting;

use App\Enums\LogModule;
use App\Http\Controllers\Controller;
use App\Models\LogRetentionOverride;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogRetentionController extends Controller
{
    public function index()
    {
        $overrides = LogRetentionOverride::orderBy('module')->get()->keyBy('module');
        $default = (int) config('activitylog.delete_records_older_than_days', 365);
        return view('settings.log-retention', [
            'overrides' => $overrides,
            'modules' => LogModule::cases(),
            'default' => $default,
        ]);
    }

    public function update(Request $request, ActivityLogService $logger)
    {
        $data = $request->validate([
            'overrides' => 'array',
            'overrides.*.module' => 'required|string|max:64',
            'overrides.*.retention_days' => 'nullable|integer|min:1|max:36500',
            'overrides.*.reason' => 'nullable|string|max:500',
        ]);

        foreach ($data['overrides'] ?? [] as $row) {
            $module = $row['module'];
            $days = $row['retention_days'] ?? null;

            if (! $days) {
                LogRetentionOverride::where('module', $module)->delete();
                continue;
            }

            LogRetentionOverride::updateOrCreate(
                ['module' => $module],
                [
                    'retention_days' => (int) $days,
                    'reason' => $row['reason'] ?? null,
                    'updated_by' => Auth::id(),
                ]
            );
        }

        $logger->log(\App\Enums\LogModule::SYSTEM, 'RETENTION_OVERRIDES_UPDATED', [
            'severity' => \App\Enums\LogSeverity::NOTICE,
            'metadata' => ['count' => count($data['overrides'] ?? [])],
        ], null, 'Log retention overrides updated');

        return back()->with('success', __('messages.log_retention.updated'));
    }
}
