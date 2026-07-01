<?php

namespace App\Services;

use App\Enums\BillingType;
use App\Enums\CreditNoteType;
use App\Enums\InvoiceStatus;
use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceReceivable;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceReceivableService
{
    public function syncFromInvoice(Invoice $invoice): Collection
    {
        return DB::transaction(function () use ($invoice) {
            $invoice = Invoice::with([
                'items',
                'payments',
                'creditNotes',
                'claim',
                'visit.visitInsurance',
                'receivables',
            ])->lockForUpdate()->findOrFail($invoice->id);

            if ($invoice->receivables->isEmpty()) {
                $this->seedReceivables($invoice);
                $invoice->load('receivables');
            } elseif ($this->canRebuildSystemReceivables($invoice)) {
                $invoice->receivables()->delete();
                $this->seedReceivables($invoice);
                $invoice->load('receivables');
            }

            $this->attachClaimToInsuranceReceivables($invoice);
            $this->refreshReceivableAmounts($invoice);

            return $invoice->receivables()
                ->with(['patient', 'insuranceProvider', 'sponsor', 'corporateClient', 'journalEntry'])
                ->orderBy('payer_type')
                ->orderBy('id')
                ->get();
        });
    }

    public function resolvePaymentReceivable(Invoice $invoice, array $data, float $amount): ?InvoiceReceivable
    {
        $receivables = $this->syncFromInvoice($invoice);

        if (! empty($data['invoice_receivable_id'])) {
            $payerType = $this->normalizePayerType($data['payer_type'] ?? null);
            $payerId = $data['payer_id'] ?? null;
            $receivable = $receivables->firstWhere('id', (int) $data['invoice_receivable_id']);

            if ($receivable && $payerType) {
                $payerMatches = $receivable->payer_type === $payerType
                    && ($payerId === null || (int) $receivable->payer_id === (int) $payerId);

                if (! $payerMatches) {
                    $receivable = null;
                }
            }

            if (! $receivable) {
                if ($payerType) {
                    $receivable = $receivables
                        ->where('payer_type', $payerType)
                        ->when($payerId !== null, fn ($rows) => $rows->where('payer_id', (int) $payerId))
                        ->where('balance', '>', 0)
                        ->first();
                }
            }

            if (! $receivable) {
                throw new \RuntimeException('Selected payer responsibility does not belong to this invoice.');
            }

            if ($amount > (float) $receivable->balance + 0.01) {
                throw new \RuntimeException(
                    'Payment amount exceeds the selected payer balance of GH' . number_format((float) $receivable->balance, 2)
                );
            }

            return $receivable;
        }

        $payerType = $this->normalizePayerType($data['payer_type'] ?? null);
        $payerId = $data['payer_id'] ?? null;

        if ($payerType) {
            $match = $receivables
                ->where('payer_type', $payerType)
                ->when($payerId !== null, fn ($rows) => $rows->where('payer_id', (int) $payerId))
                ->where('balance', '>', 0)
                ->first();

            if ($match) {
                return $match;
            }
        }

        return $receivables->where('balance', '>', 0)->first()
            ?? $receivables->first();
    }

    public function applyPayment(Payment $payment): void
    {
        $payment->loadMissing('invoice');
        if (! $payment->invoice) {
            return;
        }

        $receivable = null;
        if ($payment->invoice_receivable_id) {
            $receivable = InvoiceReceivable::find($payment->invoice_receivable_id);
        }

        if (! $receivable) {
            $receivable = $this->resolvePaymentReceivable($payment->invoice, [
                'payer_type' => $payment->payer_type,
                'payer_id' => $payment->payer_id,
            ], abs((float) $payment->amount));
        }

        if ($receivable && ! $payment->invoice_receivable_id) {
            $payment->forceFill($this->paymentPayerPayload($receivable))->save();
        }

        $this->syncFromInvoice($payment->invoice);
    }

    public function paymentPayerPayload(InvoiceReceivable $receivable): array
    {
        return [
            'invoice_receivable_id' => $receivable->id,
            'payer_type' => $receivable->payer_type,
            'payer_id' => $receivable->payer_id,
            'insurance_provider_id' => $receivable->insurance_provider_id,
            'sponsor_id' => $receivable->sponsor_id,
            'corporate_client_id' => $receivable->corporate_client_id,
            'claim_id' => $receivable->claim_id,
        ];
    }

    public function refreshReceivableAmounts(Invoice $invoice): void
    {
        $invoice->loadMissing(['receivables', 'payments', 'creditNotes', 'items']);

        $amounts = [];
        foreach ($invoice->receivables as $receivable) {
            $amounts[$receivable->id] = [
                'paid_net' => 0.0,
                'refund' => 0.0,
                'credit_note' => 0.0,
                'write_off' => 0.0,
            ];
        }

        foreach ($invoice->payments as $payment) {
            $receivable = $this->receivableForPayment($invoice, $payment);
            if (! $receivable) {
                continue;
            }

            $amount = round((float) $payment->amount, 2);
            $amounts[$receivable->id]['paid_net'] += $amount;
            if ($amount < 0) {
                $amounts[$receivable->id]['refund'] += abs($amount);
            }
        }

        foreach ($invoice->creditNotes->where('status', 'issued') as $creditNote) {
            $receivable = $this->defaultAdjustmentReceivable($invoice);
            if (! $receivable) {
                continue;
            }

            $key = $creditNote->type === CreditNoteType::WRITE_OFF ? 'write_off' : 'credit_note';
            $amounts[$receivable->id][$key] += (float) $creditNote->amount;
        }

        foreach ($invoice->receivables as $receivable) {
            $row = $amounts[$receivable->id] ?? [
                'paid_net' => 0.0,
                'refund' => 0.0,
                'credit_note' => 0.0,
                'write_off' => 0.0,
            ];
            $paidNet = round((float) $row['paid_net'], 2);
            $credit = round((float) $row['credit_note'], 2);
            $writeOff = round((float) $row['write_off'], 2);
            $balance = max(0.0, round((float) $receivable->allocated_amount - $paidNet - $credit - $writeOff, 2));

            $receivable->forceFill([
                'paid_amount' => max(0.0, $paidNet),
                'refund_amount' => round((float) $row['refund'], 2),
                'credit_note_amount' => $credit,
                'write_off_amount' => $writeOff,
                'balance' => $balance,
                'updated_by' => Auth::id(),
            ]);
            $receivable->markFromBalance()->save();
        }
    }

    private function seedReceivables(Invoice $invoice): void
    {
        $groups = [];

        foreach ($invoice->items as $item) {
            $patientAmount = round((float) $item->patient_payable, 2);
            if ($patientAmount > 0) {
                $this->addReceivableGroup(
                    $groups,
                    $this->identityForItem($invoice, $item),
                    $patientAmount,
                    (float) $item->discount_amount
                );
            }

            $insuranceAmount = round((float) $item->insurance_covered, 2);
            if ($insuranceAmount > 0 && $identity = $this->identityForInsuranceCoverage($invoice, $item)) {
                $this->addReceivableGroup($groups, $identity, $insuranceAmount, 0.0);
            }
        }

        if (empty($groups) && (float) $invoice->total_amount > 0) {
            $identity = $this->identityForInvoice($invoice);
            $groups['invoice-default'] = array_merge($identity, [
                'original_amount' => (float) $invoice->total_amount,
                'allocated_amount' => (float) $invoice->total_amount,
                'discount_amount' => (float) $invoice->discount_amount,
            ]);
        }

        foreach ($groups as $group) {
            $allocated = round((float) $group['allocated_amount'], 2);
            InvoiceReceivable::create(array_merge($group, [
                'invoice_id' => $invoice->id,
                'patient_id' => $invoice->patient_id,
                'visit_id' => $invoice->visit_id,
                'original_amount' => round((float) $group['original_amount'], 2),
                'allocated_amount' => $allocated,
                'balance' => $allocated,
                'aging_start_date' => optional($invoice->created_at)->toDateString() ?: now()->toDateString(),
                'due_date' => optional($invoice->due_date)->toDateString(),
                'status' => $allocated > 0 ? InvoiceReceivable::STATUS_PENDING : InvoiceReceivable::STATUS_PAID,
                'accounting_status' => $invoice->accounting_status,
                'accounting_posted_at' => $invoice->accounting_posted_at,
                'created_by' => Auth::id() ?? $invoice->created_by,
                'updated_by' => Auth::id(),
            ]));
        }
    }

    private function identityForItem(Invoice $invoice, InvoiceItem $item): array
    {
        $itemPayer = $this->normalizePayerType($item->payer_type);
        if ($itemPayer === InvoiceReceivable::PAYER_SPONSOR && $invoice->sponsor_id) {
            return $this->identityForInvoice($invoice);
        }

        if ($itemPayer === InvoiceReceivable::PAYER_CORPORATE && $invoice->corporate_client_id) {
            return $this->identityForInvoice($invoice);
        }

        // Insurance coverage reduces the bill, but the remaining patient_payable
        // balance is still collected from the patient unless it is explicitly
        // reallocated to a sponsor/corporate payer.
        return $this->identityForInvoice($invoice);
    }

    private function identityForInsuranceCoverage(Invoice $invoice, InvoiceItem $item): ?array
    {
        $providerId = $item->insurance_provider_id ?? $invoice->visit?->visitInsurance?->insurance_provider_id;
        if (! $providerId) {
            return null;
        }

        return [
            'payer_type' => InvoiceReceivable::PAYER_INSURANCE,
            'payer_id' => $providerId,
            'insurance_provider_id' => $providerId,
            'sponsor_id' => null,
            'corporate_client_id' => null,
            'claim_id' => $invoice->claim?->id,
            'sponsor_authorization_id' => null,
            'corporate_account_id' => null,
        ];
    }

    private function addReceivableGroup(array &$groups, array $identity, float $amount, float $discount): void
    {
        $key = implode('|', [
            $identity['payer_type'],
            $identity['payer_id'] ?? '',
            $identity['insurance_provider_id'] ?? '',
            $identity['sponsor_id'] ?? '',
            $identity['corporate_client_id'] ?? '',
        ]);

        $groups[$key] ??= array_merge($identity, [
            'original_amount' => 0.0,
            'allocated_amount' => 0.0,
            'discount_amount' => 0.0,
        ]);

        $groups[$key]['original_amount'] += $amount;
        $groups[$key]['allocated_amount'] += $amount;
        $groups[$key]['discount_amount'] += $discount;
    }

    private function canRebuildSystemReceivables(Invoice $invoice): bool
    {
        return $invoice->payments->isEmpty()
            && $invoice->receivables->isNotEmpty()
            && $invoice->receivables->every(fn (InvoiceReceivable $row) => $row->allocation_source === 'system'
                && (float) $row->paid_amount <= 0
                && (float) $row->credit_note_amount <= 0
                && (float) $row->write_off_amount <= 0
                && (float) $row->refund_amount <= 0
                && $row->journal_entry_id === null);
    }

    private function identityForInvoice(Invoice $invoice): array
    {
        $billingType = $invoice->billing_type instanceof BillingType
            ? $invoice->billing_type->value
            : (string) $invoice->billing_type;

        if ($invoice->sponsor_id) {
            return [
                'payer_type' => InvoiceReceivable::PAYER_SPONSOR,
                'payer_id' => $invoice->sponsor_id,
                'insurance_provider_id' => null,
                'sponsor_id' => $invoice->sponsor_id,
                'corporate_client_id' => null,
                'claim_id' => null,
                'sponsor_authorization_id' => null,
                'corporate_account_id' => null,
            ];
        }

        if ($billingType === BillingType::CORPORATE->value && $invoice->corporate_client_id) {
            return [
                'payer_type' => InvoiceReceivable::PAYER_CORPORATE,
                'payer_id' => $invoice->corporate_client_id,
                'insurance_provider_id' => null,
                'sponsor_id' => null,
                'corporate_client_id' => $invoice->corporate_client_id,
                'claim_id' => null,
                'sponsor_authorization_id' => null,
                'corporate_account_id' => null,
            ];
        }

        return [
            'payer_type' => InvoiceReceivable::PAYER_PATIENT,
            'payer_id' => $invoice->patient_id,
            'insurance_provider_id' => null,
            'sponsor_id' => null,
            'corporate_client_id' => null,
            'claim_id' => null,
            'sponsor_authorization_id' => null,
            'corporate_account_id' => null,
        ];
    }

    private function normalizePayerType(?string $payerType): ?string
    {
        return match ($payerType) {
            'cash', 'self', InvoiceReceivable::PAYER_PATIENT => InvoiceReceivable::PAYER_PATIENT,
            InvoiceReceivable::PAYER_INSURANCE => InvoiceReceivable::PAYER_INSURANCE,
            InvoiceReceivable::PAYER_SPONSOR => InvoiceReceivable::PAYER_SPONSOR,
            InvoiceReceivable::PAYER_CORPORATE => InvoiceReceivable::PAYER_CORPORATE,
            default => null,
        };
    }

    private function receivableForPayment(Invoice $invoice, Payment $payment): ?InvoiceReceivable
    {
        if ($payment->invoice_receivable_id) {
            $match = $invoice->receivables->firstWhere('id', (int) $payment->invoice_receivable_id);
            if ($match) {
                return $match;
            }
        }

        $payerType = $this->normalizePayerType($payment->payer_type);
        if ($payerType) {
            $match = $invoice->receivables
                ->where('payer_type', $payerType)
                ->when($payment->payer_id, fn ($rows) => $rows->where('payer_id', (int) $payment->payer_id))
                ->first();
            if ($match) {
                return $match;
            }
        }

        return $invoice->receivables->firstWhere('payer_type', InvoiceReceivable::PAYER_PATIENT)
            ?? $invoice->receivables->first();
    }

    private function defaultAdjustmentReceivable(Invoice $invoice): ?InvoiceReceivable
    {
        if ($invoice->sponsor_id) {
            $match = $invoice->receivables->firstWhere('sponsor_id', (int) $invoice->sponsor_id);
            if ($match) {
                return $match;
            }
        }

        if ($invoice->corporate_client_id) {
            $match = $invoice->receivables->firstWhere('corporate_client_id', (int) $invoice->corporate_client_id);
            if ($match) {
                return $match;
            }
        }

        return $invoice->receivables->firstWhere('payer_type', InvoiceReceivable::PAYER_PATIENT)
            ?? $invoice->receivables->first();
    }

    private function attachClaimToInsuranceReceivables(Invoice $invoice): void
    {
        if (! $invoice->claim) {
            return;
        }

        InvoiceReceivable::query()
            ->where('invoice_id', $invoice->id)
            ->where('payer_type', InvoiceReceivable::PAYER_INSURANCE)
            ->whereNull('claim_id')
            ->update(['claim_id' => $invoice->claim->id]);
    }

    public function logAllocationChange(string $action, InvoiceReceivable $receivable, array $context): void
    {
        try {
            app(ActivityLogService::class)->log(
                LogModule::BILLING,
                $action,
                array_merge(['severity' => LogSeverity::WARNING], $context),
                $receivable,
                str_replace('_', ' ', ucfirst(strtolower($action)))
            );
        } catch (\Throwable) {
            // Receivable sync/allocation must not fail because logging failed.
        }
    }
}
