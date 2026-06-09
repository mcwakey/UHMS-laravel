<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\DepartmentType;
use App\Enums\ResultType;
use App\Models\Department;
use App\Services\StockLocationSyncService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function __construct(private StockLocationSyncService $stockLocationSync) {}

    public function index(Request $request)
    {
        $departments = Department::withCount(['users', 'designations'])
            ->when($request->search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(15);

        $departmentTypes = DepartmentType::cases();
        $resultTypes     = ResultType::selectableCases();

        return view('departments.index', compact('departments', 'departmentTypes', 'resultTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'code'             => 'required|string|max:10|unique:departments,code',
            'type'             => ['nullable', Rule::enum(DepartmentType::class)],
            'result_type'      => ['required', Rule::enum(ResultType::class)],
            'is_stock_managed' => 'nullable|boolean',
            'description'      => 'nullable|string|max:500',
            'status'           => 'required|in:active,inactive',
        ]);

        $validated['is_stock_managed'] = $request->boolean('is_stock_managed');

        $department = Department::create($validated);

        // Auto-create a stock location when the department manages stock.
        $this->stockLocationSync->ensureDepartmentLocation($department);

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'code'             => "required|string|max:10|unique:departments,code,{$department->id}",
            'type'             => ['nullable', Rule::enum(DepartmentType::class)],
            'result_type'      => ['required', Rule::enum(ResultType::class)],
            'is_stock_managed' => 'nullable|boolean',
            'description'      => 'nullable|string|max:500',
            'status'           => 'required|in:active,inactive',
        ]);

        $validated['is_stock_managed'] = $request->boolean('is_stock_managed');

        $department->update($validated);

        // Keep the department's stock location in sync when stock management is on.
        $this->stockLocationSync->ensureDepartmentLocation($department);

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department)
    {
        if ($department->users()->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete department with assigned users.');
        }

        $department->delete();

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department deleted successfully.');
    }
}
