<?php

namespace App\Http\Controllers;

use App\Enums\LogModule;
use App\Exceptions\Integrations\IntegrationException;
use App\Models\PaymentRequestLink;
use App\Services\ActivityLogService;
use App\Services\Integrations\Payment\PublicPaymentLinkService;
use Illuminate\Http\Request;

/**
 * PUBLIC, unauthenticated payment-link portal. Resolves links by their random
 * public token only — never an internal id — and exposes a safe invoice summary,
 * provider payment initiation, status, and a receipt (only after a verified UHMS
 * payment exists). No clinical data, provider secrets or callback payloads.
 */
class PublicPaymentController extends Controller
{
    public function __construct(protected PublicPaymentLinkService $links) {}

    public function show(string $token)
    {
        $link = $this->resolve($token);
        $this->links->recordView($link);

        return $this->renderState($link);
    }

    public function initiate(Request $request, string $token)
    {
        $link = $this->resolve($token);

        $data = $request->validate([
            'payer_phone' => ['required', 'string', 'max:40'],
            'payer_name' => ['nullable', 'string', 'max:120'],
            'payment_method' => ['nullable', 'string', 'max:40'],
        ]);

        try {
            $this->links->initiate($link, $data);
        } catch (IntegrationException $e) {
            return redirect()->route('public.payments.show', $token)->with('error', $e->localisedMessage());
        }

        return redirect()->route('public.payments.status', $token);
    }

    public function verify(string $token)
    {
        $link = $this->resolve($token);
        $this->links->recheck($link);

        return redirect()->route('public.payments.status', $token);
    }

    public function status(string $token, ActivityLogService $logger)
    {
        $link = $this->resolve($token);
        $logger->log(LogModule::INTEGRATIONS, 'PAYMENT_LINK_PUBLIC_STATUS_VIEWED', [
            'source_type' => 'payment_request_link', 'source_id' => $link->id,
        ], $link, 'Public payment status viewed');

        return $this->renderState($link, statusPage: true);
    }

    public function receipt(string $token, ActivityLogService $logger)
    {
        $link = $this->resolve($token);
        $txn = $link->transaction;

        // Receipt only exists once a verified UHMS payment has been created.
        if (! $txn || ! $txn->uhms_payment_id) {
            return redirect()->route('public.payments.status', $token);
        }

        $logger->log(LogModule::INTEGRATIONS, 'PAYMENT_LINK_PUBLIC_RECEIPT_VIEWED', [
            'source_type' => 'payment_request_link', 'source_id' => $link->id,
        ], $link, 'Public payment receipt viewed');

        $payment = $txn->payment;

        return view('payment-link.receipt', [
            'summary' => $this->links->safeSummary($link),
            'token' => $token,
            'receipt' => [
                'receipt_number' => $payment?->payment_number,
                'amount_paid' => number_format((float) ($payment?->amount ?? $txn->amount), 2),
                'currency' => $txn->currency,
                'invoice_number' => $link->invoice?->invoice_number,
                'facility' => config('app.name', 'UHMS'),
                'paid_at' => optional($payment?->paid_at ?? $txn->paid_at)->format('Y-m-d H:i'),
            ],
        ]);
    }

    /* ── internals ──────────────────────────────────────────────────── */

    private function resolve(string $token): PaymentRequestLink
    {
        return $this->links->resolve($token) ?? abort(404);
    }

    private function renderState(PaymentRequestLink $link, bool $statusPage = false)
    {
        $summary = $this->links->safeSummary($link);
        $state = $summary['state'];
        $token = $link->public_token;

        $view = match ($state) {
            'paid' => 'payment-link.success',
            'expired' => 'payment-link.expired',
            'cancelled' => 'payment-link.expired',
            'failed' => 'payment-link.failed',
            'pending' => 'payment-link.pending',
            default => $statusPage ? 'payment-link.pending' : 'payment-link.show',
        };

        return view($view, compact('summary', 'token', 'state'));
    }
}
