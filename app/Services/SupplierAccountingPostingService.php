<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\GoodsReceivedNote;
use App\Models\JournalEntry;
use App\Models\PurchaseReturn;
use App\Models\SupplierPayable;
use App\Models\SupplierPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Posts supplier-side (AP) journal entries. Mirrors the receivables posting
 * services. Each source posts once (idempotent on accounting_status/journal_entry_id).
 *
 *   Goods receipt   : Dr Inventory (by category)  / Cr Supplier Payables
 *   Supplier payment: Dr Supplier Payables        / Cr Cash|Bank|Mobile Money
 *   Purchase return : Dr Supplier Payables        / Cr Inventory
 */
class SupplierAccountingPostingService
{
    public const POSTED = BillingAccountingPostingService::STATUS_POSTED;
    public const FAILED = BillingAccountingPostingService::STATUS_FAILED;
    public const REVERSED = BillingAccountingPostingService::STATUS_REVERSED;

    public function __construct(
        protected JournalEntryService $journal,
        protected SupplierAccountingService $accounts,
    ) {}

    /**
     * @param  array<int,array{account:\App\Models\Account,amount:float}>  $inventoryLines
     */
    public function postGoodsReceipt(GoodsReceivedNote $grn, SupplierPayable $payable, array $inventoryLines): ?JournalEntry
    {
        if ((string) $grn->accounting_status === self::POSTED && $grn->journal_entry_id) {
            return $grn->journalEntry; // already posted — idempotent
        }

        $total = round(array_sum(array_map(fn ($l) => (float) $l['amount'], $inventoryLines)), 2);
        if ($total <= 0) {
            return null;
        }

        try {
            $entry = DB::transaction(function () use ($grn, $inventoryLines, $total) {
                $payableAccount = $this->accounts->supplierPayableAccount();

                $lines = [];
                foreach ($inventoryLines as $line) {
                    $amount = round((float) $line['amount'], 2);
                    if ($amount <= 0) {
                        continue;
                    }
                    $lines[] = $this->line($line['account']->id, 'Goods received — inventory', $amount, 0, GoodsReceivedNote::class, $grn->id);
                }
                $lines[] = $this->line($payableAccount->id, 'Supplier payable', 0, $total, GoodsReceivedNote::class, $grn->id);

                $draft = $this->journal->createDraft([
                    'entry_date' => optional($grn->received_date)->toDateString() ?? now()->toDateString(),
                    'reference_number' => $grn->grn_number,
                    'reference_type' => GoodsReceivedNote::class,
                    'reference_id' => $grn->id,
                    'source_module' => 'GOODS_RECEIPT',
                    'allow_control_accounts' => true,
                    'description' => "Goods received {$grn->grn_number}",
                    'lines' => $lines,
                ]);

                return $this->journal->post($draft, $this->user());
            });

            $this->markPosted($grn, $entry);
            $this->markPosted($payable, $entry);

            $this->log('ACCOUNTING_POSTED_FOR_GOODS_RECEIPT', $grn, [
                'supplier_id' => $grn->supplier_id,
                'goods_receipt_id' => $grn->id,
                'journal_entry_id' => $entry->id,
                'amount' => $total,
            ]);

            return $entry;
        } catch (Throwable $e) {
            $this->markFailed($grn, $e);
            $this->markFailed($payable, $e);
            $this->log('ACCOUNTING_POSTING_FAILED', $grn, [
                'severity' => LogSeverity::WARNING,
                'goods_receipt_id' => $grn->id,
                'error' => mb_substr($e->getMessage(), 0, 500),
            ]);

            return null;
        }
    }

    public function postSupplierPayment(SupplierPayment $payment): ?JournalEntry
    {
        if ((string) $payment->accounting_status === self::POSTED && $payment->journal_entry_id) {
            return $payment->journalEntry;
        }

        $amount = round((float) $payment->amount, 2);
        if ($amount <= 0) {
            return null;
        }

        try {
            $entry = DB::transaction(function () use ($payment, $amount) {
                $payableAccount = $this->accounts->supplierPayableAccount();
                $cashAccount = $this->accounts->paymentAccount((string) $payment->payment_method);

                $draft = $this->journal->createDraft([
                    'entry_date' => optional($payment->payment_date)->toDateString() ?? now()->toDateString(),
                    'reference_number' => $payment->payment_number,
                    'reference_type' => SupplierPayment::class,
                    'reference_id' => $payment->id,
                    'source_module' => 'SUPPLIER_PAYMENT',
                    'allow_control_accounts' => true,
                    'description' => "Supplier payment {$payment->payment_number}",
                    'lines' => [
                        $this->line($payableAccount->id, 'Supplier payable settled', $amount, 0, SupplierPayment::class, $payment->id),
                        $this->line($cashAccount->id, 'Payment to supplier', 0, $amount, SupplierPayment::class, $payment->id),
                    ],
                ]);

                return $this->journal->post($draft, $this->user());
            });

            $this->markPosted($payment, $entry);
            $this->log('ACCOUNTING_POSTED_FOR_SUPPLIER_PAYMENT', $payment, [
                'supplier_id' => $payment->supplier_id,
                'supplier_payment_id' => $payment->id,
                'journal_entry_id' => $entry->id,
                'amount' => $amount,
            ]);

            return $entry;
        } catch (Throwable $e) {
            $this->markFailed($payment, $e);
            $this->log('ACCOUNTING_POSTING_FAILED', $payment, [
                'severity' => LogSeverity::WARNING,
                'supplier_payment_id' => $payment->id,
                'error' => mb_substr($e->getMessage(), 0, 500),
            ]);

            return null;
        }
    }

    /**
     * @param  array<int,array{account:\App\Models\Account,amount:float}>  $inventoryLines
     */
    public function postPurchaseReturn(PurchaseReturn $return, array $inventoryLines): ?JournalEntry
    {
        if ((string) $return->accounting_status === self::POSTED && $return->journal_entry_id) {
            return $return->journalEntry;
        }

        $total = round(array_sum(array_map(fn ($l) => (float) $l['amount'], $inventoryLines)), 2);
        if ($total <= 0) {
            return null;
        }

        try {
            $entry = DB::transaction(function () use ($return, $inventoryLines, $total) {
                $payableAccount = $this->accounts->supplierPayableAccount();

                $lines = [$this->line($payableAccount->id, 'Supplier payable reduced (return)', $total, 0, PurchaseReturn::class, $return->id)];
                foreach ($inventoryLines as $line) {
                    $amount = round((float) $line['amount'], 2);
                    if ($amount <= 0) {
                        continue;
                    }
                    $lines[] = $this->line($line['account']->id, 'Inventory returned to supplier', 0, $amount, PurchaseReturn::class, $return->id);
                }

                $draft = $this->journal->createDraft([
                    'entry_date' => optional($return->return_date)->toDateString() ?? now()->toDateString(),
                    'reference_number' => $return->return_number,
                    'reference_type' => PurchaseReturn::class,
                    'reference_id' => $return->id,
                    'source_module' => 'PURCHASE_RETURN',
                    'allow_control_accounts' => true,
                    'description' => "Purchase return {$return->return_number}",
                    'lines' => $lines,
                ]);

                return $this->journal->post($draft, $this->user());
            });

            $this->markPosted($return, $entry);
            $this->log('ACCOUNTING_POSTED_FOR_PURCHASE_RETURN', $return, [
                'supplier_id' => $return->supplier_id,
                'purchase_return_id' => $return->id,
                'journal_entry_id' => $entry->id,
                'amount' => $total,
            ]);

            return $entry;
        } catch (Throwable $e) {
            $this->markFailed($return, $e);
            $this->log('ACCOUNTING_POSTING_FAILED', $return, [
                'severity' => LogSeverity::WARNING,
                'purchase_return_id' => $return->id,
                'error' => mb_substr($e->getMessage(), 0, 500),
            ]);

            return null;
        }
    }

    public function reverseSupplierPayment(SupplierPayment $payment, string $reason, User $user): ?JournalEntry
    {
        if (! $payment->journal_entry_id || $payment->reversal_journal_entry_id) {
            return null; // nothing posted, or already reversed
        }

        $original = JournalEntry::find($payment->journal_entry_id);
        if (! $original) {
            return null;
        }

        $reversal = $this->journal->reverse($original, $reason, $user);

        $payment->forceFill([
            'reversal_journal_entry_id' => $reversal->id,
            'accounting_status' => self::REVERSED,
            'reversed_at' => now(),
            'reversed_by' => $user->id,
            'reversal_reason' => $reason,
        ])->save();

        $this->log('ACCOUNTING_REVERSAL_CREATED', $payment, [
            'supplier_payment_id' => $payment->id,
            'journal_entry_id' => $reversal->id,
            'reason' => $reason,
        ]);

        return $reversal;
    }

    private function line(int $accountId, string $description, float $debit, float $credit, string $refType, int $refId): array
    {
        return [
            'account_id' => $accountId,
            'description' => $description,
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
            'reference_type' => $refType,
            'reference_id' => $refId,
        ];
    }

    private function markPosted(Model $model, JournalEntry $entry): void
    {
        $model->forceFill([
            'journal_entry_id' => $entry->id,
            'accounting_status' => self::POSTED,
            'accounting_posted_at' => now(),
            'accounting_error' => null,
        ])->save();
    }

    private function markFailed(Model $model, Throwable $e): void
    {
        $model->forceFill([
            'accounting_status' => self::FAILED,
            'accounting_error' => mb_substr($e->getMessage(), 0, 2000),
        ])->save();
    }

    private function user(): User
    {
        return auth()->user() ?? User::query()->firstOrFail();
    }

    private function log(string $action, Model $source, array $context = []): void
    {
        try {
            app(ActivityLogService::class)->log(
                LogModule::ACCOUNTING,
                $action,
                array_merge(['severity' => LogSeverity::INFO], $context),
                $source,
                str_replace('_', ' ', ucfirst(strtolower($action))),
            );
        } catch (Throwable) {
            // Accounting posting must never break the operational action.
        }
    }
}
