<?php

namespace App\Http\Controllers\Admin\Integrations;

use App\Enums\InvoiceStatus;
use App\Exceptions\Integrations\IntegrationException;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PaymentProviderTransaction;
use App\Services\Integrations\Payment\PaymentGatewayService;
use Illuminate\Http\Request;

/**
 * Inline mobile-money / online payment from the existing invoice screen
 * (the third payment path). All provider logic stays here in the integration
 * layer — the billing InvoiceController is never touched. On verification the
 * existing PaymentService creates the UHMS payment, deducts the invoice and
 * posts to accounting exactly as a manual payment would.
 */
class InvoiceGatewayPaymentController extends Controller
{
    public function __construct(protected PaymentGatewayService $gateway) {}

    /** Start a mobile-money charge for the invoice's outstanding balance. */
    public function charge(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'payer_phone' => ['required', 'string', 'max:40'],
            'payment_method' => ['required', 'string', 'max:40'],
        ]);

        $status = $invoice->status?->value ?? $invoice->status;
        if ((float) $invoice->balance <= 0 || in_array($status, [InvoiceStatus::PAID->value, InvoiceStatus::CANCELLED->value], true)) {
            return back()->with('error', __('payments.gateway.not_payable'));
        }

        $description = trim('Invoice ' . $invoice->invoice_number);

        try {
            $this->gateway->initiate([
                'invoice_id' => $invoice->id,
                'visit_id' => $invoice->visit_id,
                'patient_id' => $invoice->patient_id,
                'amount' => (float) $invoice->balance,
                'currency' => config('integrations.default_currency', 'GHS'),
                'payment_method' => $data['payment_method'],
                'payer_phone' => $data['payer_phone'],
                'payer_name' => $invoice->patient?->full_name,
                'description' => $description,
                'metadata' => ['source' => 'invoice_page'],
            ]);
        } catch (IntegrationException $e) {
            return back()->with('error', $e->localisedMessage());
        }

        return redirect()
            ->route('admin.billing.invoices.show', $invoice)
            ->with('success', __('payments.gateway.charge_started'));
    }

    /** Verify/recheck a pending gateway transaction and return to the invoice. */
    public function verify(PaymentProviderTransaction $transaction)
    {
        $transaction = $this->gateway->verify($transaction);

        $target = $transaction->invoice_id
            ? route('admin.billing.invoices.show', $transaction->invoice_id)
            : route('admin.integrations.payments.transactions.show', $transaction);

        return redirect()->to($target)->with(
            $transaction->isSuccessful() ? 'success' : 'info',
            $transaction->isSuccessful() ? __('integrations.flash.payment_verified') : __('integrations.flash.payment_not_confirmed'),
        );
    }
}
