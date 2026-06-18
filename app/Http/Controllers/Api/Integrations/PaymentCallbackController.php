<?php

namespace App\Http\Controllers\Api\Integrations;

use App\Http\Controllers\Controller;
use App\Services\Integrations\Payment\PaymentCallbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Public, session-less payment webhook endpoint. No browser auth; provider
 * signature/secret verification happens in the adapter. Every callback is
 * stored and processed idempotently (duplicate callbacks never double-pay).
 * Never returns a stack trace.
 */
class PaymentCallbackController extends Controller
{
    public function __construct(protected PaymentCallbackService $callbacks) {}

    public function __invoke(Request $request, string $providerCode): JsonResponse
    {
        if (! $this->ipAllowed($request)) {
            return response()->json(['status' => 'forbidden'], 403);
        }

        try {
            $this->callbacks->handle(
                $providerCode,
                $request->all(),
                $request->headers->all(),
                $request->ip(),
            );
        } catch (\Throwable $e) {
            Log::warning('Payment callback processing error', ['provider' => $providerCode, 'error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'received']);
    }

    private function ipAllowed(Request $request): bool
    {
        $allow = (array) config('integrations.callback_ip_allowlist', []);
        return $allow === [] || in_array($request->ip(), $allow, true);
    }
}
