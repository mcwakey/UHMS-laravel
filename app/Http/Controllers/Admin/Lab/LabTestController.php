<?php

namespace App\Http\Controllers\Admin\Lab;

use App\Http\Controllers\Controller;
use App\Enums\DepartmentType;
use App\Models\Department;
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
        $tests = LabTest::with(['category.department', 'criteria'])
            ->when($request->search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            })
            ->when($request->category_id, function ($q, $categoryId) {
                $q->where('category_id', $categoryId);
            })
            ->latest()
            ->paginate(15);

        // Investigation-type departments are the only valid owners of a lab
        // test category. Their result_type drives the test creation UI
        // (parameters → criteria editor; richtext → description template).
        $investigationDepartments = Department::query()
            ->where('status', 'active')
            ->where('type', DepartmentType::INVESTIGATION->value)
            ->orderBy('name')
            ->get();

        return view('lab.tests', compact('categories', 'tests', 'investigationDepartments'));
    }

    /**
     * Store a new category.
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:lab_test_categories,name',
            'description' => 'nullable|string|max:500',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $validated['is_active'] = true;
        $this->labService->storeCategory($validated);

        return back()->with('success', __('messages.lab_tests.category_created'));
    }

    /**
     * Update a category.
     */
    public function updateCategory(Request $request, LabTestCategory $category)
    {
        $validated = $request->validate([
            'name' => "required|string|max:255|unique:lab_test_categories,name,{$category->id}",
            'description' => 'nullable|string|max:500',
            'department_id' => 'nullable|exists:departments,id',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $this->labService->updateCategory($category, $validated);

        return back()->with('success', __('messages.lab_tests.category_updated'));
    }

    /**
     * Delete a category.
     */
    public function destroyCategory(LabTestCategory $category)
    {
        if (!$this->labService->deleteCategory($category)) {
            return back()->with('error', __('messages.lab_tests.category_cannot_delete'));
        }

        return back()->with('success', __('messages.lab_tests.category_deleted'));
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
            'description_template' => 'nullable|string|max:10000',
            'criteria' => 'nullable|array',
            'criteria.*.name' => 'nullable|string|max:255',
            'criteria.*.normal_range' => 'nullable|string|max:255',
            'criteria.*.unit' => 'nullable|string|max:50',
            'price' => 'nullable|numeric|min:0',
        ]);

        $validated['is_active'] = true;
        $this->labService->storeTest($validated);

        return back()->with('success', __('messages.lab_tests.created'));
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
            'description_template' => 'nullable|string|max:10000',
            'criteria' => 'nullable|array',
            'criteria.*.name' => 'nullable|string|max:255',
            'criteria.*.normal_range' => 'nullable|string|max:255',
            'criteria.*.unit' => 'nullable|string|max:50',
            'price' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $this->labService->updateTest($test, $validated);

        return back()->with('success', __('messages.lab_tests.updated'));
    }

    /**
     * Toggle a test active/inactive.
     */
    public function toggleTest(LabTest $test)
    {
        $this->labService->toggleTest($test);

        return back()->with('success', __('messages.lab_tests.toggled'));
    }

    /**
     * Get tests by category (API for AJAX).
     */
    public function testsByCategory(LabTestCategory $category)
    {
        return response()->json(
            $category->activeTests()
                ->with('criteria')
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'normal_range', 'unit', 'price', 'category_id'])
        );
    }
}
