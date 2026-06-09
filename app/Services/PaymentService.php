<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\LogModule;
use App\Events\PaymentRecorded;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Records payments against invoice items via explicit allocations.
 * Supports: full-invoice payments, selected-line payments, partial payments,
 * and multi-line splits in a single transaction.
 */
class PaymentService
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected VisitWorkflowService $visitWorkflowService,
        protected InvoiceReceivableService $receivableService,
        protected ?ActivityLogService $logger = null,
    ) {
        $this->logger = $this->logger ?: app(ActivityLogService::class);
    }

    /**
     * @param array $data           ['amount', 'payment_method', 'reference_number'?, 'notes'?, 'paid_at'?]
     * @param array $allocations    [['invoice_item_id' => int, 'amount' => float], ...]
     *                              If omitted/empty, payment is auto-distributed across
     *                              unpaid items in id order.
     */
    public function recordPayment(Invoice $invoice, array $data, array $allocations = []): Payment
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw new \RuntimeException('Payment amount must be greater than zero.');
        }

        $payment = DB::transaction(function () use ($invoice, $data, $allocations, $amount) {
            $invoice = $invoice->fresh(['items']);

            if (in_array($invoice->status?->value ?? $invoice->status, [
                InvoiceStatus::PAID->value,
                InvoiceStatus::CANCELLED->value,
                InvoiceStatus::REFUNDED->value,
            ], true)) {
                throw new \RuntimeException('Cannot record payment on this invoice.');
            }

            $receivable = $this->receivableService->resolvePaymentReceivable($invoice, $data, $amount);

            // Build normalized allocations.
            $normalized = $this->normalizeAllocations($invoice, $allocations, $amount);

            // Validate total matches amount (within rounding tolerance).
            $allocSum = round(array_sum(array_column($normalized, 'amount')), 2);
            if (abs($allocSum - $amount) > 0.01) {
                throw new \RuntimeException(
                    "Allocation total (₵{$allocSum}) does not equal payment amount (₵{$amount})."
                );
            }

            // Validate no overpayment per line.
            foreach ($normalized as $alloc) {
                $item = $invoice->items->firstWhere('id', $alloc['invoice_item_id']);
                if (! $item) {
                    throw new \RuntimeException("Invoice item #{$alloc['invoice_item_id']} not on invoice {$invoice->invoice_number}.");
                }
                if (in_array($item->payment_status, ['cancelled', 'voided'], true)) {
                    throw new \RuntimeException("Cannot pay against {$item->payment_status} line {$item->description}.");
                }
                if (round((float) $item->balance, 2) + 0.01 < round((float) $alloc['amount'], 2)) {
                    throw new \RuntimeException(
                        "Allocation ₵{$alloc['amount']} exceeds remaining balance ₵{$item->balance} on '{$item->description}'."
                    );
                }
                if ((float) $alloc['amount'] <= 0) {
                    throw new \RuntimeException('Allocation amounts must be positive.');
                }
            }

            // Create the payment.
            $payment = Payment::create([
                'payment_number'   => Payment::generateNumber('PAY', 'payments', 'payment_number'),
                'invoice_id'       => $invoice->id,
                'invoice_receivable_id' => $receivable?->id,
                'patient_id'       => $invoice->patient_id,
                'payer_type'       => $receivable?->payer_type ?? 'patient',
                'payer_id'         => $receivable?->payer_id ?? $invoice->patient_id,
                'insurance_provider_id' => $receivable?->insurance_provider_id,
                'sponsor_id'       => $receivable?->sponsor_id,
                'corporate_client_id' => $receivable?->corporate_client_id,
                'claim_id'         => $receivable?->claim_id,
                'amount'           => $amount,
                'payment_method'   => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'received_by'      => Auth::id(),
                'notes'            => $data['notes'] ?? null,
                'paid_at'          => $data['paid_at'] ?? now(),
            ]);

            // Persist allocations + update each item.
            foreach ($normalized as $alloc) {
                PaymentAllocation::create([
                    'payment_id'      => $payment->id,
                    'invoice_item_id' => $alloc['invoice_item_id'],
                    'amount'          => $alloc['amount'],
                ]);

                $item = InvoiceItem::lockForUpdate()->find($alloc['invoice_item_id']);
                $item->forceFill([
                    'paid_amount' => round((float) $item->paid_amount + (float) $alloc['amount'], 2),
                ])->save();
                $item->refreshPaymentStatus();
            }

            // Recalculate invoice header + status.
            $invoice->refresh();
            $this->invoiceService->recalculateTotals($invoice);
            $invoice->refresh();
            $this->receivableService->applyPayment($payment->refresh());

            // Payment settlement is a billing event only; clinical visit/session
            // completion must remain an explicit workflow action.
            if (($invoice->status?->value ?? $invoice->status) === InvoiceStatus::PAID->value) {
                $visit = $invoice->visit;
                if ($visit) {
                    $this->visitWorkflowService->recordPaymentCompleted($visit);
                }
            }

            return $payment->load(['invoice', 'patient', 'receivable', 'allocations.invoiceItem']);
        });

        app(PaymentAccountingPostingService::class)->postPayment($payment);

        PaymentRecorded::dispatch($payment);

        $this->logger?->log(LogModule::PAYMENTS, 'PAYMENT_RECORDED', [
            'payment_id' => $payment->id,
            'invoice_id' => $payment->invoice_id,
            'patient_id' => $payment->patient_id,
            'metadata' => [
                'amount' => $payment->amount,
                'method' => $payment->payment_method,
                'allocations' => $payment->allocations->map(fn ($a) => [
                    'invoice_item_id' => $a->invoice_item_id,
                    'amount' => $a->amount,
                ])->all(),
                'payer_type' => $payment->payer_type,
                'invoice_receivable_id' => $payment->invoice_receivable_id,
            ],
        ], $payment, 'Payment recorded');

        return $payment;
    }

    /**
     * Auto-distribute a flat payment amount across unpaid lines (oldest first).
     */
    private function normalizeAllocations(Invoice $invoice, array $allocations, float $amount): array
    {
        $items = $invoice->items
            ->whereNotIn('payment_status', ['paid', 'cancelled', 'voided'])
            ->where(fn ($i) => (float) $i->balance > 0)
            ->sortBy('id')
            ->values();

        if (! empty($allocations)) {
            return array_values(array_map(fn ($a) => [
                'invoice_item_id' => (int) ($a['invoice_item_id'] ?? $a['id'] ?? 0),
                'amount'          => round((float) ($a['amount'] ?? 0), 2),
            ], $allocations));
        }

        // Auto-distribute.
        $remaining = $amount;
        $out       = [];
        foreach ($items as $item) {
            if ($remaining <= 0) break;
            $apply = min($remaining, (float) $item->balance);
            if ($apply <= 0) continue;
            $apply = round($apply, 2);
            $out[] = ['invoice_item_id' => $item->id, 'amount' => $apply];
            $remaining = round($remaining - $apply, 2);
        }
        if ($remaining > 0.01) {
            throw new \RuntimeException("Payment amount ₵{$amount} exceeds total outstanding balance.");
        }
        return $out;
    }
}
