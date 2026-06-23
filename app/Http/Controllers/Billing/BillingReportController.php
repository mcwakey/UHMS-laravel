<?php

namespace App\Http\Controllers\Billing;

use App\Enums\BillingType;
use App\Http\Controllers\Controller;
use App\Models\CorporateClient;
use App\Models\InsuranceProvider;
use App\Models\Patient;
use App\Models\Sponsor;
use App\Services\ARAgingService;
use App\Services\BillingReportService;
use App\Services\StatementService;
use Illuminate\Http\Request;

class BillingReportController extends Controller
{
    public function __construct(
        protected BillingReportService $reportService,
        protected StatementService $statementService,
        protected ARAgingService $arAgingService,
    ) {}

    public function dashboard()
    {
        $metrics = $this->reportService->dashboard();

        return view('billing.dashboard', compact('metrics'));
    }

    public function aging(Request $request)
    {
        $filters = $request->only(['payer_type', 'status', 'sponsor_id', 'insurance_provider_id', 'corporate_client_id', 'as_of']);
        $this->authorizeAgingPayer($request, $filters['payer_type'] ?? null);

        $aging = $this->arAgingService->report($filters);
        $sponsors = Sponsor::active()->orderBy('name')->get(['id', 'name']);
        $insuranceProviders = InsuranceProvider::active()->orderBy('name')->get(['id', 'name']);
        $corporateClients = CorporateClient::active()->orderBy('name')->get(['id', 'name']);

        return view('billing.reports.aging', compact('aging', 'filters', 'sponsors', 'insuranceProviders', 'corporateClients'));
    }

    public function agingPdf(Request $request)
    {
        $filters = $request->only(['payer_type', 'status', 'sponsor_id', 'insurance_provider_id', 'corporate_client_id', 'as_of']);
        $this->authorizeAgingPayer($request, $filters['payer_type'] ?? null);
        $aging = $this->arAgingService->report($filters);
        $generatedAt = now();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('billing.reports.aging-pdf', compact('aging', 'generatedAt'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('ar-aging-' . now()->format('Ymd') . '.pdf');
    }

    private function authorizeAgingPayer(Request $request, ?string $payerType): void
    {
        if (! $payerType) {
            return;
        }

        $permission = match ($payerType) {
            'patient' => 'reports.ar_aging.patient',
            'insurance' => 'reports.ar_aging.insurance',
            'sponsor' => 'reports.ar_aging.sponsor',
            'corporate' => 'reports.ar_aging.corporate',
            default => null,
        };

        if ($permission) {
            abort_unless($request->user()?->can($permission), 403);
        }
    }

    public function discounts(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'override']);

        $report = $this->reportService->discounts($filters);

        return view('billing.reports.discounts', compact('report', 'filters'));
    }

    public function statements(Request $request)
    {
        $patients = [];
        if ($request->filled('search')) {
            $search = $request->search;
            $patients = Patient::query()
                ->where(fn ($q) => $q
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('patient_number', 'like', "%{$search}%"))
                ->withSum(['invoices as outstanding_balance' => fn ($q) => $q->whereIn('status', ['pending', 'partially_paid'])], 'balance')
                ->limit(25)
                ->get()
                ->map(fn (Patient $p) => [
                    'id' => $p->id,
                    'name' => trim($p->first_name . ' ' . $p->last_name),
                    'patient_number' => $p->patient_number,
                    'outstanding_balance' => (float) ($p->outstanding_balance ?? 0),
                    'url' => route('admin.billing.statements.show', $p),
                ]);
        }

        $filters = $request->only(['search']);

        return view('billing.statements.index', compact('patients', 'filters'));
    }

    public function statementShow(Request $request, Patient $patient)
    {
        $statement = $this->statementService->generate($patient, $request->only(['date_from', 'date_to']));

        $filters = $request->only(['date_from', 'date_to']);

        return view('billing.statements.show', array_merge($statement, compact('patient', 'filters')));
    }

    public function statementPdf(Request $request, Patient $patient)
    {
        $statement = $this->statementService->generate($patient, $request->only(['date_from', 'date_to']));
        $generatedAt = now();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('billing.statements.statement-pdf', array_merge($statement, compact('generatedAt')))
            ->setPaper('a4');

        return $pdf->download('statement-' . $patient->patient_number . '.pdf');
    }
}
