<?php

namespace App\Exceptions;

use App\Services\Billing\BillingPolicyDecision;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Thrown by PaymentGateService when a service may not be rendered yet because of
 * the billing payment policy (e.g. an unpaid OPD consultation fee).
 *
 * Renders as a friendly message — JSON 422 for API/AJAX, a flashed error +
 * redirect back for web — never a raw stack trace (consistent with the Phase 4
 * error-handling contract).
 */
class BillingGateException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?BillingPolicyDecision $decision = null,
    ) {
        parent::__construct($message);
    }

    public static function fromDecision(BillingPolicyDecision $decision): self
    {
        return new self($decision->message, $decision);
    }

    public function render(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'billing_gate' => $this->decision?->toArray(),
            ], 422);
        }

        return back()->withInput()->with('error', $this->getMessage());
    }
}
