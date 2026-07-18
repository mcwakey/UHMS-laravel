<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Dashboards\FinanceDashboardService;

class DashboardController extends Controller
{
    public function __invoke(FinanceDashboardService $dashboard)
    {
        return view('dashboards.finance', $dashboard->build());
    }
}
