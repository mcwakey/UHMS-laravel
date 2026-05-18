<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DepartmentType;
use App\Enums\InvestigationItemCategory;
use App\Enums\ProductType;
use App\Enums\StockLocation;
use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\InvestigationItem;
use App\Models\InvestigationItemStock;
use App\Models\Product;
use App\Models\ProductStockBalance;
use App\Models\StockLocation as StockLocationModel;
use App\Models\Supplier;
use App\Services\ProductStockMovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InvestigationItemController extends Controller
{
    public function __construct(private ProductStockMovementService $productMovements)
    {
    }

    /*
    |--------------------------------------------------------------------------
    | Catalog
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        // Investigation Consumables is a **filtered product view** — every
        // physical item in the hospital is a Product, and this page only
        // surfaces products that have been linked to the Investigation /
        // Laboratory / Radiology departments and whose type makes them a
        // lab consumable (reagent, consumable, medical supply, general).
        $departmentTypes = [
            DepartmentType::INVESTIGATION->value,
            DepartmentType::RADIOLOGY->value,
        ];
        $allowedProductTypes = [
            ProductType::REAGENT->value,
            ProductType::CONSUMABLE->value,
            ProductType::MEDICAL_SUPPLY->value,
            ProductType::SURGICAL_SUPPLY->value,
            ProductType::GENERAL_ITEM->value,
        ];

        // Resolve the canonical Lab stock location(s) — quantity surfaced on
        // this page must come from the department's own stock location, NOT
        // from Main Store. If lab stock has not been transferred from the
        // store yet the available qty intentionally shows as 0.
        $labLocationIds = StockLocationModel::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereIn('type', ['lab', 'laboratory', 'radiology'])
                  ->orWhereHas('department', fn ($dq) => $dq->whereIn('type', ['investigation', 'radiology']));
            })
            ->pluck('id');

        $products = Product::query()
            ->with(['departments:id,name,type'])
            ->where('is_active', true)
            ->whereIn('product_type', $allowedProductTypes)
            ->whereHas('departments', function ($dq) use ($departmentTypes) {
                $dq->whereIn('departments.type', $departmentTypes)
                   ->where('product_department.is_active', true);
            })
            ->when($request->search, function ($q, $s) {
                $q->where(function ($qq) use ($s) {
                    $qq->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%");
                });
            })
            ->when($request->product_type, fn ($q, $t) => $q->where('product_type', $t))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        // Pre-compute Lab-only on-hand for every product on this page.
        $productIds = $products->getCollection()->pluck('id')->all();
        $balances = ProductStockBalance::query()
            ->whereIn('product_id', $productIds)
            ->when($labLocationIds->isNotEmpty(), fn ($q) => $q->whereIn('stock_location_id', $labLocationIds))
            ->select('product_id', DB::raw('SUM(quantity_on_hand) as total'))
            ->groupBy('product_id')
            ->pluck('total', 'product_id');

        $products->getCollection()->transform(function (Product $p) use ($balances) {
            $p->available_in_lab = (float) ($balances[$p->id] ?? 0);
            return $p;
        });

        $lowStockCount = $products->getCollection()
            ->filter(fn ($p) => ($p->reorder_level ?? 0) > 0 && $p->available_in_lab <= $p->reorder_level)
            ->count();

        $allowedTypes = $allowedProductTypes;

        return view('investigations.items.index', compact('products', 'lowStockCount', 'allowedTypes'));
    }

    /**
     * Investigation departments do NOT create products. Items are created and
     * linked to the Lab/Investigation department from the central Store →
     * Products admin. We intentionally refuse writes to this controller.
     */
    public function store(Request $request)
    {
        abort(403, 'Investigation items are not created here. Create the Product from the Store → Products admin, then link it to the Investigation/Laboratory department.');
    }

    public function update(Request $request, InvestigationItem $investigationItem)
    {
        abort(403, 'Investigation items are not edited here. Manage the underlying Product from the Store → Products admin.');
    }

    public function toggle(InvestigationItem $investigationItem)
    {
        abort(403, 'Investigation items are not toggled here. Deactivate the underlying Product from the Store → Products admin.');
    }

    /*
    |--------------------------------------------------------------------------
    | Stock Management
    |--------------------------------------------------------------------------
    */

    public function stock(Request $request)
    {
        $query = InvestigationItemStock::with(['item', 'supplierRecord'])
            ->when($request->search, fn ($q, $s) => $q->whereHas('item', fn ($iq) => $iq->search($s)))
            ->when($request->location, fn ($q, $l) => $q->atLocation($l))
            ->when($request->status === 'low', fn ($q) => $q->lowStock())
            ->when($request->status === 'expired', fn ($q) => $q->expired())
            ->when($request->status === 'expiring', fn ($q) => $q->expiringSoon())
            ->orderBy('expiry_date')
            ->paginate(20)->withQueryString();

        $items             = InvestigationItem::active()->orderBy('name')->get();
        $suppliers         = Supplier::active()->orderBy('name')->get();
        $locations         = StockLocation::cases();
        $stockManagedDepts = Department::stockManaged()->orderBy('name')->get();

        $stats = [
            'total_batches'    => InvestigationItemStock::where('quantity', '>', 0)->count(),
            'low_stock'        => InvestigationItemStock::lowStock()->count(),
            'expired'          => InvestigationItemStock::expired()->where('quantity', '>', 0)->count(),
            'expiring_soon'    => InvestigationItemStock::expiringSoon()->count(),
        ];

        return view('investigations.items.stock', compact('query', 'items', 'suppliers', 'locations', 'stats', 'stockManagedDepts'));
    }

    public function storeStock(Request $request)
    {
        $data = $request->validate([
            'investigation_item_id' => ['required', 'exists:investigation_items,id'],
            'location'              => ['required', 'string'],
            'batch_number'          => ['nullable', 'string', 'max:100'],
            'quantity'              => ['required', 'integer', 'min:1'],
            'unit_cost'             => ['required', 'numeric', 'min:0'],
            'expiry_date'           => ['nullable', 'date'],
            'supplier_id'           => ['nullable', 'exists:suppliers,id'],
            'reorder_level'         => ['required', 'integer', 'min:0'],
        ]);

        $supplier = !empty($data['supplier_id']) ? Supplier::find($data['supplier_id'])?->name : null;
        $item     = InvestigationItem::findOrFail($data['investigation_item_id']);

        DB::transaction(function () use ($data, $item, $supplier) {
            // Ensure mirror Product exists (covers items created before the
            // unified-inventory backfill).
            $product = $this->ensureLinkedProduct($item);

            // Legacy batch row — kept for batch/expiry metadata (FEFO display,
            // supplier lookup) but the source-of-truth on-hand quantity now
            // lives in product_stock_balances.
            $stockRow = InvestigationItemStock::create([
                ...$data,
                'supplier'      => $supplier,
                'received_date' => now(),
                'received_by'   => Auth::id(),
            ]);

            if ($product) {
                $location = $this->resolveStockLocation($data['location']);
                if ($location) {
                    $this->productMovements->createMovement([
                        'product_id'        => $product->id,
                        'stock_location_id' => $location->id,
                        'movement_type'     => StockMovementType::PURCHASE_RECEIVED,
                        'quantity'          => (float) $data['quantity'],
                        'unit_cost'         => $data['unit_cost'],
                        'batch_no'          => $data['batch_number'] ?? null,
                        'expiry_date'       => $data['expiry_date'] ?? null,
                        'source_type'       => InvestigationItemStock::class,
                        'source_id'         => $stockRow->id,
                        'notes'             => 'Investigation stock received',
                    ]);
                } else {
                    Log::warning('investigation_item.stock.no_location', [
                        'item_id'  => $item->id,
                        'location' => $data['location'],
                    ]);
                }
            }
        });

        return back()->with('success', 'Stock added — unified ledger updated.');
    }

    public function updateStock(Request $request, InvestigationItemStock $stock)
    {
        $data = $request->validate([
            'quantity'      => ['required', 'integer', 'min:0'],
            'unit_cost'     => ['required', 'numeric', 'min:0'],
            'expiry_date'   => ['nullable', 'date'],
            'reorder_level' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($stock, $data) {
            $oldQty = (int) $stock->quantity;
            $stock->update($data);

            // Reconcile the unified ledger with the new quantity using an
            // ADJUSTMENT_IN / ADJUSTMENT_OUT movement.
            $delta = (int) $data['quantity'] - $oldQty;
            if ($delta === 0) return;

            $product = $stock->item?->product_id ? Product::find($stock->item->product_id) : null;
            if (! $product) return;

            $location = $this->resolveStockLocation((string) $stock->location);
            if (! $location) return;

            $this->productMovements->createMovement([
                'product_id'        => $product->id,
                'stock_location_id' => $location->id,
                'movement_type'     => $delta > 0 ? StockMovementType::ADJUSTMENT_IN : StockMovementType::ADJUSTMENT_OUT,
                'quantity'          => (float) abs($delta),
                'unit_cost'         => $data['unit_cost'],
                'batch_no'          => $stock->batch_number,
                'expiry_date'       => $data['expiry_date'] ?? null,
                'source_type'       => InvestigationItemStock::class,
                'source_id'         => $stock->id,
                'notes'             => 'Investigation stock adjustment',
                'allow_negative'    => true,
            ]);
        });

        return back()->with('success', 'Stock updated — unified ledger reconciled.');
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX
    |--------------------------------------------------------------------------
    */

    public function search(Request $request)
    {
        $items = InvestigationItem::active()
            ->search($request->q)
            ->limit(20)
            ->get(['id', 'name', 'code', 'unit', 'category']);

        return response()->json($items);
    }

    public function getStock(Request $request)
    {
        $request->validate([
            'item_id'  => ['required', 'exists:investigation_items,id'],
            'location' => ['required', 'string'],
        ]);

        $item = InvestigationItem::find($request->item_id);

        // Prefer the unified product ledger when the item is linked.
        if ($item?->product_id) {
            $location = $this->resolveStockLocation((string) $request->location);
            if ($location) {
                $total = (float) ProductStockBalance::query()
                    ->where('product_id', $item->product_id)
                    ->where('stock_location_id', $location->id)
                    ->sum('quantity_on_hand');
                return response()->json(['available' => (int) $total, 'source' => 'product_ledger']);
            }
        }

        // Legacy fallback for items that have not yet been linked.
        $total = InvestigationItemStock::where('investigation_item_id', $request->item_id)
            ->atLocation($request->location)
            ->where('quantity', '>', 0)
            ->sum('quantity');

        return response()->json(['available' => (int) $total, 'source' => 'legacy_investigation_item_stock']);
    }

    /*
    |--------------------------------------------------------------------------
    | Internal helpers — unified inventory
    |--------------------------------------------------------------------------
    */

    /**
     * Ensure the investigation item has a mirror row in `products`. Creates and
     * links one if missing. Idempotent.
     */
    private function ensureLinkedProduct(InvestigationItem $item): ?Product
    {
        if ($item->product_id) {
            return Product::find($item->product_id);
        }

        $code = $this->buildProductCode($item);
        $type = $this->mapCategoryToProductType($item->category);

        $product = Product::where('code', $code)->first();
        if (! $product) {
            $product = Product::create([
                'name'          => $item->name,
                'code'          => $code,
                'product_type'  => $type->value,
                'unit'          => $item->unit ?? 'unit',
                'description'   => $item->description,
                'reorder_level' => $item->reorder_level,
                'default_cost'  => null,
                'base_price'    => 0,
                'is_billable'   => false,
                'is_active'     => (bool) $item->is_active,
            ]);
        }

        $item->updateQuietly(['product_id' => $product->id]);

        // Attach to investigation / radiology departments.
        $deptIds = Department::query()
            ->whereIn('type', [DepartmentType::INVESTIGATION->value, DepartmentType::RADIOLOGY->value])
            ->pluck('id')
            ->all();
        foreach ($deptIds as $deptId) {
            DB::table('product_department')->updateOrInsert(
                ['product_id' => $product->id, 'department_id' => $deptId],
                ['is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            );
        }

        return $product;
    }

    private function buildProductCode(InvestigationItem $item): string
    {
        $candidate = $item->code
            ? 'INV-' . strtoupper(Str::slug($item->code, '_'))
            : 'INV-' . strtoupper(Str::slug($item->name ?? '', '_'));

        if (strlen($candidate) > 56) {
            $candidate = substr($candidate, 0, 56);
        }
        if (! Product::where('code', $candidate)->exists()) {
            return $candidate;
        }
        return 'INV-' . $item->id;
    }

    private function mapCategoryToProductType(InvestigationItemCategory|string|null $category): ProductType
    {
        if ($category instanceof InvestigationItemCategory) {
            $category = $category->value;
        }
        return match ($category) {
            InvestigationItemCategory::REAGENT->value    => ProductType::REAGENT,
            InvestigationItemCategory::TEST_KIT->value   => ProductType::REAGENT,
            InvestigationItemCategory::CONSUMABLE->value => ProductType::CONSUMABLE,
            InvestigationItemCategory::RADIOLOGY->value  => ProductType::MEDICAL_SUPPLY,
            InvestigationItemCategory::IMAGING->value    => ProductType::MEDICAL_SUPPLY,
            default                                      => ProductType::GENERAL_ITEM,
        };
    }

    /**
     * Map the legacy InvestigationItemStock.location enum string
     * ('laboratory' / 'store' / 'pharmacy') to a row in `stock_locations`.
     */
    private function resolveStockLocation(string $key): ?StockLocationModel
    {
        $typeMap = [
            'laboratory' => 'lab',
            'lab'        => 'lab',
            'store'      => 'store',
            'pharmacy'   => 'pharmacy',
        ];
        $type = $typeMap[strtolower($key)] ?? $key;

        return StockLocationModel::query()
            ->where(function ($q) use ($key, $type) {
                $q->where('type', $type)->orWhere('name', $key);
            })
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }
}
