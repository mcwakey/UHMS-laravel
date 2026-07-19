<?php

namespace App\Http\Controllers\Administrative;

use App\Http\Controllers\Controller;
use App\Services\Dashboards\AdministrativeDashboardService;

class DashboardController extends Controller
{
    public function __invoke(AdministrativeDashboardService $dashboard)
    {
        return view('dashboards.administrative', $dashboard->build());
    }
}
