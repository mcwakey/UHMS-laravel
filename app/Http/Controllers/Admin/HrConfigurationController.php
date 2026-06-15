<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HrPolicySetting;
use App\Models\HrShift;
use App\Models\PayrollTaxTable;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class HrConfigurationController extends Controller
{
    public function index()
    {
        return view('hr.configuration.index', [
            'policies' => HrPolicySetting::orderBy('key')->get(),
            'shifts' => HrShift::orderBy('name')->get(),
            'taxTables' => PayrollTaxTable::with('bands')->orderByDesc('effective_from')->get(),
        ]);
    }

    public function storeShift(Request $request, ActivityLogService $log)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'], 'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'], 'grace_minutes' => ['required', 'integer', 'min:0'],
            'break_minutes' => ['required', 'integer', 'min:0'], 'is_night_shift' => ['nullable', 'boolean'],
        ]);
        $shift = HrShift::create($data + ['is_night_shift' => $request->boolean('is_night_shift'), 'is_active' => true]);
        $log->log('SYSTEM', 'HR_SHIFT_CREATED', ['source_type' => 'hr_shift', 'source_id' => $shift->id], $shift);
        return back()->with('success', __('hr.shift_created'));
    }

    public function updatePolicy(Request $request, HrPolicySetting $policy, ActivityLogService $log)
    {
        $data = $request->validate(['value' => ['nullable', 'string', 'max:5000']]);
        $old = $policy->getAttributes();
        $policy->update($data + ['updated_by' => $request->user()->id]);
        $log->logUpdated($policy, 'SYSTEM', $old, $policy->getAttributes(), null, ['setting_key' => $policy->key]);
        return back()->with('success', __('hr.policy_updated'));
    }
}
