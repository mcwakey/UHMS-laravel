<?php

namespace App\Services\Integrations\Payment;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\PaymentProviderAttempt;
use App\Models\PaymentProviderTransaction;
use App\Services\ActivityLogService;
use App\Services\Integrations\IntegrationProviderRegistry;
use App\Services\PaymentService;
use App\Support\Integrations\Payment\PaymentVerificationResult;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Verifies a provider transaction and — only when verified, amount- and
 * currency-matched — creates the UHMS payment through the existing
 * PaymentService (which posts to accounting and fires PaymentRecorded).
 *
 * Guarantees:
 *   • A provider transaction never becomes a UHMS payment until verified.
 *   • A transaction already linked to a UHMS payment is never re-paid
 *     (idempotent — duplicate callbacks cannot duplicate payments).
 *   • A reported amount/currency that does not match blocks payment creation.
 */
class PaymentVerificationService
{
    public function __construct(
        protected IntegrationProviderRegistry $registry,
        protected PaymentService $payments,
        protected ActivityLogService $logger,
    ) {}

    /**
     * @param PaymentVerificationResult|null $injected Callback-derived result,
     *        used when the provider cannot be polled (we still validate amount).
     */
    public function verify(PaymentProviderTransaction $transaction, ?PaymentVerificationResult $injected = null): PaymentProviderTransaction
    {
        return DB::transaction(function () use ($transaction, $injected) {
            /** @var PaymentProviderTransaction $txn */
            $txn = PaymentProviderTransaction::query()->lockForUpdate()->find($transaction->id);

            // Idempotency: already a UHMS payment — never create a second one.
            if ($txn->hasUhmsPayment()) {
                return $txn;
            }

            $result = $injected ?? $this->callProvider($txn);

            $this->recordAttempt($txn, $injected ? PaymentProviderAttempt::TYPE_CALLBACK_PROCESS : PaymentProviderAttempt::TYPE_VERIFY, $result);

            $updates = ['updated_by' => Auth::id(), 'verified_at' => now()];
            if ($result->providerTransactionId && ! $txn->provider_transaction_id) {
                $updates['provider_transaction_id'] = $result->providerTransactionId;
            }
            if ($result->providerStatus) {
                $updates['provider_status'] = $result->providerStatus;
            }

            // Not paid → record the (non-success) status, never touch the invoice.
            if (! $result->isPaid) {
                $updates['status'] = match ($result->status) {
                    'failed' => PaymentProviderTransaction::STATUS_FAILED,
                    'cancelled' => PaymentProviderTransaction::STATUS_CANCELLED,
                    'expired' => PaymentProviderTransaction::STATUS_EXPIRED,
                    default => PaymentProviderTransaction::STATUS_PENDING,
                };
                if ($updates['status'] === PaymentProviderTransaction::STATUS_FAILED) {
                    $updates['failed_at'] = now();
                    $updates['error_code'] = $result->errorCode;
                    $updates['error_message'] = $result->errorMessage;
                }
                $txn->update($updates);
                return $txn->refresh();
            }

            // Paid → validate amount + currency BEFORE creating a UHMS payment.
            $amountOk = $result->amount === null || abs((float) $result->amount - (float) $txn->amount) <= 0.01;
            $currencyOk = $result->currency === null || strtoupper((string) $result->currency) === strtoupper((string) $txn->currency);

            if (! $amountOk || ! $currencyOk) {
                $txn->update(array_merge($updates, [
                    'status' => PaymentProviderTransaction::STATUS_FAILED,
                    'failed_at' => now(),
                    'error_code' => 'amount_mismatch',
                    'error_message' => 'Reported amount/currency did not match the requested amount.',
                ]));
                $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_TRANSACTION_FAILED', [
                    'source_type' => 'payment_provider_transaction',
                    'source_id' => $txn->id,
                    'severity' => LogSeverity::WARNING,
                    'metadata' => [
                        'reason' => 'amount_or_currency_mismatch',
                        'expected_amount' => (float) $txn->amount,
                        'reported_amount' => $result->amount,
                        'expected_currency' => $txn->currency,
                        'reported_currency' => $result->currency,
                    ],
                ], $txn, 'Provider payment blocked: amount/currency mismatch');
                return $txn->refresh();
            }

            $updates['status'] = PaymentProviderTransaction::STATUS_VERIFIED;
            $updates['paid_at'] = $txn->paid_at ?? now();
            $txn->update($updates);

            $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_TRANSACTION_VERIFIED', [
                'source_type' => 'payment_provider_transaction',
                'source_id' => $txn->id,
                'invoice_id' => $txn->invoice_id,
                'metadata' => ['amount' => (float) $txn->amount, 'currency' => $txn->currency],
            ], $txn, 'Provider payment verified');

            if ($txn->invoice_id) {
                $this->createUhmsPayment($txn->refresh());
            }

            return $txn->refresh();
        });
    }

    private function callProvider(PaymentProviderTransaction $txn): PaymentVerificationResult
    {
        try {
            $adapter = $this->registry->makePayment($txn->provider);
            return $adapter->verify($txn->provider_transaction_id ?: $txn->payment_reference, $txn->payment_reference);
        } catch (\Throwable $e) {
            return new PaymentVerificationResult(false, 'pending', errorCode: 'verify_error',
                errorMessage: 'Verification call failed.');
        }
    }

    private function createUhmsPayment(PaymentProviderTransaction $txn): void
    {
        $invoice = Invoice::find($txn->invoice_id);
        if (! $invoice) {
            return;
        }

        try {
            $payment = $this->payments->recordPayment($invoice, [
                'amount' => (float) $txn->amount,
                'payment_method' => $this->mapPaymentMethod($txn),
                'reference_number' => $txn->payment_reference,
                'notes' => 'Online payment via ' . $txn->provider_code,
                'paid_at' => $txn->paid_at ?? now(),
            ]);

            $txn->update([
                'uhms_payment_id' => $payment->id,
                'status' => PaymentProviderTransaction::STATUS_VERIFIED,
            ]);

            $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_TRANSACTION_PAYMENT_CREATED', [
                'source_type' => 'payment_provider_transaction',
                'source_id' => $txn->id,
                'payment_id' => $payment->id,
                'invoice_id' => $txn->invoice_id,
                'metadata' => ['amount' => (float) $txn->amount],
            ], $txn, 'UHMS payment created from verified provider transaction');
        } catch (\Throwable $e) {
            // Retain the failure; the transaction is verified but allocation needs
            // manual attention. Never crash the verify/callback path.
            $txn->update([
                'error_code' => 'payment_creation_failed',
                'error_message' => 'Verified, but the UHMS payment could not be created automatically.',
            ]);
            $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_TRANSACTION_FAILED', [
                'source_type' => 'payment_provider_transaction',
                'source_id' => $txn->id,
                'severity' => LogSeverity::WARNING,
                'metadata' => ['reason' => 'payment_creation_failed'],
            ], $txn, 'Verified provider payment could not create a UHMS payment');
        }
    }

    private function mapPaymentMethod(PaymentProviderTransaction $txn): string
    {
        $candidate = (string) ($txn->payment_method ?: '');
        return PaymentMethod::tryFrom($candidate)?->value ?? PaymentMethod::MTN_MOMO->value;
    }

    private function recordAttempt(PaymentProviderTransaction $txn, string $type, PaymentVerificationResult $result): void
    {
        $txn->attempts()->create([
            'provider_id' => $txn->provider_id,
            'attempt_type' => $type,
            'status' => $result->success ? PaymentProviderAttempt::STATUS_SUCCEEDED : PaymentProviderAttempt::STATUS_FAILED,
            'response_payload_snapshot' => $result->raw,
            'http_status' => $result->httpStatus,
            'error_code' => $result->errorCode,
            'error_message' => $result->errorMessage,
            'started_at' => now(),
            'completed_at' => now(),
        ]);
    }
}
