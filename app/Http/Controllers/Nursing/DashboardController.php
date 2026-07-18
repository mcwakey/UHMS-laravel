<?php

namespace App\Http\Controllers\Nursing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Nursing\Concerns\ResolvesNursingDepartment;
use App\Services\Dashboards\NurseDashboardService;
use App\Services\Nursing\NursingOpdService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ResolvesNursingDepartment;

    public function __invoke(Request $request, NurseDashboardService $dashboard, NursingOpdService $opd)
    {
        $department = $this->nursingDepartment($request);

        return view('dashboards.nurse', array_merge($dashboard->build($department), [
            'nursingOpd' => true,
            'department' => $department,
            'metrics' => $opd->metrics($department),
        ]));
    }
}
