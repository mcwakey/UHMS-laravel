<?php

namespace App\Http\Controllers\Admin\FrontDesk;

use App\Http\Controllers\Controller;
use App\Services\FrontDesk\FrontDeskDashboardService;
use Illuminate\Http\Request;

class FrontDeskDashboardController extends Controller
{
    public function __construct(private FrontDeskDashboardService $dashboard) {}

    public function index(Request $request)
    {
        return view('admin.front-desk.index', [
            'metrics' => $this->dashboard->metrics($request->user()),
            'overdueHours' => (int) config('front_desk.visitor_overdue_hours', 4),
        ]);
    }
}
