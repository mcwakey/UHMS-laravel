<?php

namespace App\Http\Controllers\Nursing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Nursing\Concerns\ResolvesNursingDepartment;
use App\Services\Nursing\NursingOpdService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use ResolvesNursingDepartment;

    public function index(Request $request, NursingOpdService $opd)
    {
        $department = $this->nursingDepartment($request);
        $metrics = $opd->metrics($department);
        $daily = $opd->query($department)->whereDate('visit_date', '>=', today()->subDays(6))
            ->selectRaw('DATE(visit_date) as day, COUNT(*) as total')
            ->groupByRaw('DATE(visit_date)')->orderBy('day')->get();

        return view('nursing.reports.index', compact('department', 'metrics', 'daily'));
    }
}
