<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Enums\LogModule;
use App\Services\ActivityLogService;
use App\Services\Department\DepartmentContextSwitcherService;
use Illuminate\Http\Request;

class DepartmentContextController extends Controller
{
    public function __construct(
        private DepartmentContextSwitcherService $switcher,
        private ActivityLogService $activityLog,
    ) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_id' => ['required', 'integer'],
        ]);

        $department = $this->switcher->switch($request->user(), (int) $validated['department_id'], $request);

        if (! $department) {
            return back()->with('error', __('dashboards.department.invalid_department_context'));
        }

        $this->activityLog->log(LogModule::SYSTEM, 'DEPARTMENT_CONTEXT_SWITCHED', [
            'department_id' => $department->id,
        ], $department);

        return back()->with('success', __('dashboards.department.department_context_switched', [
            'department' => $department->name,
        ]));
    }

    public function destroy(Request $request)
    {
        $this->switcher->clear($request);

        $this->activityLog->log(LogModule::SYSTEM, 'DEPARTMENT_CONTEXT_CLEARED');

        return back()->with('success', __('dashboards.department.department_context_cleared'));
    }
}
