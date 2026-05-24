<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdmissionStatus;
use App\Enums\ClaimStatus;
use App\Enums\InvoiceStatus;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Enums\PaymentMethod;
use App\Enums\PayrollStatus;
use App\Enums\VisitStatus;
use App\Exports\AdmissionsExport;
use App\Exports\ClaimsExport;
use App\Exports\ConsultationStatsExport;
use App\Exports\DischargesExport;
use App\Exports\ExpiredStockExport;
use App\Exports\InvestigationRevenueExport;
use App\Exports\LeaveExport;
use App\Exports\PayrollExport;
use App\Exports\PharmacySalesExport;
use App\Exports\PharmacySalesSummaryExport;
use App\Exports\StockValuationExport;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\LabRequest;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Services\ReportService;
use App\Services\StatementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService,
        private StatementService $statementService,
    ) {}

    /**
     * Income report.
     */
    public function income(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'payment_method']);
        $data = $this->reportService->incomeReport($filters);
        $paymentMethods = PaymentMethod::cases();

        if ($request->has('export') && $request->export === 'pdf') {
            $pdf = Pdf::loadView('reports.income-pdf', $data);

            return $pdf->download('income-report.pdf');
        }

        return view('reports.income', array_merge($data, compact('paymentMethods', 'filters')));
    }

    /**
     * Patient report.
     */
    public function patients(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'search', 'gender']);
        $data = $this->reportService->patientReport($filters);

        if ($request->has('export') && $request->export === 'pdf') {
            $pdf = Pdf::loadView('reports.patients-pdf', $data);

            return $pdf->download('patient-report.pdf');
        }

        return view('reports.patients', array_merge($data, compact('filters')));
    }

    /**
     * Visit / appointment report.
     */
    public function visits(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'status', 'department_id']);
        $data = $this->reportService->visitReport($filters);
        $departments = Department::where('status', 'active')->orderBy('name')->get();
        $visitStatuses = VisitStatus::cases();

        if ($request->has('export') && $request->export === 'pdf') {
            $pdf = Pdf::loadView('reports.visits-pdf', $data);

            return $pdf->download('visit-report.pdf');
        }

        return view('reports.visits', array_merge($data, compact('departments', 'visitStatuses', 'filters')));
    }

    /**
     * Insurance claims report.
     */
    public function insuranceClaims(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'status']);
        $data = $this->reportService->insuranceClaimsReport($filters);
        $invoiceStatuses = InvoiceStatus::cases();

        if ($request->has('export') && $request->export === 'pdf') {
            $pdf = Pdf::loadView('reports.nhis-pdf', $data);

            return $pdf->download('insurance-claims-report.pdf');
        }

        return view('reports.nhis', array_merge($data, compact('invoiceStatuses', 'filters')));
    }

    public function nhis(Request $request)
    {
        return redirect()->route('admin.reports.insurance-claims', $request->query());
    }

    /*
    |--------------------------------------------------------------------------
    | Phase 20 — Advanced Reports
    |--------------------------------------------------------------------------
    */

    /**
     * Pharmacy sales (detailed).
     */
    public function pharmacySales(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to']);
        $data = $this->reportService->pharmacySalesReport($filters);

        if ($request->export === 'excel') {
            return Excel::download(new PharmacySalesExport($filters), 'pharmacy-sales-'.now()->format('Y-m-d').'.xlsx');
        }
        if ($request->export === 'pdf') {
            $pdf = Pdf::loadView('reports.pharmacy-sales-pdf', $data);

            return $pdf->download('pharmacy-sales.pdf');
        }

        return view('reports.pharmacy-sales', array_merge($data, compact('filters')));
    }

    /**
     * Pharmacy sales summary (by drug).
     */
    public function pharmacySalesSummary(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to']);
        $data = $this->reportService->pharmacySalesSummaryReport($filters);

        if ($request->export === 'excel') {
            return Excel::download(new PharmacySalesSummaryExport($filters), 'pharmacy-summary-'.now()->format('Y-m-d').'.xlsx');
        }

        return view('reports.pharmacy-sales-summary', array_merge($data, compact('filters')));
    }

    /**
     * Investigation revenue.
     */
    public function investigationRevenue(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'department_id']);
        $data = $this->reportService->investigationRevenueReport($filters);
        $departments = Department::where('status', 'active')->orderBy('name')->get();

        if ($request->export === 'excel') {
            return Excel::download(new InvestigationRevenueExport($filters), 'investigation-revenue-'.now()->format('Y-m-d').'.xlsx');
        }

        return view('reports.investigation-revenue', array_merge($data, compact('departments', 'filters')));
    }

    /**
     * Consultation statistics.
     */
    public function consultationStats(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'doctor_id']);
        $data = $this->reportService->consultationStatsReport($filters);
        $doctors = Role::where('name', 'Doctor')->where('guard_name', 'web')->exists()
            ? User::role('Doctor')->where('status', 'active')->orderBy('first_name')->get()
            : collect();

        if ($request->export === 'excel') {
            return Excel::download(new ConsultationStatsExport($filters), 'consultation-stats-'.now()->format('Y-m-d').'.xlsx');
        }

        return view('reports.consultation-stats', array_merge($data, compact('doctors', 'filters')));
    }

    /**
     * Daily collection.
     */
    public function dailyCollection(Request $request)
    {
        $filters = $request->only(['date', 'payment_method']);

        if ($request->export === 'pdf') {
            $filters['export'] = 'pdf';
        }

        $data = $this->reportService->dailyCollectionReport($filters);

        if ($request->export === 'pdf') {
            $pdf = Pdf::loadView('reports.daily-collection-pdf', array_merge($data, compact('filters')));

            return $pdf->download('daily-collection-'.($filters['date'] ?? today()->format('Y-m-d')).'.pdf');
        }

        unset($filters['export']);

        return view('reports.daily-collection', array_merge($data, compact('filters')));
    }

    /**
     * Patient statement.
     */
    public function patientStatement(Request $request, Patient $patient)
    {
        $filters = $request->only(['date_from', 'date_to']);
        $data = $this->statementService->generate($patient, $filters);

        if ($request->export === 'pdf') {
            $pdf = Pdf::loadView('reports.patient-statement-pdf', $data);

            return $pdf->download('statement-'.$patient->patient_number.'.pdf');
        }

        return view('reports.patient-statement', array_merge($data, compact('filters')));
    }

    /**
     * Patient statement search/lookup.
     */
    public function statementSearch(Request $request)
    {
        $patients = null;
        if ($request->filled('search')) {
            $search = $request->search;
            $patients = Patient::where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('patient_number', 'like', "%{$search}%");
            })->limit(20)->get();
        }

        return view('reports.statement-search', compact('patients'));
    }

    /**
     * Admissions report.
     */
    public function admissions(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'ward_id', 'status']);
        $data = $this->reportService->admissionsReport($filters);
        $admissionStatuses = AdmissionStatus::cases();

        if ($request->export === 'excel') {
            return Excel::download(new AdmissionsExport($filters), 'admissions-'.now()->format('Y-m-d').'.xlsx');
        }

        return view('reports.admissions', array_merge($data, compact('admissionStatuses', 'filters')));
    }

    /**
     * Discharges report.
     */
    public function discharges(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'ward_id']);
        $data = $this->reportService->dischargesReport($filters);

        if ($request->export === 'excel') {
            return Excel::download(new DischargesExport($filters), 'discharges-'.now()->format('Y-m-d').'.xlsx');
        }

        return view('reports.discharges', array_merge($data, compact('filters')));
    }

    /**
     * Leave report.
     */
    public function leave(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'leave_type', 'status']);
        $data = $this->reportService->leaveReport($filters);
        $leaveTypes = LeaveType::cases();
        $leaveStatuses = LeaveStatus::cases();

        if ($request->export === 'excel') {
            return Excel::download(new LeaveExport($filters), 'leave-report-'.now()->format('Y-m-d').'.xlsx');
        }

        return view('reports.leave', array_merge($data, compact('leaveTypes', 'leaveStatuses', 'filters')));
    }

    /**
     * Payroll summary.
     */
    public function payroll(Request $request)
    {
        $filters = $request->only(['pay_period', 'status']);
        $data = $this->reportService->payrollReport($filters);
        $payrollStatuses = PayrollStatus::cases();

        if ($request->export === 'excel') {
            return Excel::download(new PayrollExport($filters), 'payroll-'.now()->format('Y-m-d').'.xlsx');
        }
        if ($request->export === 'pdf') {
            $pdf = Pdf::loadView('reports.payroll-pdf', $data);

            return $pdf->download('payroll-summary.pdf');
        }

        return view('reports.payroll', array_merge($data, compact('payrollStatuses', 'filters')));
    }

    /**
     * Claims report.
     */
    public function claims(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'status', 'provider_id']);
        $data = $this->reportService->claimsReport($filters);
        $claimStatuses = ClaimStatus::cases();

        if ($request->export === 'excel') {
            return Excel::download(new ClaimsExport($filters), 'claims-'.now()->format('Y-m-d').'.xlsx');
        }

        return view('reports.claims', array_merge($data, compact('claimStatuses', 'filters')));
    }

    /**
     * Stock valuation report.
     */
    public function stockValuation(Request $request)
    {
        $filters = $request->only(['location']);
        $data = $this->reportService->stockValuationReport($filters);

        if ($request->export === 'excel') {
            return Excel::download(new StockValuationExport($filters), 'stock-valuation-'.now()->format('Y-m-d').'.xlsx');
        }

        return view('reports.stock-valuation', array_merge($data, compact('filters')));
    }

    /**
     * Expired stock report.
     */
    public function expiredStock(Request $request)
    {
        $filters = $request->only(['type']);
        $data = $this->reportService->expiredStockReport($filters);

        if ($request->export === 'excel') {
            return Excel::download(new ExpiredStockExport($filters), 'expired-stock-'.now()->format('Y-m-d').'.xlsx');
        }

        return view('reports.expired-stock', array_merge($data, compact('filters')));
    }

    /*
    |--------------------------------------------------------------------------
    | Printable Documents
    |--------------------------------------------------------------------------
    */

    /**
     * Printable consultation note.
     */
    public function printConsultation(MedicalRecord $record)
    {
        $record->load(['patient', 'doctor', 'visit.department', 'complaints', 'diagnoses', 'investigations', 'treatments', 'prescriptions.items']);
        $pdf = Pdf::loadView('reports.print-consultation', compact('record'));

        return $pdf->stream('consultation-'.$record->visit?->visit_number.'.pdf');
    }

    /**
     * Printable lab report.
     */
    public function printLabReport(LabRequest $labRequest)
    {
        $labRequest->load(['patient', 'requestedBy', 'department', 'items.labTest', 'items.result.performedBy', 'items.result.verifiedBy']);
        $pdf = Pdf::loadView('reports.print-lab-report', compact('labRequest'));

        return $pdf->stream('lab-report-'.$labRequest->request_number.'.pdf');
    }

    /**
     * Printable prescription.
     */
    public function printPrescription(Prescription $prescription)
    {
        $prescription->load(['patient', 'doctor', 'visit', 'items.drug']);
        $pdf = Pdf::loadView('reports.print-prescription', compact('prescription'));

        return $pdf->stream('prescription-'.$prescription->id.'.pdf');
    }
}
