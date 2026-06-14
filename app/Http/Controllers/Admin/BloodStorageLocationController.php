<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LogModule;
use App\Http\Controllers\Controller;
use App\Models\BloodStorageLocation;
use App\Models\BloodUnit;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BloodStorageLocationController extends Controller
{
    public const LOCATION_TYPES = [
        'BLOOD_BANK', 'REFRIGERATOR', 'FREEZER', 'PLATELET_AGITATOR', 'TRANSPORT_BOX', 'OTHER',
    ];

    public function __construct(protected ActivityLogService $logger) {}

    public function index()
    {
        $locations = BloodStorageLocation::query()
            ->withCount([
                'units as total_units',
                'units as available_units' => fn ($q) => $q->where('status', BloodUnit::STATUS_AVAILABLE),
            ])
            ->orderBy('name')
            ->get();

        return view('blood-bank.storage', [
            'locations' => $locations,
            'locationTypes' => self::LOCATION_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateLocation($request);
        $data['is_active'] = $request->boolean('is_active', true);

        $location = BloodStorageLocation::create($data);
        $this->logger->logCreated($location, LogModule::BLOOD_BANK);

        return back()->with('success', __('messages.blood_bank.location_added'));
    }

    public function update(Request $request, BloodStorageLocation $location)
    {
        $data = $this->validateLocation($request, $location);
        $data['is_active'] = $request->boolean('is_active');

        $old = $location->getOriginal();
        $location->update($data);
        $this->logger->logUpdated($location, LogModule::BLOOD_BANK, $old, $location->getAttributes());

        return back()->with('success', __('messages.blood_bank.location_updated'));
    }

    public function toggle(BloodStorageLocation $location)
    {
        $old = $location->getOriginal();
        $location->update(['is_active' => ! $location->is_active]);
        $this->logger->logUpdated($location, LogModule::BLOOD_BANK, $old, $location->getAttributes());

        return back()->with('success', __('messages.blood_bank.location_status_updated', ['status' => $location->is_active ? 'activated' : 'deactivated']));
    }

    private function validateLocation(Request $request, ?BloodStorageLocation $location = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('blood_storage_locations', 'code')->ignore($location?->id)],
            'location_type' => ['nullable', 'string', 'max:50'],
            'temperature_min' => ['nullable', 'numeric', 'between:-100,100'],
            'temperature_max' => ['nullable', 'numeric', 'between:-100,100', 'gte:temperature_min'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
