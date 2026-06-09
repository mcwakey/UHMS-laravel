<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Enums\PaymentStatus;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Reverses a recorded payment in an audit-safe way.
 *
 * A reversal does NOT delete the original payment. Instead it:
 *   - creates an offsetting negative "reversal" payment,
 *   - reverses each allocation (reducing item.paid_amount),
 *   - marks the original payment status = reversed,
 *   - recalculates the invoice header + status.
 *
 * This keeps a complete history and restores the invoice balance so the
 * patient can be re-billed or refunded.
 */
class RefundService
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected ?ActivityLogService $logger = null,
    ) {
        $this->logger = $this->logger ?: app(ActivityLogService::class);
    }

    /**
     * @throws AuthorizationException
     * @throws \RuntimeException
     */
    public function reversePayment(Payment $payment, string $reason, ?User $user = null): Payment
    {
        $user ??= Auth::user();

        if ($user && method_exists($user, 'can') && ! $this->canAny($user, ['payments.refund', 'billing.refund.issue', 'billing.refund.reverse'])) {
            throw new AuthorizationException('Not authorized to reverse payments.');
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw new \RuntimeException('A reason is required to reverse a payment.');
        }

        $reversal = DB::transaction(function () use ($payment, $reason, $user) {
            $payment = Payment::with('allocations')->lockForUpdate()->findOrFail($payment->id);

            if ($payment->is_reversal) {
                throw new \RuntimeException('A reversal payment cannot itself be reversed.');
            }
            $status = $payment->status instanceof PaymentStatus
                ? $payment->status
                : PaymentStatus::tryFrom((string) $payment->status);
            if ($status === PaymentStatus::REVERSED) {
                throw new \RuntimeException("Payment {$payment->payment_number} has already been reversed.");
            }
            if ((float) $payment->amount <= 0) {
                throw new \RuntimeException('Only positive payments can be reversed.');
            }

            // Create the offsetting reversal payment.
            $reversal = Payment::create([
                'payment_number'      => Payment::generateNumber('REV', 'payments', 'payment_number'),
                'invoice_id'          => $payment->invoice_id,
                'patient_id'          => $payment->patient_id,
                'amount'              => -1 * (float) $payment->amount,
                'payment_method'      => $payment->payment_method,
                'reference_number'    => $payment->payment_number,
                'received_by'         => Auth::id(),
                'notes'               => 'Reversal of ' . $payment->payment_number . ' — ' . $reason,
                'paid_at'             => now(),
                'status'              => PaymentStatus::REVERSAL->value,
                'is_reversal'         => true,
                'reversed_payment_id' => $payment->id,
            ]);

            // Reverse each allocation: subtract from the item's paid_amount.
            foreach ($payment->allocations as $alloc) {
                PaymentAllocation::create([
                    'payment_id'      => $reversal->id,
                    'invoice_item_id' => $alloc->invoice_item_id,
                    'amount'          => -1 * (float) $alloc->amount,
                ]);

                $item = InvoiceItem::lockForUpdate()->find($alloc->invoice_item_id);
                if ($item) {
                    $item->forceFill([
                        'paid_amount' => max(0.0, round((float) $item->paid_amount - (float) $alloc->amount, 2)),
                    ])->save();
                    $item->refreshPaymentStatus();
                }
            }

            // Mark the original payment reversed.
            $payment->forceFill([
                'status'          => PaymentStatus::REVERSED->value,
                'reversed_at'     => now(),
                'reversed_by'     => Auth::id(),
                'reversal_reason' => $reason,
            ])->save();

            // Recalculate invoice header + status (moves PAID → PENDING/PARTIAL).
            $invoice = $payment->invoice()->with('items')->first();
            if ($invoice) {
                $this->invoiceService->recalculateTotals($invoice);
            }

            $this->logger?->log(LogModule::PAYMENTS, 'PAYMENT_REVERSED', [
                'payment_id' => $payment->id,
                'invoice_id' => $payment->invoice_id,
                'patient_id' => $payment->patient_id,
                'severity'   => LogSeverity::WARNING,
                'metadata'   => [
                    'original_payment_number' => $payment->payment_number,
                    'reversal_payment_number' => $reversal->payment_number,
                    'amount'                  => (float) $payment->amount,
                    'reason'                  => $reason,
                    'reversed_by'             => $user?->id,
                ],
            ], $payment, 'Payment reversed');

            $this->logger?->log(LogModule::PAYMENTS, 'REFUND_ISSUED', [
                'payment_id' => $payment->id,
                'refund_id' => $reversal->id,
                'invoice_id' => $payment->invoice_id,
                'patient_id' => $payment->patient_id,
                'severity' => LogSeverity::WARNING,
                'metadata' => [
                    'original_payment_number' => $payment->payment_number,
                    'refund_number' => $reversal->payment_number,
                    'amount' => (float) $payment->amount,
                    'reason' => $reason,
                    'approved_by' => $user?->id,
                ],
            ], $reversal, 'Refund issued through payment reversal');

            return $reversal->fresh(['invoice', 'allocations']);
        });

        app(PaymentAccountingPostingService::class)->postPayment($reversal);

        return $reversal;
    }

    private function canAny(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}
