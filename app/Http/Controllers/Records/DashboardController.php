<?php

namespace App\Http\Controllers\Records;

use App\Http\Controllers\Controller;
use App\Services\Dashboards\ReceptionistDashboardService;

class DashboardController extends Controller
{
    public function __invoke(ReceptionistDashboardService $service)
    {
        return view('dashboards.records', $service->build());
    }
}
