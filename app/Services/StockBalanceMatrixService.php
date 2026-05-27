<?php

namespace App\Services;

use App\Enums\ProductType;
use App\Models\Product;
use App\Models\StockLocation;

class StockBalanceMatrixService
{
    public function __construct(private StockBalanceService $balances) {}

    public function build(array $filters = [], int $perPage = 25): array
    {
        $locations = StockLocation::query()
            ->with('department:id,name,type')
            ->active()
            ->when($filters['location_id'] ?? null, fn ($q, $id) => $q->whereKey((int) $id))
            ->orderByDesc('is_main')
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->with('departments:id,name,type')
            ->where('is_active', true)
            ->when($filters['product_type'] ?? null, fn ($q, $type) => $q->where('product_type', $type))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $productIds = $products->getCollection()->pluck('id');
        $locationIds = $locations->pluck('id');
        $quantityMatrix = $this->balances->getQuantityMatrix($productIds, $locationIds);

        $rows = $products->getCollection()->map(function (Product $product) use ($locations, $quantityMatrix) {
            $linkedDepartmentIds = $product->departments->pluck('id')->all();
            $cells = [];
            $total = 0.0;
            $warnings = [];

            foreach ($locations as $location) {
                $quantity = (float) ($quantityMatrix[$product->id.':'.$location->id] ?? 0);
                $stocked = $this->isStockedAtLocation($location, $linkedDepartmentIds);
                $status = $this->balances->stockStatus($quantity, $product, $stocked);

                if ($stocked) {
                    $total += $quantity;
                }

                if (in_array($status['label'], ['LOW', 'CRITICAL', 'OUT'], true)) {
                    $warnings[] = $location->name.' '.$status['label'];
                }

                $cells[$location->id] = [
                    'quantity' => $quantity,
                    'status' => $status,
                    'stocked' => $stocked,
                ];
            }

            return [
                'product' => $product,
                'cells' => $cells,
                'total' => $total,
                'summary' => empty($warnings) ? 'OK' : implode(', ', array_slice($warnings, 0, 3)),
                'summary_class' => empty($warnings) ? 'success' : 'warning text-dark',
            ];
        });

        return [
            'products' => $products,
            'locations' => $locations,
            'rows' => $rows,
            'productTypes' => ProductType::cases(),
            'allLocations' => StockLocation::active()->orderByDesc('is_main')->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function isStockedAtLocation(StockLocation $location, array $linkedDepartmentIds): bool
    {
        if ($location->is_main || $location->type === 'store' || ! $location->department_id) {
            return true;
        }

        if (empty($linkedDepartmentIds)) {
            return true;
        }

        return in_array($location->department_id, $linkedDepartmentIds, true);
    }
}