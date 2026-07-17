<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboards\DoctorDashboardService;
use App\Services\Dashboards\NurseDashboardService;
use App\Services\Dashboards\PharmacistDashboardService;
use App\Services\Dashboards\ReceptionistDashboardService;
use Illuminate\Http\Request;

/**
 * Modern role dashboards (Receptionist / Doctor / Nurse / Pharmacist) in the
 * Preclinic design language. Read-only pages: every widget is an aggregate
 * query, no state is mutated and no activity is logged.
 *
 * Access: the matching role (plus closely-related clinical roles) or any
 * admin-level user; everyone else gets 403 from the backend, not just the UI.
 */
class RoleDashboardController extends Controller
{
    private const ADMIN_ROLES = ['Super Admin', 'Admin'];

    /** Land each user on the dashboard that matches their primary role. */
    public function index(Request $request)
    {
        $user = $request->user();

        return match (true) {
            $user->hasRole(['Doctor', 'Consultant', 'Specialist', 'Physician Assistant']) => redirect()->route('admin.dashboards.doctor'),
            $user->hasRole(['Nurse', 'Ward Nurse', 'Emergency Nurse', 'Triage Nurse', 'Theatre Nurse']) => redirect()->route('admin.dashboards.nurse'),
            $user->hasRole('Pharmacist') => redirect()->route('admin.dashboards.pharmacist'),
            $user->hasRole(['Receptionist', 'Medical Records Officer']) => redirect()->route('admin.dashboards.receptionist'),
            default => redirect()->route('admin.dashboards.receptionist'),
        };
    }

    public function receptionist(Request $request, ReceptionistDashboardService $service)
    {
        $this->authorizeRoles($request, ['Receptionist', 'Medical Records Officer', 'Cashier']);

        return view('dashboards.records', $service->build());
    }

    public function doctor(Request $request, DoctorDashboardService $service)
    {
        abort_unless(
            $request->user()->isConsultationUser() || $request->user()->hasRole(self::ADMIN_ROLES),
            403
        );

        $doctor = $service->resolveDoctor($request->user(), $request->integer('doctor') ?: null);

        return view('dashboards.doctor', $service->build($doctor));
    }

    public function nurse(Request $request, NurseDashboardService $service)
    {
        $this->authorizeRoles($request, ['Nurse', 'Ward Nurse', 'Emergency Nurse', 'Triage Nurse', 'Theatre Nurse']);

        return view('dashboards.nurse', $service->build());
    }

    public function pharmacist(Request $request, PharmacistDashboardService $service)
    {
        $this->authorizeRoles($request, ['Pharmacist']);

        return view('dashboards.pharmacist', $service->build());
    }

    /** @param array<int, string> $roles */
    private function authorizeRoles(Request $request, array $roles): void
    {
        abort_unless($request->user()->hasRole(array_merge($roles, self::ADMIN_ROLES)), 403);
    }
}
