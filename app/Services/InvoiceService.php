<?php

namespace App\Services;

use App\Enums\BillingType;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Visit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Owns the lifecycle of a Visit's single invoice:
 *  - getOrCreateVisitInvoice — enforces "one active invoice per visit".
 *  - recalculateTotals       — sums items + payments into invoice header.
 *  - updateStatus            — derives invoice status from balance.
 */
class InvoiceService
{
    /**
     * Returns the (single) active invoice for a visit, creating an empty one
     * if necessary. "Active" = not in [cancelled, refunded].
     *
     * Idempotent: if a non-cancelled invoice already exists for the visit,
     * it is returned unchanged.
     */
    public function getOrCreateVisitInvoice(Visit $visit): Invoice
    {
        $existing = Invoice::where('visit_id', $visit->id)
            ->whereNotIn('status', [
                InvoiceStatus::CANCELLED->value,
                InvoiceStatus::REFUNDED->value,
            ])
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($visit) {
            // Double-check inside the transaction.
            $existing = Invoice::where('visit_id', $visit->id)
                ->whereNotIn('status', [
                    InvoiceStatus::CANCELLED->value,
                    InvoiceStatus::REFUNDED->value,
                ])
                ->lockForUpdate()
                ->first();
            if ($existing) {
                return $existing;
            }

            $billingType = $this->resolveBillingType($visit);

            return Invoice::create([
                'invoice_number'  => Invoice::generateNumber('INV', 'invoices', 'invoice_number'),
                'visit_id'        => $visit->id,
                'patient_id'      => $visit->patient_id,
                'billing_type'    => $billingType,
                'subtotal'        => 0,
                'tax_amount'      => 0,
                'discount_amount' => 0,
                'nhis_amount'     => 0,
                'total_amount'    => 0,
                'amount_paid'     => 0,
                'balance'         => 0,
                'status'          => InvoiceStatus::DRAFT->value,
                'due_date'        => now()->addDays(30),
                'notes'           => null,
                'created_by'      => Auth::id(),
            ]);
        });
    }

    /**
     * Recompute invoice header totals from its items + payments.
     * Does NOT touch existing item rows.
     */
    public function recalculateTotals(Invoice $invoice): Invoice
    {
        $invoice->loadMissing('items');

        $subtotal          = (float) $invoice->items->sum('total_price');
        $insuranceCovered  = (float) $invoice->items->sum('insurance_covered');
        $patientPayable    = (float) $invoice->items->sum('patient_payable');
        $paidPerLine       = (float) $invoice->items->sum('paid_amount');

        // amount_paid = insurance-covered + actual line payments received from patient
        $amountPaid = round($insuranceCovered + $paidPerLine, 2);
        $totalAmount = round($subtotal, 2);
        $balance     = max(0.0, round($totalAmount - $amountPaid, 2));

        $invoice->forceFill([
            'subtotal'     => $subtotal,
            'nhis_amount'  => $insuranceCovered,
            'total_amount' => $totalAmount,
            'amount_paid'  => $amountPaid,
            'balance'      => $balance,
        ])->save();

        return $this->updateStatus($invoice);
    }

    /**
     * Derive invoice status from current balance and item statuses.
     */
    public function updateStatus(Invoice $invoice): Invoice
    {
        // Don't override terminal states.
        if (in_array($invoice->status?->value ?? $invoice->status, [
            InvoiceStatus::CANCELLED->value,
            InvoiceStatus::REFUNDED->value,
        ], true)) {
            return $invoice;
        }

        $invoice->loadMissing('items');
        $hasItems = $invoice->items->isNotEmpty();
        $balance  = (float) $invoice->balance;
        $paid     = (float) $invoice->amount_paid;
        $total    = (float) $invoice->total_amount;

        $newStatus = match (true) {
            ! $hasItems         => InvoiceStatus::DRAFT,
            $total <= 0         => InvoiceStatus::DRAFT,
            $balance <= 0.0     => InvoiceStatus::PAID,
            $paid > 0.0         => InvoiceStatus::PARTIALLY_PAID,
            default             => InvoiceStatus::PENDING,
        };

        if (($invoice->status?->value ?? $invoice->status) !== $newStatus->value) {
            $invoice->forceFill(['status' => $newStatus->value])->save();
        }

        return $invoice;
    }

    /**
     * Decide billing_type for a freshly created invoice based on visit insurance.
     */
    public function resolveBillingType(Visit $visit): string
    {
        $visit->loadMissing('visitInsurance.insuranceProvider');
        $ins = $visit->visitInsurance;
        if ($ins && $ins->is_active && ! $ins->is_expired && $ins->insuranceProvider && ! $ins->insuranceProvider->is_default) {
            return BillingType::INSURANCE->value;
        }
        return BillingType::CASH->value;
    }
}
