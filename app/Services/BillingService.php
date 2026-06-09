<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\Invoice;
use App\Models\InvoiceDiscount;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ServiceCatalog;
use App\Models\Visit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingService
{
    public function __construct(
        protected InsuranceService $insuranceService,
        protected VisitWorkflowService $visitWorkflowService,
        protected ServicePriceResolver $priceResolver,
        protected ?InvoiceService $invoiceService = null,
        protected ?ProductPriceResolver $productPriceResolver = null,
        protected ?ActivityLogService $logger = null,
    ) {
        $this->invoiceService = $this->invoiceService ?: app(InvoiceService::class);
        $this->productPriceResolver = $this->productPriceResolver ?: app(ProductPriceResolver::class);
        $this->logger = $this->logger ?: app(ActivityLogService::class);
    }

    /**
     * Add a single billable item to the visit's (single) invoice.
     *
     * Canonical pricing model (UHMS billing rules):
     *   cash_price        = service base price
     *   insurance_price   = resolved insurance rate (null for cash & carry)
     *   selected_price    = insurance_price when insurance applies, else cash_price
     *   insurance_covered = (cash_price - insurance_price) * quantity   (INFO ONLY)
     *   discount_amount   = 0 on creation (set later via applyDiscount)
     *   patient_payable   = (selected_price * quantity) - discount_amount
     *   paid_amount       = 0
     *   balance           = patient_payable - paid_amount
     *
     * IMPORTANT: insurance_covered is NEVER treated as a payment and NEVER
     * reduces patient_payable. It is purely informational (the benefit the
     * insurer provides off the cash rate).
     *
     * @throws \RuntimeException on duplicate billing
     */
    public function addItemToVisitInvoice(
        Visit $visit,
        ServiceCatalog $service,
        string $sourceType,
        ?int $sourceId = null,
        int $quantity = 1,
        ?int $departmentId = null,
        ?string $description = null,
        ?float $unitPriceOverride = null,
    ): InvoiceItem {
        if ($quantity < 1) {
            throw new \RuntimeException('Quantity must be at least 1.');
        }

        // G6: respect is_billable flag on the service catalog (symmetric with products).
        // Treat NULL as billable for backward compatibility with rows that pre-date the column.
        if (array_key_exists('is_billable', $service->getAttributes()) && $service->is_billable === false) {
            throw new \RuntimeException("Service '{$service->name}' is marked non-billable and cannot be added to an invoice.");
        }

        return DB::transaction(function () use ($visit, $service, $sourceType, $sourceId, $quantity, $departmentId, $description, $unitPriceOverride) {
            $invoice = $this->invoiceService->getOrCreateVisitInvoice($visit);

            // Duplicate guard #1: same source_type+source_id may only appear once per invoice.
            if ($sourceId !== null) {
                $dup = InvoiceItem::where('invoice_id', $invoice->id)
                    ->where('source_type', $sourceType)
                    ->where('source_id', $sourceId)
                    ->exists();
                if ($dup) {
                    throw new \RuntimeException(
                        "Duplicate billing prevented for {$sourceType}#{$sourceId} on invoice {$invoice->invoice_number}."
                    );
                }
            }

            // Duplicate guard #2: the same visit-creation service may not be billed
            // twice across different source_types. This prevents the historical bug
            // where selected services were billed once as 'service_catalog' and again
            // as 'visit_service' (different source_type bypassed guard #1).
            //
            // Source types that represent "this service was selected on the visit":
            //   service_catalog, visit_service, visit_selected_service, visit_creation
            $visitCreationSources = ['service_catalog', 'visit_service', 'visit_selected_service', 'visit_creation'];
            if (in_array($sourceType, $visitCreationSources, true)) {
                $dupService = InvoiceItem::where('invoice_id', $invoice->id)
                    ->where('service_catalog_id', $service->id)
                    ->whereIn('source_type', $visitCreationSources)
                    ->exists();
                if ($dupService) {
                    throw new \RuntimeException(
                        "Duplicate billing prevented: service '{$service->name}' is already billed on invoice {$invoice->invoice_number}."
                    );
                }
            }

            // Resolve insurance-aware pricing snapshot.
            $snap = $this->priceResolver->resolveForVisit($service, $visit);
            $cashPrice = (float) ($snap['cash_price'] ?? $service->price);
            $selectedPrice = (float) ($snap['selected_price'] ?? $cashPrice);
            $payerType = $snap['payer_type'] ?? 'cash';
            $providerId = $snap['insurance_provider_id'] ?? null;
            $insType = $snap['insurance_type'] ?? null;
            $pricingSrc = $snap['pricing_source'] ?? 'cash_price';
            $isInsurance = $payerType === 'insurance';

            if ($unitPriceOverride !== null) {
                $cashPrice = $unitPriceOverride;
                $selectedPrice = $unitPriceOverride;
                $insurancePrice = null;
                $payerType = 'cash';
                $providerId = null;
                $insType = null;
                $pricingSrc = 'manual_override';
                $isInsurance = false;
            }

            // Canonical formulas (per UHMS billing rules).
            $insurancePrice = $isInsurance ? $selectedPrice : null;
            $lineTotal = round($selectedPrice * $quantity, 2);
            $insuranceCovered = $isInsurance
                ? max(0.0, round(($cashPrice - $selectedPrice) * $quantity, 2))
                : 0.0;
            $discountAmount = 0.0; // discounts are applied later via applyDiscount()
            $patientPayable = max(0.0, round($lineTotal - $discountAmount, 2));
            $balance = $patientPayable;
            $paymentStatus = $patientPayable <= 0.0 ? 'paid' : 'unpaid';

            // Resolve visit insurance link (for audit only; no longer reduces payable).
            $visit->loadMissing('visitInsurance.insuranceProvider');
            $visitIns = $visit->visitInsurance;
            $hasIns = $visitIns && $visitIns->is_active && ! $visitIns->is_expired
                && $visitIns->insuranceProvider && ! $visitIns->insuranceProvider->is_default;

            $item = InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'visit_id' => $visit->id,
                'patient_id' => $invoice->patient_id,
                'service_catalog_id' => $service->id,
                'department_id' => $departmentId ?? $service->department_id,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => $description ?: $service->name,
                'quantity' => $quantity,
                // Legacy unit_price column is still NOT NULL on older schemas;
                // mirror selected_price so existing reports remain consistent.
                'unit_price' => $selectedPrice,
                'cash_price' => $cashPrice,
                'insurance_price' => $insurancePrice,
                'selected_price' => $selectedPrice,
                'insurance_covered' => $insuranceCovered,
                'discount_amount' => $discountAmount,
                'patient_payable' => $patientPayable,
                'paid_amount' => 0,
                'balance' => $balance,
                'payment_status' => $paymentStatus,
                'total_price' => $lineTotal,
                'payer_type' => $payerType,
                'insurance_provider_id' => $providerId,
                'patient_insurance_id' => $hasIns ? $visitIns->id : null,
                'insurance_type' => $insType,
                'pricing_source' => $pricingSrc,
                'created_by' => Auth::id(),
            ]);

            // Update invoice header totals + status.
            $this->invoiceService->recalculateTotals($invoice->fresh('items'));

            app(ServiceRenderingService::class)->createForInvoiceItem(
                $item->fresh(['invoice', 'visit.emergencyCase', 'visit.admission', 'visit.activeConsultationRoute', 'patient', 'department', 'serviceCatalog.department']),
                Auth::user()
            );

            return $item->fresh();
        });
    }

    /**
     * Apply (or update) a manual discount on an invoice item and recalculate.
     *
     * Rules:
     *   - discount_amount must be >= 0
     *   - discount_amount must not exceed (selected_price * quantity)
     *   - patient_payable = (selected_price * quantity) - discount_amount
     *   - balance         = patient_payable - paid_amount
     *   - paid_amount is NEVER modified here
     *   - Invoice totals + status are recalculated
     *   - The action is logged via spatie/activitylog (if installed) and
     *     a Laravel log entry recording the actor.
     *
     * @throws \InvalidArgumentException on validation failure
     * @throws AuthorizationException when user lacks permission
     */
    public function applyDiscount(InvoiceItem $item, float $discountAmount, string $reason, ?User $user = null): InvoiceItem
    {
        $user ??= Auth::user();

        if ($discountAmount < 0) {
            throw new \InvalidArgumentException('Discount amount must be >= 0.');
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw new \InvalidArgumentException('A reason is required for discount changes.');
        }

        if (in_array((string) $item->payment_status, ['paid', 'cancelled', 'voided', 'waived'], true)) {
            throw new \InvalidArgumentException('Discounts cannot be changed on paid, cancelled, voided, or waived items.');
        }

        $oldDiscount = round((float) $item->discount_amount, 2);
        $isRemoval = $discountAmount <= 0.0 && $oldDiscount > 0.0;
        $isReduction = $discountAmount < $oldDiscount - 0.001;

        if ($user && method_exists($user, 'can')) {
            if ($isReduction) {
                if (! ($user->can('billing.discount.remove') || $user->can('billing.discount.reverse'))) {
                    throw new AuthorizationException('Not authorized to reverse or reduce discounts.');
                }
            } elseif (! ($user->can('billing.discount.apply') || $user->can('invoices.discount'))) {
                throw new AuthorizationException('Not authorized to apply discounts.');
            }
        }

        $lineTotal = round((float) $item->selected_price * (int) $item->quantity, 2);
        if ($discountAmount > $lineTotal + 0.001) {
            throw new \InvalidArgumentException("Discount (₵{$discountAmount}) cannot exceed line total (₵{$lineTotal}).");
        }

        if (! $isRemoval && $discountAmount <= 0.0) {
            throw new \InvalidArgumentException('Discount amount must be greater than 0.');
        }

        $allowedPercent = (float) config('billing.discount.max_without_override_percent', 10);
        $allowedAmount = round($lineTotal * max(0.0, $allowedPercent) / 100, 2);
        $isOverride = ! $isRemoval && $discountAmount > $allowedAmount + 0.001;
        if ($isOverride && $user && method_exists($user, 'can') && ! $user->can('billing.discount.override_limit')) {
            throw new AuthorizationException(
                "Discount exceeds the configured {$allowedPercent}% limit and requires override permission."
            );
        }

        return DB::transaction(function () use ($item, $discountAmount, $lineTotal, $user, $reason, $isRemoval, $isReduction, $isOverride) {
            $item->refresh();
            $oldValues = [
                'discount_amount' => round((float) $item->discount_amount, 2),
                'patient_payable' => round((float) $item->patient_payable, 2),
                'balance' => round((float) $item->balance, 2),
            ];
            $discountToReverse = null;
            if ($isReduction) {
                $discountToReverse = InvoiceDiscount::query()
                    ->where('invoice_item_id', $item->id)
                    ->whereColumn('new_discount_amount', '>', 'old_discount_amount')
                    ->whereNull('reversed_at')
                    ->latest('performed_at')
                    ->lockForUpdate()
                    ->first();
            }

            $paid = (float) $item->paid_amount;
            $patientPayable = max(0.0, round($lineTotal - $discountAmount, 2));
            $balance = max(0.0, round($patientPayable - $paid, 2));

            $status = match (true) {
                $patientPayable <= 0.0 => 'paid',
                $balance <= 0.0 => 'paid',
                $paid > 0.0 => 'partially_paid',
                default => 'unpaid',
            };

            $item->forceFill([
                'discount_amount' => round($discountAmount, 2),
                'patient_payable' => $patientPayable,
                'balance' => $balance,
                'payment_status' => $status,
            ])->save();

            $discountEvent = InvoiceDiscount::create([
                'invoice_id' => $item->invoice_id,
                'invoice_item_id' => $item->id,
                'action' => $isRemoval ? InvoiceDiscount::ACTION_REMOVED : InvoiceDiscount::ACTION_APPLIED,
                'line_total' => $lineTotal,
                'old_discount_amount' => $oldValues['discount_amount'],
                'new_discount_amount' => round($discountAmount, 2),
                'old_patient_payable' => $oldValues['patient_payable'],
                'new_patient_payable' => $patientPayable,
                'old_balance' => $oldValues['balance'],
                'new_balance' => $balance,
                'is_override' => $isOverride,
                'reason' => $reason,
                'performed_by' => $user?->id,
                'performed_at' => now(),
                'reverses_discount_id' => $discountToReverse?->id,
            ]);

            Log::info('Invoice item discount applied', [
                'invoice_item_id' => $item->id,
                'invoice_id' => $item->invoice_id,
                'amount' => $discountAmount,
                'applied_by' => $user?->id,
                'is_override' => $isOverride,
            ]);

            $this->logger?->log(
                LogModule::BILLING,
                $isRemoval ? 'DISCOUNT_REMOVED' : ($isOverride ? 'DISCOUNT_OVERRIDE_APPLIED' : 'DISCOUNT_APPLIED'),
                [
                    'old_values' => $oldValues,
                    'new_values' => [
                        'discount_amount' => round($discountAmount, 2),
                        'patient_payable' => $patientPayable,
                        'balance' => $balance,
                    ],
                    'reason' => $reason,
                    'severity' => $isOverride || $isRemoval ? LogSeverity::CRITICAL : LogSeverity::WARNING,
                    'invoice_id' => $item->invoice_id,
                    'invoice_item_id' => $item->id,
                    'patient_id' => $item->patient_id,
                    'visit_id' => $item->visit_id,
                    'metadata' => [
                        'line_total' => $lineTotal,
                        'amount' => round($discountAmount, 2),
                        'is_override' => $isOverride,
                    ],
                ],
                $item,
                $isRemoval ? 'Discount removed from invoice item' : 'Discount applied to invoice item'
            );

            app(BillingAccountingPostingService::class)->postDiscount($discountEvent);

            if ($isReduction && $discountToReverse) {
                $discountEvent->refresh();
                $discountToReverse->forceFill([
                    'reversal_journal_entry_id' => $discountEvent->journal_entry_id,
                    'reversed_at' => now(),
                    'reversed_by' => $user?->id,
                    'reversal_reason' => $reason,
                    'accounting_status' => $discountEvent->journal_entry_id ? BillingAccountingPostingService::STATUS_REVERSED : $discountToReverse->accounting_status,
                ])->save();
            }

            // Refresh invoice header totals + status.
            $invoice = $item->invoice()->with('items')->first();
            if ($invoice) {
                $this->invoiceService->recalculateTotals($invoice);
            }

            return $item->fresh();
        });
    }

    /**
     * Add a billable Product (pharmacy item, ward consumable, etc.) to the visit's invoice.
     *
     * Guards:
     *   - product.is_billable must be true
     *   - duplicate source_type + source_id prevented per invoice
     *
     * Pricing is resolved via ProductPriceResolver (same priority as services:
     *   provider-specific → type-default → base_price cash fallback).
     *
     * @throws \RuntimeException when product is not billable or duplicate detected
     */
    public function addProductToVisitInvoice(
        Visit $visit,
        Product $product,
        string $sourceType,
        ?int $sourceId = null,
        int|float $quantity = 1,
        ?int $departmentId = null,
        ?string $description = null,
    ): InvoiceItem {
        if (! $product->is_billable) {
            throw new \RuntimeException(
                "Product '{$product->name}' is not billable. Enable billing on the product first."
            );
        }

        if ($quantity < 1) {
            throw new \RuntimeException('Quantity must be at least 1.');
        }

        return DB::transaction(function () use ($visit, $product, $sourceType, $sourceId, $quantity, $departmentId, $description) {
            $invoice = $this->invoiceService->getOrCreateVisitInvoice($visit);

            // Duplicate guard: same source_type + source_id may only appear once per invoice.
            if ($sourceId !== null) {
                $dup = InvoiceItem::where('invoice_id', $invoice->id)
                    ->where('source_type', $sourceType)
                    ->where('source_id', $sourceId)
                    ->exists();
                if ($dup) {
                    throw new \RuntimeException(
                        "Duplicate billing prevented for {$sourceType}#{$sourceId} on invoice {$invoice->invoice_number}."
                    );
                }
            }

            // Resolve insurance-aware pricing snapshot.
            $snap = $this->productPriceResolver->resolveForVisit($product, $visit);
            $cashPrice = (float) ($snap['cash_price'] ?? $product->base_price ?? 0);
            $selectedPrice = (float) ($snap['selected_price'] ?? $cashPrice);
            $payerType = $snap['payer_type'] ?? 'cash';
            $providerId = $snap['insurance_provider_id'] ?? null;
            $insType = $snap['insurance_type'] ?? null;
            $pricingSrc = $snap['pricing_source'] ?? 'cash_price';
            $isInsurance = $payerType === 'insurance';

            $insurancePrice = $isInsurance ? $selectedPrice : null;
            $lineTotal = round($selectedPrice * $quantity, 2);
            $insuranceCovered = $isInsurance
                ? max(0.0, round(($cashPrice - $selectedPrice) * $quantity, 2))
                : 0.0;
            $discountAmount = 0.0;
            $patientPayable = max(0.0, round($lineTotal - $discountAmount, 2));
            $balance = $patientPayable;
            $paymentStatus = $patientPayable <= 0.0 ? 'paid' : 'unpaid';

            $visit->loadMissing('visitInsurance.insuranceProvider');
            $visitIns = $visit->visitInsurance;
            $hasIns = $visitIns && $visitIns->is_active && ! $visitIns->is_expired
                && $visitIns->insuranceProvider && ! $visitIns->insuranceProvider->is_default;

            $item = InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'visit_id' => $visit->id,
                'patient_id' => $invoice->patient_id,
                'service_catalog_id' => null,
                'product_id' => $product->id,
                'department_id' => $departmentId,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => $description ?: $product->name,
                'quantity' => $quantity,
                'unit_price' => $selectedPrice,
                'cash_price' => $cashPrice,
                'insurance_price' => $insurancePrice,
                'selected_price' => $selectedPrice,
                'insurance_covered' => $insuranceCovered,
                'discount_amount' => $discountAmount,
                'patient_payable' => $patientPayable,
                'paid_amount' => 0,
                'balance' => $balance,
                'payment_status' => $paymentStatus,
                'total_price' => $lineTotal,
                'payer_type' => $payerType,
                'insurance_provider_id' => $providerId,
                'patient_insurance_id' => $hasIns ? $visitIns->id : null,
                'insurance_type' => $insType,
                'pricing_source' => $pricingSrc,
                'created_by' => Auth::id(),
            ]);

            $this->invoiceService->recalculateTotals($invoice->fresh('items'));

            return $item->fresh();
        });
    }

    /**
     * Convenience: skip if already billed; return existing item.
     */
    public function addItemIfNotBilled(
        Visit $visit,
        ServiceCatalog $service,
        string $sourceType,
        int $sourceId,
        int $quantity = 1,
        ?int $departmentId = null,
        ?string $description = null,
    ): InvoiceItem {
        $invoice = $this->invoiceService->getOrCreateVisitInvoice($visit);
        $existing = InvoiceItem::where('invoice_id', $invoice->id)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->first();
        if ($existing) {
            return $existing;
        }

        return $this->addItemToVisitInvoice(
            $visit, $service, $sourceType, $sourceId, $quantity, $departmentId, $description
        );
    }

    /**
     * Create an invoice for a visit.
     * After inserting items, links insurance usage records to the invoice and
     * records any lab/pharmacy usages that weren't captured at service-attach time.
     */
    public function createInvoice(array $data, array $items): Invoice
    {
        return DB::transaction(function () use ($data, $items) {
            $invoiceNumber = Invoice::generateNumber('INV', 'invoices', 'invoice_number');
            $visit = Visit::find($data['visit_id']);
            $actorId = Auth::id() ?? ($data['created_by'] ?? null) ?? $visit?->created_by;

            // Calculate totals from items
            $subtotal = 0;
            $insuranceAmount = 0;

            foreach ($items as $item) {
                $unitPrice = $item['selected_price'] ?? $item['unit_price'] ?? $item['cash_price'] ?? 0;
                $lineTotal = $unitPrice * ($item['quantity'] ?? 1);
                $subtotal += $lineTotal;
                if (! empty($item['is_nhis_covered']) && ! empty($item['nhis_approved_amount'])) {
                    $insuranceAmount += $item['nhis_approved_amount'];
                }
            }

            $taxAmount = $data['tax_amount'] ?? 0;
            $discountAmount = $data['discount_amount'] ?? 0;
            if ((float) $discountAmount > 0) {
                throw new \RuntimeException('Manual header discounts are not allowed. Apply item discounts with a reason.');
            }
            $totalAmount = $subtotal + $taxAmount - $discountAmount;
            $balance = $totalAmount - $insuranceAmount;

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'visit_id' => $data['visit_id'],
                'patient_id' => $data['patient_id'],
                'billing_type' => $data['billing_type'],
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'nhis_amount' => $insuranceAmount,
                'total_amount' => $totalAmount,
                'amount_paid' => $insuranceAmount,
                'balance' => max(0, $balance),
                'status' => $insuranceAmount >= $totalAmount ? InvoiceStatus::PAID->value : InvoiceStatus::PENDING->value,
                'due_date' => $data['due_date'] ?? now()->addDays(30),
                'notes' => $data['notes'] ?? null,
                'created_by' => $actorId,
            ]);

            foreach ($items as $item) {
                $quantity = $item['quantity'] ?? 1;
                $cashPrice = (float) ($item['cash_price'] ?? $item['unit_price'] ?? 0);
                $selectedPrice = (float) ($item['selected_price'] ?? $item['unit_price'] ?? $cashPrice);
                $payerType = $item['payer_type'] ?? 'cash';
                $isInsurance = $payerType === 'insurance';
                $insurancePrice = $isInsurance ? $selectedPrice : null;
                $lineTotal = round($selectedPrice * $quantity, 2);
                $insCovered = $isInsurance
                    ? max(0.0, round(($cashPrice - $selectedPrice) * $quantity, 2))
                    : 0.0;
                $discount = (float) ($item['discount_amount'] ?? 0);
                $payable = max(0.0, round($lineTotal - $discount, 2));

                $invoiceItem = InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'visit_id' => $data['visit_id'],
                    'patient_id' => $data['patient_id'],
                    'service_catalog_id' => $item['service_catalog_id'] ?? null,
                    'department_id' => $item['department_id'] ?? null,
                    'source_type' => $item['source_type'] ?? null,
                    'source_id' => $item['source_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $quantity,
                    'unit_price' => $selectedPrice,
                    'cash_price' => $cashPrice,
                    'insurance_price' => $insurancePrice,
                    'selected_price' => $selectedPrice,
                    'insurance_covered' => $insCovered,
                    'discount_amount' => $discount,
                    'patient_payable' => $payable,
                    'paid_amount' => 0,
                    'balance' => $payable,
                    'payment_status' => $payable > 0 ? 'unpaid' : 'paid',
                    'total_price' => $lineTotal,
                    'payer_type' => $payerType,
                    'insurance_provider_id' => $item['insurance_provider_id'] ?? null,
                    'patient_insurance_id' => $item['patient_insurance_id'] ?? null,
                    'insurance_type' => $item['insurance_type'] ?? null,
                    'pricing_source' => $item['pricing_source'] ?? null,
                    'created_by' => $actorId,
                ]);

                app(ServiceRenderingService::class)->createForInvoiceItem(
                    $invoiceItem->fresh(['invoice', 'visit.emergencyCase', 'visit.admission', 'visit.activeConsultationRoute', 'patient', 'department', 'serviceCatalog.department']),
                    Auth::user()
                );

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
            if ($visit?->visitInsurance) {
                $this->insuranceService->linkInvoiceToUsages(
                    $visit->id,
                    $invoice->id,
                    $visit->visitInsurance->id
                );
            }

            if ($visit) {
                $this->visitWorkflowService->moveToBilling($visit);
            }

            return $invoice->load('items', 'patient', 'visit');
        });
    }

    /**
     * Record a payment against an invoice.
     *
     * Backward-compatible: when called without explicit allocations the payment
     * is auto-distributed across unpaid invoice items (oldest first). For
     * itemised payments, use PaymentService::recordPayment directly with
     * the allocations array.
     */
    public function recordPayment(Invoice $invoice, array $data, array $allocations = []): Payment
    {
        return app(PaymentService::class)->recordPayment($invoice, $data, $allocations);
    }

    /**
     * Cancel an invoice.
     */
    public function cancelInvoice(Invoice $invoice): Invoice
    {
        $invoice->update(['status' => InvoiceStatus::CANCELLED->value]);
        app(BillingAccountingPostingService::class)->reverseInvoice($invoice, 'Invoice cancelled');

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
        $provider = $visitInsurance?->insuranceProvider;
        $hasInsurance = $visitInsurance
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
            $catalog = $vs->serviceCatalog;
            $insuredAmount = (float) $vs->insurance_covered;
            $cashPrice = $catalog ? (float) $catalog->price : (float) $vs->unit_price;
            $unitPrice = (float) $vs->unit_price;
            $quantity = (int) $vs->quantity;
            $discount = max(0.0, round(($cashPrice - $unitPrice) * $quantity, 2));

            // Prefer the snapshot stored on visit_services (already normalized by
            // ServicePricingService). Fall back to a derived value for legacy rows.
            $payerType = $vs->payment_type
                ?? ($hasInsurance ? 'insurance' : 'cash');
            $pricingSource = $vs->pricing_source
                ?? ($cashPrice > $unitPrice ? 'provider_specific' : 'cash_and_carry');

            $items[] = [
                'service_catalog_id' => $vs->service_catalog_id,
                'description' => $catalog ? $catalog->name : 'Service',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'is_nhis_covered' => $insuredAmount > 0,
                'nhis_approved_amount' => $insuredAmount,
                'cash_price' => $cashPrice,
                'discount_amount' => $discount,
                'payer_type' => $payerType,
                'insurance_provider_id' => $hasInsurance ? $visitInsurance->insurance_provider_id : null,
                'pricing_source' => $pricingSource,
                '_record_usage' => false, // already recorded in attachServices()
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
                $snap = $this->priceResolver->resolveForVisit($consultationService, $visit);
                $unitPrice = $snap['selected_price'];
                [$coveredAmt, $sessionOffset] = $this->evalAndOffset(
                    $hasInsurance, $visitInsurance, $visit,
                    $unitPrice, $sessionOffset
                );

                $items[] = [
                    'service_catalog_id' => $consultationService->id,
                    'description' => $consultationService->name,
                    'quantity' => 1,
                    'unit_price' => $unitPrice,
                    'is_nhis_covered' => $coveredAmt > 0,
                    'nhis_approved_amount' => $coveredAmt,
                    'cash_price' => $snap['cash_price'],
                    'discount_amount' => $snap['discount_amount'],
                    'payer_type' => $snap['payer_type'],
                    'insurance_provider_id' => $snap['insurance_provider_id'],
                    'pricing_source' => $snap['pricing_source'],
                    '_record_usage' => $coveredAmt > 0,
                    '_insurance' => $visitInsurance,
                    '_coverage_reason' => null,
                ];
            }
        }

        // 3. Lab tests ──────────────────────────────────────────────────────
        $visit->loadMissing('labRequests.items.labTest');
        foreach ($visit->labRequests as $labRequest) {
            foreach ($labRequest->items as $item) {
                if (! $item->labTest) {
                    continue;
                }
                $labService = ServiceCatalog::where('category', 'lab')
                    ->where('code', 'LAB-'.$item->labTest->code)
                    ->where('is_active', true)
                    ->first();

                if ($labService) {
                    $snap = $this->priceResolver->resolveForVisit($labService, $visit);
                    $unitPrice = $snap['selected_price'];
                    [$coveredAmt, $sessionOffset] = $this->evalAndOffset(
                        $hasInsurance, $visitInsurance, $visit,
                        $unitPrice, $sessionOffset
                    );

                    $items[] = [
                        'service_catalog_id' => $labService->id,
                        'description' => $item->labTest->name,
                        'quantity' => 1,
                        'unit_price' => $unitPrice,
                        'is_nhis_covered' => $coveredAmt > 0,
                        'nhis_approved_amount' => $coveredAmt,
                        'cash_price' => $snap['cash_price'],
                        'discount_amount' => $snap['discount_amount'],
                        'payer_type' => $snap['payer_type'],
                        'insurance_provider_id' => $snap['insurance_provider_id'],
                        'pricing_source' => $snap['pricing_source'],
                        '_record_usage' => $coveredAmt > 0,
                        '_insurance' => $visitInsurance,
                        '_coverage_reason' => null,
                    ];
                }
            }
        }

        // 4. Prescriptions ──────────────────────────────────────────────────
        $visit->loadMissing('prescriptions.items.drug');
        foreach ($visit->prescriptions as $prescription) {
            foreach ($prescription->items as $prescItem) {
                $drugService = ServiceCatalog::where('category', 'pharmacy')
                    ->where('code', 'DRUG-'.($prescItem->drug->code ?? $prescItem->drug_id))
                    ->where('is_active', true)
                    ->first();

                if ($drugService) {
                    $snap = $this->priceResolver->resolveForVisit($drugService, $visit);
                    $unitPrice = $snap['selected_price'];
                    $linePrice = $unitPrice * $prescItem->quantity;

                    [$coveredAmt, $sessionOffset] = $this->evalAndOffset(
                        $hasInsurance, $visitInsurance, $visit,
                        $linePrice, $sessionOffset
                    );

                    $items[] = [
                        'service_catalog_id' => $drugService->id,
                        'description' => $prescItem->drug->name.' ('.$prescItem->quantity.')',
                        'quantity' => $prescItem->quantity,
                        'unit_price' => $unitPrice,
                        'is_nhis_covered' => $coveredAmt > 0,
                        'nhis_approved_amount' => $coveredAmt,
                        'cash_price' => $snap['cash_price'],
                        'discount_amount' => max(0.0, round(($snap['cash_price'] - $unitPrice) * $prescItem->quantity, 2)),
                        'payer_type' => $snap['payer_type'],
                        'insurance_provider_id' => $snap['insurance_provider_id'],
                        'pricing_source' => $snap['pricing_source'],
                        '_record_usage' => $coveredAmt > 0,
                        '_insurance' => $visitInsurance,
                        '_coverage_reason' => null,
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
            'total_invoices' => Invoice::count(),
            'pending_invoices' => Invoice::unpaid()->count(),
            'total_revenue' => Payment::whereMonth('paid_at', now()->month)->sum('amount'),
            'today_revenue' => Payment::whereDate('paid_at', today())->sum('amount'),
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

        $eval = $this->insuranceService->evaluateCoverage($visitInsurance, $visit, $price, $sessionOffset);
        $covered = $eval['covered_amount'];
        $newOffset = $sessionOffset + $covered;

        return [$covered, $newOffset];
    }
}
