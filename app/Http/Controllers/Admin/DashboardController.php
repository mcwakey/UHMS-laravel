<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;
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
        $recentVisits = Visit::with(['patient', 'department', 'activeConsultationRoute.doctor', 'pendingConsultationRoutes.doctor'])
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

        // Low stock items — products where total on-hand qty is at or below reorder level
        $lowStockItems = DB::table('products as p')
            ->join('product_stock_balances as psb', 'p.id', '=', 'psb.product_id')
            ->select('p.id', 'p.name', 'p.reorder_level', DB::raw('SUM(psb.quantity_on_hand) as total_qty'))
            ->whereNull('p.deleted_at')
            ->groupBy('p.id', 'p.name', 'p.reorder_level')
            ->havingRaw('SUM(psb.quantity_on_hand) > 0')
            ->havingRaw('SUM(psb.quantity_on_hand) <= p.reorder_level')
            ->orderByRaw('SUM(psb.quantity_on_hand) - p.reorder_level ASC')
            ->take(5)
            ->get();

        // Expired stock — batches in stock_movements with expiry_date in the past
        $expiredStockItems = DB::table('stock_movements as sm')
            ->join('products as p', 'sm.product_id', '=', 'p.id')
            ->whereNotNull('sm.expiry_date')
            ->whereDate('sm.expiry_date', '<', today())
            ->where('sm.direction', 'in')
            ->where('sm.quantity', '>', 0)
            ->whereNull('p.deleted_at')
            ->select('p.name', 'sm.quantity', 'sm.expiry_date', 'sm.batch_no')
            ->orderBy('sm.expiry_date', 'asc')
            ->take(5)
            ->get()
            ->map(function ($item) {
                $item->expiry_date = $item->expiry_date
                    ? \Carbon\Carbon::parse($item->expiry_date)
                    : null;
                return $item;
            });

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
