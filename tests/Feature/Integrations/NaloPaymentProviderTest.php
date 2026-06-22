<?php

namespace Tests\Feature\Integrations;

use App\Models\IntegrationProvider;
use App\Services\Integrations\Providers\Payment\NaloPaymentProvider;
use App\Support\Integrations\Payment\PaymentInitiationRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Verifies the NALOPAY adapter against a faked HTTP client (no network):
 * token → collection (with trans_hash) → status, plus callback mapping.
 */
class NaloPaymentProviderTest extends TestCase
{
    private array $creds = [
        'merchant_id' => 'MERCH',
        'basic_auth_token' => 'BASICTOKEN',
        'secret_key' => 'secret',
    ];

    private function provider(): IntegrationProvider
    {
        // Not persisted — the adapter only needs base_url + environment.
        return new IntegrationProvider([
            'module_type' => 'payment',
            'code' => 'nalo_payment',
            'name' => 'NALOPAY',
            'environment' => 'sandbox',
            'base_url' => 'https://nalo.test',
        ]);
    }

    private function adapter(): NaloPaymentProvider
    {
        return new NaloPaymentProvider($this->provider(), $this->creds);
    }

    public function test_initiate_generates_token_and_sends_correct_trans_hash(): void
    {
        Http::fake([
            'nalo.test/clientapi/generate-payment-token/' => Http::response(['success' => true, 'data' => ['token' => 'jwt-123']]),
            'nalo.test/clientapi/collection/' => Http::response([
                'success' => true, 'code' => 'PAY-CRTD-0055',
                'data' => ['order_id' => 'ORDER123', 'status' => 'PENDING', 'amount' => 0.2, 'otp_code' => 'None*252#'],
            ]),
        ]);

        $result = $this->adapter()->initiate(new PaymentInitiationRequest(
            paymentReference: 'REF-1',
            amount: 0.20,
            currency: 'GHS',
            payerName: 'Abdul Razak',
            payerPhone: '233597990630',
            paymentMethod: 'mtn_momo',
            callbackUrl: 'https://uhms.test/api/integrations/payments/nalo_payment/callback',
        ));

        $this->assertTrue($result->success);
        $this->assertSame('pending', $result->status);
        $this->assertSame('ORDER123', $result->providerTransactionId);

        $expectedHash = hash_hmac('sha256', 'MERCH' . '233597990630' . '0.20' . 'REF-1', 'secret');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/clientapi/generate-payment-token/')
                && $request->hasHeader('Authorization', 'Basic BASICTOKEN');
        });
        Http::assertSent(function ($request) use ($expectedHash) {
            if (! str_contains($request->url(), '/clientapi/collection/')) {
                return false;
            }
            $data = $request->data();
            return $request->hasHeader('token', 'jwt-123')
                && $data['trans_hash'] === $expectedHash
                && $data['service_name'] === 'MOMO_TRANSACTION'
                && $data['network'] === 'MTN'
                && $data['account_number'] === '233597990630'
                && $data['amount'] === '0.20'
                && $data['reference'] === 'REF-1';
        });
    }

    public function test_verify_maps_completed_to_paid(): void
    {
        Http::fake([
            'nalo.test/clientapi/collection-status/' => Http::response([
                'success' => true, 'code' => 'PAY-STAT-0080',
                'data' => ['status' => 'COMPLETED', 'amount' => 0.2],
            ]),
        ]);

        $result = $this->adapter()->verify('ORDER123', 'REF-1');

        $this->assertTrue($result->success);
        $this->assertSame('paid', $result->status);
        $this->assertTrue($result->isPaid);
        $this->assertSame(0.2, $result->amount);
    }

    public function test_initiate_without_credentials_is_not_configured_and_makes_no_calls(): void
    {
        Http::fake();
        $adapter = new NaloPaymentProvider($this->provider(), []); // no creds

        $result = $adapter->initiate(new PaymentInitiationRequest('REF-2', 1.0, 'GHS', payerPhone: '0244000000', paymentMethod: 'mtn_momo'));

        $this->assertFalse($result->success);
        $this->assertSame('not_configured', $result->errorCode);
        Http::assertNothingSent();
    }

    public function test_callback_maps_completed_to_paid(): void
    {
        $result = $this->adapter()->handleCallback([
            'order_id' => 'ORDER123', 'status' => 'COMPLETED', 'amount' => '50.00',
        ]);

        $this->assertSame('paid', $result->status);
        $this->assertSame('ORDER123', $result->providerTransactionId);
        $this->assertSame(50.0, $result->amount);
    }

    public function test_failed_token_blocks_initiation(): void
    {
        Http::fake([
            'nalo.test/clientapi/generate-payment-token/' => Http::response(['success' => false], 401),
        ]);

        $result = $this->adapter()->initiate(new PaymentInitiationRequest('REF-3', 1.0, 'GHS', payerPhone: '0244000000', paymentMethod: 'mtn_momo'));

        $this->assertFalse($result->success);
        $this->assertSame('auth_failed', $result->errorCode);
    }
}
