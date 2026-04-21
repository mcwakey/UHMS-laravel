<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InsuranceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInsuranceProviderRequest;
use App\Models\InsuranceProvider;
use App\Models\InsuranceTier;
use Illuminate\Http\Request;

class InsuranceProviderController extends Controller
{
    public function index(Request $request)
    {
        $providers = InsuranceProvider::query()
            ->withCount('claims')
            ->when($request->search, fn ($q, $s) => $q->search($s))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->orderBy('name')
            ->paginate(15);

        $types = InsuranceType::cases();

        return view('insurance.index', compact('providers', 'types'));
    }

    public function store(StoreInsuranceProviderRequest $request)
    {
        $provider = InsuranceProvider::create($request->validated());

        // Auto-create a Standard tier so the provider is immediately usable
        InsuranceTier::create([
            'insurance_provider_id' => $provider->id,
            'name'                  => 'Standard',
            'code'                  => 'STD',
            'is_default'            => true,
            'is_active'             => true,
            'coverage_percentage'   => 100,
        ]);

        return redirect()
            ->route('admin.insurance-providers.index')
            ->with('success', 'Insurance provider created. A Standard tier has been added — configure its limits from the provider dropdown.');
    }

    public function update(StoreInsuranceProviderRequest $request, InsuranceProvider $provider)
    {
        $provider->update($request->validated());

        return redirect()
            ->route('admin.insurance-providers.index')
            ->with('success', 'Insurance provider updated successfully.');
    }

    public function toggle(InsuranceProvider $provider)
    {
        $provider->update(['is_active' => ! $provider->is_active]);

        return back()->with('success', 'Provider status updated.');
    }
}
