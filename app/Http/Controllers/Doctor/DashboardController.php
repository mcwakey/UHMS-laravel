<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Enums\VisitStatus;
use App\Models\LabRequest;
use App\Models\Prescription;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $doctor = Auth::user();
        $doctorId = $doctor->id;
        $doctorRouteScope = function ($query) use ($doctorId) {
            $query->where('doctor_id', $doctorId)
                ->whereIn('status', [
                    VisitConsultationRoute::STATUS_PENDING,
                    VisitConsultationRoute::STATUS_ACTIVE,
                ]);
        };

        // Today's assigned patients
        $todayVisits = Visit::with(['patient', 'department', 'latestQueue', 'activeConsultationRoute.doctor', 'pendingConsultationRoutes.doctor'])
            ->whereHas('consultationRoutes', $doctorRouteScope)
            ->today()
            ->latest('visit_date')
            ->get();

        // Stats
        $stats = [
            'total_today' => $todayVisits->count(),
            'waiting' => $todayVisits->where('status', VisitStatus::QUEUED)->count(),
            'consulting' => $todayVisits->where('status', VisitStatus::CONSULTING)->count(),
            'completed_today' => $todayVisits->where('status', VisitStatus::COMPLETED)->count(),
            'pending_lab' => LabRequest::whereHas('visit', function ($q) use ($doctorId) {
                $q->whereHas('consultationRoutes', fn ($route) => $route->where('doctor_id', $doctorId));
            })->where('status', 'pending')->count(),
            'pending_prescriptions' => \App\Models\Prescription::whereHas('visit', function ($q) use ($doctorId) {
                $q->whereHas('consultationRoutes', fn ($route) => $route->where('doctor_id', $doctorId));
            })->where('status', 'pending')->count(),
        ];

        // Upcoming visits this week
        $upcomingVisits = Visit::with(['patient', 'department', 'activeConsultationRoute.doctor', 'pendingConsultationRoutes.doctor'])
            ->whereHas('consultationRoutes', $doctorRouteScope)
            ->where('visit_date', '>', today())
            ->where('visit_date', '<=', now()->endOfWeek())
            ->orderBy('visit_date')
            ->take(10)
            ->get();

        // Recent completed
        $recentCompleted = Visit::with(['patient', 'department', 'activeConsultationRoute.doctor', 'pendingConsultationRoutes.doctor'])
            ->whereHas('consultationRoutes', fn ($query) => $query->where('doctor_id', $doctorId))
            ->where('status', VisitStatus::COMPLETED->value)
            ->latest('visit_date')
            ->take(5)
            ->get();

        return view('dashboard.doctor', compact(
            'todayVisits', 'stats', 'upcomingVisits', 'recentCompleted'
        ));
    }
}
