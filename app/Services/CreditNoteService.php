<?php

namespace App\Services;

use App\Enums\CreditNoteType;
use App\Enums\InvoiceStatus;
use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\CreditNote;
use App\Models\Invoice;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Issues credit notes and write-offs against an invoice.
 *
 * A credit note is a non-cash reduction of the patient's outstanding balance.
 * The total of all active credit notes is mirrored into
 * invoices.adjustment_amount, which InvoiceService factors into the balance.
 */
class CreditNoteService
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected ?ActivityLogService $logger = null,
    ) {
        $this->logger = $this->logger ?: app(ActivityLogService::class);
    }

    /**
     * Net outstanding balance available for adjustment.
     */
    public function availableToCredit(Invoice $invoice): float
    {
        $invoice->loadMissing('items');
        $itemsBalance = (float) $invoice->items->sum('balance');
        $existingAdjustment = (float) $invoice->adjustment_amount;

        return max(0.0, round($itemsBalance - $existingAdjustment, 2));
    }

    /**
     * @throws AuthorizationException
     * @throws \RuntimeException
     */
    public function issue(Invoice $invoice, CreditNoteType $type, float $amount, string $reason, ?string $notes = null, ?User $user = null): CreditNote
    {
        $user ??= Auth::user();

        $permissions = $type === CreditNoteType::WRITE_OFF
            ? ['credit_notes.write_off', 'billing.write_off.issue']
            : ['credit_notes.create', 'billing.credit_note.issue'];
        if ($user && method_exists($user, 'can') && ! $this->canAny($user, $permissions)) {
            throw new AuthorizationException('Not authorized to issue this credit note.');
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw new \RuntimeException('A reason is required.');
        }
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new \RuntimeException('Credit note amount must be greater than zero.');
        }

        $status = $invoice->status?->value ?? $invoice->status;
        if (in_array($status, [InvoiceStatus::CANCELLED->value, InvoiceStatus::REFUNDED->value], true)) {
            throw new \RuntimeException('Cannot issue a credit note on a cancelled or refunded invoice.');
        }

        $creditNote = DB::transaction(function () use ($invoice, $type, $amount, $reason, $notes, $user) {
            $invoice = Invoice::with('items')->lockForUpdate()->findOrFail($invoice->id);

            $available = $this->availableToCredit($invoice);
            if ($amount > $available + 0.01) {
                throw new \RuntimeException(
                    "Amount (\u20b5" . number_format($amount, 2) . ') exceeds outstanding balance (\u20b5' . number_format($available, 2) . ').'
                );
            }

            $creditNote = CreditNote::create([
                'credit_note_number' => CreditNote::generateNumber('CN', 'credit_notes', 'credit_note_number'),
                'invoice_id' => $invoice->id,
                'patient_id' => $invoice->patient_id,
                'type' => $type->value,
                'status' => 'issued',
                'amount' => $amount,
                'reason' => $reason,
                'notes' => $notes,
                'issued_by' => Auth::id(),
            ]);

            $this->syncInvoiceAdjustment($invoice);

            $this->logger?->log(LogModule::BILLING, $type === CreditNoteType::WRITE_OFF ? 'WRITE_OFF_ISSUED' : 'CREDIT_NOTE_ISSUED', [
                'invoice_id' => $invoice->id,
                'patient_id' => $invoice->patient_id,
                'severity' => $type === CreditNoteType::WRITE_OFF ? LogSeverity::WARNING : LogSeverity::INFO,
                'metadata' => [
                    'credit_note_number' => $creditNote->credit_note_number,
                    'type' => $type->value,
                    'amount' => $amount,
                    'reason' => $reason,
                ],
            ], $creditNote, ucfirst(str_replace('_', ' ', $type->value)) . ' issued');

            return $creditNote->fresh(['invoice', 'issuedBy']);
        });

        app(BillingAccountingPostingService::class)->postCreditNote($creditNote);

        return $creditNote->refresh();
    }

    /**
     * @throws AuthorizationException
     * @throws \RuntimeException
     */
    public function reverse(CreditNote $creditNote, string $reason, ?User $user = null): CreditNote
    {
        $user ??= Auth::user();
        $permissions = $creditNote->type === CreditNoteType::WRITE_OFF
            ? ['credit_notes.write_off', 'billing.write_off.reverse']
            : ['credit_notes.create', 'billing.credit_note.reverse'];
        if ($user && method_exists($user, 'can') && ! $this->canAny($user, $permissions)) {
            throw new AuthorizationException('Not authorized to reverse this adjustment.');
        }

        if ($creditNote->status !== 'issued') {
            throw new \RuntimeException('Only an issued credit note or write-off can be reversed.');
        }
        $reason = trim($reason);
        if ($reason === '') {
            throw new \RuntimeException('A reversal reason is required.');
        }

        $reversal = DB::transaction(function () use ($creditNote, $reason, $user) {
            $creditNote = CreditNote::lockForUpdate()->findOrFail($creditNote->id);

            if ($creditNote->is_reversal) {
                throw new \RuntimeException('A reversal record cannot itself be reversed.');
            }
            if ($creditNote->status !== 'issued') {
                throw new \RuntimeException('This adjustment has already been reversed.');
            }
            if ($creditNote->reversal()->exists()) {
                throw new \RuntimeException('A reversal record already exists for this adjustment.');
            }

            $reversal = CreditNote::create([
                'credit_note_number' => CreditNote::generateNumber('REV', 'credit_notes', 'credit_note_number'),
                'invoice_id' => $creditNote->invoice_id,
                'patient_id' => $creditNote->patient_id,
                'type' => $creditNote->type->value,
                'status' => 'reversal',
                'is_reversal' => true,
                'reverses_credit_note_id' => $creditNote->id,
                'amount' => $creditNote->amount,
                'reason' => "Reversal of {$creditNote->credit_note_number}",
                'notes' => $reason,
                'issued_by' => Auth::id(),
            ]);

            $creditNote->forceFill([
                'status' => 'reversed',
                'reversed_at' => now(),
                'reversed_by' => Auth::id(),
                'reversal_reason' => $reason,
            ])->save();

            $invoice = Invoice::with('items')->find($creditNote->invoice_id);
            if ($invoice) {
                $this->syncInvoiceAdjustment($invoice);
            }

            $this->logger?->log(LogModule::BILLING, $creditNote->type === CreditNoteType::WRITE_OFF ? 'WRITE_OFF_REVERSED' : 'CREDIT_NOTE_REVERSED', [
                'invoice_id' => $creditNote->invoice_id,
                'patient_id' => $creditNote->patient_id,
                'severity' => LogSeverity::WARNING,
                'metadata' => [
                    'credit_note_number' => $creditNote->credit_note_number,
                    'reversal_number' => $reversal->credit_note_number,
                    'reversal_id' => $reversal->id,
                    'reason' => $reason,
                ],
            ], $reversal, ucfirst(str_replace('_', ' ', $creditNote->type->value)) . ' reversed');

            return $reversal->fresh(['originalCreditNote']);
        });

        $original = $reversal->originalCreditNote;
        app(BillingAccountingPostingService::class)->reverseCreditNote($original, $reason);

        $original->refresh();
        if ($original->reversal_journal_entry_id) {
            $reversal->forceFill([
                'journal_entry_id' => $original->reversal_journal_entry_id,
                'accounting_posted_at' => now(),
                'accounting_status' => BillingAccountingPostingService::STATUS_POSTED,
                'accounting_error' => null,
            ])->save();
        }

        return $reversal->refresh(['originalCreditNote', 'journalEntry']);
    }

    /**
     * Backwards-compatible method name for existing callers and route names.
     */
    public function cancel(CreditNote $creditNote, string $reason, ?User $user = null): CreditNote
    {
        return $this->reverse($creditNote, $reason, $user);
    }

    /**
     * Recompute invoices.adjustment_amount from active credit notes and refresh totals.
     */
    private function syncInvoiceAdjustment(Invoice $invoice): void
    {
        $total = (float) CreditNote::where('invoice_id', $invoice->id)
            ->where('status', 'issued')
            ->sum('amount');

        $invoice->forceFill(['adjustment_amount' => round($total, 2)])->save();
        $this->invoiceService->recalculateTotals($invoice->fresh('items'));
    }

    private function canAny(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}
