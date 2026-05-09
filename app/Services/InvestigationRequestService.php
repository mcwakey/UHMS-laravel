<?php

namespace App\Services;

use App\Enums\BillingType;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Handles selective acceptance of investigation request items + auto-invoicing.
 * Only items the investigation staff explicitly select are billed and accepted.
 */
class InvestigationRequestService
{
    public function __construct(
        protected BillingService $billingService,
        protected ServicePriceResolver $priceResolver,
    ) {}

    /**
     * Accept a subset of a LabRequest's items, generate an invoice for them only,
     * and update aggregate request status.
     *
     * @return array{request: LabRequest, invoice: Invoice|null, accepted_count: int}
     */
    public function acceptSelectedItems(LabRequest $labRequest, array $itemIds, User $user): array
    {
        return DB::transaction(function () use ($labRequest, $itemIds, $user) {
            $labRequest->loadMissing(['items.service.prices', 'items.labTest', 'visit.visitInsurance.insuranceProvider', 'patient']);

            $itemIds = array_values(array_unique(array_filter(array_map('intval', $itemIds))));
            if (empty($itemIds)) {
                throw new \RuntimeException('Select at least one item to accept.');
            }

            $items = $labRequest->items->whereIn('id', $itemIds);
            if ($items->count() !== count($itemIds)) {
                throw new \RuntimeException('Some selected items do not belong to this request.');
            }

            // Validate: only pending items can be accepted.
            $invalid = $items->filter(fn ($i) => !in_array($i->status, ['pending']));
            if ($invalid->isNotEmpty()) {
                throw new \RuntimeException('Only pending items can be accepted. Some selected items are already processed.');
            }

            // Build invoice items snapshot (only for selected, billable items)
            $visit = $labRequest->visit;
            $invoiceItemsPayload = [];
            foreach ($items as $item) {
                $service = $item->service;
                if (!$service) {
                    // Free-text or test-catalog-only items have no service price → skip billing for them
                    // but still mark accepted.
                    continue;
                }
                $snap = $this->priceResolver->resolveForVisit($service, $visit);
                $unitPrice = (float) ($snap['selected_price'] ?? 0);
                $invoiceItemsPayload[] = [
                    'service_catalog_id'    => $service->id,
                    'description'           => $service->name . ' — ' . $labRequest->request_number,
                    'quantity'              => 1,
                    'unit_price'            => $unitPrice,
                    'is_nhis_covered'       => false,
                    'nhis_approved_amount'  => 0,
                    'cash_price'            => $snap['cash_price'] ?? $unitPrice,
                    'discount_amount'       => $snap['discount_amount'] ?? 0,
                    'payer_type'            => $snap['payer_type'] ?? 'cash',
                    'insurance_provider_id' => $snap['insurance_provider_id'] ?? null,
                    'pricing_source'        => $snap['pricing_source'] ?? 'cash_price',
                    '_lab_request_item_id'  => $item->id,
                ];
            }

            $invoice = null;
            if (!empty($invoiceItemsPayload)) {
                $invoice = $this->billingService->createInvoice([
                    'visit_id'     => $visit->id,
                    'patient_id'   => $labRequest->patient_id,
                    'billing_type' => $visit?->visitInsurance?->is_active ? BillingType::INSURANCE->value : BillingType::CASH->value,
                    'notes'        => 'Auto-generated for investigation request ' . $labRequest->request_number,
                ], $invoiceItemsPayload);

                // Map invoice items back to lab_request_items by service_catalog_id + service id
                $invoice->loadMissing('items');
                foreach ($invoiceItemsPayload as $payload) {
                    $invItem = $invoice->items->firstWhere('service_catalog_id', $payload['service_catalog_id']);
                    if ($invItem) {
                        LabRequestItem::where('id', $payload['_lab_request_item_id'])->update([
                            'invoice_item_id' => $invItem->id,
                            'unit_price'      => $payload['unit_price'],
                            'billed_at'       => now(),
                        ]);
                    }
                }
            }

            // Mark all selected items accepted (whether billed or not).
            LabRequestItem::whereIn('id', $itemIds)->update([
                'status'      => 'accepted',
                'accepted_at' => now(),
                'accepted_by' => $user->id,
            ]);

            // Update aggregate request status: any accepted → processing.
            $labRequest->refresh()->loadMissing('items');
            $itemStatuses = $labRequest->items->pluck('status');
            $allFinalized = $itemStatuses->every(fn ($s) => in_array($s, ['completed', 'verified', 'cancelled', 'rejected']));
            if ($allFinalized) {
                $labRequest->update(['status' => 'completed']);
            } else {
                $labRequest->update(['status' => 'processing']);
            }

            return [
                'request'        => $labRequest->fresh(['items', 'visit']),
                'invoice'        => $invoice,
                'accepted_count' => count($itemIds),
            ];
        });
    }

    /**
     * Reject (mark as rejected) one or more pending items.
     */
    public function rejectItems(LabRequest $labRequest, array $itemIds, ?string $reason = null): LabRequest
    {
        $itemIds = array_filter(array_map('intval', $itemIds));
        if (empty($itemIds)) {
            return $labRequest;
        }
        LabRequestItem::where('lab_request_id', $labRequest->id)
            ->whereIn('id', $itemIds)
            ->where('status', 'pending')
            ->update(['status' => 'rejected']);

        return $labRequest->fresh();
    }
}
