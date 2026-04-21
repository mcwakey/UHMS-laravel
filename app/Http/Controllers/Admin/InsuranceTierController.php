<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InsuranceProvider;
use App\Models\InsuranceTier;
use Illuminate\Http\Request;

class InsuranceTierController extends Controller
{
    /** Show all tiers for a provider. */
    public function index(InsuranceProvider $provider)
    {
        $tiers = $provider->tiers()->get();
        return view('insurance.tiers', compact('provider', 'tiers'));
    }

    /** Create a new tier for a provider. */
    public function store(Request $request, InsuranceProvider $provider)
    {
        $data = $request->validate($this->rules());
        $data['insurance_provider_id'] = $provider->id;

        // Only one tier can be default per provider
        if (! empty($data['is_default'])) {
            $provider->tiers()->update(['is_default' => false]);
        }

        InsuranceTier::create($data);

        return redirect()
            ->route('admin.insurance-providers.tiers.index', $provider)
            ->with('success', 'Tier created successfully.');
    }

    /** Update an existing tier. */
    public function update(Request $request, InsuranceTier $tier)
    {
        $data = $request->validate($this->rules());

        if (! empty($data['is_default'])) {
            $tier->insuranceProvider->tiers()
                ->where('id', '!=', $tier->id)
                ->update(['is_default' => false]);
        }

        $tier->update($data);

        return redirect()
            ->route('admin.insurance-providers.tiers.index', $tier->insurance_provider_id)
            ->with('success', 'Tier updated.');
    }

    /** Delete a tier (blocked if patients are enrolled). */
    public function destroy(InsuranceTier $tier)
    {
        if ($tier->patientInsurances()->exists()) {
            return back()->with('error', 'Cannot delete tier: patients are currently enrolled on it.');
        }

        $providerId = $tier->insurance_provider_id;
        $tier->delete();

        return redirect()
            ->route('admin.insurance-providers.tiers.index', $providerId)
            ->with('success', 'Tier deleted.');
    }

    /**
     * AJAX: Return tiers for a provider (used in patient insurance enrolment form).
     */
    public function tiersForProvider(InsuranceProvider $provider)
    {
        $tiers = $provider->tiers()
            ->active()
            ->get(['id', 'name', 'code', 'is_default', 'coverage_percentage', 'max_beneficiaries']);

        return response()->json($tiers);
    }

    // ── Shared validation rules ──────────────────────────────────────────────

    private function rules(): array
    {
        return [
            'name'                              => ['required', 'string', 'max:100'],
            'code'                              => ['nullable', 'string', 'max:30'],
            'description'                       => ['nullable', 'string', 'max:2000'],
            'is_default'                        => ['nullable', 'boolean'],
            'is_active'                         => ['nullable', 'boolean'],
            'sort_order'                        => ['nullable', 'integer', 'min:0'],
            // Base constraints
            'coverage_percentage'               => ['nullable', 'numeric', 'min:0', 'max:100'],
            'per_visit_limit'                   => ['nullable', 'numeric', 'min:0'],
            'annual_limit'                      => ['nullable', 'numeric', 'min:0'],
            'max_per_month'                     => ['nullable', 'numeric', 'min:0'],
            'max_visits_per_month'              => ['nullable', 'integer', 'min:1'],
            // Family & interval
            'min_visit_interval_days'           => ['nullable', 'integer', 'min:1'],
            'max_beneficiaries'                 => ['nullable', 'integer', 'min:1'],
            // Holder overrides
            'holder_per_visit_limit'            => ['nullable', 'numeric', 'min:0'],
            'holder_annual_limit'               => ['nullable', 'numeric', 'min:0'],
            'holder_max_per_month'              => ['nullable', 'numeric', 'min:0'],
            'holder_max_visits_per_month'       => ['nullable', 'integer', 'min:1'],
            // Beneficiary overrides
            'beneficiary_per_visit_limit'       => ['nullable', 'numeric', 'min:0'],
            'beneficiary_annual_limit'          => ['nullable', 'numeric', 'min:0'],
            'beneficiary_max_per_month'         => ['nullable', 'numeric', 'min:0'],
            'beneficiary_max_visits_per_month'  => ['nullable', 'integer', 'min:1'],
        ];
    }
}
