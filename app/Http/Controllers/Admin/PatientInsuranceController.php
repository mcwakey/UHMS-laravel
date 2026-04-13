<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InsuranceProvider;
use App\Models\Patient;
use App\Models\PatientInsurance;
use Illuminate\Http\Request;

class PatientInsuranceController extends Controller
{
    public function store(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'insurance_provider_id' => ['required', 'exists:insurance_providers,id'],
            'membership_number' => ['nullable', 'string', 'max:50'],
            'policy_number' => ['nullable', 'string', 'max:50'],
            'expiry_date' => ['nullable', 'date'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        // Check for duplicate
        $exists = $patient->insurances()
            ->where('insurance_provider_id', $data['insurance_provider_id'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Patient already has this insurance provider.');
        }

        if (!empty($data['is_primary'])) {
            $patient->insurances()->update(['is_primary' => false]);
        }

        $data['is_active'] = true;
        $patient->insurances()->create($data);

        return back()->with('success', 'Insurance added to patient.');
    }

    public function update(Request $request, Patient $patient, PatientInsurance $insurance)
    {
        $data = $request->validate([
            'insurance_provider_id' => ['required', 'exists:insurance_providers,id'],
            'membership_number' => ['nullable', 'string', 'max:50'],
            'policy_number' => ['nullable', 'string', 'max:50'],
            'expiry_date' => ['nullable', 'date'],
            'is_primary' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (!empty($data['is_primary'])) {
            $patient->insurances()->where('id', '!=', $insurance->id)->update(['is_primary' => false]);
        }

        $insurance->update($data);

        return back()->with('success', 'Insurance updated.');
    }

    public function destroy(Patient $patient, PatientInsurance $insurance)
    {
        // Prevent removing Cash & Carry default
        if ($insurance->insuranceProvider->is_default) {
            return back()->with('error', 'Cannot remove the default Cash & Carry insurance.');
        }

        $insurance->delete();

        return back()->with('success', 'Insurance removed.');
    }

    public function setPrimary(Patient $patient, PatientInsurance $insurance)
    {
        $patient->insurances()->update(['is_primary' => false]);
        $insurance->update(['is_primary' => true]);

        return back()->with('success', 'Primary insurance updated.');
    }

    /**
     * AJAX: Get providers by insurance type.
     */
    public function providersByType(Request $request)
    {
        $type = $request->get('type');
        $providers = InsuranceProvider::active()
            ->when($type, fn ($q) => $q->where('type', $type))
            ->orderBy('name')
            ->get(['id', 'name', 'short_name', 'type', 'tier', 'annual_limit', 'per_visit_limit']);

        return response()->json($providers);
    }
}
