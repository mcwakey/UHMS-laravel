<?php

namespace App\Services;

use App\Models\StockBalance;

/**
 * Inventory valuation report — reads stock_balances (quantity_on_hand ×
 * average_cost = total_value) so it is a fast read, not a full ledger replay.
 */
class InventoryValuationReportService
{
    public function __construct(protected InventoryAccountingService $accounts) {}

    public function report(array $filters = []): array
    {
        $query = StockBalance::query()
            ->with(['product:id,name,code,product_type', 'location:id,name'])
            ->where('quantity_on_hand', '>', 0)
            ->whereNotNull('product_id');

        if (! empty($filters['location_id'])) {
            $query->where('stock_location_id', $filters['location_id']);
        }
        if (! empty($filters['product_type'])) {
            $query->whereHas('product', fn ($q) => $q->where('product_type', $filters['product_type']));
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('product', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
        }

        $rows = [];
        $total = 0.0;
        $byLocation = [];
        $byType = [];

        $query->orderBy('product_id')->chunk(500, function ($balances) use (&$rows, &$total, &$byLocation, &$byType) {
            foreach ($balances as $b) {
                $value = round((float) $b->total_value, 2);
                $total += $value;

                $loc = $b->location?->name ?? 'Location #'.$b->stock_location_id;
                $type = $b->product?->product_type?->value ?? ($b->product?->product_type ?? 'other');

                $byLocation[$loc] = round(($byLocation[$loc] ?? 0) + $value, 2);
                $byType[$type] = round(($byType[$type] ?? 0) + $value, 2);

                $rows[] = [
                    'product_code' => $b->product?->code,
                    'product_name' => $b->product?->name ?? 'Product #'.$b->product_id,
                    'product_type' => $type,
                    'location' => $loc,
                    'quantity_on_hand' => (float) $b->quantity_on_hand,
                    'average_cost' => (float) $b->average_cost,
                    'total_value' => $value,
                    'inventory_account' => $this->inventoryAccountLabel($b->product),
                    'last_movement_at' => optional($b->last_movement_at)->format('d M Y'),
                ];
            }
        });

        usort($rows, fn ($a, $b) => $b['total_value'] <=> $a['total_value']);

        return [
            'rows' => $rows,
            'total_value' => round($total, 2),
            'count' => count($rows),
            'by_location' => $byLocation,
            'by_type' => $byType,
        ];
    }

    private function inventoryAccountLabel($product): string
    {
        try {
            $account = $this->accounts->inventoryAccountForProduct($product);

            return $account->code.' '.$account->name;
        } catch (\Throwable) {
            return '—';
        }
    }
}
