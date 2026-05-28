<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DepartmentType;
use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockLocation;
use App\Services\ProductService;
use App\Services\StockBalanceService;
use App\Services\StockLocationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentConsumablesController extends Controller
{
    public function __construct(
        private ProductService $products,
        private StockBalanceService $stockBalances,
        private StockLocationService $stockLocations,
    ) {}

    public function ward(Request $request)
    {
        $allowedProductTypes = $this->allowedProductTypes();
        $locationIds = StockLocation::active()
            ->where(function ($query) {
                $query->where('type', 'ward')
                    ->orWhereHas('department', fn ($department) => $department->where('type', DepartmentType::TREATMENT->value));
            })
            ->pluck('id');

        $products = $this->products
            ->queryProductsForDepartmentTypes([DepartmentType::TREATMENT], $allowedProductTypes)
            ->when($request->search, $this->searchFilter())
            ->when($request->product_type, fn ($query, $type) => $query->where('product_type', $type))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return $this->viewCatalogue($products, $locationIds->all(), [
            'title' => 'Ward Consumables',
            'routeName' => 'admin.wards.consumables.index',
            'locationLabel' => 'Ward stock location',
            'departmentLabel' => 'Ward / Treatment departments',
            'emptyMessage' => 'No products are linked to Ward / Treatment departments yet.',
            'allowedTypes' => $allowedProductTypes,
        ]);
    }

    public function emergency(Request $request)
    {
        $allowedProductTypes = $this->allowedProductTypes();
        $locationIds = StockLocation::active()
            ->where(function ($query) {
                $query->where('type', 'emergency')
                    ->orWhereHas('department', fn ($department) => $this->emergencyDepartmentFilter($department));
            })
            ->pluck('id');

        $stockedProductIds = $locationIds->isEmpty()
            ? collect()
            : StockBalance::query()
                ->whereIn('stock_location_id', $locationIds)
                ->where('quantity_on_hand', '>', 0)
                ->pluck('product_id')
                ->filter()
                ->unique()
                ->values();

        $products = Product::query()
            ->with(['departments:id,name,type'])
            ->where('is_active', true)
            ->whereIn('product_type', $allowedProductTypes)
            ->where(function (Builder $query) use ($stockedProductIds) {
                $query->whereHas('departments', function ($department) {
                    $department->where('product_department.is_active', true);
                    $this->emergencyDepartmentFilter($department);
                });

                if ($stockedProductIds->isNotEmpty()) {
                    $query->orWhereIn('id', $stockedProductIds->all());
                }
            })
            ->when($request->search, $this->searchFilter())
            ->when($request->product_type, fn ($query, $type) => $query->where('product_type', $type))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return $this->viewCatalogue($products, $locationIds->all(), [
            'title' => 'Emergency Consumables',
            'routeName' => 'admin.emergency.consumables.index',
            'locationLabel' => 'Emergency stock location',
            'departmentLabel' => 'Emergency department or stocked emergency location',
            'emptyMessage' => 'No products are linked to Emergency or stocked in an Emergency stock location yet.',
            'allowedTypes' => $allowedProductTypes,
        ]);
    }

    private function viewCatalogue($products, array $locationIds, array $viewData)
    {
        $productIds = $products->getCollection()->pluck('id')->all();
        $balances = empty($locationIds)
            ? collect()
            : StockBalance::query()
                ->whereIn('product_id', $productIds)
                ->whereIn('stock_location_id', $locationIds)
                ->select('product_id', DB::raw('SUM(quantity_on_hand) as total'))
                ->groupBy('product_id')
                ->pluck('total', 'product_id');

        $mainStore = $this->stockLocations->getMainStoreLocation();
        $mainBalances = $this->stockBalances->getQuantitiesForProductsAtLocation($productIds, $mainStore);

        $products->getCollection()->transform(function (Product $product) use ($balances, $mainBalances) {
            $departmentQuantity = (float) ($balances[$product->id] ?? 0);
            $mainQuantity = (float) ($mainBalances[$product->id] ?? 0);

            $product->department_available_quantity = $departmentQuantity;
            $product->available_in_main_store = $mainQuantity;
            $product->department_stock_status = $this->stockBalances->stockStatus($departmentQuantity, $product);
            $product->main_stock_status = $this->stockBalances->stockStatus($mainQuantity, $product);

            return $product;
        });

        $lowStockCount = $products->getCollection()
            ->filter(fn (Product $product) => ($product->reorder_level ?? 0) > 0 && $product->department_available_quantity <= $product->reorder_level)
            ->count();

        return view('department-consumables.index', array_merge($viewData, [
            'products' => $products,
            'lowStockCount' => $lowStockCount,
        ]));
    }

    private function allowedProductTypes(): array
    {
        return [
            ProductType::CONSUMABLE->value,
            ProductType::SURGICAL_SUPPLY->value,
            ProductType::MEDICAL_SUPPLY->value,
            ProductType::GENERAL_ITEM->value,
        ];
    }

    private function searchFilter(): callable
    {
        return fn ($query, $search) => $query->where(function ($nested) use ($search) {
            $nested->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%");
        });
    }

    private function emergencyDepartmentFilter($query): void
    {
        $query->where(function ($department) {
            $department->whereRaw('LOWER(name) LIKE ?', ['%emergency%'])
                ->orWhereRaw('LOWER(code) IN (?, ?, ?, ?, ?)', ['er', 'ed', 'emr', 'emer', 'emergency'])
                ->orWhere('type', 'emergency');
        });
    }
}