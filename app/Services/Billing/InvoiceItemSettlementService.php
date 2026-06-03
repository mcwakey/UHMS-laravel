<?php

namespace App\Services\Billing;

use App\Models\InvoiceItem;

/**
 * Determines the *intrinsic* settlement of a single invoice line — i.e. whether
 * money or coverage has settled it — using the columns UHMS already maintains
 * (payment_status, patient_payable, paid_amount, balance, insurance_covered).
 *
 * It deliberately does NOT consider billing overrides (deferred settlement,
 * credit) — that contextual layer belongs to BillingPolicyService, so this
 * service stays a pure, side-effect-free read of the line's own financial state.
 */
class InvoiceItemSettlementService
{
    private const EPS = 0.009;

    public const UNBILLED = 'UNBILLED';
    public const BILLED_UNPAID = 'BILLED_UNPAID';
    public const PARTIALLY_PAID = 'PARTIALLY_PAID';
    public const PAID = 'PAID';
    public const COVERED_BY_INSURANCE = 'COVERED_BY_INSURANCE';
    public const WAIVED = 'WAIVED';
    public const CANCELLED = 'CANCELLED';

    public function settlementStatus(InvoiceItem $item): string
    {
        $ps = strtolower((string) ($item->payment_status ?? ''));

        if (in_array($ps, ['cancelled', 'voided'], true)) {
            return self::CANCELLED;
        }
        if ($ps === 'waived') {
            return self::WAIVED;
        }

        $payable = (float) ($item->patient_payable ?? $item->balance ?? 0);
        $paid = (float) ($item->paid_amount ?? 0);
        $balance = $item->balance !== null
            ? (float) $item->balance
            : max(0.0, round($payable - $paid, 2));
        $covered = (float) ($item->insurance_covered ?? 0);

        // Fully settled: explicit paid flag or zero remaining balance.
        if ($ps === 'paid' || $balance <= self::EPS) {
            if ($payable <= self::EPS && $covered > self::EPS) {
                return self::COVERED_BY_INSURANCE;
            }

            return self::PAID;
        }

        // Nothing left for the patient to pay because insurance covers it all.
        if ($payable <= self::EPS && $covered > self::EPS) {
            return self::COVERED_BY_INSURANCE;
        }

        if ($paid > self::EPS) {
            return self::PARTIALLY_PAID;
        }

        return self::BILLED_UNPAID;
    }

    public function isPaid(InvoiceItem $item): bool
    {
        return $this->settlementStatus($item) === self::PAID;
    }

    public function isCovered(InvoiceItem $item): bool
    {
        return $this->settlementStatus($item) === self::COVERED_BY_INSURANCE;
    }

    public function isWaived(InvoiceItem $item): bool
    {
        return $this->settlementStatus($item) === self::WAIVED;
    }

    public function isPartiallyPaid(InvoiceItem $item): bool
    {
        return $this->settlementStatus($item) === self::PARTIALLY_PAID;
    }

    /**
     * The line needs no cash from the patient right now: it is paid, fully
     * insurance-covered, or waived.
     */
    public function canProceedWithoutCashPayment(InvoiceItem $item): bool
    {
        return in_array($this->settlementStatus($item), [
            self::PAID,
            self::COVERED_BY_INSURANCE,
            self::WAIVED,
        ], true);
    }

    /**
     * Patient-facing outstanding balance on the line.
     */
    public function outstandingBalance(InvoiceItem $item): float
    {
        if ($item->balance !== null) {
            return max(0.0, (float) $item->balance);
        }
        $payable = (float) ($item->patient_payable ?? 0);
        $paid = (float) ($item->paid_amount ?? 0);

        return max(0.0, round($payable - $paid, 2));
    }
}
