<?php

namespace App\Services;

use App\Enums\CreditNoteType;
use App\Enums\PaymentStatus;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\InvoiceDiscount;
use App\Models\Payment;
use Illuminate\Support\Collection;

class InvoiceBalanceService
{
    public function summary(Invoice $invoice): array
    {
        $invoice->loadMissing(['items', 'payments', 'creditNotes']);

        $creditNotes = $invoice->creditNotes->where('status', 'issued');
        $positivePayments = $invoice->payments->filter(fn (Payment $payment) => (float) $payment->amount > 0 && ! $payment->is_reversal);
        $refunds = $invoice->payments->filter(fn (Payment $payment) => $payment->is_reversal || (float) $payment->amount < 0);

        $gross = round((float) $invoice->subtotal, 2);
        $discounts = round((float) $invoice->discount_amount, 2);
        $creditNoteTotal = round((float) $creditNotes
            ->filter(fn (CreditNote $note) => $note->type === CreditNoteType::CREDIT_NOTE)
            ->sum('amount'), 2);
        $writeOffTotal = round((float) $creditNotes
            ->filter(fn (CreditNote $note) => $note->type === CreditNoteType::WRITE_OFF)
            ->sum('amount'), 2);
        $paymentTotal = round((float) $positivePayments->sum(fn (Payment $payment) => abs((float) $payment->amount)), 2);
        $refundTotal = round((float) $refunds->sum(fn (Payment $payment) => abs((float) $payment->amount)), 2);
        $formulaBalance = max(0.0, round($gross - $discounts - $creditNoteTotal - $writeOffTotal - $paymentTotal + $refundTotal, 2));

        return [
            'gross_total' => $gross,
            'discounts' => $discounts,
            'credit_notes' => $creditNoteTotal,
            'write_offs' => $writeOffTotal,
            'payments' => $paymentTotal,
            'refunds' => $refundTotal,
            'outstanding_balance' => round((float) $invoice->balance, 2),
            'formula_balance' => $formulaBalance,
            'formula_matches_invoice' => abs($formulaBalance - (float) $invoice->balance) < 0.01,
            'accounting_status' => $invoice->accounting_status ?: 'pending',
        ];
    }

    public function history(Invoice $invoice): Collection
    {
        $invoice->loadMissing([
            'payments.receivedBy',
            'payments.journalEntry',
            'payments.reversalJournalEntry',
            'discountEvents.performedBy',
            'discountEvents.journalEntry',
            'discountEvents.reversalJournalEntry',
            'creditNotes.issuedBy',
            'creditNotes.originalCreditNote',
            'creditNotes.reversal',
            'creditNotes.cancelledBy',
            'creditNotes.journalEntry',
            'creditNotes.reversalJournalEntry',
        ]);

        return collect()
            ->merge($invoice->payments->map(fn (Payment $payment) => $this->paymentRow($payment)))
            ->merge($invoice->discountEvents->map(fn (InvoiceDiscount $discount) => $this->discountRow($discount)))
            ->merge($invoice->creditNotes->map(fn (CreditNote $creditNote) => $this->creditNoteRow($creditNote)))
            ->sortByDesc('date_sort')
            ->values();
    }

    protected function paymentRow(Payment $payment): array
    {
        $isRefund = $payment->is_reversal || (float) $payment->amount < 0;

        return [
            'date' => $payment->paid_at,
            'date_sort' => optional($payment->paid_at)->timestamp ?? optional($payment->created_at)->timestamp ?? 0,
            'type' => $isRefund ? 'Refund' : 'Payment',
            'reference' => $payment->payment_number,
            'amount' => abs((float) $payment->amount),
            'reason' => $isRefund ? ($payment->reversal_reason ?: $payment->notes) : $payment->notes,
            'status' => $payment->status instanceof PaymentStatus ? $payment->status->label() : ucfirst((string) ($payment->status ?: 'active')),
            'actor' => $payment->receivedBy?->name,
            'journal' => $payment->journalEntry,
            'reversal_journal' => $payment->reversalJournalEntry,
            'accounting_status' => $payment->accounting_status ?: 'pending',
            'accounting_error' => $payment->accounting_error,
            'retry_source_type' => 'payment',
            'retry_source_id' => $payment->id,
            'can_reverse' => $payment->can_reverse,
            'reversal_kind' => 'payment',
            'reverse_url' => $payment->can_reverse
                ? route('admin.billing.payments.reverse', $payment)
                : null,
            'badge' => $isRefund ? 'danger' : 'success',
        ];
    }

    protected function discountRow(InvoiceDiscount $discount): array
    {
        $delta = round((float) $discount->new_discount_amount - (float) $discount->old_discount_amount, 2);
        $isReversal = $delta < 0;

        return [
            'date' => $discount->performed_at,
            'date_sort' => optional($discount->performed_at)->timestamp ?? optional($discount->created_at)->timestamp ?? 0,
            'type' => $isReversal ? 'Discount Reversal' : 'Discount',
            'reference' => 'DISC-' . $discount->id,
            'amount' => abs($delta),
            'reason' => $discount->reason,
            'status' => $discount->reversed_at ? 'Reversed' : ucfirst((string) $discount->action),
            'actor' => $discount->performedBy?->name,
            'journal' => $discount->journalEntry,
            'reversal_journal' => $discount->reversalJournalEntry,
            'accounting_status' => $discount->accounting_status ?: 'pending',
            'accounting_error' => $discount->accounting_error,
            'retry_source_type' => 'discount',
            'retry_source_id' => $discount->id,
            'can_reverse' => false,
            'reversal_kind' => null,
            'reverse_url' => null,
            'badge' => $isReversal ? 'warning' : ($discount->is_override ? 'danger' : 'warning'),
        ];
    }

    protected function creditNoteRow(CreditNote $creditNote): array
    {
        $isWriteOff = $creditNote->type === CreditNoteType::WRITE_OFF;
        $isReversal = $creditNote->is_reversal;
        $isReversed = $creditNote->status === 'reversed';

        return [
            'date' => $creditNote->created_at,
            'date_sort' => optional($creditNote->created_at)->timestamp ?? 0,
            'type' => $isReversal
                ? ($isWriteOff ? 'Write-off Reversal' : 'Credit Note Reversal')
                : ($isWriteOff ? 'Write-off' : 'Credit Note'),
            'reference' => $creditNote->credit_note_number,
            'amount' => (float) $creditNote->amount,
            'reason' => $isReversal
                ? ($creditNote->notes ?: $creditNote->reason)
                : ($isReversed ? ($creditNote->reversal_reason ?: $creditNote->reason) : $creditNote->reason),
            'status' => $isReversal ? 'Reversal' : ucfirst($creditNote->status),
            'actor' => $creditNote->issuedBy?->name,
            'journal' => $creditNote->journalEntry,
            'reversal_journal' => $creditNote->reversalJournalEntry,
            'accounting_status' => $creditNote->accounting_status ?: 'pending',
            'accounting_error' => $creditNote->accounting_error,
            'retry_source_type' => 'credit_note',
            'retry_source_id' => $creditNote->id,
            'can_reverse' => ! $isReversal && ! $isReversed && $creditNote->status === 'issued',
            'reversal_kind' => $isWriteOff ? 'write_off' : 'credit_note',
            'reverse_url' => ! $isReversal && ! $isReversed && $creditNote->status === 'issued'
                ? route('admin.billing.credit-notes.reverse', $creditNote)
                : null,
            'badge' => $isReversal ? 'warning' : ($isWriteOff ? 'dark' : 'info'),
        ];
    }
}
