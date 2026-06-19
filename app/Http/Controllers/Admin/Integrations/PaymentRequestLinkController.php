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
        $resend = $request->boolean('resend');

        $link = $this->links->create([
            'invoice_id' => $invoice->id,
            'visit_id' => $invoice->visit_id,
            'patient_id' => $invoice->patient_id,
            'amount' => (float) $invoice->balance,
        ]);

        // The SMS carries the public payment link ({{payment_link}}); resolution
        // and dedup happen in the event service.
        $event = $events->dispatch([
            'event_type' => \App\Models\SmsNotificationEvent::TYPE_INVOICE_PAYMENT_REQUEST,
            'source_type' => 'invoice',
            'source_id' => $invoice->id,
            'phone' => $phone,
            'force_resend' => $resend,
            'data' => [
                'patient_name' => $invoice->patient?->first_name,
                'invoice_number' => $invoice->invoice_number,
                'amount' => number_format((float) $invoice->balance, 2),
                'currency' => config('integrations.default_currency', 'GHS'),
                'payment_link' => route('public.payments.show', $link->public_token),
                'hospital_name' => config('app.name', 'UHMS'),
            ],
        ]);

        $this->logger->log(LogModule::INTEGRATIONS, $resend ? 'PAYMENT_REQUEST_SMS_RESENT' : 'PAYMENT_REQUEST_SMS_SENT', [
            'source_type' => 'invoice', 'source_id' => $invoice->id,
            'invoice_id' => $invoice->id,
            'metadata' => ['event_status' => $event->status, 'link' => $link->public_token],
        ], $invoice, 'Payment request SMS dispatched');

        return back()->with(
            $event->status === 'sent' ? 'success' : 'info',
            $event->status === 'sent' ? __('payments.gateway.request_sms_sent') : __('payments.gateway.request_sms_skipped'),
        );
    }
}
