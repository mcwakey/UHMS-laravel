<?php

namespace App\Services;

use App\Enums\BillingType;
use App\Enums\InvoiceStatus;
use App\Enums\VisitStatus;
use App\Events\PaymentRecorded;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\ServiceCatalog;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

class BillingService
{
    /**
     * Create an invoice for a visit.
     */
    public function createInvoice(array $data, array $items): Invoice
    {
        return DB::transaction(function () use ($data, $items) {
            $invoiceNumber = Invoice::generateNumber('INV', 'invoices', 'invoice_number');

            // Calculate totals
            $subtotal = 0;
            $nhisAmount = 0;

            foreach ($items as $item) {
                $lineTotal = ($item['unit_price'] ?? 0) * ($item['quantity'] ?? 1);
                $subtotal += $lineTotal;
                if (!empty($item['is_nhis_covered']) && !empty($item['nhis_approved_amount'])) {
                    $nhisAmount += $item['nhis_approved_amount'];
                }
            }

            $taxAmount = $data['tax_amount'] ?? 0;
            $discountAmount = $data['discount_amount'] ?? 0;
            $totalAmount = $subtotal + $taxAmount - $discountAmount;
            $balance = $totalAmount - $nhisAmount;

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'visit_id' => $data['visit_id'],
                'patient_id' => $data['patient_id'],
                'billing_type' => $data['billing_type'],
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'nhis_amount' => $nhisAmount,
                'total_amount' => $totalAmount,
                'amount_paid' => $nhisAmount, // NHIS portion counts as paid
                'balance' => $balance,
                'status' => $nhisAmount >= $totalAmount ? InvoiceStatus::PAID->value : InvoiceStatus::PENDING->value,
                'due_date' => $data['due_date'] ?? now()->addDays(30),
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Create invoice items
            foreach ($items as $item) {
                $lineTotal = ($item['unit_price'] ?? 0) * ($item['quantity'] ?? 1);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'service_catalog_id' => $item['service_catalog_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['unit_price'],
                    'total_price' => $lineTotal,
                    'is_nhis_covered' => $item['is_nhis_covered'] ?? false,
                    'nhis_approved_amount' => $item['nhis_approved_amount'] ?? 0,
                ]);
            }

            // Transition visit to billing if appropriate
            $visit = Visit::find($data['visit_id']);
            if ($visit && in_array(VisitStatus::BILLING, $visit->status->allowedTransitions())) {
                $visit->update(['status' => VisitStatus::BILLING->value]);
            }

            return $invoice->load('items', 'patient', 'visit');
        });
    }

    /**
     * Record a payment against an invoice.
     */
    public function recordPayment(Invoice $invoice, array $data): Payment
    {
        return DB::transaction(function () use ($invoice, $data) {
            $paymentNumber = Payment::generateNumber('PAY', 'payments', 'payment_number');

            $payment = Payment::create([
                'payment_number' => $paymentNumber,
                'invoice_id' => $invoice->id,
                'patient_id' => $invoice->patient_id,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'received_by' => auth()->id(),
                'notes' => $data['notes'] ?? null,
                'paid_at' => $data['paid_at'] ?? now(),
            ]);

            // Update invoice totals
            $totalPaid = $invoice->amount_paid + $data['amount'];
            $balance = $invoice->total_amount - $totalPaid;

            $status = InvoiceStatus::PARTIALLY_PAID;
            if ($balance <= 0) {
                $status = InvoiceStatus::PAID;
                $balance = 0;
            }

            $invoice->update([
                'amount_paid' => $totalPaid,
                'balance' => $balance,
                'status' => $status->value,
            ]);

            // If fully paid, transition visit to completed
            if ($status === InvoiceStatus::PAID) {
                $visit = $invoice->visit;
                if ($visit && $visit->status === VisitStatus::BILLING) {
                    $visit->update([
                        'status' => VisitStatus::COMPLETED->value,
                        'checked_out_at' => now(),
                    ]);
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
     * Auto-generate invoice items from visit services (visit_services table + consultation, lab, prescriptions).
     */
    public function generateItemsFromVisit(Visit $visit): array
    {
        $items = [];

        // Determine insurance coverage for this visit
        $visitInsurance = $visit->visitInsurance;
        $provider = $visitInsurance?->insuranceProvider;
        $hasInsurance = $visitInsurance && $visitInsurance->is_active && !$visitInsurance->is_expired && $provider && !$provider->is_default;
        $coveragePercentage = $hasInsurance ? ($provider->coverage_percentage / 100) : 0;

        // 1. Visit Services (from visit_services table — the primary billing source)
        $visit->loadMissing('visitServices.serviceCatalog');
        foreach ($visit->visitServices as $vs) {
            $catalog = $vs->serviceCatalog;
            $totalPrice = $vs->total_price;
            $insuranceCoveredAmount = $hasInsurance
                ? round($totalPrice * $coveragePercentage, 2)
                : 0;

            $items[] = [
                'service_catalog_id' => $vs->service_catalog_id,
                'description' => $catalog ? $catalog->name : 'Service',
                'quantity' => $vs->quantity,
                'unit_price' => $vs->unit_price,
                'is_nhis_covered' => $hasInsurance,
                'nhis_approved_amount' => $insuranceCoveredAmount,
            ];
        }

        // 2. Consultation fee (only if no visit_services cover consultation)
        $hasConsultationService = $visit->visitServices
            ->filter(fn ($vs) => $vs->serviceCatalog && $vs->serviceCatalog->category === 'consultation')
            ->isNotEmpty();

        if (!$hasConsultationService) {
            $consultationService = ServiceCatalog::where('category', 'consultation')
                ->where('is_active', true)
                ->first();

            if ($consultationService) {
                $insuranceCoveredAmount = $hasInsurance
                    ? round($consultationService->price * $coveragePercentage, 2)
                    : 0;
                $items[] = [
                    'service_catalog_id' => $consultationService->id,
                    'description' => $consultationService->name,
                    'quantity' => 1,
                    'unit_price' => $consultationService->price,
                    'is_nhis_covered' => $hasInsurance,
                    'nhis_approved_amount' => $insuranceCoveredAmount,
                ];
            }
        }

        // Lab tests
        $visit->loadMissing('labRequests.items.labTest');
        foreach ($visit->labRequests as $labRequest) {
            foreach ($labRequest->items as $item) {
                $labService = ServiceCatalog::where('category', 'lab')
                    ->where('code', 'LAB-' . $item->labTest->code)
                    ->where('is_active', true)
                    ->first();

                if ($labService) {
                    $labCoveredAmount = $hasInsurance
                        ? round($labService->price * $coveragePercentage, 2)
                        : 0;
                    $items[] = [
                        'service_catalog_id' => $labService->id,
                        'description' => $item->labTest->name,
                        'quantity' => 1,
                        'unit_price' => $labService->price,
                        'is_nhis_covered' => $hasInsurance,
                        'nhis_approved_amount' => $labCoveredAmount,
                    ];
                }
            }
        }

        // Prescriptions (pharmacy items)
        $visit->loadMissing('prescriptions.items.drug');
        foreach ($visit->prescriptions as $prescription) {
            foreach ($prescription->items as $item) {
                $drugService = ServiceCatalog::where('category', 'pharmacy')
                    ->where('code', 'DRUG-' . ($item->drug->code ?? $item->drug_id))
                    ->where('is_active', true)
                    ->first();

                if ($drugService) {
                    $drugCoveredAmount = $hasInsurance
                        ? round($drugService->price * $coveragePercentage * $item->quantity, 2)
                        : 0;
                    $items[] = [
                        'service_catalog_id' => $drugService->id,
                        'description' => $item->drug->name . ' (' . $item->quantity . ')',
                        'quantity' => $item->quantity,
                        'unit_price' => $drugService->price,
                        'is_nhis_covered' => $hasInsurance,
                        'nhis_approved_amount' => $drugCoveredAmount,
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
}
