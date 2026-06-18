<?php

namespace App\Contracts\Integrations;

use App\Support\Integrations\Payment\PaymentCallbackResult;
use App\Support\Integrations\Payment\PaymentInitiationRequest;
use App\Support\Integrations\Payment\PaymentInitiationResult;
use App\Support\Integrations\Payment\PaymentRefundRequest;
use App\Support\Integrations\Payment\PaymentRefundResult;
use App\Support\Integrations\Payment\PaymentVerificationResult;
use App\Support\Integrations\ProviderTestResult;

/**
 * Contract every payment provider adapter must implement.
 */
interface PaymentProviderInterface
{
    public function code(): string;

    public function initiate(PaymentInitiationRequest $request): PaymentInitiationResult;

    public function verify(string $providerTransactionId, ?string $paymentReference = null): PaymentVerificationResult;

    public function handleCallback(array $payload, array $headers = []): PaymentCallbackResult;

    public function refund(PaymentRefundRequest $request): PaymentRefundResult;

    public function testConnection(): ProviderTestResult;
}
