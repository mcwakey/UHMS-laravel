<?php

namespace App\Services\Dashboards;

use App\Models\DispensingRecord;
use App\Models\Drug;
use App\Models\Prescription;
use App\Models\PurchaseOrder;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\Dashboards\Concerns\BuildsPressure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Metrics for the modern Pharmacist dashboard. Read-only aggregate queries.
 */
class PharmacistDashboardService
{
    use BuildsPressure;

    private const PENDING_PO_STATUSES = ['submitted', 'approved', 'partially_received'];

    /** @return array<string, mixed> */
    public function build(): array
    {
        return [
            'insight' => $this->dispensingInsight(),
            'pressure' => $this->dispensingPressure(),
            'kpis' => $this->kpis(),
            'salesTrend' => $this->salesTrend(),
            'stockByCategory' => $this->stockByCategory(),
            'recentPrescriptions' => Prescription::with([
                'patient:id,patient_number,first_name,last_name,other_names',
                'doctor:id,name',
                'items:id,prescription_id,drug_name',
            ])->latest()->take(4)->get(),
            'expiryAlerts' => $this->expiryAlerts(),
            'lowStock' => $this->lowStock(),
            'topSelling' => $this->topSelling(),
            'suppliers' => Supplier::withCount(['purchaseOrders as month_orders' => fn ($q) => $q
                ->whereYear('created_at', today()->year)
                ->whereMonth('created_at', today()->month)])
                ->orderByDesc('month_orders')
                ->take(4)
                ->get(),
        ];
    }

    /**
     * Dispensing backlog banner: pending prescriptions + oldest waiting time.
     *
     * @return array<string, mixed>|null
     */
    private function dispensingInsight(): ?array
    {
        $pendingQuery = Prescription::whereNotIn('status', ['dispensed', 'completed', 'cancelled']);
        $pending = (clone $pendingQuery)->count();
        if ($pending < 1) {
            return null;
        }

        $oldest = (clone $pendingQuery)->oldest()->value('created_at');
        $oldestMinutes = $oldest ? (int) Carbon::parse($oldest)->diffInMinutes(now()) : 0;
        $oldestText = $oldestMinutes >= 1440
            ? intdiv($oldestMinutes, 1440).'d'
            : ($oldestMinutes >= 60 ? intdiv($oldestMinutes, 60).'h' : $oldestMinutes.'m');

        return [
            'variant' => $oldestMinutes >= 120 ? 'danger' : 'warning',
            'icon' => 'ti-prescription',
            'title' => __('role_dashboards.pharmacist.dispensing_insight'),
            'badge' => __('role_dashboards.pharmacist.pending_rx_count', ['count' => $pending]),
            'cause' => ['icon' => 'ti-clock-exclamation', 'label' => __('role_dashboards.pharmacist.oldest_waiting', ['time' => $oldestText])],
            'action' => __('role_dashboards.pharmacist.action_dispense'),
            'link' => ['url' => route('admin.pharmacy.dispensing.index'), 'label' => __('role_dashboards.pharmacist.open_dispensing')],
        ];
    }

    /** @return array<string, mixed> */
    private function dispensingPressure(): array
    {
        $pending = Prescription::whereNotIn('status', ['dispensed', 'completed', 'cancelled'])->count();
        $dispensedToday = DispensingRecord::whereDate('dispensed_at', today())->count();
        $lowStock = $this->lowStockQuery()->count();

        return $this->pressure(
            __('dashboards.department.widget.dispensing_efficiency'),
            'ti-pill',
            $pending,
            [6, 15, 25],
            [
                __('dashboards.department.widget.metric.pending', ['count' => $pending]),
                __('role_dashboards.pharmacist.dispensed_today', ['count' => $dispensedToday]),
                __('dashboards.department.widget.metric.low_stock', ['count' => $lowStock]),
            ],
        );
    }

    /** @return array<string, array<string, mixed>> */
    private function kpis(): array
    {
        $newDrugs = Drug::where('created_at', '>=', now()->subDays(7))->count();

        return [
            'medicines' => ['value' => Drug::where('is_active', true)->count(), 'trend' => $newDrugs > 0 ? $newDrugs : null],
            'pending_orders' => ['value' => PurchaseOrder::whereIn('status', self::PENDING_PO_STATUSES)->count(), 'trend' => null],
            'low_stock' => ['value' => $this->lowStockQuery()->count(), 'trend' => null],
            'expiring' => [
                'value' => StockMovement::whereNotNull('expiry_date')
                    ->whereBetween('expiry_date', [today(), today()->addDays(30)])
                    ->where('direction', 'in')->where('quantity', '>', 0)
                    ->distinct('product_id')->count('product_id'),
                'trend' => null,
            ],
        ];
    }

    /** Units dispensed + revenue per day, last 7 days. @return array<string, mixed> */
    private function salesTrend(): array
    {
        $rows = DispensingRecord::query()
            ->join('prescription_items as pi', 'pi.id', '=', 'dispensing_records.prescription_item_id')
            ->leftJoin('drugs as d', 'd.id', '=', 'pi.drug_id')
            ->where('dispensing_records.dispensed_at', '>=', now()->subDays(7)->startOfDay())
            ->get([
                'dispensing_records.dispensed_at',
                'dispensing_records.quantity_dispensed',
                'd.price as unit_price',
            ]);

        $labels = [];
        $units = [];
        $revenue = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = today()->subDays($i);
            $labels[] = $day->format('D');
            $dayRows = $rows->filter(fn ($r) => Carbon::parse($r->dispensed_at)->isSameDay($day));
            $units[] = (int) $dayRows->sum('quantity_dispensed');
            $revenue[] = round((float) $dayRows->sum(fn ($r) => (float) $r->quantity_dispensed * (float) ($r->unit_price ?? 0)), 2);
        }

        return ['labels' => $labels, 'units' => $units, 'revenue' => $revenue];
    }

    /** On-hand stock grouped by drug category (top 4 + others). @return array<string, int> */
    private function stockByCategory(): array
    {
        $rows = StockBalance::query()
            ->join('drugs as d', 'd.id', '=', 'stock_balances.drug_id')
            ->leftJoin('drug_categories as c', 'c.id', '=', 'd.category_id')
            ->where('stock_balances.quantity_on_hand', '>', 0)
            ->select(DB::raw("COALESCE(c.name, d.dosage_form, 'Other') as category"), DB::raw('SUM(stock_balances.quantity_on_hand) as qty'))
            ->groupBy('category')
            ->orderByDesc('qty')
            ->get();

        $out = [];
        foreach ($rows->take(4) as $row) {
            $out[ucfirst((string) $row->category)] = (int) $row->qty;
        }
        $others = (int) $rows->skip(4)->sum('qty');
        if ($others > 0) {
            $out[__('role_dashboards.pharmacist.others')] = $others;
        }

        return $out;
    }

    /** @return array<int, object> */
    private function expiryAlerts(): array
    {
        return StockMovement::query()
            ->join('products as p', 'p.id', '=', 'stock_movements.product_id')
            ->whereNotNull('stock_movements.expiry_date')
            ->whereBetween('stock_movements.expiry_date', [today(), today()->addDays(30)])
            ->where('stock_movements.direction', 'in')
            ->where('stock_movements.quantity', '>', 0)
            ->orderBy('stock_movements.expiry_date')
            ->take(4)
            ->get(['p.name', 'stock_movements.batch_no', 'stock_movements.expiry_date'])
            ->map(function ($row) {
                $row->days_left = (int) today()->diffInDays(Carbon::parse($row->expiry_date), false);

                return $row;
            })
            ->all();
    }

    private function lowStockQuery()
    {
        return StockBalance::query()
            ->join('drugs as d', 'd.id', '=', 'stock_balances.drug_id')
            ->select('d.id', 'd.name', 'd.dosage_form', 'd.reorder_level', DB::raw('SUM(stock_balances.quantity_on_hand) as qty'))
            ->groupBy('d.id', 'd.name', 'd.dosage_form', 'd.reorder_level')
            ->havingRaw('SUM(stock_balances.quantity_on_hand) > 0')
            ->havingRaw('SUM(stock_balances.quantity_on_hand) <= d.reorder_level');
    }

    /** @return array<int, object> */
    private function lowStock(): array
    {
        return $this->lowStockQuery()->orderByRaw('SUM(stock_balances.quantity_on_hand) - d.reorder_level ASC')->take(4)->get()->all();
    }

    /** Top dispensed drugs, last 30 days, with pct-of-max for progress bars. @return array<int, array<string, mixed>> */
    private function topSelling(): array
    {
        $rows = DispensingRecord::query()
            ->join('prescription_items as pi', 'pi.id', '=', 'dispensing_records.prescription_item_id')
            ->where('dispensing_records.dispensed_at', '>=', now()->subDays(30))
            ->select('pi.drug_name', DB::raw('SUM(dispensing_records.quantity_dispensed) as units'))
            ->groupBy('pi.drug_name')
            ->orderByDesc('units')
            ->take(5)
            ->get();

        $max = max(1, (int) ($rows->max('units') ?? 1));

        return $rows->map(fn ($row) => [
            'name' => $row->drug_name,
            'units' => (int) $row->units,
            'pct' => (int) round(((int) $row->units / $max) * 100),
        ])->all();
    }
}
