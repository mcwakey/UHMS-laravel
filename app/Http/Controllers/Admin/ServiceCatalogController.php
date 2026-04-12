<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceCatalogRequest;
use App\Models\ServiceCatalog;
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

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $services = $query->paginate(20)->withQueryString();

        $categories = ['consultation', 'lab', 'pharmacy', 'procedure', 'other'];

        return view('admin.services.index', compact('services', 'categories'));
    }

    /**
     * Store a new service.
     */
    public function store(StoreServiceCatalogRequest $request)
    {
        ServiceCatalog::create([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'category' => $request->category,
            'price' => $request->price,
            'nhis_price' => $request->nhis_price,
            'is_nhis_covered' => $request->boolean('is_nhis_covered'),
            'is_active' => true,
        ]);

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
            'nhis_price' => $request->nhis_price,
            'is_nhis_covered' => $request->boolean('is_nhis_covered'),
        ]);

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
}
