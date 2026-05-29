<?php

namespace App\Listeners;

use App\Enums\NotificationModule;
use App\Enums\NotificationPriority;
use App\Events\PaymentRecorded;
use App\Services\NotificationService;

class NotifyAccountants
{
    public function __construct(protected NotificationService $notifier) {}

    public function handle(PaymentRecorded $event): void
    {
        $payment = $event->payment;
        $this->notifier->notifyRole('Accountant', [
            'module' => NotificationModule::BILLING,
            'priority' => NotificationPriority::NORMAL,
            'title' => 'Payment recorded',
            'message' => sprintf('Payment %s for ₵%s received.', $payment->payment_number, $payment->amount),
            'url' => url("/admin/payments/{$payment->id}"),
            'source_type' => 'payment',
            'source_id' => $payment->id,
            'patient_id' => $payment->patient_id,
            'invoice_id' => $payment->invoice_id,
        ]);
    }
}
