<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Department\DepartmentContextSwitcherService;
use Illuminate\Http\Request;

class DepartmentContextController extends Controller
{
    public function __construct(
        private DepartmentContextSwitcherService $switcher,
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

        return back()->with('success', __('dashboards.department.department_context_switched', [
            'department' => $department->name,
        ]));
    }

    public function destroy(Request $request)
    {
        $this->switcher->clear($request);

        return back()->with('success', __('dashboards.department.department_context_cleared'));
    }
}
