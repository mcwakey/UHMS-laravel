<?php

namespace App\Http\Controllers\Nursing\Concerns;

use App\Models\Department;
use App\Services\Department\DepartmentContextSwitcherService;
use Illuminate\Http\Request;

trait ResolvesNursingDepartment
{
    private function nursingDepartment(Request $request): Department
    {
        $department = app(DepartmentContextSwitcherService::class)->currentDepartment($request->user(), $request);
        abort_unless($department, 403, __('nursing.unauthorized'));

        return $department;
    }
}
