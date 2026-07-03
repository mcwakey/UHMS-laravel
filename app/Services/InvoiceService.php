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
            $actorId = Auth::id() ?? $visit->created_by;

            return Invoice::create([
                'invoice_number' => Invoice::generateNumber('INV', 'invoices', 'invoice_number'),
                'visit_id' => $visit->id,
                'patient_id' => $visit->patient_id,
                'billing_type' => $billingType,
                'subtotal' => 0,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'nhis_amount' => 0,
                'total_amount' => 0,
                'amount_paid' => 0,
                'balance' => 0,
                'status' => InvoiceStatus::DRAFT->value,
                'due_date' => now()->addDays(30),
                'notes' => null,
                'created_by' => $actorId,
            ]);
        });
    }

    /**
     * Recompute invoice header totals from its items + payments.
     *
     * Canonical formulas (UHMS billing rules):
     *   subtotal           = sum(selected_price * quantity)         [== sum(total_price)]
     *   total_discount     = sum(items.discount_amount)
     *   insurance_total    = sum(items.insurance_covered)            (insurer responsibility)
     *   total_amount       = sum(items.patient_payable)              (patient responsibility)
     *   amount_paid        = sum(items.paid_amount)                  (ONLY from real payments)
     *   balance            = sum(max(items.patient_payable - items.paid_amount, 0))
     *
     * IMPORTANT: insurance_covered is a payer responsibility, not a patient payment.
     * Does NOT modify any item rows.
     */
    public function recalculateTotals(Invoice $invoice): Invoice
    {
        $invoice->loadMissing([
            'items',
            'visit.visitInsurance.insuranceProvider',
            'visit.visitInsurance.insuranceTier',
        ]);

        if ($this->rebalanceCoverageAgainstVisitLimit($invoice)) {
            $invoice->load('items');
        }

        foreach ($invoice->items as $item) {
            app(InsuranceService::class)->syncUsageForInvoiceItem($item);
        }

        $subtotal = (float) $invoice->items->sum(fn ($i) => (float) $i->selected_price * (int) $i->quantity);
        $totalDiscount = (float) $invoice->items->sum('discount_amount');
        $insuranceCovered = (float) $invoice->items->sum('insurance_covered');
        $patientTotal = (float) $invoice->items->sum('patient_payable');
        $paidAmount = (float) $invoice->items->sum('paid_amount');
        // Non-cash adjustments (credit notes / write-offs) reduce the balance owed.
        $adjustment = (float) $invoice->adjustment_amount;
        $settlement = app(\App\Services\Billing\InvoiceItemSettlementService::class);
        $balance = max(0.0, round((float) $invoice->items->sum(
            fn ($item) => $settlement->outstandingBalance($item)
        ) - $adjustment, 2));

        $invoice->forceFill([
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($totalDiscount, 2),
            'nhis_amount' => round($insuranceCovered, 2), // info only, retained for compat
            'total_amount' => round($patientTotal, 2),
            'amount_paid' => round($paidAmount, 2),
            'balance' => round($balance, 2),
        ])->save();

        $invoice = $this->updateStatus($invoice);

        app(InvoiceReceivableService::class)->syncFromInvoice($invoice);

        if (! in_array($invoice->status?->value ?? $invoice->status, [
            InvoiceStatus::DRAFT->value,
            InvoiceStatus::CANCELLED->value,
            InvoiceStatus::REFUNDED->value,
        ], true)) {
            app(BillingAccountingPostingService::class)->postInvoice($invoice);
        }

        return $invoice->refresh();
    }

    private function rebalanceCoverageAgainstVisitLimit(Invoice $invoice): bool
    {
        $insurance = $invoice->visit?->visitInsurance;
        $provider = $insurance?->insuranceProvider;
        $tier = $insurance?->insuranceTier;

        if (! $insurance || ! $provider || $provider->is_default || ! $tier) {
            return false;
        }

        $constraints = $tier->effectiveConstraints($insurance->member_type?->value ?? 'holder');
        $limit = $constraints['per_visit_limit'] ?? null;

        if ($limit === null || (float) $limit <= 0) {
            return false;
        }

        $remaining = round((float) $limit, 2);
        $changed = false;

        foreach ($invoice->items->sortBy('id') as $item) {
            if (in_array((string) $item->payment_status, ['cancelled', 'voided'], true)) {
                continue;
            }

            $covered = round((float) $item->insurance_covered, 2);
            if ($covered <= 0.0) {
                continue;
            }

            $belongsToVisitInsurance = (int) $item->patient_insurance_id === (int) $insurance->id
                || (
                    $item->patient_insurance_id === null
                    && (int) $item->insurance_provider_id === (int) $insurance->insurance_provider_id
                );

            if (! $belongsToVisitInsurance) {
                continue;
            }

            $allowed = min($covered, max(0.0, $remaining));
            $excess = round($covered - $allowed, 2);
            $remaining = max(0.0, round($remaining - $allowed, 2));

            if ($excess <= 0.0) {
                continue;
            }

            $item->forceFill([
                'insurance_covered' => round($allowed, 2),
                'patient_payable' => round((float) $item->patient_payable + $excess, 2),
            ])->save();
            $item->refreshPaymentStatus();
            $changed = true;
        }

        return $changed;
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
        $balance = (float) $invoice->balance;
        $paid = (float) $invoice->amount_paid;
        $total = (float) $invoice->total_amount;
        $insuranceCovered = (float) $invoice->items->sum('insurance_covered');
        $grossResponsibility = round($total + $insuranceCovered, 2);

        $newStatus = match (true) {
            ! $hasItems => InvoiceStatus::DRAFT,
            $grossResponsibility <= 0 => InvoiceStatus::DRAFT,
            $total <= 0 && $insuranceCovered > 0 => InvoiceStatus::PENDING,
            $balance <= 0.0 => InvoiceStatus::PAID,
            $paid > 0.0 => InvoiceStatus::PARTIALLY_PAID,
            default => InvoiceStatus::PENDING,
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
