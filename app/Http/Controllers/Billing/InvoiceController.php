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
        $canInvoicesVoid = $user?->can('invoices.void') ?? false;

        $invoicesPayload = $invoices->through(function (Invoice $invoice) use ($canClaimsView, $canClaimsCreate, $canInvoicesEdit, $canInvoicesVoid) {
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
                'external_party_name' => $invoice->external_party_name,
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
                    'cancel'         => $cancellable && $canInvoicesVoid ? route('admin.billing.invoices.cancel', $invoice) : null,
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
        if ((float) $request->input('discount_amount', 0) > 0) {
            abort_unless($request->user()?->can('billing.discount.apply'), 403);

            return back()
                ->withInput()
                ->with('error', 'Manual discounts must be applied to invoice items with a reason after invoice creation.');
        }

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
        $invoice->load([
            'items.serviceCatalog',
            'items.department',
            'items.discountEvents.performedBy',
            'payments.receivedBy',
            'patient',
            'visit.department',
            'visit.visitInsurance.insuranceProvider',
            'claim',
            'createdBy',
            'discountEvents.invoiceItem',
            'discountEvents.performedBy',
        ]);

        return view('billing.invoices.show', compact('invoice'));
    }

    /**
     * Edit invoice header (billing type, sponsor, due date, tax, notes).
     */
    public function edit(Invoice $invoice)
    {
        $invoice->load(['patient', 'sponsor']);

        return Inertia::render('Billing/Invoices/Edit', [
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'patient_name' => $invoice->patient ? $invoice->patient->full_name : '—',
                'billing_type' => $invoice->billing_type?->value,
                'sponsor_id' => $invoice->sponsor_id,
                'tax_amount' => (float) $invoice->tax_amount,
                'due_date' => optional($invoice->due_date)->format('Y-m-d'),
                'notes' => $invoice->notes,
                'total_amount' => (float) $invoice->total_amount,
                'balance' => (float) $invoice->balance,
            ],
            'billingTypeOptions' => collect(BillingType::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ])->values(),
            'sponsors' => \App\Models\Sponsor::active()->orderBy('name')->get(['id', 'name']),
            'routes' => [
                'update' => route('admin.billing.invoices.update', $invoice),
                'show' => route('admin.billing.invoices.show', $invoice),
            ],
        ]);
    }

    /**
     * Persist invoice header changes.
     */
    public function update(Request $request, Invoice $invoice)
    {
        if (in_array($invoice->status, [InvoiceStatus::CANCELLED, InvoiceStatus::REFUNDED, InvoiceStatus::PAID], true)) {
            return back()->with('error', 'This invoice can no longer be edited.');
        }

        $data = $request->validate([
            'billing_type' => ['required', \Illuminate\Validation\Rule::in(array_column(BillingType::cases(), 'value'))],
            'sponsor_id' => ['nullable', 'exists:sponsors,id'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['billing_type'] !== BillingType::CORPORATE->value) {
            $data['sponsor_id'] = null;
        }

        $invoice->update([
            'billing_type' => $data['billing_type'],
            'sponsor_id' => $data['sponsor_id'] ?? null,
            'tax_amount' => $data['tax_amount'] ?? 0,
            'due_date' => $data['due_date'] ?? $invoice->due_date,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('admin.billing.invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_number} updated.");
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
     * Download invoice as a PDF.
     */
    public function downloadPdf(Invoice $invoice)
    {
        $invoice->load(['items.serviceCatalog', 'payments', 'patient', 'sponsor', 'visit.department', 'createdBy', 'creditNotes']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('billing.invoices.invoice-pdf', compact('invoice'))
            ->setPaper('a4');

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }

    /**
     * Apply a manual discount to an invoice line item.
     *
     * POST /admin/billing/invoices/{invoice}/items/{item}/discount
     *   body: { discount_amount: numeric, reason: string }
     */
    public function applyItemDiscount(Request $request, Invoice $invoice, \App\Models\InvoiceItem $item)
    {
        if ($item->invoice_id !== $invoice->id) {
            return back()->with('error', 'Item does not belong to this invoice.');
        }

        $data = $request->validate([
            'discount_amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->billingService->applyDiscount($item, (float) $data['discount_amount'], $data['reason'], $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        return back()->with('success', 'Discount applied successfully.');
    }

    /**
     * Remove a manual discount from an invoice line item.
     */
    public function removeItemDiscount(Request $request, Invoice $invoice, \App\Models\InvoiceItem $item)
    {
        if ($item->invoice_id !== $invoice->id) {
            return back()->with('error', 'Item does not belong to this invoice.');
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->billingService->applyDiscount($item, 0.0, $data['reason'], $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        return back()->with('success', 'Discount removed successfully.');
    }
}
