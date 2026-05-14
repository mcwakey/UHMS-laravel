<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Throwable;

class ProductController extends Controller
{
    public function __construct(private ProductService $products) {}

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'type', 'department_id', 'is_active']);
        if (array_key_exists('is_active', $filters) && $filters['is_active'] === '') {
            unset($filters['is_active']);
        }
        $products    = $this->products->listProducts($filters);
        $types       = ProductType::cases();
        $departments = Department::orderBy('name')->get();
        return view('admin.products.index', compact('products', 'types', 'departments', 'filters'));
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
