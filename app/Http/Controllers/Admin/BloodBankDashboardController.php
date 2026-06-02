<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BloodBankDashboardService;

class BloodBankDashboardController extends Controller
{
    public function __construct(private BloodBankDashboardService $dashboard) {}

    public function index()
    {
        return view('blood-bank.dashboard', $this->dashboard->dashboard());
    }
}
