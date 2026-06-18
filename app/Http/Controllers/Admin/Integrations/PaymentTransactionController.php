<?php

namespace App\Http\Controllers\Admin\Integrations;

use App\Exceptions\Integrations\IntegrationException;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PaymentProviderTransaction;
use App\Services\Integrations\Payment\PaymentGatewayService;
use Illuminate\Http\Request;

class PaymentTransactionController extends Controller
{
    public function __construct(protected PaymentGatewayService $gateway) {}

    public function index(Request $request)
    {
        $transactions = PaymentProviderTransaction::query()
            ->with(['provider', 'invoice', 'patient'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.integrations.payments.transactions.index', compact('transactions'));
    }

    public function show(PaymentProviderTransaction $transaction)
    {
        $transaction->load(['provider', 'invoice', 'patient', 'attempts', 'refunds', 'payment']);

        return view('admin.integrations.payments.transactions.show', compact('transaction'));
    }

    public function create(Request $request)
    {
        $invoice = $request->filled('invoice_id')
            ? Invoice::with('patient')->find($request->integer('invoice_id'))
            : null;
        $hasProvider = $this->gateway->hasActiveProvider();

        return view('admin.integrations.payments.transactions.create', compact('invoice', 'hasProvider'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:40'],
            'payer_name' => ['nullable', 'string', 'max:255'],
            'payer_phone' => ['required', 'string', 'max:40'],
            'payer_email' => ['nullable', 'email', 'max:255'],
        ]);

        $invoice = ! empty($data['invoice_id']) ? Invoice::find($data['invoice_id']) : null;

        try {
            $transaction = $this->gateway->initiate([
                'invoice_id' => $invoice?->id,
                'visit_id' => $invoice?->visit_id,
                'patient_id' => $invoice?->patient_id,
                'amount' => $data['amount'],
                'currency' => config('integrations.default_currency', 'GHS'),
                'payment_method' => $data['payment_method'],
                'payer_name' => $data['payer_name'] ?? null,
                'payer_phone' => $data['payer_phone'],
                'payer_email' => $data['payer_email'] ?? null,
                'description' => $invoice ? ('Invoice ' . $invoice->invoice_number) : 'UHMS payment',
            ]);
        } catch (IntegrationException $e) {
            return back()->withInput()->with('error', $e->localisedMessage());
        }

        return redirect()
            ->route('admin.integrations.payments.transactions.show', $transaction)
            ->with('success', __('integrations.flash.payment_initiated'));
    }

    public function verify(PaymentProviderTransaction $transaction)
    {
        $transaction = $this->gateway->verify($transaction);

        $flash = $transaction->isSuccessful()
            ? ['success', __('integrations.flash.payment_verified')]
            : ['info', __('integrations.flash.payment_not_confirmed')];

        return back()->with($flash[0], $flash[1]);
    }
}
