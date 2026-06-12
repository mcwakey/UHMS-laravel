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
use Inertia\Inertia;

class BillingReportController extends Controller
{
    public function __construct(
        protected BillingReportService $reportService,
        protected StatementService $statementService,
        protected ARAgingService $arAgingService,
    ) {}

    public function dashboard()
    {
        return Inertia::render('Billing/Dashboard', [
            'metrics' => $this->reportService->dashboard(),
            'routes' => [
                'invoices' => route('admin.billing.invoices.index'),
                'counterSale' => route('admin.billing.counter-sale.create'),
                'aging' => route('admin.billing.reports.aging'),
                'receive' => route('admin.billing.payments.receive'),
                'creditNotes' => route('admin.billing.credit-notes.index'),
                'statements' => route('admin.billing.statements.index'),
            ],
        ]);
    }

    public function aging(Request $request)
    {
        $filters = $request->only(['payer_type', 'status', 'sponsor_id', 'insurance_provider_id', 'corporate_client_id', 'as_of']);
        $this->authorizeAgingPayer($request, $filters['payer_type'] ?? null);

        return Inertia::render('Billing/Reports/Aging', [
            'aging' => $this->arAgingService->report($filters),
            'filters' => $filters,
            'billingTypeOptions' => collect(BillingType::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ])->values(),
            'payerTypeOptions' => [
                ['value' => 'patient', 'label' => __('common.patient')],
                ['value' => 'insurance', 'label' => __('common.billing_type_insurance')],
                ['value' => 'sponsor', 'label' => __('reports.insurance.sponsor')],
                ['value' => 'corporate', 'label' => __('common.billing_type_corporate')],
            ],
            'statusOptions' => [
                ['value' => 'pending', 'label' => __('reports.statuses.pending')],
                ['value' => 'partially_paid', 'label' => __('reports.statuses.partially_paid')],
                ['value' => 'overdue', 'label' => __('reports.statuses.overdue')],
            ],
            'sponsors' => Sponsor::active()->orderBy('name')->get(['id', 'name']),
            'insuranceProviders' => InsuranceProvider::active()->orderBy('name')->get(['id', 'name']),
            'corporateClients' => CorporateClient::active()->orderBy('name')->get(['id', 'name']),
            'routes' => [
                'aging' => route('admin.billing.reports.aging'),
                'pdf' => route('admin.billing.reports.aging.pdf'),
            ],
        ]);
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

        return Inertia::render('Billing/Reports/Discounts', [
            'report' => $this->reportService->discounts($filters),
            'filters' => $filters,
            'routes' => [
                'discounts' => route('admin.billing.reports.discounts'),
            ],
        ]);
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

        return Inertia::render('Billing/Statements/Index', [
            'patients' => $patients,
            'filters' => $request->only(['search']),
            'routes' => [
                'index' => route('admin.billing.statements.index'),
            ],
        ]);
    }

    public function statementShow(Request $request, Patient $patient)
    {
        $statement = $this->statementService->generate($patient, $request->only(['date_from', 'date_to']));

        return Inertia::render('Billing/Statements/Show', [
            'patient' => [
                'id' => $patient->id,
                'name' => trim($patient->first_name . ' ' . $patient->last_name),
                'patient_number' => $patient->patient_number,
                'phone' => $patient->phone,
            ],
            'summary' => $statement['summary'],
            'ledger' => collect($statement['ledger'])->map(fn ($e) => [
                'date' => optional($e['date'])->format('d M Y'),
                'type' => $e['type'],
                'reference' => $e['reference'],
                'description' => $e['description'],
                'charges' => (float) $e['charges'],
                'payments' => (float) $e['payments'],
                'balance' => (float) $e['balance'],
            ])->values(),
            'filters' => $request->only(['date_from', 'date_to']),
            'routes' => [
                'self' => route('admin.billing.statements.show', $patient),
                'pdf' => route('admin.billing.statements.pdf', $patient),
                'index' => route('admin.billing.statements.index'),
            ],
        ]);
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
