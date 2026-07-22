<?php

namespace App\Services\Billing;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\Invoice;
use App\Models\InvoiceReceivable;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Visit;
use App\Services\ActivityLogService;
use App\Services\LegacyMigration\Foundation\Runtime\OperationalEffectGate;
use App\Services\LegacyMigration\Foundation\Runtime\ProhibitedSubsystem;
use App\Services\PaymentService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Distributes ONE physical patient tender across the patient's open visit
 * invoices without ever merging invoices.
 *
 * Architecture note (deliberate): the platform ties a Payment to a single
 * invoice + receivable, and the accounting/AR pipeline assumes that. So a
 * cross-visit tender is recorded as SEVERAL per-invoice Payment rows through the
 * existing PaymentService::recordPayment() — each visit keeps its own clean
 * invoice and its own Dr Cash / Cr Patient Receivables journal entry. The rows
 * are grouped by a shared payment_batch_reference and one
 * CROSS_VISIT_PAYMENT_ALLOCATED activity log records the distribution. No old
 * revenue is recognised again — money only reduces existing receivables.
 */
class PatientPaymentAllocationService
{
    public function __construct(
        protected PatientOutstandingBalanceService $balances,
        protected PaymentService $paymentService,
        protected ActivityLogService $activityLog,
    ) {}

    public const MODE_OLDEST_FIRST = 'oldest_first';

    public const MODE_CURRENT_VISIT = 'current_visit';

    public const MODE_MANUAL = 'manual';

    public const MODE_SYSTEM = 'system';

    /**
     * Allocate a tender oldest-outstanding-invoice first across the patient's
     * open invoices. The current visit (if given) is settled LAST.
     *
     * @param  array  $data  ['amount', 'payment_method', 'reference_number'?, 'notes'?, 'paid_at'?]
     * @return Collection<int,Payment>
     */
    public function allocatePaymentOldestFirst(Patient $patient, array $data, ?Visit $currentVisit = null): Collection
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::PaymentAllocation);

        $amount = $this->tenderAmount($data);
        $plan = $this->planOldestFirst($patient, $amount, $currentVisit);

        return $this->execute($patient, $plan, $data, self::MODE_OLDEST_FIRST, $currentVisit);
    }

    /**
     * Allocate a tender to the CURRENT visit's invoice(s) only. Previous debt is
     * left untouched.
     *
     * @return Collection<int,Payment>
     */
    public function allocatePaymentToCurrentVisit(Visit $visit, array $data): Collection
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::PaymentAllocation);

        $patient = $visit->patient ?: Patient::findOrFail($visit->patient_id);
        $amount = $this->tenderAmount($data);

        $receivables = $this->balances->getOutstandingReceivables($patient, ['visit_id' => $visit->id]);
        $plan = $this->planFromReceivables($receivables, $amount);

        if (empty($plan)) {
            throw new RuntimeException('This visit has no outstanding patient balance to pay.');
        }

        return $this->execute($patient, $plan, $data, self::MODE_CURRENT_VISIT, $visit);
    }

    /**
     * Manually allocate a tender to specific invoices.
     *
     * @param  array  $invoiceAllocations  [['invoice_id' => int, 'amount' => float], ...]
     * @return Collection<int,Payment>
     */
    public function allocatePaymentManually(Patient $patient, array $invoiceAllocations, array $data, ?Visit $currentVisit = null): Collection
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::PaymentAllocation);

        $amount = $this->tenderAmount($data);
        $receivables = $this->balances->getOutstandingReceivables($patient)->keyBy('invoice_id');

        $plan = [];
        foreach ($invoiceAllocations as $row) {
            $invoiceId = (int) ($row['invoice_id'] ?? 0);
            $lineAmount = round((float) ($row['amount'] ?? 0), 2);
            if ($lineAmount <= 0) {
                continue;
            }
            $receivable = $receivables->get($invoiceId);
            if (! $receivable) {
                throw new RuntimeException("Invoice #{$invoiceId} has no open patient balance for this patient.");
            }
            $plan[] = [
                'invoice' => $receivable->invoice,
                'receivable' => $receivable,
                'amount' => $lineAmount,
            ];
        }

        if (empty($plan)) {
            throw new RuntimeException('No valid invoice allocations were provided.');
        }

        $this->validateAllocation($amount, $plan);

        return $this->execute($patient, $plan, $data, self::MODE_MANUAL, $currentVisit);
    }

    /*
    |--------------------------------------------------------------------------
    | Planning (pure — safe for UI preview)
    |--------------------------------------------------------------------------
    */

    /**
     * Build an oldest-first plan without touching the database.
     *
     * @return array<int,array{invoice:Invoice,receivable:InvoiceReceivable,amount:float}>
     */
    public function planOldestFirst(Patient $patient, float $amount, ?Visit $currentVisit = null): array
    {
        $receivables = $this->balances->getOutstandingReceivables($patient);

        // Oldest previous debt first; the current visit is intentionally settled last.
        if ($currentVisit) {
            $receivables = $receivables
                ->sortBy(fn (InvoiceReceivable $r) => $r->visit_id === $currentVisit->id ? 1 : 0)
                ->values();
        }

        return $this->planFromReceivables($receivables, $amount);
    }

    /**
     * @param  Collection<int,InvoiceReceivable>  $receivables
     * @return array<int,array{invoice:Invoice,receivable:InvoiceReceivable,amount:float}>
     */
    private function planFromReceivables(Collection $receivables, float $amount): array
    {
        $this->assertPositive($amount);

        $remaining = round($amount, 2);
        $plan = [];

        foreach ($receivables as $receivable) {
            if ($remaining <= 0.0) {
                break;
            }
            $balance = round((float) $receivable->balance, 2);
            if ($balance <= 0.0 || ! $receivable->invoice) {
                continue;
            }
            $apply = round(min($remaining, $balance), 2);
            if ($apply <= 0.0) {
                continue;
            }
            $plan[] = [
                'invoice' => $receivable->invoice,
                'receivable' => $receivable,
                'amount' => $apply,
            ];
            $remaining = round($remaining - $apply, 2);
        }

        $this->assertNoUnhandledSurplus($remaining, $amount);

        return $plan;
    }

    /**
     * Allocation guardrails (spec §10):
     *   * payment amount > 0
     *   * total allocation must not exceed the payment amount
     *   * an allocation must not exceed the invoice's open balance
     *
     * @param  array<int,array{invoice:Invoice,receivable:InvoiceReceivable,amount:float}>  $plan
     */
    public function validateAllocation(float $amount, array $plan): void
    {
        $this->assertPositive($amount);

        $total = 0.0;
        foreach ($plan as $line) {
            $lineAmount = round((float) $line['amount'], 2);
            if ($lineAmount <= 0) {
                throw new RuntimeException('Each allocation amount must be greater than zero.');
            }
            $balance = round((float) $line['receivable']->balance, 2);
            if ($lineAmount > $balance + 0.01) {
                $number = $line['invoice']?->invoice_number ?? ('#'.$line['receivable']->invoice_id);
                throw new RuntimeException(
                    'Allocation of GH₵'.number_format($lineAmount, 2).' exceeds the open balance of GH₵'
                    .number_format($balance, 2)." on invoice {$number}."
                );
            }
            $total = round($total + $lineAmount, 2);
        }

        if ($total > $amount + 0.01) {
            throw new RuntimeException(
                'Total allocation (GH₵'.number_format($total, 2).') exceeds the payment amount (GH₵'
                .number_format($amount, 2).').'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Execution
    |--------------------------------------------------------------------------
    */

    /**
     * @param  array<int,array{invoice:Invoice,receivable:InvoiceReceivable,amount:float}>  $plan
     * @return Collection<int,Payment>
     */
    private function execute(Patient $patient, array $plan, array $data, string $mode, ?Visit $currentVisit): Collection
    {
        $amount = $this->tenderAmount($data);
        $this->validateAllocation($amount, $plan);

        // Single-invoice tender: no batch grouping needed — behaves exactly like
        // a normal cashier payment.
        $batchReference = count($plan) > 1
            ? 'ALB-'.strtoupper(Str::random(10))
            : null;

        $payments = DB::transaction(function () use ($plan, $data, $batchReference) {
            $created = collect();
            foreach ($plan as $order => $line) {
                $lineData = array_merge($data, [
                    'amount' => round((float) $line['amount'], 2),
                    'payment_batch_reference' => $batchReference,
                    // Steer PaymentService onto the patient receivable for this invoice.
                    'invoice_receivable_id' => $line['receivable']->id,
                    'payer_type' => InvoiceReceivable::PAYER_PATIENT,
                    'payer_id' => $line['receivable']->payer_id,
                ]);

                $payment = $this->paymentService->recordPayment($line['invoice'], $lineData);
                $created->push($payment);
            }

            return $created;
        });

        if (count($plan) > 1) {
            $this->logAllocation($patient, $payments, $plan, $mode, $batchReference, $currentVisit);
        }

        return $payments;
    }

    private function logAllocation(
        Patient $patient,
        Collection $payments,
        array $plan,
        string $mode,
        ?string $batchReference,
        ?Visit $currentVisit,
    ): void {
        $distribution = [];
        foreach ($plan as $line) {
            $distribution[] = [
                'invoice_id' => $line['receivable']->invoice_id,
                'invoice_number' => $line['invoice']?->invoice_number,
                'visit_id' => $line['receivable']->visit_id,
                'amount' => round((float) $line['amount'], 2),
                'is_current_visit' => $currentVisit && $line['receivable']->visit_id === $currentVisit->id,
            ];
        }

        $this->activityLog->log(LogModule::PAYMENTS, 'CROSS_VISIT_PAYMENT_ALLOCATED', [
            'patient_id' => $patient->id,
            'visit_id' => $currentVisit?->id,
            'severity' => LogSeverity::NOTICE,
            'metadata' => [
                'allocation_mode' => $mode,
                'payment_batch_reference' => $batchReference,
                'total_amount' => round((float) $payments->sum('amount'), 2),
                'payment_ids' => $payments->pluck('id')->all(),
                'distribution' => $distribution,
            ],
        ], $patient, 'Cross-visit payment allocated');
    }

    /*
    |--------------------------------------------------------------------------
    | Guards
    |--------------------------------------------------------------------------
    */

    private function tenderAmount(array $data): float
    {
        return round((float) ($data['amount'] ?? 0), 2);
    }

    private function assertPositive(float $amount): void
    {
        if ($amount <= 0) {
            throw new RuntimeException('Payment amount must be greater than zero.');
        }
    }

    private function assertNoUnhandledSurplus(float $remaining, float $amount): void
    {
        if ($remaining <= 0.01) {
            return;
        }

        $behaviour = (string) config('billing.previous_balance_policy.overpayment_behaviour', 'reject');
        if ($behaviour === 'ignore') {
            return; // caller keeps the change; only the outstanding portion is applied
        }

        throw new RuntimeException(
            'Payment amount (GH₵'.number_format($amount, 2).') exceeds the total outstanding patient balance by GH₵'
            .number_format($remaining, 2).'. Reduce the amount or enable a deposit/overpayment workflow.'
        );
    }
}
