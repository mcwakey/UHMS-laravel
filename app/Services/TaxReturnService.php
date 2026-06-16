<?php

namespace App\Services;

use App\Models\Account;
use App\Models\TaxLedgerEntry;
use App\Models\TaxPayment;
use App\Models\TaxPaymentAllocation;
use App\Models\TaxReturn;
use App\Models\TaxReturnPeriod;
use App\Models\TaxType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaxReturnService
{
    public function __construct(
        protected TaxLedgerService $ledger,
        protected JournalEntryService $journals,
    ) {}

    public function prepare(string $taxCode, string $periodStart, string $periodEnd, User $user): TaxReturn
    {
        $this->ledger->ensureDefaults();
        $taxType = TaxType::where('code', strtoupper($taxCode))->firstOrFail();

        return DB::transaction(function () use ($taxType, $periodStart, $periodEnd, $user) {
            $period = TaxReturnPeriod::firstOrCreate([
                'tax_type_id' => $taxType->id,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ]);

            $return = TaxReturn::create([
                'tax_return_period_id' => $period->id,
                'return_number' => $this->nextReturnNumber($taxType->code),
                'status' => TaxReturn::STATUS_PREPARED,
                'prepared_by' => $user->id,
                'prepared_at' => now(),
            ]);

            $entries = TaxLedgerEntry::query()
                ->where('tax_type_id', $taxType->id)
                ->whereDate('entry_date', '>=', $periodStart)
                ->whereDate('entry_date', '<=', $periodEnd)
                ->orderBy('entry_date')
                ->get();

            $total = 0.0;
            foreach ($entries as $entry) {
                $amount = round((float) $entry->tax_amount, 2);
                $return->lines()->create([
                    'tax_ledger_entry_id' => $entry->id,
                    'line_type' => $entry->direction,
                    'tax_base_amount' => $entry->tax_base_amount,
                    'tax_amount' => $amount,
                ]);
                $total = round($total + $amount, 2);
            }

            $return->update(['total_tax_due' => $total, 'balance_due' => $total]);

            return $return->refresh()->load('lines');
        });
    }

    public function approve(TaxReturn $return, User $user): TaxReturn
    {
        if ($return->status !== TaxReturn::STATUS_PREPARED) {
            throw ValidationException::withMessages(['return' => 'Only prepared tax returns can be approved.']);
        }

        $return->update([
            'status' => TaxReturn::STATUS_APPROVED,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return $return->refresh();
    }

    public function recordPayment(string $taxCode, array $data, User $user): TaxPayment
    {
        $this->ledger->ensureDefaults();
        $taxType = TaxType::where('code', strtoupper($taxCode))->firstOrFail();
        $amount = round((float) $data['amount'], 2);
        $paymentAccount = Account::findOrFail($data['payment_account_id']);

        return DB::transaction(function () use ($taxType, $data, $user, $amount, $paymentAccount) {
            $payableAccount = \App\Models\TaxAccountMapping::query()
                ->where('tax_type_id', $taxType->id)
                ->where('is_active', true)
                ->latest('effective_from')
                ->value('payable_account_id');

            if (! $payableAccount) {
                throw ValidationException::withMessages(['mapping' => 'No payable account mapping exists for this tax type.']);
            }

            $journal = $this->journals->createDraft([
                'entry_date' => $data['payment_date'],
                'description' => $taxType->code.' tax payment',
                'source_module' => 'TAX_PAYMENT',
                'reference_type' => TaxPayment::class,
                'reference_id' => null,
                'lines' => [
                    ['account_id' => $payableAccount, 'debit' => $amount, 'credit' => 0, 'description' => 'Tax liability settlement'],
                    ['account_id' => $paymentAccount->id, 'debit' => 0, 'credit' => $amount, 'description' => 'Tax payment from bank/cash'],
                ],
            ]);
            $journal = $this->journals->post($journal, $user);

            $payment = TaxPayment::create([
                'tax_type_id' => $taxType->id,
                'payment_number' => $this->nextPaymentNumber($taxType->code),
                'payment_date' => $data['payment_date'],
                'amount' => $amount,
                'unallocated_amount' => $amount,
                'payment_account_id' => $paymentAccount->id,
                'status' => 'posted',
                'journal_entry_id' => $journal->id,
                'created_by' => $user->id,
                'notes' => $data['notes'] ?? null,
            ]);

            return $payment->refresh();
        });
    }

    public function allocatePayment(TaxPayment $payment, TaxReturn $return, float $amount, User $user): TaxPaymentAllocation
    {
        $payment->refresh();
        $return->refresh();
        $amount = round($amount, 2);
        if ($amount <= 0 || $amount > (float) $payment->unallocated_amount) {
            throw ValidationException::withMessages(['amount' => 'Payment allocation exceeds the unallocated payment balance.']);
        }
        if ($amount > (float) $return->balance_due) {
            throw ValidationException::withMessages(['amount' => 'Payment allocation exceeds the return balance due.']);
        }

        return DB::transaction(function () use ($payment, $return, $amount, $user) {
            $allocation = TaxPaymentAllocation::create([
                'tax_payment_id' => $payment->id,
                'tax_return_id' => $return->id,
                'amount' => $amount,
                'created_by' => $user->id,
            ]);
            $payment->update(['unallocated_amount' => round((float) $payment->unallocated_amount - $amount, 2)]);
            $return->update([
                'total_payments' => round((float) $return->total_payments + $amount, 2),
                'balance_due' => round((float) $return->balance_due - $amount, 2),
            ]);
            $this->ledger->applyPaymentToOpenLedger($return->period->taxType->code, $amount, $user);

            return $allocation;
        });
    }

    private function nextReturnNumber(string $code): string
    {
        $prefix = 'TR-'.$code.'-'.now()->format('Y').'-';
        $last = TaxReturn::where('return_number', 'like', $prefix.'%')->orderByDesc('return_number')->value('return_number');
        $next = $last ? ((int) substr($last, -6)) + 1 : 1;

        return $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    private function nextPaymentNumber(string $code): string
    {
        $prefix = 'TP-'.$code.'-'.now()->format('Y').'-';
        $last = TaxPayment::where('payment_number', 'like', $prefix.'%')->orderByDesc('payment_number')->value('payment_number');
        $next = $last ? ((int) substr($last, -6)) + 1 : 1;

        return $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
