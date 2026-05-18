<?php

namespace App\Http\Controllers\Billing;

use App\Enums\BillingType;
use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\Visit;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function __construct(
        protected BillingService $billingService
    ) {}

    /**
     * Invoice list.
     */
    public function index(Request $request)
    {
        $query = Invoice::with(['patient', 'visit.visitInsurance.insuranceProvider', 'claim', 'createdBy'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('billing_type')) {
            $query->where('billing_type', $request->billing_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($q2) use ($search) {
                        $q2->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('patient_number', 'like', "%{$search}%");
                    });
            });
        }

        $invoices = $query->paginate(20)->withQueryString();
        $stats = $this->billingService->getStats();

        $user = $request->user();
        $canClaimsView   = $user?->can('claims.view')   ?? false;
        $canClaimsCreate = $user?->can('claims.create') ?? false;
        $canInvoicesEdit = $user?->can('invoices.edit') ?? false;

        $invoicesPayload = $invoices->through(function (Invoice $invoice) use ($canClaimsView, $canClaimsCreate, $canInvoicesEdit) {
            $claim = $invoice->claim;
            $insuranceProviderId = $invoice->visit?->visitInsurance?->insurance_provider_id;
            $canCreateInsuranceClaim = (float) $invoice->nhis_amount > 0 && ! $claim;
            $cancellable = ! in_array($invoice->status, [InvoiceStatus::PAID, InvoiceStatus::CANCELLED], true);

            return [
                'id'             => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'patient' => $invoice->patient ? [
                    'full_name'      => $invoice->patient->full_name,
                    'patient_number' => $invoice->patient->patient_number,
                ] : null,
                'billing_type' => $invoice->billing_type ? [
                    'value' => $invoice->billing_type->value,
                    'label' => $invoice->billing_type->label(),
                    'color' => $invoice->billing_type->color(),
                ] : null,
                'status' => $invoice->status ? [
                    'value' => $invoice->status->value,
                    'label' => $invoice->status->label(),
                    'color' => $invoice->status->color(),
                ] : null,
                'total_amount'  => (float) $invoice->total_amount,
                'amount_paid'   => (float) $invoice->amount_paid,
                'balance'       => (float) $invoice->balance,
                'created_at_display' => optional($invoice->created_at)->format('d M Y'),
                'urls' => [
                    'show'           => route('admin.billing.invoices.show', $invoice),
                    'cancel'         => $cancellable && $canInvoicesEdit ? route('admin.billing.invoices.cancel', $invoice) : null,
                    'view_claim'     => $claim && $canClaimsView ? route('admin.claims.show', $claim) : null,
                    'generate_claim' => $canCreateInsuranceClaim && $canClaimsCreate
                        ? ($insuranceProviderId
                            ? route('admin.claims.store-from-invoice')
                            : route('admin.claims.create', ['invoice_id' => $invoice->id]))
                        : null,
                ],
                'insurance_provider_id'    => $canCreateInsuranceClaim ? $insuranceProviderId : null,
                'has_insurance_provider'   => $canCreateInsuranceClaim && (bool) $insuranceProviderId,
                'can_create_claim'         => $canCreateInsuranceClaim && $canClaimsCreate,
            ];
        });

        return Inertia::render('Billing/Invoices/Index', [
            'invoices' => $invoicesPayload,
            'stats'    => $stats,
            'filters'  => $request->only(['search', 'status', 'billing_type']),
            'statusOptions' => collect(InvoiceStatus::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ])->values(),
            'billingTypeOptions' => collect(BillingType::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ])->values(),
            'routes' => [
                'index'  => route('admin.billing.invoices.index'),
                'create' => route('admin.billing.invoices.create'),
                'storeClaimFromInvoice' => route('admin.claims.store-from-invoice'),
            ],
            'can' => [
                'create'       => $user?->can('invoices.create') ?? false,
                'editInvoices' => $canInvoicesEdit,
                'viewClaims'   => $canClaimsView,
                'createClaims' => $canClaimsCreate,
            ],
            'csrf' => csrf_token(),
        ]);
    }

    /**
     * Create invoice form.
     */
    public function create(Request $request)
    {
        $visit = null;
        $suggestedItems = [];

        if ($request->filled('visit_id')) {
            $visit = Visit::with(['patient', 'visitServices.serviceCatalog', 'labRequests.items.labTest', 'prescriptions.items.drug'])
                ->findOrFail($request->visit_id);
            $suggestedItems = $this->billingService->generateItemsFromVisit($visit);
        }

        $billableVisits = Visit::with(['patient', 'visitServices.serviceCatalog'])
            ->whereHas('visitServices')
            ->latest('visit_date')
            ->limit(100)
            ->get();

        $services = ServiceCatalog::active()->orderBy('category')->orderBy('name')->get();
        $patients = Patient::orderBy('first_name')->get();
        $billingTypes = BillingType::cases();

        return view('billing.invoices.create', compact('visit', 'suggestedItems', 'billableVisits', 'services', 'patients', 'billingTypes'));
    }

    /**
     * Store a new invoice.
     */
    public function store(StoreInvoiceRequest $request)
    {
        $invoice = $this->billingService->createInvoice(
            $request->only(['visit_id', 'patient_id', 'billing_type', 'tax_amount', 'discount_amount', 'due_date', 'notes']),
            $request->input('items', [])
        );

        return redirect()
            ->route('admin.billing.invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_number} created successfully.");
    }

    /**
     * Show invoice details.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['items.serviceCatalog', 'payments.receivedBy', 'patient', 'visit.department', 'visit.visitInsurance.insuranceProvider', 'claim', 'createdBy']);

        return view('billing.invoices.show', compact('invoice'));
    }

    /**
     * Cancel an invoice.
     */
    public function cancel(Invoice $invoice)
    {
        if ($invoice->status === InvoiceStatus::PAID) {
            return back()->with('error', 'Cannot cancel a fully paid invoice.');
        }

        $this->billingService->cancelInvoice($invoice);

        return back()->with('success', "Invoice {$invoice->invoice_number} has been cancelled.");
    }

    /**
     * Print invoice (PDF view).
     */
    public function print(Invoice $invoice)
    {
        $invoice->load(['items.serviceCatalog', 'payments', 'patient', 'visit.department', 'createdBy']);

        return view('billing.invoices.print', compact('invoice'));
    }

    /**
     * Apply a manual discount to an invoice line item.
     *
     * POST /admin/billing/invoices/{invoice}/items/{item}/discount
     *   body: { discount_amount: numeric }
     */
    public function applyItemDiscount(Request $request, Invoice $invoice, \App\Models\InvoiceItem $item)
    {
        if ($item->invoice_id !== $invoice->id) {
            return back()->with('error', 'Item does not belong to this invoice.');
        }

        $data = $request->validate([
            'discount_amount' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $this->billingService->applyDiscount($item, (float) $data['discount_amount']);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Discount applied successfully.');
    }
}
