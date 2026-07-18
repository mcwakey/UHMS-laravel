<?php

namespace App\Http\Controllers\Investigations;

use App\Http\Controllers\Controller;
use App\Services\Dashboards\InvestigationsDashboardService;

class DashboardController extends Controller
{
    public function __invoke(InvestigationsDashboardService $dashboard)
    {
        return view('dashboards.investigations', $dashboard->build());
    }
}
