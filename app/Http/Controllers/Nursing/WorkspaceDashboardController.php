<?php

namespace App\Http\Controllers\Nursing;

use App\Enums\DepartmentType;
use App\Http\Controllers\Controller;
use App\Services\Dashboards\NurseDashboardService;
use App\Services\Department\DepartmentContextSwitcherService;
use Illuminate\Http\Request;

class WorkspaceDashboardController extends Controller
{
    public function __invoke(
        Request $request,
        NurseDashboardService $dashboard,
        DepartmentContextSwitcherService $departments,
    ) {
        $department = $departments->currentDepartment($request->user(), $request);
        $type = $department?->type instanceof DepartmentType
            ? $department->type
            : DepartmentType::tryFrom((string) ($department?->type ?? ''));

        abort_unless(in_array($type, [DepartmentType::INPATIENT, DepartmentType::EMERGENCY], true), 403);

        return view('dashboards.nurse', array_merge($dashboard->build($department), [
            'department' => $department,
            'workspaceType' => $type,
        ]));
    }
}
