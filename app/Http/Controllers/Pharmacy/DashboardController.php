<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Services\Dashboards\PharmacistDashboardService;

class DashboardController extends Controller
{
    public function __invoke(PharmacistDashboardService $dashboard)
    {
        return view('dashboards.pharmacist', $dashboard->build());
    }
}
