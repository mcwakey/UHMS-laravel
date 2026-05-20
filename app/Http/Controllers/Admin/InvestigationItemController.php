<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DepartmentType;
use App\Enums\InvestigationItemCategory;
use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\InvestigationItem;
use App\Models\InvestigationItemStock;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockLocation as StockLocationModel;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvestigationItemController extends Controller
{
    public function __construct(private ProductService $productService)
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

        $products = $this->productService
            ->queryProductsForDepartmentTypes($departmentTypes, $allowedProductTypes)
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
        $balances = StockBalance::query()
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
        return redirect()->route('admin.investigations.items.index');
    }

    public function storeStock(Request $request)
    {
        abort(403, 'Investigation stock is received in Main Store and transferred to Laboratory. Direct investigation stock entry is disabled.');
    }

    public function updateStock(Request $request, InvestigationItemStock $stock)
    {
        abort(403, 'Investigation stock is adjusted from the central Product Stock workflow, not from investigation item batches.');
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX
    |--------------------------------------------------------------------------
    */

    public function search(Request $request)
    {
        $term = (string) $request->q;
        $departmentTypes = [DepartmentType::INVESTIGATION->value, DepartmentType::RADIOLOGY->value];
        $allowedProductTypes = [
            ProductType::REAGENT->value,
            ProductType::CONSUMABLE->value,
            ProductType::MEDICAL_SUPPLY->value,
            ProductType::GENERAL_ITEM->value,
        ];

        $items = $this->productService
            ->queryProductsForDepartmentTypes($departmentTypes, $allowedProductTypes)
            ->when($term !== '', fn ($q) => $q->where(fn ($qq) => $qq->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%")))
            ->limit(20)
            ->get(['id', 'name', 'code', 'unit', 'product_type']);

        return response()->json($items);
    }

    public function getStock(Request $request)
    {
        $request->validate([
            'product_id'  => ['required', 'exists:products,id'],
            'location' => ['required', 'string'],
        ]);

        $location = $this->resolveStockLocation((string) $request->location);
        $total = $location
            ? (float) StockBalance::query()
                ->where('product_id', $request->integer('product_id'))
                ->where('stock_location_id', $location->id)
                ->sum('quantity_on_hand')
            : 0.0;

        return response()->json(['available' => (int) $total, 'source' => 'stock_balances']);
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
