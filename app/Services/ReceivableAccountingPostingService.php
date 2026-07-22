<?php

namespace App\Services;

use App\Services\LegacyMigration\Foundation\Runtime\OperationalEffectGate;
use App\Services\LegacyMigration\Foundation\Runtime\ProhibitedSubsystem;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\InvoiceReceivable;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class ReceivableAccountingPostingService
{
    public function __construct(
        protected JournalEntryService $journalEntryService,
        protected ReceivableAccountingService $receivables,
    ) {}

    public function postReallocation(InvoiceReceivable $from, InvoiceReceivable $to, float $amount, string $reason): ?JournalEntry
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::AccountingPosting);
        $amount = round($amount, 2);
        if ($amount <= 0) {
            return null;
        }

        $from->loadMissing('invoice');
        $to->loadMissing('invoice');

        try {
            $entry = DB::transaction(function () use ($from, $to, $amount, $reason) {
                $fromAccount = $this->receivables->accountForReceivable($from);
                $toAccount = $this->receivables->accountForReceivable($to);

                $entry = $this->journalEntryService->createDraft([
                    'entry_date' => now()->toDateString(),
                    'reference_number' => $to->invoice?->invoice_number,
                    'reference_type' => InvoiceReceivable::class,
                    'reference_id' => $to->id,
                    'source_module' => 'RECEIVABLE_REALLOCATION',
                    'allow_control_accounts' => true,
                    'description' => "Receivable reallocation {$to->invoice?->invoice_number}: {$reason}",
                    'lines' => [
                        $this->line($toAccount, 'Receivable reallocation in', $amount, 0, $to),
                        $this->line($fromAccount, 'Receivable reallocation out', 0, $amount, $from),
                    ],
                ]);

                return $this->journalEntryService->post($entry, $this->postingUser($to));
            });

            foreach ([$from, $to] as $receivable) {
                $receivable->forceFill([
                    'journal_entry_id' => $entry->id,
                    'accounting_status' => BillingAccountingPostingService::STATUS_POSTED,
                    'accounting_posted_at' => now(),
                    'accounting_error' => null,
                ])->save();
            }

            $this->log('ACCOUNTING_POSTED_FOR_RECEIVABLE_REALLOCATION', $to, [
                'journal_entry_id' => $entry->id,
                'invoice_id' => $to->invoice_id,
                'from_receivable_id' => $from->id,
                'to_receivable_id' => $to->id,
                'amount' => $amount,
                'reason' => $reason,
            ]);

            return $entry;
        } catch (Throwable $e) {
            foreach ([$from, $to] as $receivable) {
                $receivable->forceFill([
                    'accounting_status' => BillingAccountingPostingService::STATUS_FAILED,
                    'accounting_error' => mb_substr($e->getMessage(), 0, 2000),
                ])->save();
            }

            $this->log('ACCOUNTING_RECEIVABLE_REALLOCATION_FAILED', $to, [
                'severity' => LogSeverity::WARNING,
                'invoice_id' => $to->invoice_id,
                'from_receivable_id' => $from->id,
                'to_receivable_id' => $to->id,
                'amount' => $amount,
                'error' => $to->accounting_error,
            ]);

            return null;
        }
    }

    protected function line($account, string $description, float $debit, float $credit, InvoiceReceivable $receivable): array
    {
        return [
            'account_id' => $account->id,
            'description' => $description,
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
            'department_id' => null,
            'patient_id' => $receivable->patient_id,
            'visit_id' => $receivable->visit_id,
            'invoice_id' => $receivable->invoice_id,
            'sponsor_id' => $receivable->sponsor_id,
            'insurance_provider_id' => $receivable->insurance_provider_id,
            'reference_type' => InvoiceReceivable::class,
            'reference_id' => $receivable->id,
        ];
    }

    protected function postingUser(InvoiceReceivable $receivable): User
    {
        return auth()->user()
            ?? $receivable->updatedBy
            ?? $receivable->createdBy
            ?? User::query()->firstOrFail();
    }

    protected function log(string $action, InvoiceReceivable $receivable, array $context = []): void
    {
        try {
            app(ActivityLogService::class)->log(
                LogModule::ACCOUNTING,
                $action,
                array_merge(['severity' => LogSeverity::INFO], $context),
                $receivable,
                str_replace('_', ' ', ucfirst(strtolower($action)))
            );
        } catch (Throwable) {
            // Accounting posting must not fail receivable allocation.
        }
    }
}
