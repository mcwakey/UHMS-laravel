<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\DrugStock;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\ReportService;
use App\Services\VisitService;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index(VisitService $visitService, ReportService $reportService)
    {
        $totalUsers    = User::count();
        $activeUsers   = User::where('status', 'active')->count();
        $totalDepartments = Department::where('status', 'active')->count();
        $totalRoles    = Role::count();
        $totalPatients = Patient::count();
        $recentPatients = Patient::with('registeredBy')->latest()->take(5)->get();

        $visitStats  = $visitService->todayStats();
        $recentVisits = Visit::with(['patient', 'department', 'assignedDoctor'])
            ->today()
            ->latest()
            ->take(8)
            ->get();

        // Today's appointments
        $todayAppointments = Appointment::with(['patient', 'doctor'])
            ->today()
            ->latest('appointment_date')
            ->take(5)
            ->get();

        // Enhanced dashboard stats
        $dashboardStats = $reportService->adminDashboardStats();
        $revenueTrend   = $reportService->revenueTrend(7);
        $visitTrend     = $reportService->visitTrend(7);
        $departmentLoad = $reportService->departmentLoad();

        // Low stock items
        $lowStockItems = DrugStock::with('drug')
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->where('quantity', '>', 0)
            ->orderByRaw('quantity - reorder_level ASC')
            ->take(5)
            ->get();

        // Expired stock
        $expiredStockItems = DrugStock::with('drug')
            ->whereDate('expiry_date', '<', today())
            ->where('quantity', '>', 0)
            ->take(5)
            ->get();

        return view('dashboard.admin', compact(
            'totalUsers', 'activeUsers', 'totalDepartments', 'totalRoles',
            'totalPatients', 'recentPatients',
            'visitStats', 'recentVisits',
            'todayAppointments',
            'dashboardStats', 'revenueTrend', 'visitTrend',
            'departmentLoad', 'lowStockItems', 'expiredStockItems'
        ));
    }
}
