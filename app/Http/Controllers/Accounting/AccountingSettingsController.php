<?php

namespace App\Http\Controllers\Accounting;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\AccountingSettingsService;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class AccountingSettingsController extends Controller
{
    public function index(AccountingSettingsService $service)
    {
        $service->ensureDefaults();

        return view('accounting.settings.index', [
            'settings' => $service->all(),
            'accounts' => Account::active()->orderBy('code')->get(),
        ]);
    }

    public function update(Request $request, AccountingSettingsService $service)
    {
        $service->ensureDefaults();
        $rules = [];
        foreach ($service->definitions() as $key => $description) {
            if (str_ends_with($key, '_account_id')) {
                $rules[$key] = ['nullable', 'exists:accounts,id'];
            }
        }
        $request->validate($rules);

        $before = $service->all()->keyBy('key')->map(fn ($setting) => [
            'account_id' => $setting->account_id,
            'value' => $setting->value,
        ])->all();

        $payload = [];
        foreach ($service->definitions() as $key => $description) {
            $payload[$key] = str_ends_with($key, '_account_id')
                ? $request->input($key)
                : $request->boolean($key);
        }

        $service->update($payload, $request->user());
        $after = $service->all()->keyBy('key')->map(fn ($setting) => [
            'account_id' => $setting->account_id,
            'value' => $setting->value,
        ])->all();

        app(ActivityLogService::class)->log(LogModule::ACCOUNTING, 'ACCOUNTING_SETTINGS_UPDATED', [
            'severity' => LogSeverity::WARNING,
            'old_values' => $before,
            'new_values' => $after,
        ], null, 'Accounting settings updated');

        return back()->with('success', __('messages.accounting.settings_updated'));
    }
}
