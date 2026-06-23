<?php

namespace App\Http\Controllers\Admin\AdmissionsWard;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBedRequest;
use App\Http\Requests\StoreWardRequest;
use App\Http\Requests\UpdateWardRequest;
use App\Models\Bed;
use App\Models\Department;
use App\Models\Ward;
use App\Services\WardService;
use Illuminate\Http\Request;

class WardController extends Controller
{
    public function __construct(
        private WardService $wardService
    ) {}

    public function index(Request $request)
    {
        $wards = $this->wardService->listWards($request->all());
        $departments = Department::orderBy('name')->get();

        return view('wards.index', compact('wards', 'departments'));
    }

    public function store(StoreWardRequest $request)
    {
        $ward = $this->wardService->createWard($request->validated());

        return redirect()
            ->route('admin.wards.index')
            ->with('success', __('messages.wards.created', ['name' => $ward->name]));
    }

    public function update(UpdateWardRequest $request, Ward $ward)
    {
        $this->wardService->updateWard($ward, $request->validated());

        return redirect()
            ->route('admin.wards.index')
            ->with('success', __('messages.wards.updated', ['name' => $ward->name]));
    }

    public function toggle(Ward $ward)
    {
        $this->wardService->toggleWard($ward);
        $status = $ward->is_active ? 'activated' : 'deactivated';

        return back()->with('success', __('messages.wards.toggled', ['name' => $ward->name, 'status' => $status]));
    }

    public function beds(Request $request)
    {
        $beds = $this->wardService->listBeds($request->all());
        $wards = Ward::active()->orderBy('name')->get();

        return view('wards.beds', compact('beds', 'wards'));
    }

    public function storeBed(StoreBedRequest $request)
    {
        $this->wardService->createBed($request->validated());

        return redirect()
            ->route('admin.wards.beds')
            ->with('success', __('messages.wards.bed_created'));
    }

    public function updateBed(Request $request, Bed $bed)
    {
        $request->validate([
            'bed_number' => ['required', 'string', 'max:20'],
            'bed_type' => ['required', 'string'],
            'status' => ['required', 'string'],
            'daily_rate' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->wardService->updateBed($bed, $request->only(['bed_number', 'bed_type', 'status', 'daily_rate', 'notes']));

        return redirect()
            ->route('admin.wards.beds')
            ->with('success', __('messages.wards.bed_updated'));
    }

    public function bedMap()
    {
        $wards = $this->wardService->getBedMap();

        return view('wards.bed-map', compact('wards'));
    }
}
