<?php

namespace App\Observers;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\Payment;
use App\Services\ActivityLogService;

class PaymentObserver
{
    public $afterCommit = true;
    public function __construct(protected ActivityLogService $logger) {}

    public function created(Payment $payment): void
    {
        $this->logger->log(LogModule::PAYMENTS, 'PAYMENT_RECORDED', [
            'payment_id' => $payment->id,
            'invoice_id' => $payment->invoice_id,
            'patient_id' => $payment->patient_id,
            'metadata' => [
                'payment_number' => $payment->payment_number,
                'amount' => $payment->amount,
                'method' => $payment->payment_method,
            ],
        ], $payment, 'Payment recorded');
        $this->markStale($payment, 'payment_recorded');
    }

    public function updated(Payment $payment): void
    {
        $this->markStale($payment, 'payment_updated');
    }

    public function deleted(Payment $payment): void
    {
        $this->logger->log(LogModule::PAYMENTS, 'PAYMENT_REFUNDED', [
            'payment_id' => $payment->id,
            'invoice_id' => $payment->invoice_id,
            'patient_id' => $payment->patient_id,
            'severity' => LogSeverity::WARNING,
            'metadata' => [
                'payment_number' => $payment->payment_number,
                'amount' => $payment->amount,
            ],
        ], $payment, 'Payment refunded/deleted');
        $this->markStale($payment, 'payment_deleted');
    }

    private function markStale(Payment $payment, string $reason): void
    {
        try { if ($payment->invoice?->visit) app(\App\Services\Billing\VisitFinancialClearanceService::class)->markStale($payment->invoice->visit, $reason); } catch (\Throwable $e) { report($e); }
    }
}
