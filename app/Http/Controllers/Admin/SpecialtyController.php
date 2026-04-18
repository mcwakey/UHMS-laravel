<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Specialty;
use Illuminate\Http\Request;

class SpecialtyController extends Controller
{
    public function index(Request $request)
    {
        $specialties = Specialty::query()
            ->with('department')
            ->withCount(['doctors', 'services'])
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $departments = Department::active()->orderBy('name')->get();

        return view('admin.specialties.index', compact('specialties', 'departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255', 'unique:specialties,name'],
            'description'   => ['nullable', 'string', 'max:1000'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        $data['is_active'] = true;
        Specialty::create($data);

        return back()->with('success', 'Specialty created successfully.');
    }

    public function update(Request $request, Specialty $specialty)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255', 'unique:specialties,name,' . $specialty->id],
            'description'   => ['nullable', 'string', 'max:1000'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        $specialty->update($data);

        return back()->with('success', 'Specialty updated successfully.');
    }

    public function toggle(Specialty $specialty)
    {
        $specialty->update(['is_active' => !$specialty->is_active]);

        return back()->with('success', "Specialty {$specialty->name} " . ($specialty->is_active ? 'activated' : 'deactivated') . '.');
    }
}
