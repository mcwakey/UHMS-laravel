<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class PaymentAccountingPostingService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_POSTED = 'posted';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REVERSED = 'reversed';

    public function __construct(
        protected JournalEntryService $journalEntryService,
        protected ReceivableAccountingService $receivables,
        protected PaymentAccountResolver $paymentResolver,
    ) {}

    public function postPayment(Payment $payment): ?JournalEntry
    {
        $payment->loadMissing([
            'invoice.visit',
            'invoice.patient',
            'invoice.visit.visitInsurance',
            'receivable',
            'receivedBy',
            'originalPayment',
        ]);

        if ((string) $payment->accounting_status === self::STATUS_POSTED && $payment->journal_entry_id) {
            return $payment->journalEntry;
        }

        if ((string) $payment->accounting_status === self::STATUS_REVERSED) {
            return $payment->journalEntry;
        }

        $amount = abs(round((float) $payment->amount, 2));
        if ($amount <= 0) {
            $payment->forceFill([
                'accounting_status' => self::STATUS_POSTED,
                'accounting_posted_at' => now(),
                'accounting_error' => null,
            ])->save();

            return null;
        }

        try {
            $entry = DB::transaction(function () use ($payment, $amount) {
                $paymentAccount = $this->paymentResolver->accountFor($payment);
                $receivableAccount = $this->receivables->accountForPayment($payment);
                $isReversal = (bool) $payment->is_reversal || (float) $payment->amount < 0;

                $lines = $isReversal
                    ? [
                        $this->line($receivableAccount, "Payment reversal {$payment->payment_number}", $amount, 0, $payment),
                        $this->line($paymentAccount, "Payment reversal {$payment->payment_number}", 0, $amount, $payment),
                    ]
                    : [
                        $this->line($paymentAccount, "Payment received {$payment->payment_number}", $amount, 0, $payment),
                        $this->line($receivableAccount, "Receivable collection {$payment->invoice?->invoice_number}", 0, $amount, $payment),
                    ];

                $entry = $this->journalEntryService->createDraft([
                    'entry_date' => optional($payment->paid_at)->toDateString() ?: now()->toDateString(),
                    'reference_number' => $payment->payment_number,
                    'reference_type' => Payment::class,
                    'reference_id' => $payment->id,
                    'source_module' => $isReversal ? 'PAYMENT_REVERSAL' : 'PAYMENT',
                    'allow_control_accounts' => true,
                    'description' => ($isReversal ? 'Payment reversal ' : 'Payment posting ') . $payment->payment_number,
                    'lines' => $lines,
                ]);

                return $this->journalEntryService->post($entry, $this->postingUser($payment));
            });

            $payment->forceFill([
                'journal_entry_id' => $entry->id,
                'accounting_posted_at' => now(),
                'accounting_status' => self::STATUS_POSTED,
                'accounting_error' => null,
            ])->save();

            if ($payment->is_reversal && $payment->originalPayment) {
                $payment->originalPayment->forceFill([
                    'reversal_journal_entry_id' => $entry->id,
                    'accounting_status' => self::STATUS_REVERSED,
                    'accounting_error' => null,
                ])->save();
            }

            $this->log(((bool) $payment->is_reversal || (float) $payment->amount < 0) ? 'ACCOUNTING_POSTED_FOR_REFUND' : 'ACCOUNTING_POSTED_FOR_PAYMENT', $payment, [
                'journal_entry_id' => $entry->id,
                'payment_id' => $payment->id,
                'invoice_id' => $payment->invoice_id,
                'patient_id' => $payment->patient_id,
                'amount' => (float) $payment->amount,
            ]);

            return $entry;
        } catch (Throwable $e) {
            $this->markFailed($payment, $e);

            return null;
        }
    }

    protected function line($account, string $description, float $debit, float $credit, Payment $payment): array
    {
        return [
            'account_id' => $account->id,
            'description' => $description,
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
            'department_id' => null,
            'patient_id' => $payment->patient_id,
            'visit_id' => $payment->invoice?->visit_id,
            'invoice_id' => $payment->invoice_id,
            'sponsor_id' => $payment->sponsor_id ?? $payment->invoice?->sponsor_id,
            'insurance_provider_id' => $payment->insurance_provider_id ?? $payment->invoice?->visit?->visitInsurance?->insurance_provider_id,
            'reference_type' => Payment::class,
            'reference_id' => $payment->id,
        ];
    }

    protected function markFailed(Payment $payment, Throwable $e): void
    {
        $payment->forceFill([
            'accounting_status' => self::STATUS_FAILED,
            'accounting_error' => mb_substr($e->getMessage(), 0, 2000),
        ])->save();

        $this->log('ACCOUNTING_PAYMENT_POSTING_FAILED', $payment, [
            'severity' => LogSeverity::WARNING,
            'payment_id' => $payment->id,
            'invoice_id' => $payment->invoice_id,
            'error' => $payment->accounting_error,
        ]);
    }

    protected function postingUser(Payment $payment): User
    {
        return auth()->user()
            ?? $payment->receivedBy
            ?? User::query()->firstOrFail();
    }

    protected function log(string $action, Payment $payment, array $context = []): void
    {
        try {
            app(ActivityLogService::class)->log(
                LogModule::ACCOUNTING,
                $action,
                array_merge(['severity' => LogSeverity::INFO], $context),
                $payment,
                str_replace('_', ' ', ucfirst(strtolower($action)))
            );
        } catch (Throwable) {
            // Accounting posting must not fail payment collection because logging failed.
        }
    }
}
