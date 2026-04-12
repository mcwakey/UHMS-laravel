<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\VisitStatus;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService) {}

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
     * NHIS claims report.
     */
    public function nhis(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'status']);
        $data = $this->reportService->nhisReport($filters);
        $invoiceStatuses = InvoiceStatus::cases();

        if ($request->has('export') && $request->export === 'pdf') {
            $pdf = Pdf::loadView('reports.nhis-pdf', $data);
            return $pdf->download('nhis-report.pdf');
        }

        return view('reports.nhis', array_merge($data, compact('invoiceStatuses', 'filters')));
    }
}
