<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\VisitStatus;
use App\Events\PaymentRecorded;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\ServiceCatalog;
use App\Models\Visit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\VisitWorkflowService;

class BillingService
{
    public function __construct(
        protected InsuranceService $insuranceService,
        protected VisitWorkflowService $visitWorkflowService,
        protected ServicePriceResolver $priceResolver,
    ) {}

    /**
     * Create an invoice for a visit.
     * After inserting items, links insurance usage records to the invoice and
     * records any lab/pharmacy usages that weren't captured at service-attach time.
     */
    public function createInvoice(array $data, array $items): Invoice
    {
        return DB::transaction(function () use ($data, $items) {
            $invoiceNumber = Invoice::generateNumber('INV', 'invoices', 'invoice_number');

            // Calculate totals from items
            $subtotal    = 0;
            $nhisAmount  = 0;

            foreach ($items as $item) {
                $lineTotal   = ($item['unit_price'] ?? 0) * ($item['quantity'] ?? 1);
                $subtotal   += $lineTotal;
                if (! empty($item['is_nhis_covered']) && ! empty($item['nhis_approved_amount'])) {
                    $nhisAmount += $item['nhis_approved_amount'];
                }
            }

            $taxAmount      = $data['tax_amount'] ?? 0;
            $discountAmount = $data['discount_amount'] ?? 0;
            $totalAmount    = $subtotal + $taxAmount - $discountAmount;
            $balance        = $totalAmount - $nhisAmount;

            $invoice = Invoice::create([
                'invoice_number'  => $invoiceNumber,
                'visit_id'        => $data['visit_id'],
                'patient_id'      => $data['patient_id'],
                'billing_type'    => $data['billing_type'],
                'subtotal'        => $subtotal,
                'tax_amount'      => $taxAmount,
                'discount_amount' => $discountAmount,
                'nhis_amount'     => $nhisAmount,
                'total_amount'    => $totalAmount,
                'amount_paid'     => $nhisAmount,
                'balance'         => max(0, $balance),
                'status'          => $nhisAmount >= $totalAmount ? InvoiceStatus::PAID->value : InvoiceStatus::PENDING->value,
                'due_date'        => $data['due_date'] ?? now()->addDays(30),
                'notes'           => $data['notes'] ?? null,
                'created_by'      => Auth::id(),
            ]);

            foreach ($items as $item) {
                $quantity   = $item['quantity'] ?? 1;
                $unitPrice  = $item['unit_price'] ?? 0;
                $lineTotal  = $unitPrice * $quantity;
                $cashPrice  = $item['cash_price'] ?? $unitPrice;
                $discount   = $item['discount_amount'] ?? max(0, ($cashPrice - $unitPrice) * $quantity);

                InvoiceItem::create([
                    'invoice_id'           => $invoice->id,
                    'service_catalog_id'   => $item['service_catalog_id'] ?? null,
                    'description'          => $item['description'],
                    'quantity'             => $quantity,
                    'unit_price'           => $unitPrice,
                    'total_price'          => $lineTotal,
                    'is_nhis_covered'      => $item['is_nhis_covered'] ?? false,
                    'nhis_approved_amount' => $item['nhis_approved_amount'] ?? 0,
                    'cash_price'           => $cashPrice,
                    'selected_price'       => $unitPrice,
                    'discount_amount'      => $discount,
                    'payer_type'           => $item['payer_type'] ?? null,
                    'insurance_provider_id' => $item['insurance_provider_id'] ?? null,
                    'pricing_source'       => $item['pricing_source'] ?? null,
                ]);

                // Record usage for items that were evaluated at invoice time
                // (lab / pharmacy — visit_services were already recorded in attachServices)
                if (! empty($item['_record_usage']) && ! empty($item['_insurance'])) {
                    $this->insuranceService->recordUsage(
                        $item['_insurance'],
                        Visit::find($data['visit_id']),
                        $item['nhis_approved_amount'] ?? 0,
                        $lineTotal - ($item['nhis_approved_amount'] ?? 0),
                        $item['_coverage_reason'] ?? null,
                        $invoice->id
                    );
                }
            }

            // Link any existing visit_service usage records to this invoice
            $visit = Visit::find($data['visit_id']);
            if ($visit?->visitInsurance) {
                $this->insuranceService->linkInvoiceToUsages(
                    $visit->id,
                    $invoice->id,
                    $visit->visitInsurance->id
                );
            }

            // Transition visit to billing via the workflow engine
            if ($visit && in_array(VisitStatus::BILLING, $visit->status->allowedTransitions())) {
                $this->visitWorkflowService->moveToBilling($visit);
            }

            return $invoice->load('items', 'patient', 'visit');
        });
    }

    /**
     * Record a payment against an invoice.
     */
    public function recordPayment(Invoice $invoice, array $data): Payment
    {
        $payment = DB::transaction(function () use ($invoice, $data) {
            $paymentNumber = Payment::generateNumber('PAY', 'payments', 'payment_number');

            $payment = Payment::create([
                'payment_number'   => $paymentNumber,
                'invoice_id'       => $invoice->id,
                'patient_id'       => $invoice->patient_id,
                'amount'           => $data['amount'],
                'payment_method'   => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'received_by'      => Auth::id(),
                'notes'            => $data['notes'] ?? null,
                'paid_at'          => $data['paid_at'] ?? now(),
            ]);

            $totalPaid = $invoice->amount_paid + $data['amount'];
            $balance   = $invoice->total_amount - $totalPaid;

            $status = InvoiceStatus::PARTIALLY_PAID;
            if ($balance <= 0) {
                $status  = InvoiceStatus::PAID;
                $balance = 0;
            }

            $invoice->update([
                'amount_paid' => $totalPaid,
                'balance'     => $balance,
                'status'      => $status->value,
            ]);

            if ($status === InvoiceStatus::PAID) {
                $visit = $invoice->visit;
                if ($visit && $visit->status === VisitStatus::BILLING) {
                    $this->visitWorkflowService->completeAfterPayment($visit);
                }
            }

            return $payment->load('invoice', 'patient');
        });

        PaymentRecorded::dispatch($payment);

        return $payment;
    }

    /**
     * Cancel an invoice.
     */
    public function cancelInvoice(Invoice $invoice): Invoice
    {
        $invoice->update(['status' => InvoiceStatus::CANCELLED->value]);
        return $invoice;
    }

    /**
     * Auto-generate invoice items from visit services + lab + prescriptions.
     *
     * For visit_services: reads already-evaluated amounts (recorded at attachServices time).
     * For lab/pharmacy items: evaluates coverage in real time using the insurance engine,
     * accounting for visit_service amounts already committed + previous lab/pharmacy items
     * in this same billing pass (via sessionOffset).
     *
     * Items destined for invoice-time usage recording carry a '_record_usage' flag.
     */
    public function generateItemsFromVisit(Visit $visit): array
    {
        $items = [];

        // Resolve insurance for this visit
        $visitInsurance = $visit->visitInsurance;
        $provider       = $visitInsurance?->insuranceProvider;
        $hasInsurance   = $visitInsurance
            && $visitInsurance->is_active
            && ! $visitInsurance->is_expired
            && $provider
            && ! $provider->is_default;

        // Running offset: track lab/pharmacy insurance amounts committed in this pass
        // (not yet in insurance_usages — prevents over-coverage on simultaneous items)
        $sessionOffset = 0.0;

        // 1. Visit Services ─────────────────────────────────────────────────
        // Coverage was already evaluated + recorded at attachServices() time.
        // Just read the stored amounts directly — do NOT re-evaluate.
        $visit->loadMissing('visitServices.serviceCatalog.prices');
        foreach ($visit->visitServices as $vs) {
            $catalog       = $vs->serviceCatalog;
            $insuredAmount = (float) $vs->insurance_covered;
            $cashPrice     = $catalog ? (float) $catalog->price : (float) $vs->unit_price;
            $unitPrice     = (float) $vs->unit_price;
            $quantity      = (int) $vs->quantity;
            $discount      = max(0.0, round(($cashPrice - $unitPrice) * $quantity, 2));

            $items[] = [
                'service_catalog_id'   => $vs->service_catalog_id,
                'description'          => $catalog ? $catalog->name : 'Service',
                'quantity'             => $quantity,
                'unit_price'           => $unitPrice,
                'is_nhis_covered'      => $insuredAmount > 0,
                'nhis_approved_amount' => $insuredAmount,
                'cash_price'           => $cashPrice,
                'discount_amount'      => $discount,
                'payer_type'           => $hasInsurance ? 'insurance' : 'cash',
                'insurance_provider_id' => $hasInsurance ? $visitInsurance->insurance_provider_id : null,
                'pricing_source'       => $cashPrice > $unitPrice ? 'payer_specific_price' : 'cash_price',
                '_record_usage'        => false, // already recorded in attachServices()
            ];
        }

        // 2. Auto-consultation fee ──────────────────────────────────────────
        $hasConsultationService = $visit->visitServices
            ->filter(fn ($vs) => $vs->serviceCatalog && $vs->serviceCatalog->category === 'consultation')
            ->isNotEmpty();

        if (! $hasConsultationService) {
            $consultationService = ServiceCatalog::where('category', 'consultation')
                ->where('is_active', true)
                ->first();

            if ($consultationService) {
                $snap         = $this->priceResolver->resolveForVisit($consultationService, $visit);
                $unitPrice    = $snap['selected_price'];
                [$coveredAmt, $sessionOffset] = $this->evalAndOffset(
                    $hasInsurance, $visitInsurance, $visit,
                    $unitPrice, $sessionOffset
                );

                $items[] = [
                    'service_catalog_id'   => $consultationService->id,
                    'description'          => $consultationService->name,
                    'quantity'             => 1,
                    'unit_price'           => $unitPrice,
                    'is_nhis_covered'      => $coveredAmt > 0,
                    'nhis_approved_amount' => $coveredAmt,
                    'cash_price'           => $snap['cash_price'],
                    'discount_amount'      => $snap['discount_amount'],
                    'payer_type'           => $snap['payer_type'],
                    'insurance_provider_id' => $snap['insurance_provider_id'],
                    'pricing_source'       => $snap['pricing_source'],
                    '_record_usage'        => $coveredAmt > 0,
                    '_insurance'           => $visitInsurance,
                    '_coverage_reason'     => null,
                ];
            }
        }

        // 3. Lab tests ──────────────────────────────────────────────────────
        $visit->loadMissing('labRequests.items.labTest');
        foreach ($visit->labRequests as $labRequest) {
            foreach ($labRequest->items as $item) {
                $labService = ServiceCatalog::where('category', 'lab')
                    ->where('code', 'LAB-' . $item->labTest->code)
                    ->where('is_active', true)
                    ->first();

                if ($labService) {
                    $snap      = $this->priceResolver->resolveForVisit($labService, $visit);
                    $unitPrice = $snap['selected_price'];
                    [$coveredAmt, $sessionOffset] = $this->evalAndOffset(
                        $hasInsurance, $visitInsurance, $visit,
                        $unitPrice, $sessionOffset
                    );

                    $items[] = [
                        'service_catalog_id'   => $labService->id,
                        'description'          => $item->labTest->name,
                        'quantity'             => 1,
                        'unit_price'           => $unitPrice,
                        'is_nhis_covered'      => $coveredAmt > 0,
                        'nhis_approved_amount' => $coveredAmt,
                        'cash_price'           => $snap['cash_price'],
                        'discount_amount'      => $snap['discount_amount'],
                        'payer_type'           => $snap['payer_type'],
                        'insurance_provider_id' => $snap['insurance_provider_id'],
                        'pricing_source'       => $snap['pricing_source'],
                        '_record_usage'        => $coveredAmt > 0,
                        '_insurance'           => $visitInsurance,
                        '_coverage_reason'     => null,
                    ];
                }
            }
        }

        // 4. Prescriptions ──────────────────────────────────────────────────
        $visit->loadMissing('prescriptions.items.drug');
        foreach ($visit->prescriptions as $prescription) {
            foreach ($prescription->items as $prescItem) {
                $drugService = ServiceCatalog::where('category', 'pharmacy')
                    ->where('code', 'DRUG-' . ($prescItem->drug->code ?? $prescItem->drug_id))
                    ->where('is_active', true)
                    ->first();

                if ($drugService) {
                    $snap         = $this->priceResolver->resolveForVisit($drugService, $visit);
                    $unitPrice    = $snap['selected_price'];
                    $linePrice    = $unitPrice * $prescItem->quantity;

                    [$coveredAmt, $sessionOffset] = $this->evalAndOffset(
                        $hasInsurance, $visitInsurance, $visit,
                        $linePrice, $sessionOffset
                    );

                    $items[] = [
                        'service_catalog_id'   => $drugService->id,
                        'description'          => $prescItem->drug->name . ' (' . $prescItem->quantity . ')',
                        'quantity'             => $prescItem->quantity,
                        'unit_price'           => $unitPrice,
                        'is_nhis_covered'      => $coveredAmt > 0,
                        'nhis_approved_amount' => $coveredAmt,
                        'cash_price'           => $snap['cash_price'],
                        'discount_amount'      => max(0.0, round(($snap['cash_price'] - $unitPrice) * $prescItem->quantity, 2)),
                        'payer_type'           => $snap['payer_type'],
                        'insurance_provider_id' => $snap['insurance_provider_id'],
                        'pricing_source'       => $snap['pricing_source'],
                        '_record_usage'        => $coveredAmt > 0,
                        '_insurance'           => $visitInsurance,
                        '_coverage_reason'     => null,
                    ];
                }
            }
        }

        return $items;
    }

    /**
     * Get invoice statistics.
     */
    public function getStats(): array
    {
        return [
            'total_invoices'     => Invoice::count(),
            'pending_invoices'   => Invoice::unpaid()->count(),
            'total_revenue'      => Payment::whereMonth('paid_at', now()->month)->sum('amount'),
            'today_revenue'      => Payment::whereDate('paid_at', today())->sum('amount'),
            'outstanding_balance' => Invoice::unpaid()->sum('balance'),
        ];
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    /**
     * Evaluate coverage for one item and return [coveredAmount, newSessionOffset].
     */
    private function evalAndOffset(
        bool $hasInsurance,
        $visitInsurance,
        Visit $visit,
        float $price,
        float $sessionOffset
    ): array {
        if (! $hasInsurance || ! $visitInsurance) {
            return [0.0, $sessionOffset];
        }

        $eval       = $this->insuranceService->evaluateCoverage($visitInsurance, $visit, $price, $sessionOffset);
        $covered    = $eval['covered_amount'];
        $newOffset  = $sessionOffset + $covered;

        return [$covered, $newOffset];
    }
}
