<?php

namespace App\Http\Controllers\Admin\Integrations;

use App\Enums\LogModule;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PaymentRequestLink;
use App\Services\ActivityLogService;
use App\Services\Integrations\Sms\SmsNotificationEventService;
use App\Services\Integrations\Payment\PaymentRequestLinkService;
use Illuminate\Http\Request;

class PaymentRequestLinkController extends Controller
{
    public function __construct(
        protected PaymentRequestLinkService $links,
        protected ActivityLogService $logger,
    ) {}

    public function index()
    {
        $requestLinks = PaymentRequestLink::with(['invoice', 'patient', 'transaction'])->latest()->paginate(20);

        return view('admin.integrations.payments.request-links.index', compact('requestLinks'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $invoice = ! empty($data['invoice_id']) ? Invoice::find($data['invoice_id']) : null;

        $this->links->create([
            'invoice_id' => $invoice?->id,
            'visit_id' => $invoice?->visit_id,
            'patient_id' => $invoice?->patient_id,
            'amount' => $data['amount'],
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return back()->with('success', __('payments.gateway.link_created'));
    }

    public function expire(PaymentRequestLink $link)
    {
        $this->links->expire($link);

        return back()->with('success', __('payments.gateway.link_expired'));
    }

    /** Generate a payment-request link and send it by SMS (opt-in event). */
    public function sendSms(Request $request, Invoice $invoice, SmsNotificationEventService $events)
    {
        $phone = $request->input('phone', $invoice->patient?->phone);

        $link = $this->links->create([
            'invoice_id' => $invoice->id,
            'visit_id' => $invoice->visit_id,
            'patient_id' => $invoice->patient_id,
            'amount' => (float) $invoice->balance,
        ]);

        $event = $events->invoicePaymentRequest($invoice, $phone, 'Ref: ' . $link->link_uuid);

        $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_REQUEST_SMS_SENT', [
            'source_type' => 'invoice', 'source_id' => $invoice->id,
            'invoice_id' => $invoice->id,
            'metadata' => ['event_status' => $event->status, 'link' => $link->link_uuid],
        ], $invoice, 'Payment request SMS dispatched');

        return back()->with(
            $event->status === 'sent' ? 'success' : 'info',
            $event->status === 'sent' ? __('payments.gateway.request_sms_sent') : __('payments.gateway.request_sms_skipped'),
        );
    }
}
