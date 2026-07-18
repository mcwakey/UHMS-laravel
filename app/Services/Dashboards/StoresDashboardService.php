<?php

namespace App\Services\Dashboards;

use App\Enums\PurchaseOrderStatus;
use App\Enums\StockRequisitionStatus;
use App\Models\ProductStockBalance;
use App\Models\ProductStockMovement;
use App\Models\PurchaseOrder;
use App\Models\StockRequisition;
use App\Services\Dashboards\Concerns\BuildsPressure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Metrics for the Stores workspace dashboard. Read-only aggregate queries over
 * the existing requisition, purchase-order and product stock ledger tables.
 */
class StoresDashboardService
{
    use BuildsPressure;

    private const PO_AWAITING_RECEIPT = [
        PurchaseOrderStatus::SUBMITTED->value,
        PurchaseOrderStatus::APPROVED->value,
        PurchaseOrderStatus::PARTIALLY_RECEIVED->value,
    ];

    /** @return array<string, mixed> */
    public function build(): array
    {
        return [
            'insight' => $this->requisitionInsight(),
            'pressure' => $this->fulfilmentPressure(),
            'kpis' => $this->kpis(),
            'movementTrend' => $this->movementTrend(),
            'stockByType' => $this->stockByType(),
            'recentRequisitions' => StockRequisition::with(['department:id,name', 'requestedByUser:id,first_name,last_name'])
                ->withCount('items')
                ->whereIn('status', [
                    StockRequisitionStatus::SUBMITTED->value,
                    StockRequisitionStatus::APPROVED->value,
                    StockRequisitionStatus::PARTIALLY_APPROVED->value,
                    StockRequisitionStatus::AWAITING_ACKNOWLEDGEMENT->value,
                ])
                ->latest('requested_at')
                ->take(6)
                ->get(),
            'awaitingDeliveries' => PurchaseOrder::with('supplier:id,name')
                ->whereIn('status', self::PO_AWAITING_RECEIPT)
                ->orderBy('expected_date')
                ->take(5)
                ->get(),
            'lowStock' => $this->lowStockQuery()->orderByRaw('SUM(product_stock_balances.quantity_on_hand) - products.reorder_level ASC')->take(5)->get(),
        ];
    }

    /**
     * Requisition backlog banner: submitted requisitions + oldest waiting time.
     *
     * @return array<string, mixed>|null
     */
    private function requisitionInsight(): ?array
    {
        $pendingQuery = StockRequisition::where('status', StockRequisitionStatus::SUBMITTED->value);
        $pending = (clone $pendingQuery)->count();
        if ($pending < 1) {
            return null;
        }

        $oldest = (clone $pendingQuery)->oldest('requested_at')->value('requested_at');
        $oldestMinutes = $oldest ? (int) Carbon::parse($oldest)->diffInMinutes(now()) : 0;
        $oldestText = $oldestMinutes >= 1440
            ? intdiv($oldestMinutes, 1440).'d'
            : ($oldestMinutes >= 60 ? intdiv($oldestMinutes, 60).'h' : $oldestMinutes.'m');

        return [
            'variant' => $oldestMinutes >= 2880 ? 'danger' : 'warning',
            'icon' => 'ti-clipboard-list',
            'title' => __('stores.dashboard.requisition_insight'),
            'badge' => __('stores.dashboard.pending_requisitions_count', ['count' => $pending]),
            'cause' => ['icon' => 'ti-clock-exclamation', 'label' => __('stores.dashboard.oldest_waiting', ['time' => $oldestText])],
            'action' => __('stores.dashboard.action_review'),
            'link' => ['url' => route('stores.stock-requisitions.index'), 'label' => __('stores.dashboard.open_requisitions')],
        ];
    }

    /** @return array<string, mixed> */
    private function fulfilmentPressure(): array
    {
        $submitted = StockRequisition::where('status', StockRequisitionStatus::SUBMITTED->value)->count();
        $awaitingIssue = StockRequisition::whereIn('status', [
            StockRequisitionStatus::APPROVED->value,
            StockRequisitionStatus::PARTIALLY_APPROVED->value,
        ])->count();
        $awaitingAck = StockRequisition::whereIn('status', [
            StockRequisitionStatus::AWAITING_ACKNOWLEDGEMENT->value,
            StockRequisitionStatus::PARTIALLY_ACKNOWLEDGED->value,
        ])->count();
        $awaitingReceipt = PurchaseOrder::whereIn('status', self::PO_AWAITING_RECEIPT)->count();

        return $this->pressure(
            __('stores.dashboard.fulfilment_load'),
            'ti-building-warehouse',
            $submitted + $awaitingIssue,
            [6, 15, 25],
            [
                __('stores.dashboard.pending_approval', ['count' => $submitted]),
                __('stores.dashboard.awaiting_issue', ['count' => $awaitingIssue]),
                __('stores.dashboard.awaiting_acknowledgement', ['count' => $awaitingAck]),
                __('stores.dashboard.awaiting_delivery', ['count' => $awaitingReceipt]),
            ],
        );
    }

    /** @return array<string, array<string, mixed>> */
    private function kpis(): array
    {
        return [
            'pending_requisitions' => ['value' => StockRequisition::where('status', StockRequisitionStatus::SUBMITTED->value)->count()],
            'awaiting_issue' => ['value' => StockRequisition::whereIn('status', [
                StockRequisitionStatus::APPROVED->value,
                StockRequisitionStatus::PARTIALLY_APPROVED->value,
            ])->count()],
            'awaiting_receipt' => ['value' => PurchaseOrder::whereIn('status', self::PO_AWAITING_RECEIPT)->count()],
            'low_stock' => ['value' => (clone $this->lowStockQuery())->get()->count()],
        ];
    }

    /** Stock in vs out per day, last 7 days. @return array<string, mixed> */
    private function movementTrend(): array
    {
        $rows = ProductStockMovement::query()
            ->where('movement_date', '>=', now()->subDays(7)->startOfDay())
            ->get(['movement_date', 'direction', 'quantity']);

        $labels = [];
        $in = [];
        $out = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = today()->subDays($i);
            $labels[] = $day->format('D');
            $dayRows = $rows->filter(fn ($row) => Carbon::parse($row->movement_date)->isSameDay($day));
            $in[] = (float) $dayRows->where('direction', \App\Enums\StockMovementDirection::IN)->sum('quantity');
            $out[] = (float) $dayRows->where('direction', \App\Enums\StockMovementDirection::OUT)->sum('quantity');
        }

        return ['labels' => $labels, 'in' => $in, 'out' => $out];
    }

    /** On-hand stock grouped by product type (top 4 + others). @return array<string, int|float> */
    private function stockByType(): array
    {
        $rows = ProductStockBalance::query()
            ->join('products', 'products.id', '=', 'product_stock_balances.product_id')
            ->where('product_stock_balances.quantity_on_hand', '>', 0)
            ->select('products.product_type', DB::raw('SUM(product_stock_balances.quantity_on_hand) as qty'))
            ->groupBy('products.product_type')
            ->orderByDesc('qty')
            ->get();

        $out = [];
        foreach ($rows->take(4) as $row) {
            $out[ucfirst(str_replace('_', ' ', (string) $row->product_type))] = (float) $row->qty;
        }
        $others = (float) $rows->skip(4)->sum('qty');
        if ($others > 0) {
            $out[__('stores.dashboard.others')] = $others;
        }

        return $out;
    }

    private function lowStockQuery()
    {
        return ProductStockBalance::query()
            ->join('products', 'products.id', '=', 'product_stock_balances.product_id')
            ->whereNotNull('products.reorder_level')
            ->where('products.reorder_level', '>', 0)
            ->select('products.id', 'products.name', 'products.code', 'products.reorder_level', DB::raw('SUM(product_stock_balances.quantity_on_hand) as qty'))
            ->groupBy('products.id', 'products.name', 'products.code', 'products.reorder_level')
            ->havingRaw('SUM(product_stock_balances.quantity_on_hand) <= products.reorder_level');
    }
}
