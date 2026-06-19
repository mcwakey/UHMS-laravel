<?php

namespace App\Http\Controllers\Admin\Integrations;

use App\Exceptions\Integrations\IntegrationException;
use App\Http\Controllers\Controller;
use App\Models\PaymentProviderTransaction;
use App\Services\Integrations\Payment\PaymentGatewayService;
use App\Services\Integrations\Payment\PaymentRefundBridgeService;
use Illuminate\Http\Request;

class PaymentRefundController extends Controller
{
    public function __construct(protected PaymentGatewayService $gateway) {}

    public function store(Request $request, PaymentProviderTransaction $transaction)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:' . (float) $transaction->amount],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->gateway->requestRefund($transaction, (float) $data['amount'], $data['reason'] ?? null);

        return back()->with('success', __('integrations.flash.refund_requested'));
    }

    /**
     * Refund-bridge entry: prevents over-refund and retains unsupported provider
     * refunds visibly. The caller supplies an already-approved UHMS refund id when
     * one exists (this does not bypass the credit-note/refund approval workflow).
     */
    public function bridge(Request $request, PaymentProviderTransaction $transaction, PaymentRefundBridgeService $bridge)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:500'],
            'uhms_refund_id' => ['nullable', 'integer'],
            'credit_note_id' => ['nullable', 'integer', 'exists:credit_notes,id'],
        ]);

        try {
            $refund = $bridge->prepare(
                $transaction,
                (float) $data['amount'],
                $data['reason'] ?? null,
                $data['uhms_refund_id'] ?? null,
                $data['credit_note_id'] ?? null,
            );
        } catch (IntegrationException $e) {
            return back()->with('error', $e->localisedMessage());
        }

        $unsupported = (bool) data_get($refund->metadata_snapshot, 'unsupported');

        return back()->with(
            $unsupported ? 'info' : 'success',
            $unsupported ? __('payments.gateway.refund_unsupported_notice') : __('integrations.flash.refund_requested'),
        );
    }
}
