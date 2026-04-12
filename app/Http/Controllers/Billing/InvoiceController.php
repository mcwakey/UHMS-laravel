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
        $query = Invoice::with(['patient', 'visit', 'createdBy'])
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
            $visit = Visit::with(['patient', 'labRequests.items.labTest', 'prescriptions.items.drug'])
                ->findOrFail($request->visit_id);
            $suggestedItems = $this->billingService->generateItemsFromVisit($visit);
        }

        $services = ServiceCatalog::active()->orderBy('category')->orderBy('name')->get();
        $patients = Patient::orderBy('first_name')->get();
        $billingTypes = BillingType::cases();

        return view('billing.invoices.create', compact('visit', 'suggestedItems', 'services', 'patients', 'billingTypes'));
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
        $invoice->load(['items.serviceCatalog', 'payments.receivedBy', 'patient', 'visit.department', 'createdBy']);

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
}
