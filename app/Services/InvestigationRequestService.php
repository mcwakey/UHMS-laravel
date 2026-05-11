<?php

namespace App\Services;

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

            // Bill each selected item against the single visit invoice.
            $visit = $labRequest->visit;
            $invoice = null;
            $billedCount = 0;
            foreach ($items as $item) {
                $service = $item->service;
                if (!$service) {
                    // Free-text or test-catalog-only items have no service price → skip billing
                    continue;
                }
                try {
                    $invItem = $this->billingService->addItemToVisitInvoice(
                        $visit,
                        $service,
                        'lab_request_item',
                        $item->id,
                        1,
                        null,
                        $service->name . ' — ' . $labRequest->request_number,
                    );
                } catch (\RuntimeException $e) {
                    // Already billed earlier — fetch existing line.
                    $invItem = \App\Models\InvoiceItem::where('source_type', 'lab_request_item')
                        ->where('source_id', $item->id)
                        ->first();
                    if (! $invItem) {
                        throw $e;
                    }
                }
                LabRequestItem::where('id', $item->id)->update([
                    'invoice_item_id' => $invItem->id,
                    'unit_price'      => $invItem->unit_price,
                    'billed_at'       => now(),
                ]);
                $invoice = $invItem->invoice;
                $billedCount++;
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
