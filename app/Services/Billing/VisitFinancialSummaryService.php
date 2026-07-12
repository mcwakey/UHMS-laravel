<?php

namespace App\Services\Billing;

use App\Data\Billing\VisitFinancialSummary;
use App\Enums\InvoiceStatus;
use App\Models\InvoiceReceivable;
use App\Models\ServiceRendering;
use App\Models\Visit;

class VisitFinancialSummaryService
{
    public function summarize(Visit $visit): VisitFinancialSummary
    {
        $invoices = $visit->invoices()->with(['items', 'receivables'])->get()
            ->reject(fn ($invoice) => in_array($invoice->status, [InvoiceStatus::CANCELLED, InvoiceStatus::REFUNDED], true));
        $items = $invoices->flatMap->items->reject(fn ($item) => in_array($item->payment_status, ['cancelled', 'voided'], true));
        $receivables = $invoices->flatMap->receivables->reject(fn ($r) => $r->status === InvoiceReceivable::STATUS_CANCELLED);

        $patient = $receivables->where('payer_type', InvoiceReceivable::PAYER_PATIENT);
        $responsibility = $patient->isNotEmpty() ? $patient->sum('allocated_amount') : $items->sum('patient_payable');
        $paid = $patient->isNotEmpty() ? $patient->sum('paid_amount') : $items->sum('paid_amount');
        $outstanding = $patient->isNotEmpty() ? $patient->sum('balance') : $items->sum('balance');
        $payerAmount = fn (string $type) => $receivables->where('payer_type', $type)->sum('allocated_amount');
        $unbilled = $visit->serviceRenderings()->whereNull('invoice_item_id')
            ->whereHas('service', fn ($query) => $query->where('is_billable', true))
            ->whereNotIn('status', [ServiceRendering::STATUS_CANCELLED, ServiceRendering::STATUS_NOT_RENDERED])->exists();

        return new VisitFinancialSummary(
            currency: (string) config('app.currency', 'GHS'),
            patientResponsibility: $this->money($responsibility),
            patientPaid: $this->money($paid),
            patientOutstanding: $this->money(max(0, $outstanding)),
            insuranceResponsibility: $this->money($payerAmount(InvoiceReceivable::PAYER_INSURANCE)),
            sponsorResponsibility: $this->money($payerAmount(InvoiceReceivable::PAYER_SPONSOR)),
            corporateResponsibility: $this->money($payerAmount(InvoiceReceivable::PAYER_CORPORATE)),
            invoiceCount: $invoices->count(), invoiceItemCount: $items->count(), receivableCount: $receivables->count(),
            hasUnbilledBillableItems: $unbilled, hasPendingFinancialAdjustments: false,
            context: ['source' => $patient->isNotEmpty() ? 'invoice_receivables' : 'invoice_items'],
        );
    }

    private function money(mixed $amount): string { return number_format(round((float) $amount, 2), 2, '.', ''); }
}
