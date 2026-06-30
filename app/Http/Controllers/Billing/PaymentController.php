<?php

namespace App\Http\Controllers\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\AccountingService;
use App\Services\BillingService;
use App\Services\RefundService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected BillingService $billingService,
        protected AccountingService $accountingService,
        protected RefundService $refundService,
    ) {}

    /**
     * Cashier intake screen for collecting outstanding invoice payments.
     */
    public function receive(Request $request)
    {
        $query = Invoice::with(['patient', 'visit.department'])
            ->withSum(['items as cashier_balance' => fn ($q) => $q->where('balance', '>', 0)], 'balance')
            ->unpaid()
            ->whereHas('items', fn ($q) => $q->where('balance', '>', 0));

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
                            ->orWhere('patient_number', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('visit', function ($q2) use ($search) {
                        $q2->where('visit_number', 'like', "%{$search}%");
                    });
            });
        }

        $stats = [
            'waiting_invoices' => (clone $query)->count(),
            'outstanding_balance' => (clone $query)->get()->sum(fn ($invoice) => (float) $invoice->cashier_balance),
            'cashier_today' => Payment::where('received_by', $request->user()->id)
                ->whereDate('paid_at', today())
                ->sum('amount'),
        ];

        $invoices = (clone $query)
            ->orderByRaw('due_date IS NULL, due_date ASC')
            ->latest('created_at')
            ->paginate(12)
            ->withQueryString();

        $paymentMethods = collect(PaymentMethod::cases())
            ->reject(fn (PaymentMethod $method) => $method === PaymentMethod::INSURANCE)
            ->values();
        $openShift = $this->accountingService->getOpenShift();
        $invoiceStatuses = [InvoiceStatus::PENDING, InvoiceStatus::PARTIALLY_PAID];

        return view('billing.payments.receive', compact('invoices', 'paymentMethods', 'openShift', 'stats', 'invoiceStatuses'));
    }

    /**
     * Payment list.
     */
    public function index(Request $request)
    {
        $query = Payment::with(['invoice', 'patient', 'receivedBy'])
            ->latest('paid_at');

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($q2) use ($search) {
                        $q2->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('patient_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('paid_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('paid_at', '<=', $request->date_to);
        }

        $payments = $query->paginate(20)->withQueryString();
        $paymentMethods = PaymentMethod::cases();

        $totalToday = Payment::whereDate('paid_at', today())->sum('amount');
        $totalMonth = Payment::whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        return view('billing.payments.index', compact('payments', 'paymentMethods', 'totalToday', 'totalMonth'));
    }

    /**
     * Record payment against an invoice.
     */
    public function store(StorePaymentRequest $request, Invoice $invoice)
    {
        $validated = $request->validated();

        if (in_array($invoice->status, [InvoiceStatus::PAID, InvoiceStatus::CANCELLED, InvoiceStatus::REFUNDED])) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('messages.payments.cannot_record'),
                ], 409);
            }

            return back()->with('error', __('messages.payments.cannot_record'));
        }

        $collectableBalance = (float) $invoice->balance;
        if ($request->input('return_to') === 'receive') {
            $validated['payer_type'] = 'patient';
            $validated['payer_id'] = $invoice->patient_id;
            $collectableBalance = (float) $invoice->items()
                ->where('balance', '>', 0)
                ->sum('balance');
        }

        if ((float) $validated['amount'] > $collectableBalance) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('messages.payments.exceeds_balance', ['balance' => '₵' . number_format($collectableBalance, 2)]),
                ], 422);
            }

            return back()->with('error', __('messages.payments.exceeds_balance', ['balance' => '₵' . number_format($collectableBalance, 2)]));
        }

        if ($validated['payment_method'] === PaymentMethod::CASH->value && ! $this->accountingService->getOpenShift()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('messages.payments.open_shift_required'),
                ], 409);
            }

            return back()->with('error', __('messages.payments.open_shift_required'));
        }

        try {
            $payment = $this->billingService->recordPayment(
                $invoice,
                $validated,
                $validated['allocations'] ?? [],
            );
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }
        $payment->loadMissing(['invoice.visit']);
        $invoice->refresh();
        $visit = $invoice->visit?->fresh();
        $successMessage = __('messages.payments.recorded', [
            'number' => $payment->payment_number,
            'amount' => '₵' . number_format($payment->amount, 2),
        ]);

        if ($request->expectsJson()) {
            $request->session()->flash('success', $successMessage);
            return response()->json([
                'message' => $successMessage,
                'payment_id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'amount' => (float) $payment->amount,
                'invoice_id' => $invoice->id,
                'invoice_status' => $invoice->status->value,
                'invoice_status_label' => $invoice->status->label(),
                'visit_id' => $visit?->id,
                'visit_status' => $visit?->status?->value,
                'visit_status_label' => $visit?->status?->label(),
                'redirect_url' => route('admin.billing.invoices.show', $invoice),
                'receipt_url' => route('admin.billing.payments.receipt', $payment),
            ], 201);
        }

        if ($request->input('return_to') === 'receive') {
            return redirect()
                ->route('admin.billing.payments.receive')
                ->with('success', $successMessage);
        }

        return redirect()
            ->route('admin.billing.invoices.show', $invoice)
            ->with('success', $successMessage);
    }

    /**
     * Print payment receipt.
     */
    public function receipt(Payment $payment)
    {
        $payment->load(['invoice.items.department', 'patient', 'receivedBy']);

        return view('billing.payments.receipt', compact('payment'));
    }

    /**
     * Print payment receipt on an 80mm thermal roll.
     */
    public function receiptThermal(Payment $payment)
    {
        $payment->load(['invoice.items.department', 'patient', 'receivedBy']);

        return view('billing.payments.receipt-thermal', compact('payment'));
    }

    /**
     * Download payment receipt as a PDF.
     */
    public function receiptPdf(Payment $payment)
    {
        $payment->load(['invoice.items.department', 'patient', 'receivedBy']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('billing.payments.receipt-pdf', compact('payment'))
            ->setPaper('a5');

        return $pdf->download("receipt-{$payment->payment_number}.pdf");
    }

    /**
     * Reverse (void) a recorded payment, restoring the invoice balance.
     */
    public function reverse(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $reversal = $this->refundService->reversePayment($payment, $data['reason']);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            __('messages.payments.reversed', ['number' => $payment->payment_number, 'reversal' => $reversal->payment_number])
        );
    }
}
