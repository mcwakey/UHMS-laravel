<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Designation;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    public function index(Request $request)
    {
        $designations = Designation::with('department')
            ->withCount('users')
            ->when($request->search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->when($request->department_id, function ($q, $deptId) {
                $q->where('department_id', $deptId);
            })
            ->latest()
            ->paginate(15);

        $departments = Department::active()->get();

        return view('designations.index', compact('designations', 'departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'description' => 'nullable|string|max:500',
        ]);

        Designation::create($validated);

        return redirect()->route('admin.designations.index')
            ->with('success', __('messages.designations.created'));
    }

    public function update(Request $request, Designation $designation)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'description' => 'nullable|string|max:500',
        ]);

        $designation->update($validated);

        return redirect()->route('admin.designations.index')
            ->with('success', __('messages.designations.updated'));
    }

    public function destroy(Designation $designation)
    {
        if ($designation->users()->exists()) {
            return redirect()->back()
                ->with('error', __('messages.designations.cannot_delete'));
        }

        $designation->delete();

        return redirect()->route('admin.designations.index')
            ->with('success', __('messages.designations.deleted'));
    }
}
