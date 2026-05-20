<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InsuranceType;
use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\InsuranceProvider;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Throwable;

class ProductController extends Controller
{
    public function __construct(private ProductService $products) {}

    public function index(Request $request)
    {
        $filters = $request->only([
            'search', 'product_type', 'type', 'department_id', 'status',
            'is_active', 'is_billable', 'has_insurance_prices', 'supplier_id',
        ]);
        foreach (['is_active', 'is_billable', 'has_insurance_prices'] as $booleanFilter) {
            if (array_key_exists($booleanFilter, $filters) && $filters[$booleanFilter] === '') {
                unset($filters[$booleanFilter]);
            }
        }
        $products    = $this->products->listProducts($filters);

        // Pre-load prices so the per-row pricing modal can be rendered inline
        // without N+1 queries (mirrors services index).
        $products->getCollection()->load('prices.insuranceProvider');

        $types       = ProductType::cases();
        $departments = Department::orderBy('name')->get();
        $suppliers   = Supplier::active()->orderBy('name')->get(['id', 'name']);

        $insuranceTypes     = InsuranceType::cases();
        $insuranceProviders = InsuranceProvider::where('is_active', true)
            ->where('is_default', false)
            ->orderBy('name')
            ->get(['id', 'name', 'short_name', 'type']);

        return view('admin.products.index', compact(
            'products', 'types', 'departments', 'suppliers', 'filters', 'insuranceTypes', 'insuranceProviders'
        ));
    }

    public function show(Product $product)
    {
        $product->load([
            'prices.insuranceProvider',
            'departments',
            'stockBalances.stockLocation',
        ]);

        $insuranceTypes     = InsuranceType::cases();
        $insuranceProviders = InsuranceProvider::where('is_active', true)
            ->where('is_default', false)
            ->orderBy('name')
            ->get(['id', 'name', 'short_name', 'type']);

        return view('admin.products.show', compact(
            'product', 'insuranceTypes', 'insuranceProviders'
        ));
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);
        try {
            $this->products->create($data);
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Product created.');
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validatePayload($request, $product->id);
        try {
            $this->products->update($product, $data);
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Product updated.');
    }

    public function toggle(Product $product)
    {
        $this->products->toggle($product);
        return back()->with('success', 'Product status toggled.');
    }

    /**
     * AJAX: products available for a department (used by theatre / lab consumable pickers).
     */
    public function forDepartment(Department $department)
    {
        $products = Product::query()
            ->where('is_active', true)
            ->forDepartment($department->id)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'unit', 'product_type']);
        return response()->json($products);
    }

    protected function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        $payload = $request->all();
        if (! array_key_exists('department_ids', $payload) && array_key_exists('department_ids[]', $payload)) {
            $request->merge(['department_ids' => (array) $payload['department_ids[]']]);
        }

        $validated = $request->validate([
            'name'           => 'required|string|max:191',
            'code'           => 'nullable|string|max:60',
            'product_type'   => 'required|string|in:' . implode(',', array_column(ProductType::cases(), 'value')),
            'unit'           => 'nullable|string|max:30',
            'description'    => 'nullable|string',
            'reorder_level'  => 'nullable|numeric|min:0',
            'default_cost'   => 'nullable|numeric|min:0',
            'base_price'     => 'nullable|numeric|min:0',
            'is_billable'    => 'nullable|boolean',
            'is_active'      => 'nullable|boolean',
            'department_ids' => 'nullable|array',
            'department_ids.*' => 'integer|exists:departments,id',
        ]);

        // Unchecked checkboxes are not submitted by browsers; default to [] so
        // syncDepartments always runs and can clear stale links.
        $validated['department_ids'] = $validated['department_ids'] ?? [];

        return $validated;
    }
}
