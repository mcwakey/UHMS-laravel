<?php

namespace App\Services;

use App\Enums\Accounting\JournalEntryStatus;
use App\Enums\CreditNoteType;
use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\Account;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\InvoiceDiscount;
use App\Models\InvoiceItem;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Throwable;

class BillingAccountingPostingService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_POSTED = 'posted';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REVERSED = 'reversed';

    public function __construct(
        protected JournalEntryService $journalEntryService,
        protected ReceivableAccountingService $receivables,
        protected RevenueAccountResolver $revenueResolver,
        protected AccountingSettingsService $settings,
    ) {}

    public function postInvoice(Invoice $invoice): ?JournalEntry
    {
        $invoice->loadMissing([
            'items.invoice',
            'items.serviceCatalog',
            'items.department',
            'items.product',
            'visit',
            'patient',
        ]);

        $items = $invoice->items
            ->filter(fn (InvoiceItem $item) => (float) $item->patient_payable > 0)
            ->reject(fn (InvoiceItem $item) => in_array((string) $item->accounting_status, [self::STATUS_POSTED, self::STATUS_REVERSED], true))
            ->values();

        if ($items->isEmpty()) {
            return $invoice->journalEntry;
        }

        try {
            $entry = DB::transaction(function () use ($invoice, $items) {
                $entry = $this->journalEntryService->createDraft([
                    'entry_date' => optional($invoice->created_at)->toDateString() ?: now()->toDateString(),
                    'reference_number' => $invoice->invoice_number,
                    'reference_type' => Invoice::class,
                    'reference_id' => $invoice->id,
                    'source_module' => 'BILLING',
                    'allow_control_accounts' => true,
                    'description' => "Invoice recognition {$invoice->invoice_number}",
                    'lines' => $this->invoiceLines($invoice, $items),
                ]);

                return $this->journalEntryService->post($entry, $this->postingUser($invoice));
            });

            InvoiceItem::query()
                ->whereIn('id', $items->pluck('id'))
                ->update([
                    'journal_entry_id' => $entry->id,
                    'accounting_posted_at' => now(),
                    'accounting_status' => self::STATUS_POSTED,
                    'accounting_error' => null,
                ]);

            $invoice->forceFill([
                'journal_entry_id' => $entry->id,
                'accounting_posted_at' => now(),
                'accounting_status' => self::STATUS_POSTED,
                'accounting_error' => null,
            ])->save();

            $this->log('ACCOUNTING_POSTED_FOR_INVOICE', $invoice, [
                'journal_entry_id' => $entry->id,
                'invoice_id' => $invoice->id,
                'patient_id' => $invoice->patient_id,
                'visit_id' => $invoice->visit_id,
                'item_ids' => $items->pluck('id')->all(),
            ]);

            return $entry;
        } catch (Throwable $e) {
            $this->markFailed($invoice, $items, $e);

            return null;
        }
    }

    public function postDiscount(InvoiceDiscount $discount): ?JournalEntry
    {
        $discount->loadMissing(['invoice', 'invoiceItem.invoice', 'invoiceItem.department', 'invoiceItem.serviceCatalog']);

        if ((string) $discount->accounting_status === self::STATUS_POSTED && $discount->journal_entry_id) {
            return $discount->journalEntry;
        }

        $delta = round((float) $discount->new_discount_amount - (float) $discount->old_discount_amount, 2);
        if (abs($delta) < 0.005 || (string) $discount->invoiceItem?->accounting_status !== self::STATUS_POSTED) {
            $discount->forceFill([
                'accounting_posted_at' => now(),
                'accounting_status' => self::STATUS_POSTED,
                'accounting_error' => null,
            ])->save();

            return null;
        }

        try {
            $entry = DB::transaction(function () use ($discount, $delta) {
                $amount = abs($delta);
                $discountAccount = $this->requiredAccount('default_discount_account_id');
                $receivableAccount = $this->receivables->accountForDiscount($discount);
                $lines = $delta > 0
                    ? [
                        $this->line($discountAccount, "Discount {$discount->invoice?->invoice_number}", $amount, 0, $discount),
                        $this->line($receivableAccount, "Receivable reduction {$discount->invoice?->invoice_number}", 0, $amount, $discount),
                    ]
                    : [
                        $this->line($receivableAccount, "Discount reversal {$discount->invoice?->invoice_number}", $amount, 0, $discount),
                        $this->line($discountAccount, "Discount reversal {$discount->invoice?->invoice_number}", 0, $amount, $discount),
                    ];

                $entry = $this->journalEntryService->createDraft([
                    'entry_date' => optional($discount->performed_at)->toDateString() ?: now()->toDateString(),
                    'reference_number' => $discount->invoice?->invoice_number,
                    'reference_type' => InvoiceDiscount::class,
                    'reference_id' => $discount->id,
                    'source_module' => 'BILLING_DISCOUNT',
                    'allow_control_accounts' => true,
                    'description' => "Discount posting {$discount->invoice?->invoice_number}",
                    'lines' => $lines,
                ]);

                return $this->journalEntryService->post($entry, $this->postingUser($discount));
            });

            $discount->forceFill([
                'journal_entry_id' => $entry->id,
                'accounting_posted_at' => now(),
                'accounting_status' => self::STATUS_POSTED,
                'accounting_error' => null,
            ])->save();

            $this->log('ACCOUNTING_POSTED_FOR_DISCOUNT', $discount, [
                'journal_entry_id' => $entry->id,
                'invoice_id' => $discount->invoice_id,
                'invoice_item_id' => $discount->invoice_item_id,
                'discount_delta' => $delta,
            ]);

            return $entry;
        } catch (Throwable $e) {
            $this->markFailed($discount, collect([$discount]), $e);

            return null;
        }
    }

    public function postCreditNote(CreditNote $creditNote): ?JournalEntry
    {
        $creditNote->loadMissing(['invoice.visit', 'invoice.patient']);

        if ($creditNote->status !== 'issued') {
            return $creditNote->journalEntry;
        }

        if ((string) $creditNote->accounting_status === self::STATUS_POSTED && $creditNote->journal_entry_id) {
            return $creditNote->journalEntry;
        }

        try {
            $entry = DB::transaction(function () use ($creditNote) {
                $amount = round((float) $creditNote->amount, 2);
                $adjustmentAccount = $creditNote->type === CreditNoteType::WRITE_OFF
                    ? $this->requiredAccount('default_write_off_account_id')
                    : $this->requiredAccount('default_credit_note_account_id');
                $receivableAccount = $this->receivables->accountForCreditNote($creditNote);

                $entry = $this->journalEntryService->createDraft([
                    'entry_date' => optional($creditNote->created_at)->toDateString() ?: now()->toDateString(),
                    'reference_number' => $creditNote->credit_note_number,
                    'reference_type' => CreditNote::class,
                    'reference_id' => $creditNote->id,
                    'source_module' => $creditNote->type === CreditNoteType::WRITE_OFF ? 'BILLING_WRITE_OFF' : 'BILLING_CREDIT_NOTE',
                    'allow_control_accounts' => true,
                    'description' => "{$creditNote->type->label()} {$creditNote->credit_note_number}",
                    'lines' => [
                        $this->line($adjustmentAccount, "{$creditNote->type->label()} {$creditNote->credit_note_number}", $amount, 0, $creditNote),
                        $this->line($receivableAccount, "Receivable reduction {$creditNote->invoice?->invoice_number}", 0, $amount, $creditNote),
                    ],
                ]);

                return $this->journalEntryService->post($entry, $this->postingUser($creditNote));
            });

            $creditNote->forceFill([
                'journal_entry_id' => $entry->id,
                'accounting_posted_at' => now(),
                'accounting_status' => self::STATUS_POSTED,
                'accounting_error' => null,
            ])->save();

            $this->log('ACCOUNTING_POSTED_FOR_CREDIT_NOTE', $creditNote, [
                'journal_entry_id' => $entry->id,
                'invoice_id' => $creditNote->invoice_id,
                'credit_note_id' => $creditNote->id,
                'type' => $creditNote->type?->value,
                'amount' => (float) $creditNote->amount,
            ]);

            return $entry;
        } catch (Throwable $e) {
            $this->markFailed($creditNote, collect([$creditNote]), $e);

            return null;
        }
    }

    public function reverseInvoice(Invoice $invoice, string $reason): void
    {
        $invoice->loadMissing('items.journalEntry');
        $journals = $invoice->items
            ->pluck('journalEntry')
            ->filter(fn (?JournalEntry $entry) => $entry && $entry->status === JournalEntryStatus::POSTED)
            ->unique('id')
            ->values();

        foreach ($journals as $journal) {
            try {
                $this->journalEntryService->reverse($journal, $reason, $this->postingUser($invoice));
            } catch (Throwable) {
                continue;
            }
        }

        InvoiceItem::query()
            ->where('invoice_id', $invoice->id)
            ->where('accounting_status', self::STATUS_POSTED)
            ->update(['accounting_status' => self::STATUS_REVERSED]);

        $invoice->forceFill([
            'accounting_status' => self::STATUS_REVERSED,
            'accounting_error' => null,
        ])->save();
    }

    public function reverseCreditNote(CreditNote $creditNote, string $reason): void
    {
        $creditNote->loadMissing('journalEntry');

        if (! $creditNote->journalEntry || $creditNote->journalEntry->status !== JournalEntryStatus::POSTED) {
            return;
        }

        try {
            $this->journalEntryService->reverse($creditNote->journalEntry, $reason, $this->postingUser($creditNote));
            $creditNote->forceFill([
                'accounting_status' => self::STATUS_REVERSED,
                'accounting_error' => null,
            ])->save();
        } catch (Throwable $e) {
            $this->markFailed($creditNote, collect([$creditNote]), $e);
        }
    }

    protected function invoiceLines(Invoice $invoice, Collection $items): array
    {
        $debits = [];
        $credits = [];

        foreach ($items as $item) {
            $amount = round((float) $item->patient_payable, 2);
            if ($amount <= 0) {
                continue;
            }

            $receivable = $this->receivables->accountForInvoiceItem($item);
            $revenue = $this->revenueResolver->accountForInvoiceItem($item);

            $debitKey = implode('|', [
                $receivable->id,
                $invoice->sponsor_id,
                $item->insurance_provider_id,
            ]);
            $creditKey = implode('|', [
                $revenue->id,
                $item->department_id,
            ]);

            $debits[$debitKey] ??= $this->line($receivable, "Receivable {$invoice->invoice_number}", 0, 0, $item);
            $credits[$creditKey] ??= $this->line($revenue, "Revenue {$invoice->invoice_number}", 0, 0, $item);

            $debits[$debitKey]['debit'] = round($debits[$debitKey]['debit'] + $amount, 2);
            $credits[$creditKey]['credit'] = round($credits[$creditKey]['credit'] + $amount, 2);
        }

        return array_values(array_merge($debits, $credits));
    }

    protected function line(Account $account, string $description, float $debit, float $credit, Invoice|InvoiceItem|InvoiceDiscount|CreditNote $source): array
    {
        $invoice = $source instanceof Invoice
            ? $source
            : ($source instanceof InvoiceItem ? $source->invoice : $source->invoice);
        $item = $source instanceof InvoiceItem ? $source : ($source instanceof InvoiceDiscount ? $source->invoiceItem : null);

        return [
            'account_id' => $account->id,
            'description' => $description,
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
            'department_id' => $item?->department_id,
            'patient_id' => $source->patient_id ?? $invoice?->patient_id,
            'visit_id' => $source->visit_id ?? $invoice?->visit_id,
            'invoice_id' => $invoice?->id,
            'sponsor_id' => $invoice?->sponsor_id,
            'insurance_provider_id' => $item?->insurance_provider_id ?? $invoice?->visit?->visitInsurance?->insurance_provider_id,
            'reference_type' => $source::class,
            'reference_id' => $source->id,
        ];
    }

    protected function requiredAccount(string $key): Account
    {
        $account = $this->settings->account($key);

        if (! $account) {
            throw new \RuntimeException("Accounting setting {$key} is not configured.");
        }

        if (! $account->is_active) {
            throw new \RuntimeException("Accounting account {$account->display_name} is inactive.");
        }

        return $account;
    }

    protected function markFailed(object $source, iterable $children, Throwable $e): void
    {
        $message = mb_substr($e->getMessage(), 0, 2000);

        foreach ($children as $child) {
            if (method_exists($child, 'forceFill')) {
                $child->forceFill([
                    'accounting_status' => self::STATUS_FAILED,
                    'accounting_error' => $message,
                ])->save();
            }
        }

        if (method_exists($source, 'forceFill')) {
            $source->forceFill([
                'accounting_status' => self::STATUS_FAILED,
                'accounting_error' => $message,
            ])->save();
        }

        $this->log('ACCOUNTING_POSTING_FAILED', $source, [
            'severity' => LogSeverity::WARNING,
            'error' => $message,
        ]);
    }

    protected function postingUser(object $source): User
    {
        return auth()->user()
            ?? ($source->createdBy ?? null)
            ?? ($source->receivedBy ?? null)
            ?? ($source->issuedBy ?? null)
            ?? User::query()->firstOrFail();
    }

    protected function log(string $action, object $subject, array $context = []): void
    {
        try {
            app(ActivityLogService::class)->log(
                LogModule::ACCOUNTING,
                $action,
                array_merge(['severity' => LogSeverity::INFO], $context),
                $subject,
                str_replace('_', ' ', ucfirst(strtolower($action)))
            );
        } catch (Throwable) {
            // Accounting posting must not fail the originating clinical/billing flow because logging failed.
        }
    }
}
