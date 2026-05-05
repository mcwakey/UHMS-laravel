<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Payment $payment
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $patient = $this->payment->patient;

        return [
            'type' => 'payment',
            'action' => 'received',
            'message' => "Payment of GHS " . number_format($this->payment->amount, 2) . " received from {$patient->full_name}",
            'payment_id' => $this->payment->id,
            'invoice_id' => $this->payment->invoice_id,
            'patient_id' => $patient->id,
            'patient_name' => $patient->full_name,
            'amount' => $this->payment->amount,
            'url' => route('admin.billing.invoices.show', $this->payment->invoice_id),
            'icon' => 'ti-cash',
            'color' => 'warning',
        ];
    }
}
