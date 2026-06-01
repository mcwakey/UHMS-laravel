<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceCatalogRequest;
use App\Models\Department;
use App\Models\InsuranceProvider;
use App\Models\ServiceCatalog;
use App\Models\ServicePrice;
use App\Models\Specialty;
use App\Enums\InsuranceType;
use Illuminate\Http\Request;

class ServiceCatalogController extends Controller
{
    /**
     * Service catalog list.
     */
    public function index(Request $request)
    {
        $query = ServiceCatalog::query()->latest();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $services = $query->with('department', 'specialties', 'prices.insuranceProvider')->paginate(20)->withQueryString();

        $categories = ['consultation', 'lab', 'pharmacy', 'procedure', 'imaging', 'surgery', 'admin', 'other'];
        $departments = Department::active()->orderBy('name')->get();
        $specialties = Specialty::active()->orderBy('name')->get();
        $insuranceProviders = InsuranceProvider::where('is_active', true)->orderBy('name')->get();
        $insuranceTypes = InsuranceType::cases();

        return view('admin.services.index', compact('services', 'categories', 'departments', 'specialties', 'insuranceProviders', 'insuranceTypes'));
    }

    /**
     * Store a new service.
     */
    public function store(StoreServiceCatalogRequest $request)
    {
        $service = ServiceCatalog::create([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'category' => $request->category,
            'price' => $request->price,
            'department_id' => $request->department_id,
            'department_type' => $request->department_type,
            'requires_rendering_tracking' => $request->input('requires_rendering_tracking') === '' ? null : $request->boolean('requires_rendering_tracking'),
            'is_active' => true,
        ]);

        if ($request->has('specialties')) {
            $service->specialties()->sync($request->specialties ?? []);
        }

        return back()->with('success', 'Service added successfully.');
    }

    /**
     * Update a service.
     */
    public function update(StoreServiceCatalogRequest $request, ServiceCatalog $service)
    {
        $service->update([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'category' => $request->category,
            'price' => $request->price,
            'department_id' => $request->department_id,
            'department_type' => $request->department_type,
            'requires_rendering_tracking' => $request->input('requires_rendering_tracking') === '' ? null : $request->boolean('requires_rendering_tracking'),
        ]);

        $service->specialties()->sync($request->specialties ?? []);

        return back()->with('success', 'Service updated successfully.');
    }

    /**
     * Toggle service active status.
     */
    public function toggle(ServiceCatalog $service)
    {
        $service->update(['is_active' => !$service->is_active]);

        return back()->with('success', "Service {$service->name} " . ($service->is_active ? 'activated' : 'deactivated') . '.');
    }

    /**
     * Save (upsert) insurance prices for a service.
     * Handles both default type prices and provider-specific prices.
     *
     * Filters out incomplete provider rows up front so adding an empty row
     * in the UI without filling it doesn't fail the whole save.
     */
    public function storePrices(Request $request, ServiceCatalog $service)
    {
        // Drop incomplete provider rows BEFORE validation so an unfinished
        // "Add Provider Override" click never blocks the save.
        $providerPrices = collect($request->input('provider_prices', []))
            ->filter(function ($row) {
                return is_array($row)
                    && ! empty($row['insurance_type'])
                    && ! empty($row['insurance_provider_id'])
                    && isset($row['price'])
                    && $row['price'] !== '';
            })
            ->values()
            ->all();

        $request->merge(['provider_prices' => $providerPrices]);

        $validTypes = collect(InsuranceType::cases())->pluck('value')->all();

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'type_prices'                              => ['nullable', 'array'],
            'type_prices.*'                            => ['nullable', 'numeric', 'min:0'],
            'provider_prices'                          => ['nullable', 'array'],
            'provider_prices.*.insurance_type'         => ['required', 'string', \Illuminate\Validation\Rule::in($validTypes)],
            'provider_prices.*.insurance_provider_id'  => ['required', 'exists:insurance_providers,id'],
            'provider_prices.*.price'                  => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Please correct the highlighted fields.',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        // Upsert default type prices
        foreach (($data['type_prices'] ?? []) as $type => $price) {
            if (! InsuranceType::tryFrom($type)) {
                continue;
            }
            if ($price === null || $price === '') {
                // Remove if cleared
                ServicePrice::where('service_catalog_id', $service->id)
                    ->where('insurance_type', $type)
                    ->whereNull('insurance_provider_id')
                    ->delete();
                continue;
            }
            ServicePrice::updateOrCreate(
                [
                    'service_catalog_id'    => $service->id,
                    'insurance_type'        => $type,
                    'insurance_provider_id' => null,
                ],
                ['price' => $price]
            );
        }

        // Upsert provider-specific prices
        foreach (($data['provider_prices'] ?? []) as $row) {
            ServicePrice::updateOrCreate(
                [
                    'service_catalog_id'    => $service->id,
                    'insurance_type'        => $row['insurance_type'],
                    'insurance_provider_id' => $row['insurance_provider_id'],
                ],
                ['price' => $row['price']]
            );
        }

        $message = "Prices for \"{$service->name}\" updated.";

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'service' => [
                    'id'    => $service->id,
                    'name'  => $service->name,
                    'price' => (float) $service->price,
                ],
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Delete a single service price entry.
     */
    public function deletePrice(Request $request, ServiceCatalog $service, ServicePrice $price)
    {
        abort_unless($price->service_catalog_id === $service->id, 403);
        $price->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Price entry removed.']);
        }

        return back()->with('success', 'Price entry removed.');
    }
}
