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
        // Ensure department_ids is always present in the validated payload so
        // unchecking all departments correctly clears the link table (instead of
        // silently keeping the old links because the key was missing).
        if (!$request->has('department_ids')) {
            $request->merge(['department_ids' => []]);
        }

        return $request->validate([
            'name'           => 'required|string|max:191',
            'code'           => 'nullable|string|max:60',
            'product_type'   => 'required|string|in:' . implode(',', array_column(ProductType::cases(), 'value')),
            'unit'           => 'nullable|string|max:30',
            'description'    => 'nullable|string',
            'reorder_level'  => 'nullable|numeric|min:0',
            'default_cost'   => 'nullable|numeric|min:0',
            'is_active'      => 'nullable|boolean',
            'department_ids' => 'present|array',
            'department_ids.*' => 'integer|exists:departments,id',
        ]);
    }
}
