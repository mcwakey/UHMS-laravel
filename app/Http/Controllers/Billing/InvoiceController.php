<?php

namespace App\Http\Controllers\Billing;

use App\Enums\BillingType;
use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\CorporateClient;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\Sponsor;
use App\Models\Visit;
use App\Services\BillingService;
use App\Services\InvoiceBalanceService;
use App\Services\InvoiceReceivableService;
use Illuminate\Http\Request;

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

        return view('billing.invoices.index', compact('invoices', 'stats'));
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
                ->with('error', __('messages.invoices.manual_discount_note'));
        }

        $invoice = $this->billingService->createInvoice(
            $request->only(['visit_id', 'patient_id', 'billing_type', 'sponsor_id', 'corporate_client_id', 'tax_amount', 'discount_amount', 'due_date', 'notes']),
            $request->input('items', [])
        );

        return redirect()
            ->route('admin.billing.invoices.show', $invoice)
            ->with('success', __('messages.invoices.created', ['number' => $invoice->invoice_number]));
    }

    /**
     * Show invoice details.
     */
    public function show(Invoice $invoice, InvoiceBalanceService $balanceService, InvoiceReceivableService $receivableService)
    {
        $receivableService->syncFromInvoice($invoice);

        $invoice->load([
            'items.serviceCatalog',
            'items.department',
            'items.journalEntry',
            'items.discountEvents.performedBy',
            'journalEntry',
            'payments.receivedBy',
            'payments.journalEntry',
            'payments.receivable.patient',
            'payments.receivable.insuranceProvider',
            'payments.receivable.sponsor',
            'payments.receivable.corporateClient',
            'patient',
            'visit.department',
            'visit.visitInsurance.insuranceProvider',
            'claim',
            'receivables.patient',
            'receivables.insuranceProvider',
            'receivables.sponsor',
            'receivables.corporateClient',
            'receivables.journalEntry',
            'createdBy',
            'discountEvents.invoiceItem',
            'discountEvents.performedBy',
            'discountEvents.journalEntry',
            'discountEvents.reversalJournalEntry',
            'creditNotes.issuedBy',
            'creditNotes.cancelledBy',
            'creditNotes.journalEntry',
            'creditNotes.reversalJournalEntry',
        ]);

        return view('billing.invoices.show', [
            'invoice' => $invoice,
            'invoiceBalanceSummary' => $balanceService->summary($invoice),
            'adjustmentHistory' => $balanceService->history($invoice),
            'receivablePayerOptions' => [
                'sponsors' => Sponsor::active()->orderBy('name')->get(['id', 'name']),
                'corporate' => CorporateClient::active()->orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    /**
     * Edit invoice header (billing type, sponsor, due date, tax, notes).
     */
    public function edit(Invoice $invoice)
    {
        $invoice->load(['patient', 'sponsor', 'corporateClient']);

        return view('billing.invoices.edit', [
            'invoice' => $invoice,
            'billingTypes' => BillingType::cases(),
            'sponsors' => Sponsor::active()->orderBy('name')->get(['id', 'name']),
            'corporateClients' => CorporateClient::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Persist invoice header changes.
     */
    public function update(Request $request, Invoice $invoice)
    {
        if (in_array($invoice->status, [InvoiceStatus::CANCELLED, InvoiceStatus::REFUNDED, InvoiceStatus::PAID], true)) {
            return back()->with('error', __('messages.invoices.cannot_edit'));
        }

        $data = $request->validate([
            'billing_type' => ['required', \Illuminate\Validation\Rule::in(array_column(BillingType::cases(), 'value'))],
            'sponsor_id' => ['nullable', 'exists:sponsors,id'],
            'corporate_client_id' => ['nullable', 'exists:corporate_clients,id'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['billing_type'] !== BillingType::CORPORATE->value) {
            $data['sponsor_id'] = null;
            $data['corporate_client_id'] = null;
        }

        $invoice->update([
            'billing_type' => $data['billing_type'],
            'sponsor_id' => $data['sponsor_id'] ?? null,
            'corporate_client_id' => $data['corporate_client_id'] ?? null,
            'tax_amount' => $data['tax_amount'] ?? 0,
            'due_date' => $data['due_date'] ?? $invoice->due_date,
            'notes' => $data['notes'] ?? null,
        ]);

        app(InvoiceReceivableService::class)->syncFromInvoice($invoice->fresh(['items', 'payments', 'creditNotes']));

        return redirect()
            ->route('admin.billing.invoices.show', $invoice)
            ->with('success', __('messages.invoices.updated', ['number' => $invoice->invoice_number]));
    }

    /**
     * Cancel an invoice.
     */
    public function cancel(Invoice $invoice)
    {
        if ($invoice->status === InvoiceStatus::PAID) {
            return back()->with('error', __('messages.invoices.cannot_cancel'));
        }

        $this->billingService->cancelInvoice($invoice);

        return back()->with('success', __('messages.invoices.cancelled', ['number' => $invoice->invoice_number]));
    }

    /**
     * Print invoice (PDF view).
     */
    public function print(Invoice $invoice)
    {
        $invoice->load(['items.serviceCatalog', 'items.department', 'payments', 'receivables.sponsor', 'receivables.insuranceProvider', 'receivables.corporateClient', 'patient', 'visit.department', 'createdBy']);

        return view('billing.invoices.print', compact('invoice'));
    }

    /**
     * Download invoice as a PDF.
     */
    public function downloadPdf(Invoice $invoice)
    {
        $invoice->load(['items.serviceCatalog', 'items.department', 'payments', 'receivables.sponsor', 'receivables.insuranceProvider', 'receivables.corporateClient', 'patient', 'sponsor', 'corporateClient', 'visit.department', 'createdBy', 'creditNotes']);

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
            return back()->with('error', __('messages.invoices.item_not_belong'));
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

        return back()->with('success', __('messages.invoices.discount_applied'));
    }

    /**
     * Remove a manual discount from an invoice line item.
     */
    public function removeItemDiscount(Request $request, Invoice $invoice, \App\Models\InvoiceItem $item)
    {
        if ($item->invoice_id !== $invoice->id) {
            return back()->with('error', __('messages.invoices.item_not_belong'));
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

        return back()->with('success', __('messages.invoices.discount_removed'));
    }
}
