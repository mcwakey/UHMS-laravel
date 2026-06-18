<?php

namespace App\Http\Controllers\Admin\Integrations;

use App\Http\Controllers\Controller;
use App\Models\PaymentProviderTransaction;
use App\Services\Integrations\Payment\PaymentGatewayService;
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
}
