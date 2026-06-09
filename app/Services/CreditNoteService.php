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
    public function cancel(CreditNote $creditNote, string $reason, ?User $user = null): CreditNote
    {
        $user ??= Auth::user();
        $permissions = $creditNote->type === CreditNoteType::WRITE_OFF
            ? ['credit_notes.write_off', 'billing.write_off.reverse']
            : ['credit_notes.create', 'billing.credit_note.reverse'];
        if ($user && method_exists($user, 'can') && ! $this->canAny($user, $permissions)) {
            throw new AuthorizationException('Not authorized to cancel credit notes.');
        }

        if ($creditNote->status !== 'issued') {
            throw new \RuntimeException('Only an issued credit note can be cancelled.');
        }
        $reason = trim($reason);
        if ($reason === '') {
            throw new \RuntimeException('A cancellation reason is required.');
        }

        $creditNote = DB::transaction(function () use ($creditNote, $reason) {
            $creditNote->forceFill([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => Auth::id(),
                'cancellation_reason' => $reason,
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
                    'reason' => $reason,
                ],
            ], $creditNote, 'Credit note cancelled');

            return $creditNote->fresh();
        });

        app(BillingAccountingPostingService::class)->reverseCreditNote($creditNote, $reason);

        return $creditNote->refresh();
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
