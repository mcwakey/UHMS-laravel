<?php

namespace App\Services;

use App\Models\CorporateClient;
use App\Models\InsuranceProvider;
use App\Models\Invoice;
use App\Models\InvoiceReceivable;
use App\Models\Sponsor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReceivableAllocationService
{
    public function __construct(
        protected InvoiceReceivableService $receivableService,
        protected ReceivableAccountingPostingService $accountingPostingService,
    ) {}

    public function allocatePatientResponsibility(Invoice $invoice, float $amount, string $reason): InvoiceReceivable
    {
        return $this->reallocateResponsibility(
            $invoice,
            $this->largestOpenReceivable($invoice, InvoiceReceivable::PAYER_PATIENT),
            InvoiceReceivable::PAYER_PATIENT,
            $invoice->patient_id,
            $amount,
            $reason
        );
    }

    public function allocateInsuranceResponsibility(Invoice $invoice, InsuranceProvider $provider, float $amount, string $reason): InvoiceReceivable
    {
        return $this->reallocateResponsibility(
            $invoice,
            $this->largestOpenReceivable($invoice, InvoiceReceivable::PAYER_INSURANCE),
            InvoiceReceivable::PAYER_INSURANCE,
            $provider->id,
            $amount,
            $reason
        );
    }

    public function allocateSponsorResponsibility(Invoice $invoice, Sponsor $sponsor, float $amount, string $reason): InvoiceReceivable
    {
        return $this->reallocateResponsibility(
            $invoice,
            $this->largestOpenReceivable($invoice, InvoiceReceivable::PAYER_SPONSOR),
            InvoiceReceivable::PAYER_SPONSOR,
            $sponsor->id,
            $amount,
            $reason
        );
    }

    public function allocateCorporateResponsibility(Invoice $invoice, CorporateClient $client, float $amount, string $reason): InvoiceReceivable
    {
        return $this->reallocateResponsibility(
            $invoice,
            $this->largestOpenReceivable($invoice, InvoiceReceivable::PAYER_CORPORATE),
            InvoiceReceivable::PAYER_CORPORATE,
            $client->id,
            $amount,
            $reason
        );
    }

    public function reallocateResponsibility(
        Invoice $invoice,
        InvoiceReceivable $from,
        string $targetPayerType,
        ?int $targetPayerId,
        float $amount,
        string $reason,
        array $extra = [],
    ): InvoiceReceivable {
        $amount = round($amount, 2);
        $reason = trim($reason);
        if ($amount <= 0) {
            throw new \RuntimeException('Allocation amount must be greater than zero.');
        }
        if ($reason === '') {
            throw new \RuntimeException('A reason is required when reallocating receivables.');
        }

        return DB::transaction(function () use ($invoice, $from, $targetPayerType, $targetPayerId, $amount, $reason, $extra) {
            $this->receivableService->syncFromInvoice($invoice);
            $from = InvoiceReceivable::where('invoice_id', $invoice->id)->lockForUpdate()->findOrFail($from->id);

            if ($from->balance + 0.01 < $amount) {
                throw new \RuntimeException('Allocation amount exceeds the source payer balance.');
            }
            if ($from->allocated_amount + 0.01 < $amount) {
                throw new \RuntimeException('Allocation amount exceeds the source payer allocation.');
            }

            $target = $this->targetReceivable($invoice, $targetPayerType, $targetPayerId, $extra);
            if ($target->id === $from->id) {
                throw new \RuntimeException('Choose a different payer to reallocate responsibility.');
            }

            $oldFrom = $from->getAttributes();
            $oldTarget = $target->getAttributes();

            $from->forceFill([
                'allocated_amount' => round((float) $from->allocated_amount - $amount, 2),
                'balance' => round((float) $from->balance - $amount, 2),
                'updated_by' => Auth::id(),
            ]);
            $from->markFromBalance()->save();

            $target->forceFill([
                'original_amount' => round((float) $target->original_amount + $amount, 2),
                'allocated_amount' => round((float) $target->allocated_amount + $amount, 2),
                'balance' => round((float) $target->balance + $amount, 2),
                'allocation_source' => 'manual',
                'updated_by' => Auth::id(),
            ]);
            $target->markFromBalance()->save();

            $this->accountingPostingService->postReallocation($from->refresh(), $target->refresh(), $amount, $reason);

            $this->receivableService->logAllocationChange('RECEIVABLE_RESPONSIBILITY_REALLOCATED', $target, [
                'invoice_id' => $invoice->id,
                'from_receivable_id' => $from->id,
                'to_receivable_id' => $target->id,
                'amount' => $amount,
                'reason' => $reason,
                'old_values' => [
                    'from' => $oldFrom,
                    'to' => $oldTarget,
                ],
                'new_values' => [
                    'from' => $from->fresh()->getAttributes(),
                    'to' => $target->fresh()->getAttributes(),
                ],
            ]);

            $this->receivableService->syncFromInvoice($invoice);

            return $target->fresh(['patient', 'insuranceProvider', 'sponsor', 'corporateClient']);
        });
    }

    private function largestOpenReceivable(Invoice $invoice, string $excludePayerType): InvoiceReceivable
    {
        $receivables = $this->receivableService->syncFromInvoice($invoice);

        return $receivables
            ->where('payer_type', '!=', $excludePayerType)
            ->where('balance', '>', 0)
            ->sortByDesc('balance')
            ->first()
            ?? $receivables->where('balance', '>', 0)->sortByDesc('balance')->first()
            ?? throw new \RuntimeException('No open receivable balance is available for allocation.');
    }

    private function targetReceivable(Invoice $invoice, string $payerType, ?int $payerId, array $extra): InvoiceReceivable
    {
        $query = InvoiceReceivable::where('invoice_id', $invoice->id)
            ->where('payer_type', $payerType);

        if ($payerId !== null) {
            $query->where('payer_id', $payerId);
        }

        $target = $query->lockForUpdate()->first();
        if ($target) {
            return $target;
        }

        $payload = [
            'invoice_id' => $invoice->id,
            'patient_id' => $invoice->patient_id,
            'visit_id' => $invoice->visit_id,
            'payer_type' => $payerType,
            'payer_id' => $payerId,
            'original_amount' => 0,
            'allocated_amount' => 0,
            'paid_amount' => 0,
            'balance' => 0,
            'aging_start_date' => optional($invoice->created_at)->toDateString() ?: now()->toDateString(),
            'due_date' => optional($invoice->due_date)->toDateString(),
            'status' => InvoiceReceivable::STATUS_PENDING,
            'allocation_source' => 'manual',
            'created_by' => Auth::id() ?? $invoice->created_by,
            'updated_by' => Auth::id(),
        ];

        $payload = array_merge($payload, match ($payerType) {
            InvoiceReceivable::PAYER_INSURANCE => ['insurance_provider_id' => $payerId, 'claim_id' => $extra['claim_id'] ?? null],
            InvoiceReceivable::PAYER_SPONSOR => ['sponsor_id' => $payerId, 'sponsor_authorization_id' => $extra['sponsor_authorization_id'] ?? null],
            InvoiceReceivable::PAYER_CORPORATE => ['corporate_client_id' => $payerId, 'corporate_account_id' => $extra['corporate_account_id'] ?? null],
            default => [],
        });

        return InvoiceReceivable::create($payload);
    }
}
