<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LabTest;
use App\Models\LabTestCategory;
use App\Services\LabService;
use Illuminate\Http\Request;

class LabTestController extends Controller
{
    public function __construct(
        protected LabService $labService,
    ) {}

    /**
     * Lab test catalog management page.
     */
    public function index(Request $request)
    {
        $categories = $this->labService->getCategories();
        $tests = LabTest::with('category')
            ->when($request->search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            })
            ->when($request->category_id, function ($q, $categoryId) {
                $q->where('category_id', $categoryId);
            })
            ->latest()
            ->paginate(15);

        return view('lab.tests', compact('categories', 'tests'));
    }

    /**
     * Store a new category.
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:lab_test_categories,name',
            'description' => 'nullable|string|max:500',
        ]);

        $validated['is_active'] = true;
        $this->labService->storeCategory($validated);

        return back()->with('success', 'Category created successfully.');
    }

    /**
     * Update a category.
     */
    public function updateCategory(Request $request, LabTestCategory $category)
    {
        $validated = $request->validate([
            'name' => "required|string|max:255|unique:lab_test_categories,name,{$category->id}",
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $this->labService->updateCategory($category, $validated);

        return back()->with('success', 'Category updated successfully.');
    }

    /**
     * Delete a category.
     */
    public function destroyCategory(LabTestCategory $category)
    {
        if (!$this->labService->deleteCategory($category)) {
            return back()->with('error', 'Cannot delete category with existing tests. Remove or reassign tests first.');
        }

        return back()->with('success', 'Category deleted successfully.');
    }

    /**
     * Store a new test.
     */
    public function storeTest(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:lab_test_categories,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:lab_tests,code',
            'normal_range' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:50',
            'price' => 'nullable|numeric|min:0',
        ]);

        $validated['is_active'] = true;
        $this->labService->storeTest($validated);

        return back()->with('success', 'Lab test created successfully.');
    }

    /**
     * Update a test.
     */
    public function updateTest(Request $request, LabTest $test)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:lab_test_categories,id',
            'name' => 'required|string|max:255',
            'code' => "required|string|max:50|unique:lab_tests,code,{$test->id}",
            'normal_range' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:50',
            'price' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $this->labService->updateTest($test, $validated);

        return back()->with('success', 'Lab test updated successfully.');
    }

    /**
     * Toggle a test active/inactive.
     */
    public function toggleTest(LabTest $test)
    {
        $this->labService->toggleTest($test);

        return back()->with('success', 'Lab test status toggled.');
    }

    /**
     * Get tests by category (API for AJAX).
     */
    public function testsByCategory(LabTestCategory $category)
    {
        return response()->json(
            $category->activeTests()->orderBy('name')->get(['id', 'name', 'code', 'normal_range', 'unit', 'price'])
        );
    }
}
