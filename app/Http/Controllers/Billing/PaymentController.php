<?php

namespace App\Http\Controllers\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\BillingService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected BillingService $billingService
    ) {}

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
        if (in_array($invoice->status, [InvoiceStatus::PAID, InvoiceStatus::CANCELLED, InvoiceStatus::REFUNDED])) {
            return back()->with('error', 'Cannot record payment on this invoice.');
        }

        if ($request->amount > $invoice->balance) {
            return back()->with('error', 'Payment amount exceeds outstanding balance of ₵' . number_format($invoice->balance, 2));
        }

        $payment = $this->billingService->recordPayment($invoice, $request->validated());

        return redirect()
            ->route('admin.billing.invoices.show', $invoice)
            ->with('success', "Payment {$payment->payment_number} of ₵" . number_format($payment->amount, 2) . " recorded successfully.");
    }

    /**
     * Print payment receipt.
     */
    public function receipt(Payment $payment)
    {
        $payment->load(['invoice.items', 'patient', 'receivedBy']);

        return view('billing.payments.receipt', compact('payment'));
    }
}
