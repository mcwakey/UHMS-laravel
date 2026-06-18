<?php

namespace App\Http\Controllers\Api\Integrations;

use App\Http\Controllers\Controller;
use App\Services\Integrations\Sms\SmsCallbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Public, session-less SMS delivery-report endpoint. No browser auth; provider
 * verification happens in the adapter. Every callback is stored and processed
 * idempotently. Never returns a stack trace.
 */
class SmsCallbackController extends Controller
{
    public function __construct(protected SmsCallbackService $callbacks) {}

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
            Log::warning('SMS callback processing error', ['provider' => $providerCode, 'error' => $e->getMessage()]);
        }

        // Always acknowledge so the provider does not endlessly retry; the row is
        // stored regardless and can be reprocessed internally.
        return response()->json(['status' => 'received']);
    }

    private function ipAllowed(Request $request): bool
    {
        $allow = (array) config('integrations.callback_ip_allowlist', []);
        return $allow === [] || in_array($request->ip(), $allow, true);
    }
}
