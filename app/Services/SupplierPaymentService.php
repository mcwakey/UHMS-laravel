<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\Supplier;
use App\Models\SupplierLedgerEntry;
use App\Models\SupplierPayment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Records supplier payments: creates the payment, a supplier-ledger debit,
 * reduces open payables (FIFO) and posts Dr Supplier Payables / Cr Cash|Bank.
 * Reversal is controlled (journal reversal + offsetting ledger credit).
 */
class SupplierPaymentService
{
    public function __construct(
        protected SupplierLedgerService $ledger,
        protected SupplierPayableService $payables,
        protected SupplierAccountingPostingService $posting,
    ) {}

    public function record(Supplier $supplier, array $data): SupplierPayment
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Payment amount must be greater than zero.']);
        }

        $method = in_array($data['payment_method'] ?? 'cash', ['cash', 'bank', 'mobile_money'], true)
            ? $data['payment_method']
            : 'cash';

        $outstanding = $this->ledger->balance($supplier);
        if ($amount > $outstanding + 0.01) {
            throw ValidationException::withMessages([
                'amount' => "Payment ({$amount}) exceeds the supplier outstanding balance (".number_format($outstanding, 2)."). Supplier advances/prepayments are not supported (TODO).",
            ]);
        }

        return DB::transaction(function () use ($supplier, $data, $amount, $method) {
            $payment = SupplierPayment::create([
                'payment_number' => SupplierPayment::generateNumber(),
                'supplier_id' => $supplier->id,
                'supplier_payable_id' => $data['supplier_payable_id'] ?? null,
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'amount' => $amount,
                'payment_method' => $method,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // Supplier ledger debit (reduces what the facility owes).
            $ledgerEntry = $this->ledger->recordEntry(
                supplier: $supplier,
                entryType: SupplierLedgerEntry::TYPE_PAYMENT,
                debit: $amount,
                credit: 0,
                description: "Supplier payment {$payment->payment_number}",
                sourceType: SupplierPayment::class,
                sourceId: $payment->id,
            );
            $payment->forceFill(['supplier_ledger_entry_id' => $ledgerEntry->id])->save();

            // Reduce open payables FIFO.
            $this->payables->applyPaymentFifo($supplier, $amount);

            // Post Dr Supplier Payables / Cr Cash|Bank|Mobile Money.
            $this->posting->postSupplierPayment($payment);

            $this->log('SUPPLIER_PAYMENT_RECORDED', $payment, [
                'supplier_id' => $supplier->id,
                'supplier_payment_id' => $payment->id,
                'supplier_ledger_entry_id' => $ledgerEntry->id,
                'amount' => $amount,
                'payment_method' => $method,
            ]);

            return $payment->fresh();
        });
    }

    public function reverse(SupplierPayment $payment, string $reason, User $user): SupplierPayment
    {
        if ($payment->isReversed()) {
            throw ValidationException::withMessages(['payment' => 'This payment has already been reversed.']);
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'A reversal reason is required.']);
        }

        return DB::transaction(function () use ($payment, $reason, $user) {
            $supplier = $payment->supplier;
            $amount = round((float) $payment->amount, 2);

            // Reverse the journal entry (creates a controlled reversal entry).
            $this->posting->reverseSupplierPayment($payment, $reason, $user);

            // Offset the supplier ledger: credit re-instates what we owe.
            $this->ledger->recordEntry(
                supplier: $supplier,
                entryType: SupplierLedgerEntry::TYPE_ADJUSTMENT,
                debit: 0,
                credit: $amount,
                description: "Reversal of supplier payment {$payment->payment_number}: {$reason}",
                sourceType: SupplierPayment::class,
                sourceId: $payment->id,
            );

            // Restore payable balances.
            $this->payables->restoreFromPaymentReversal($supplier, $amount);

            $this->log('SUPPLIER_PAYMENT_REVERSED', $payment, [
                'supplier_id' => $supplier->id,
                'supplier_payment_id' => $payment->id,
                'amount' => $amount,
                'reason' => $reason,
                'severity' => LogSeverity::WARNING,
            ]);

            return $payment->fresh();
        });
    }

    private function log(string $action, SupplierPayment $payment, array $context): void
    {
        try {
            app(ActivityLogService::class)->log(
                LogModule::SUPPLIER_LEDGER,
                $action,
                array_merge(['severity' => LogSeverity::NOTICE], $context),
                $payment,
                str_replace('_', ' ', ucfirst(strtolower($action))),
            );
        } catch (Throwable) {
            // Logging must never break a supplier payment.
        }
    }
}
