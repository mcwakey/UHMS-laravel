<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\VisitStatus;
use App\Models\Department;
use App\Models\DrugStock;
use App\Models\Invoice;
use App\Models\LabRequest;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Income report data.
     */
    public function incomeReport(array $filters = []): array
    {
        $query = Payment::query();

        if (!empty($filters['date_from'])) {
            $query->whereDate('paid_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('paid_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        $payments = $query->with(['invoice.items.serviceCatalog', 'patient', 'receivedBy'])
            ->latest('paid_at')
            ->paginate(20)
            ->withQueryString();

        // Category breakdown
        $categoryBreakdown = Invoice::join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->leftJoin('service_catalog', 'invoice_items.service_catalog_id', '=', 'service_catalog.id')
            ->whereIn('invoices.status', [InvoiceStatus::PAID->value, InvoiceStatus::PARTIALLY_PAID->value]);

        if (!empty($filters['date_from'])) {
            $categoryBreakdown->whereDate('invoices.created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $categoryBreakdown->whereDate('invoices.created_at', '<=', $filters['date_to']);
        }

        $categoryBreakdown = $categoryBreakdown
            ->select('service_catalog.category', DB::raw('SUM(invoice_items.total_price) as total'))
            ->groupBy('service_catalog.category')
            ->pluck('total', 'category')
            ->toArray();

        // Summary stats
        $totalQuery = Payment::query();
        if (!empty($filters['date_from'])) {
            $totalQuery->whereDate('paid_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $totalQuery->whereDate('paid_at', '<=', $filters['date_to']);
        }

        $stats = [
            'total_income' => (clone $totalQuery)->sum('amount'),
            'consultation_fees' => $categoryBreakdown['consultation'] ?? 0,
            'lab_revenue' => $categoryBreakdown['lab'] ?? 0,
            'pharmacy_sales' => $categoryBreakdown['pharmacy'] ?? 0,
            'nhis_revenue' => (clone $totalQuery)->where('payment_method', 'nhis')->sum('amount'),
        ];

        return compact('payments', 'stats', 'categoryBreakdown');
    }

    /**
     * Patient report data.
     */
    public function patientReport(array $filters = []): array
    {
        $query = Patient::withCount(['visits', 'invoices']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('patient_number', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        $patients = $query->latest()->paginate(20)->withQueryString();

        // Stats
        $stats = [
            'total_patients' => Patient::count(),
            'new_this_month' => Patient::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)->count(),
            'male_patients' => Patient::where('gender', 'male')->count(),
            'female_patients' => Patient::where('gender', 'female')->count(),
            'nhis_active' => Patient::whereNotNull('nhis_number')
                ->where('nhis_expiry_date', '>', now())->count(),
        ];

        // Monthly registration trend (last 6 months)
        $registrationTrend = Patient::select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

        return compact('patients', 'stats', 'registrationTrend');
    }

    /**
     * Visit / appointment report data.
     */
    public function visitReport(array $filters = []): array
    {
        $query = Visit::with(['patient', 'department', 'assignedDoctor']);

        if (!empty($filters['date_from'])) {
            $query->whereDate('visit_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('visit_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        $visits = $query->latest('visit_date')->paginate(20)->withQueryString();

        // Stats
        $baseQuery = Visit::query();
        if (!empty($filters['date_from'])) {
            $baseQuery->whereDate('visit_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $baseQuery->whereDate('visit_date', '<=', $filters['date_to']);
        }

        $stats = [
            'total_visits' => (clone $baseQuery)->count(),
            'completed' => (clone $baseQuery)->where('status', VisitStatus::COMPLETED->value)->count(),
            'cancelled' => (clone $baseQuery)->where('status', VisitStatus::CANCELLED->value)->count(),
            'in_progress' => (clone $baseQuery)->whereNotIn('status', [
                VisitStatus::COMPLETED->value, VisitStatus::CANCELLED->value,
            ])->count(),
        ];

        // Department load
        $departmentLoad = Visit::join('departments', 'visits.department_id', '=', 'departments.id')
            ->select('departments.name', DB::raw('COUNT(*) as count'));
        if (!empty($filters['date_from'])) {
            $departmentLoad->whereDate('visit_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $departmentLoad->whereDate('visit_date', '<=', $filters['date_to']);
        }
        $departmentLoad = $departmentLoad->groupBy('departments.name')
            ->orderByDesc('count')
            ->pluck('count', 'name')
            ->toArray();

        // Daily trend (last 30 days)
        $dailyTrend = Visit::select(
                DB::raw("DATE_FORMAT(visit_date, '%Y-%m-%d') as day"),
                DB::raw('COUNT(*) as count')
            )
            ->where('visit_date', '>=', now()->subDays(30))
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('count', 'day')
            ->toArray();

        return compact('visits', 'stats', 'departmentLoad', 'dailyTrend');
    }

    /**
     * NHIS report data.
     */
    public function nhisReport(array $filters = []): array
    {
        $query = Invoice::with(['patient', 'visit.department', 'items'])
            ->where(function ($q) {
                $q->where('billing_type', 'nhis')
                    ->orWhere('billing_type', 'mixed');
            });

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $invoices = $query->latest()->paginate(20)->withQueryString();

        // Stats
        $baseQuery = Invoice::where(function ($q) {
            $q->where('billing_type', 'nhis')->orWhere('billing_type', 'mixed');
        });
        if (!empty($filters['date_from'])) {
            $baseQuery->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $baseQuery->whereDate('created_at', '<=', $filters['date_to']);
        }

        $stats = [
            'total_claims' => (clone $baseQuery)->count(),
            'total_nhis_amount' => (clone $baseQuery)->sum('nhis_amount'),
            'approved_claims' => (clone $baseQuery)->whereIn('status', [
                InvoiceStatus::PAID->value, InvoiceStatus::PARTIALLY_PAID->value,
            ])->count(),
            'pending_claims' => (clone $baseQuery)->whereIn('status', [
                InvoiceStatus::PENDING->value, InvoiceStatus::DRAFT->value,
            ])->count(),
            'nhis_patients' => Patient::whereNotNull('nhis_number')
                ->where('nhis_expiry_date', '>', now())->count(),
        ];

        return compact('invoices', 'stats');
    }

    /**
     * Dashboard stats for admin.
     */
    public function adminDashboardStats(): array
    {
        return [
            'total_patients' => Patient::count(),
            'today_visits' => Visit::today()->count(),
            'month_revenue' => Payment::whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)->sum('amount'),
            'pending_lab' => LabRequest::where('status', 'pending')->count(),
            'active_doctors' => \App\Models\User::role('Doctor')
                ->where('status', 'active')->count(),
            'outstanding_balance' => Invoice::unpaid()->sum('balance'),
            'low_stock_alerts' => DrugStock::whereColumn('quantity', '<=', 'reorder_level')
                ->where('quantity', '>', 0)->count(),
            'today_revenue' => Payment::whereDate('paid_at', today())->sum('amount'),
        ];
    }

    /**
     * Revenue trend (last 7 days).
     */
    public function revenueTrend(int $days = 7): array
    {
        return Payment::select(
                DB::raw("DATE_FORMAT(paid_at, '%Y-%m-%d') as day"),
                DB::raw('SUM(amount) as total')
            )
            ->where('paid_at', '>=', now()->subDays($days))
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day')
            ->toArray();
    }

    /**
     * Visit trend (last 7 days).
     */
    public function visitTrend(int $days = 7): array
    {
        return Visit::select(
                DB::raw("DATE_FORMAT(visit_date, '%Y-%m-%d') as day"),
                DB::raw('COUNT(*) as count')
            )
            ->where('visit_date', '>=', now()->subDays($days))
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('count', 'day')
            ->toArray();
    }

    /**
     * Department load for dashboard.
     */
    public function departmentLoad(): array
    {
        return Visit::join('departments', 'visits.department_id', '=', 'departments.id')
            ->select('departments.name', DB::raw('COUNT(*) as count'))
            ->today()
            ->groupBy('departments.name')
            ->orderByDesc('count')
            ->pluck('count', 'name')
            ->toArray();
    }
}
