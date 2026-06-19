<?php

namespace App\Http\Controllers\Admin\Integrations;

use App\Enums\LogModule;
use App\Http\Controllers\Controller;
use App\Models\PaymentProviderTransaction;
use App\Services\ActivityLogService;
use App\Services\Integrations\Payment\PaymentGatewayService;
use App\Services\Integrations\Payment\PaymentReconciliationService;
use Illuminate\Http\Request;

class PaymentReconciliationController extends Controller
{
    public function __construct(
        protected PaymentReconciliationService $reconciliation,
        protected PaymentGatewayService $gateway,
        protected ActivityLogService $logger,
    ) {}

    public function index(Request $request)
    {
        $metrics = $this->reconciliation->metrics();
        $transactions = $this->reconciliation->query($request->only([
            'provider_code', 'status', 'from', 'to', 'reference', 'payer_phone', 'amount', 'patient_id', 'invoice_number', 'linked',
        ]));
        $staleMinutes = $this->reconciliation->staleThresholdMinutes();

        $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_RECONCILIATION_VIEWED', [
            'metadata' => ['filters' => array_filter($request->only(['provider_code', 'status', 'linked']))],
        ], null, 'Payment reconciliation viewed');

        return view('admin.integrations.payments.reconciliation.index', compact('metrics', 'transactions', 'staleMinutes'));
    }

    /** Manual verify / recheck — always via PaymentVerificationService. */
    public function recheck(PaymentProviderTransaction $transaction)
    {
        $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_TRANSACTION_RECHECK_REQUESTED', [
            'source_type' => 'payment_provider_transaction', 'source_id' => $transaction->id,
        ], $transaction, 'Payment transaction recheck requested');

        $transaction = $this->gateway->verify($transaction);

        $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_TRANSACTION_RECHECK_COMPLETED', [
            'source_type' => 'payment_provider_transaction', 'source_id' => $transaction->id,
            'metadata' => ['status' => $transaction->status],
        ], $transaction, 'Payment transaction recheck completed');

        return back()->with(
            $transaction->isSuccessful() ? 'success' : 'info',
            $transaction->isSuccessful() ? __('integrations.flash.payment_verified') : __('integrations.flash.payment_not_confirmed'),
        );
    }

    /** Stream a CSV of the filtered reconciliation transactions. */
    public function export(Request $request)
    {
        $filters = $request->only(['provider_code', 'status', 'from', 'to', 'reference', 'payer_phone', 'amount', 'patient_id', 'invoice_number', 'linked']);

        $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_RECONCILIATION_EXPORTED', [
            'metadata' => ['filters' => array_filter($filters)],
        ], null, 'Payment reconciliation exported');

        $filename = 'payment-reconciliation-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($filters) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['reference', 'provider', 'amount', 'currency', 'status', 'provider_status', 'invoice', 'uhms_payment_id', 'created_at']);

            $this->reconciliation->query($filters)->getCollection()->each(function ($txn) use ($out) {
                fputcsv($out, [
                    $txn->payment_reference,
                    $txn->provider_code,
                    (float) $txn->amount,
                    $txn->currency,
                    $txn->status,
                    $txn->provider_status,
                    $txn->invoice?->invoice_number,
                    $txn->uhms_payment_id,
                    optional($txn->created_at)->toDateTimeString(),
                ]);
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function markExpired(PaymentProviderTransaction $transaction)
    {
        if (! $transaction->isSuccessful() && ! $transaction->hasUhmsPayment()) {
            $transaction->update(['status' => PaymentProviderTransaction::STATUS_EXPIRED, 'expired_at' => now()]);
            $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_TRANSACTION_MARKED_EXPIRED', [
                'source_type' => 'payment_provider_transaction', 'source_id' => $transaction->id,
            ], $transaction, 'Provider transaction marked expired');
        }

        return back()->with('success', __('payments.gateway.marked_expired'));
    }

    public function cancel(PaymentProviderTransaction $transaction)
    {
        if (! $transaction->isSuccessful() && ! $transaction->hasUhmsPayment()) {
            $transaction->update(['status' => PaymentProviderTransaction::STATUS_CANCELLED, 'cancelled_at' => now()]);
        }

        return back()->with('success', __('payments.gateway.cancelled'));
    }
}
