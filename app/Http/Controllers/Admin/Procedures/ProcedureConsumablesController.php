<?php

namespace App\Http\Controllers\Admin\Procedures;

use App\Enums\DepartmentType;
use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockLocation;
use App\Services\ProductService;
use App\Services\StockBalanceService;
use App\Services\StockLocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Procedure / Theatre Consumables — read-only filtered product catalogue.
 *
 * Per the unified-inventory rule: every physical item is a Product. This
 * page is a **view** of products linked to the Procedure/Theatre department,
 * filtered to consumable / surgical-supply / medical-supply / general-item
 * product types. Theatre staff cannot create products from this page; new
 * items are added by Store/Admin and linked to the Theatre department.
 *
 * On-hand quantity is sourced from `stock_balances` AT the Theatre
 * stock location only — stock still sitting in Main Store does NOT count
 * as available to the theatre.
 */
class ProcedureConsumablesController extends Controller
{
    public function __construct(
        private ProductService $productService,
        private StockBalanceService $stockBalances,
        private StockLocationService $stockLocations,
    )
    {
    }

    public function index(Request $request)
    {
        $allowedProductTypes = [
            ProductType::CONSUMABLE->value,
            ProductType::SURGICAL_SUPPLY->value,
            ProductType::MEDICAL_SUPPLY->value,
            ProductType::GENERAL_ITEM->value,
        ];

        $theatreLocationIds = StockLocation::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereIn('type', ['theatre', 'procedure'])
                  ->orWhereHas('department', fn ($dq) => $dq->where('type', DepartmentType::PROCEDURE->value));
            })
            ->pluck('id');

        $products = $this->productService
            ->queryProductsForDepartmentTypes([DepartmentType::PROCEDURE], $allowedProductTypes)
            ->when($request->search, function ($q, $s) {
                $q->where(function ($qq) use ($s) {
                    $qq->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%");
                });
            })
            ->when($request->product_type, fn ($q, $t) => $q->where('product_type', $t))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $productIds = $products->getCollection()->pluck('id')->all();
        $balances = $theatreLocationIds->isEmpty()
            ? collect()
            : StockBalance::query()
                ->whereIn('product_id', $productIds)
                ->whereIn('stock_location_id', $theatreLocationIds)
                ->select('product_id', DB::raw('SUM(quantity_on_hand) as total'))
                ->groupBy('product_id')
                ->pluck('total', 'product_id');

        $mainStore = $this->stockLocations->getMainStoreLocation();
        $mainBalances = $this->stockBalances->getQuantitiesForProductsAtLocation($productIds, $mainStore);

        $products->getCollection()->transform(function (Product $p) use ($balances, $mainBalances) {
            $p->available_in_theatre = (float) ($balances[$p->id] ?? 0);
            $p->available_in_main_store = (float) ($mainBalances[$p->id] ?? 0);
            $p->theatre_stock_status = $this->stockBalances->stockStatus($p->available_in_theatre, $p);
            $p->main_stock_status = $this->stockBalances->stockStatus($p->available_in_main_store, $p);
            return $p;
        });

        $lowStockCount = $products->getCollection()
            ->filter(fn ($p) => ($p->reorder_level ?? 0) > 0 && $p->available_in_theatre <= $p->reorder_level)
            ->count();

        $allowedTypes = $allowedProductTypes;

        return view('theatre.consumables.index', compact('products', 'lowStockCount', 'allowedTypes'));
    }
}
