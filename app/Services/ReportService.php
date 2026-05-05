<?php

namespace App\Services;

use App\Enums\AdmissionStatus;
use App\Enums\BillingType;
use App\Enums\ClaimStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\VisitStatus;
use App\Enums\BedStatus;
use App\Enums\PrescriptionStatus;
use App\Models\Admission;
use App\Models\Appointment;
use App\Models\Bed;
use App\Models\Claim;
use App\Models\DispensingRecord;
use App\Models\DrugStock;
use App\Models\InsuranceProvider;
use App\Models\Invoice;
use App\Models\Prescription;
use App\Models\LabRequest;
use App\Models\LeaveRequest;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PayrollRecord;
use App\Models\Visit;
use App\Models\Ward;
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
            'insurance_revenue' => (clone $totalQuery)->whereIn('payment_method', [PaymentMethod::INSURANCE->value, 'nhis'])->sum('amount'),
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
        $query = Visit::with(['patient', 'assignedDoctor']);

        if (!empty($filters['date_from'])) {
            $query->whereDate('visit_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('visit_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
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

        $departmentLoad = [];

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
     * Insurance claims report data.
     */
    public function insuranceClaimsReport(array $filters = []): array
    {
        $insuranceBillingTypes = [
            BillingType::INSURANCE->value,
            BillingType::CORPORATE->value,
            BillingType::MIXED->value,
            'nhis',
        ];

        $query = Invoice::with(['patient', 'items'])
            ->whereIn('billing_type', $insuranceBillingTypes);

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
        $baseQuery = Invoice::whereIn('billing_type', $insuranceBillingTypes);
        if (!empty($filters['date_from'])) {
            $baseQuery->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $baseQuery->whereDate('created_at', '<=', $filters['date_to']);
        }

        $stats = [
            'total_claims' => (clone $baseQuery)->count(),
            'total_insurance_amount' => (clone $baseQuery)->sum('nhis_amount'),
            'total_nhis_amount' => (clone $baseQuery)->sum('nhis_amount'),
            'approved_claims' => (clone $baseQuery)->whereIn('status', [
                InvoiceStatus::PAID->value, InvoiceStatus::PARTIALLY_PAID->value,
            ])->count(),
            'pending_claims' => (clone $baseQuery)->whereIn('status', [
                InvoiceStatus::PENDING->value, InvoiceStatus::DRAFT->value,
            ])->count(),
        ];

        return compact('invoices', 'stats');
    }

    public function nhisReport(array $filters = []): array
    {
        return $this->insuranceClaimsReport($filters);
    }

    /**
     * Dashboard stats for admin.
     */
    public function adminDashboardStats(): array
    {
        return [
            'total_patients'        => Patient::count(),
            'today_visits'          => Visit::today()->count(),
            'month_revenue'         => Payment::whereMonth('paid_at', now()->month)
                                        ->whereYear('paid_at', now()->year)->sum('amount'),
            'today_revenue'         => Payment::whereDate('paid_at', today())->sum('amount'),
            'outstanding_balance'   => Invoice::unpaid()->sum('balance'),
            'active_doctors'        => \App\Models\User::role('Doctor')
                                        ->where('status', 'active')->count(),
            'pending_lab'           => LabRequest::where('status', 'pending')->count(),
            'low_stock_alerts'      => DrugStock::whereColumn('quantity', '<=', 'reorder_level')
                                        ->where('quantity', '>', 0)->count(),
            'today_appointments'    => Appointment::today()->count(),
            'today_admissions'      => Admission::whereDate('created_at', today())->count(),
            'active_admissions'     => Admission::where('status', AdmissionStatus::ADMITTED)->count(),
            'occupied_beds'         => Bed::where('status', BedStatus::OCCUPIED)->count(),
            'pending_prescriptions' => Prescription::where('status', PrescriptionStatus::PENDING)->count(),
            'pending_claims'        => Claim::whereIn('status', [
                                        ClaimStatus::SUBMITTED->value,
                                        ClaimStatus::UNDER_REVIEW->value,
                                       ])->count(),
            'expired_stock_count'   => DrugStock::whereDate('expiry_date', '<', today())
                                        ->where('quantity', '>', 0)->count(),
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
        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | Phase 20 — Advanced Reports
    |--------------------------------------------------------------------------
    */

    /**
     * Pharmacy sales (detailed) report.
     */
    public function pharmacySalesReport(array $filters = []): array
    {
        $query = DispensingRecord::with(['prescriptionItem.drug', 'drugStock', 'patient', 'dispensedBy']);

        if (!empty($filters['date_from'])) {
            $query->whereDate('dispensed_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('dispensed_at', '<=', $filters['date_to']);
        }

        $records = $query->latest('dispensed_at')->paginate(25)->withQueryString();

        // Stats
        $baseQuery = DispensingRecord::query();
        if (!empty($filters['date_from'])) {
            $baseQuery->whereDate('dispensed_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $baseQuery->whereDate('dispensed_at', '<=', $filters['date_to']);
        }

        $totalRevenue = (clone $baseQuery)
            ->join('drug_stock', 'dispensing_records.drug_stock_id', '=', 'drug_stock.id')
            ->selectRaw('SUM(dispensing_records.quantity_dispensed * drug_stock.selling_price) as total')
            ->value('total') ?? 0;

        $stats = [
            'total_dispensed' => (clone $baseQuery)->count(),
            'total_revenue' => $totalRevenue,
            'items_dispensed' => (clone $baseQuery)->sum('quantity_dispensed'),
            'unique_patients' => (clone $baseQuery)->distinct('patient_id')->count('patient_id'),
        ];

        return compact('records', 'stats');
    }

    /**
     * Pharmacy sales summary (aggregated by drug).
     */
    public function pharmacySalesSummaryReport(array $filters = []): array
    {
        $query = DispensingRecord::join('drug_stock', 'dispensing_records.drug_stock_id', '=', 'drug_stock.id')
            ->join('drugs', 'drug_stock.drug_id', '=', 'drugs.id')
            ->select(
                'drugs.id',
                'drugs.name as drug_name',
                'drugs.generic_name',
                DB::raw('SUM(dispensing_records.quantity_dispensed) as total_quantity'),
                DB::raw('SUM(dispensing_records.quantity_dispensed * drug_stock.selling_price) as total_revenue'),
                DB::raw('COUNT(DISTINCT dispensing_records.patient_id) as patient_count')
            );

        if (!empty($filters['date_from'])) {
            $query->whereDate('dispensing_records.dispensed_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('dispensing_records.dispensed_at', '<=', $filters['date_to']);
        }

        $summary = $query->groupBy('drugs.id', 'drugs.name', 'drugs.generic_name')
            ->orderByDesc('total_revenue')
            ->paginate(25)
            ->withQueryString();

        return compact('summary');
    }

    /**
     * Investigation (lab) revenue report.
     */
    public function investigationRevenueReport(array $filters = []): array
    {
        $query = LabRequest::with(['patient', 'department', 'items.labTest'])
            ->where('status', 'completed');

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        $requests = $query->latest()->paginate(25)->withQueryString();

        // Revenue by department
        $departmentRevenue = LabRequest::join('lab_request_items', 'lab_requests.id', '=', 'lab_request_items.lab_request_id')
            ->join('lab_tests', 'lab_request_items.lab_test_id', '=', 'lab_tests.id')
            ->join('departments', 'lab_requests.department_id', '=', 'departments.id')
            ->where('lab_requests.status', 'completed');

        if (!empty($filters['date_from'])) {
            $departmentRevenue->whereDate('lab_requests.created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $departmentRevenue->whereDate('lab_requests.created_at', '<=', $filters['date_to']);
        }

        $departmentRevenue = $departmentRevenue
            ->select('departments.name', DB::raw('SUM(lab_tests.price) as total_revenue'), DB::raw('COUNT(*) as test_count'))
            ->groupBy('departments.name')
            ->orderByDesc('total_revenue')
            ->get();

        $totalRevenue = $departmentRevenue->sum('total_revenue');

        $stats = [
            'total_revenue' => $totalRevenue,
            'total_requests' => LabRequest::where('status', 'completed')->count(),
            'departments' => $departmentRevenue->count(),
        ];

        return compact('requests', 'departmentRevenue', 'stats');
    }

    /**
     * Consultation statistics report.
     */
    public function consultationStatsReport(array $filters = []): array
    {
        $query = MedicalRecord::with(['patient', 'doctor', 'visit', 'diagnoses']);

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['doctor_id'])) {
            $query->where('doctor_id', $filters['doctor_id']);
        }

        $records = $query->latest()->paginate(25)->withQueryString();

        // By doctor
        $byDoctor = MedicalRecord::join('users', 'medical_records.doctor_id', '=', 'users.id')
            ->select(
                'users.id',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as doctor_name"),
                DB::raw('COUNT(*) as consultation_count')
            );

        if (!empty($filters['date_from'])) {
            $byDoctor->whereDate('medical_records.created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $byDoctor->whereDate('medical_records.created_at', '<=', $filters['date_to']);
        }

        $byDoctor = $byDoctor->groupBy('users.id', 'users.first_name', 'users.last_name')
            ->orderByDesc('consultation_count')
            ->get();

        $stats = [
            'total_consultations' => $byDoctor->sum('consultation_count'),
            'active_doctors' => $byDoctor->count(),
            'avg_per_doctor' => $byDoctor->count() > 0 ? round($byDoctor->sum('consultation_count') / $byDoctor->count()) : 0,
        ];

        return compact('records', 'byDoctor', 'stats');
    }

    /**
     * Daily collection report.
     */
    public function dailyCollectionReport(array $filters = []): array
    {
        $date = $filters['date'] ?? today()->format('Y-m-d');
        $paymentMethod = $filters['payment_method'] ?? null;

        $baseQuery = Payment::query()->whereDate('paid_at', $date);

        if (!empty($paymentMethod)) {
            $baseQuery->where('payment_method', $paymentMethod);
        }

        $paymentsQuery = (clone $baseQuery)
            ->with(['invoice.patient', 'receivedBy'])
            ->latest('paid_at');

        $payments = !empty($filters['export'])
            ? $paymentsQuery->get()
            : $paymentsQuery->paginate(25)->withQueryString();

        $byMethod = (clone $baseQuery)
            ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get()
            ->map(function ($method) {
                $methodValue = $method->getRawOriginal('payment_method')
                    ?? ($method->payment_method instanceof PaymentMethod ? $method->payment_method->value : $method->payment_method);

                $method->payment_method = $methodValue;
                $method->payment_method_label = PaymentMethod::tryFrom((string) $methodValue)?->label()
                    ?? ucwords(str_replace('_', ' ', (string) $methodValue));
                $method->total = (float) $method->total;
                $method->count = (int) $method->count;

                return $method;
            });

        $stats = [
            'total_collected' => $byMethod->sum('total'),
            'total_transactions' => $byMethod->sum('count'),
            'transaction_count' => $byMethod->sum('count'),
            'methods' => $byMethod->count(),
            'cash' => $byMethod->where('payment_method', PaymentMethod::CASH->value)->first()?->total ?? 0,
            'momo' => $byMethod
                ->whereIn('payment_method', [
                    PaymentMethod::MTN_MOMO->value,
                    PaymentMethod::VODAFONE_CASH->value,
                    PaymentMethod::AIRTELTIGO_MONEY->value,
                ])
                ->sum('total'),
        ];

        return compact('payments', 'byMethod', 'stats', 'date');
    }

    /**
     * Admissions report.
     */
    public function admissionsReport(array $filters = []): array
    {
        $query = Admission::with(['patient', 'bed.ward', 'admittedBy']);

        if (!empty($filters['date_from'])) {
            $query->whereDate('admission_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('admission_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['ward_id'])) {
            $query->whereHas('bed', fn ($q) => $q->where('ward_id', $filters['ward_id']));
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $admissions = $query->latest('admission_date')->paginate(25)->withQueryString();

        // Stats
        $baseQ = Admission::query();
        if (!empty($filters['date_from'])) {
            $baseQ->whereDate('admission_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $baseQ->whereDate('admission_date', '<=', $filters['date_to']);
        }

        $stats = [
            'total' => (clone $baseQ)->count(),
            'active' => (clone $baseQ)->where('status', AdmissionStatus::ADMITTED)->count(),
            'discharged' => (clone $baseQ)->where('status', AdmissionStatus::DISCHARGED)->count(),
        ];

        $wards = Ward::orderBy('name')->get();

        return compact('admissions', 'stats', 'wards');
    }

    /**
     * Discharges report.
     */
    public function dischargesReport(array $filters = []): array
    {
        $query = Admission::with(['patient', 'bed.ward', 'dischargedBy'])
            ->where('status', AdmissionStatus::DISCHARGED)
            ->whereNotNull('actual_discharge_date');

        if (!empty($filters['date_from'])) {
            $query->whereDate('actual_discharge_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('actual_discharge_date', '<=', $filters['date_to']);
        }

        $discharges = $query->latest('actual_discharge_date')->paginate(25)->withQueryString();

        // Average length of stay
        $avgLos = Admission::where('status', AdmissionStatus::DISCHARGED)
            ->whereNotNull('actual_discharge_date')
            ->selectRaw('AVG(DATEDIFF(actual_discharge_date, admission_date)) as avg_days')
            ->value('avg_days');

        $stats = [
            'total_discharged' => $discharges->total(),
            'avg_length_of_stay' => round($avgLos ?? 0, 1),
        ];

        return compact('discharges', 'stats');
    }

    /**
     * Leave report.
     */
    public function leaveReport(array $filters = []): array
    {
        $query = LeaveRequest::with(['employee.user', 'employee.department', 'approvedByUser']);

        if (!empty($filters['date_from'])) {
            $query->whereDate('start_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('start_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['leave_type'])) {
            $query->where('leave_type', $filters['leave_type']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $leaves = $query->latest('start_date')->paginate(25)->withQueryString();

        $stats = [
            'total_requests' => $leaves->total(),
            'total_days' => LeaveRequest::sum('days'),
            'approved' => LeaveRequest::where('status', 'approved')->count(),
            'pending' => LeaveRequest::where('status', 'pending')->count(),
        ];

        return compact('leaves', 'stats');
    }

    /**
     * Payroll summary report.
     */
    public function payrollReport(array $filters = []): array
    {
        $query = PayrollRecord::with(['employee.user', 'employee.department']);

        if (!empty($filters['pay_period'])) {
            $query->where('pay_period', $filters['pay_period']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $records = $query->latest('pay_period')->paginate(25)->withQueryString();

        // Summary by department
        $byDept = PayrollRecord::join('employees', 'payroll_records.employee_id', '=', 'employees.id')
            ->join('departments', 'employees.department_id', '=', 'departments.id');

        if (!empty($filters['pay_period'])) {
            $byDept->where('payroll_records.pay_period', $filters['pay_period']);
        }

        $byDept = $byDept->select(
                'departments.name',
                DB::raw('COUNT(*) as employee_count'),
                DB::raw('SUM(payroll_records.gross_pay) as total_gross'),
                DB::raw('SUM(payroll_records.net_pay) as total_net')
            )
            ->groupBy('departments.name')
            ->orderBy('departments.name')
            ->get();

        $stats = [
            'total_gross' => $records->sum('gross_pay'),
            'total_net' => $records->sum('net_pay'),
            'total_tax' => $records->sum('tax'),
            'employee_count' => $records->total(),
        ];

        return compact('records', 'byDept', 'stats');
    }

    /**
     * Claims report.
     */
    public function claimsReport(array $filters = []): array
    {
        $query = Claim::with(['insuranceProvider', 'patient', 'invoice']);

        if (!empty($filters['date_from'])) {
            $query->whereDate('claim_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('claim_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['provider_id'])) {
            $query->where('insurance_provider_id', $filters['provider_id']);
        }

        $statsQuery = clone $query;
        $claims = $query->latest('claim_date')->paginate(25)->withQueryString();

        $totalClaimed = (clone $statsQuery)->sum('total_amount');
        $totalApproved = (clone $statsQuery)->whereNotNull('approved_amount')->sum('approved_amount');

        $stats = [
            'total_claims' => (clone $statsQuery)->count(),
            'total_claimed' => $totalClaimed,
            'total_approved' => $totalApproved,
            'total_amount' => $totalClaimed,
            'approved_amount' => $totalApproved,
            'pending' => (clone $statsQuery)->whereIn('status', [
                ClaimStatus::DRAFT->value,
                ClaimStatus::SUBMITTED->value,
                ClaimStatus::UNDER_REVIEW->value,
                ClaimStatus::APPEALED->value,
            ])->count(),
        ];

        $providers = InsuranceProvider::orderBy('name')->get();

        return compact('claims', 'stats', 'providers');
    }

    /**
     * Stock valuation report.
     */
    public function stockValuationReport(array $filters = []): array
    {
        $query = DrugStock::with(['drug'])
            ->where('quantity', '>', 0);

        if (!empty($filters['location'])) {
            $query->where('location', $filters['location']);
        }

        $stocks = $query->orderBy('drug_id')->paginate(25)->withQueryString();

        $totalValue = DrugStock::where('quantity', '>', 0)
            ->selectRaw('SUM(quantity * unit_cost) as cost_value, SUM(quantity * selling_price) as sell_value')
            ->first();

        $stats = [
            'cost_value' => $totalValue->cost_value ?? 0,
            'sell_value' => $totalValue->sell_value ?? 0,
            'total_items' => DrugStock::where('quantity', '>', 0)->count(),
            'unique_drugs' => DrugStock::where('quantity', '>', 0)->distinct('drug_id')->count('drug_id'),
        ];

        return compact('stocks', 'stats');
    }

    /**
     * Expired stock report.
     */
    public function expiredStockReport(array $filters = []): array
    {
        $query = DrugStock::with(['drug']);

        $type = $filters['type'] ?? 'expired';
        if ($type === 'expiring') {
            $query->expiringSoon(90)->where('quantity', '>', 0);
        } else {
            $query->expired()->where('quantity', '>', 0);
        }

        $stocks = $query->orderBy('expiry_date')->paginate(25)->withQueryString();

        $stats = [
            'expired_count' => DrugStock::expired()->where('quantity', '>', 0)->count(),
            'expiring_soon' => DrugStock::expiringSoon(90)->where('quantity', '>', 0)->count(),
            'expired_value' => DrugStock::expired()->where('quantity', '>', 0)
                ->selectRaw('SUM(quantity * unit_cost) as total')->value('total') ?? 0,
        ];

        return compact('stocks', 'stats', 'type');
    }
}
