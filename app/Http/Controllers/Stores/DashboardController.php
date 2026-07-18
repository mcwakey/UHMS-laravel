<?php

namespace App\Http\Controllers\Stores;

use App\Http\Controllers\Controller;
use App\Services\Dashboards\StoresDashboardService;

class DashboardController extends Controller
{
    public function __invoke(StoresDashboardService $dashboard)
    {
        return view('dashboards.stores', $dashboard->build());
    }
}
