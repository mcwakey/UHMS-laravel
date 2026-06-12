<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InsuranceProvider;
use App\Models\InsuranceTier;
use App\Models\Patient;
use App\Models\PatientInsurance;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PatientInsuranceController extends Controller
{
    public function store(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'insurance_provider_id'    => ['required', 'exists:insurance_providers,id'],
            'insurance_tier_id'        => ['nullable', 'exists:insurance_tiers,id'],
            'member_type'              => ['nullable', 'in:holder,beneficiary'],
            'card_holder_insurance_id' => ['nullable', 'exists:patient_insurances,id'],
            'membership_number'        => ['nullable', 'string', 'max:50'],
            'policy_number'            => ['nullable', 'string', 'max:50'],
            'ccc_code'                 => ['nullable', 'string', 'max:64'],
            'expiry_date'              => ['nullable', 'date'],
            'is_primary'               => ['nullable', 'boolean'],
        ]);

        $data = $this->resolveInsuranceTier($data);

        // Prevent duplicate provider assignment
        $exists = $patient->insurances()
            ->where('insurance_provider_id', $data['insurance_provider_id'])
            ->exists();

        if ($exists) {
            return $this->respondError($request, 'Patient already has this insurance provider.');
        }

        $memberType = $data['member_type'] ?? 'holder';

        // Beneficiary-specific validation
        if ($memberType === 'beneficiary') {
            if (empty($data['card_holder_insurance_id'])) {
                return $this->respondError($request, 'Card holder must be specified for beneficiaries.');
            }

            // Check max_beneficiaries on the tier
            if (! empty($data['insurance_tier_id'])) {
                $tier = InsuranceTier::find($data['insurance_tier_id']);

                if ($tier && $tier->max_beneficiaries !== null) {
                    $currentCount = PatientInsurance::where('card_holder_insurance_id', $data['card_holder_insurance_id'])
                        ->where('member_type', 'beneficiary')
                        ->where('is_active', true)
                        ->count();

                    if ($currentCount >= $tier->max_beneficiaries) {
                        return $this->respondError($request, "Maximum beneficiaries ({$tier->max_beneficiaries}) already reached for this card holder on the selected tier.");
                    }
                }
            }
        }

        if (! empty($data['is_primary'])) {
            $patient->insurances()->update(['is_primary' => false]);
        }

        $data['is_active']   = true;
        $data['member_type'] = $memberType;

        $insurance = $patient->insurances()->create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'message'      => __('messages.patient_insurance.added'),
                'insurance_id' => $insurance->id,
            ]);
        }

        return back()->with('success', __('messages.patient_insurance.added'));
    }

    public function update(Request $request, Patient $patient, PatientInsurance $insurance)
    {
        $data = $request->validate([
            'insurance_provider_id'    => ['required', 'exists:insurance_providers,id'],
            'insurance_tier_id'        => ['nullable', 'exists:insurance_tiers,id'],
            'member_type'              => ['nullable', 'in:holder,beneficiary'],
            'card_holder_insurance_id' => ['nullable', 'exists:patient_insurances,id'],
            'membership_number'        => ['nullable', 'string', 'max:50'],
            'policy_number'            => ['nullable', 'string', 'max:50'],
            'ccc_code'                 => ['nullable', 'string', 'max:64'],
            'expiry_date'              => ['nullable', 'date'],
            'is_primary'               => ['nullable', 'boolean'],
            'is_active'                => ['nullable', 'boolean'],
        ]);

        $data = $this->resolveInsuranceTier($data);

        if (! empty($data['is_primary'])) {
            $patient->insurances()->where('id', '!=', $insurance->id)->update(['is_primary' => false]);
        }

        $insurance->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'message'      => __('messages.patient_insurance.updated'),
                'insurance_id' => $insurance->id,
            ]);
        }

        return back()->with('success', __('messages.patient_insurance.updated'));
    }

    public function destroy(Patient $patient, PatientInsurance $insurance)
    {
        if ($insurance->insuranceProvider->is_default) {
            return back()->with('error', __('messages.patient_insurance.cannot_remove_default'));
        }

        $insurance->delete();

        return back()->with('success', __('messages.patient_insurance.removed'));
    }

    public function setPrimary(Patient $patient, PatientInsurance $insurance)
    {
        $patient->insurances()->update(['is_primary' => false]);
        $insurance->update(['is_primary' => true]);

        return back()->with('success', __('messages.patient_insurance.primary_updated'));
    }

    /**
     * AJAX: Get providers (with their tiers) filtered by insurance type.
     */
    public function providersByType(Request $request)
    {
        $type = $request->get('type');

        $providers = InsuranceProvider::active()
            ->where('is_default', false)
            ->with(['tiers' => fn ($q) => $q->active()->orderBy('sort_order')->orderBy('name')])
            ->when($type, fn ($q) => $q->where('type', $type))
            ->orderBy('name')
            ->get(['id', 'name', 'short_name', 'type']);

        return response()->json($providers);
    }

    /**
     * Accept an omitted tier from the UI and fall back to the provider's default
     * active tier. Also guards against mismatched provider/tier combinations.
     */
    private function resolveInsuranceTier(array $data): array{
        $provider = InsuranceProvider::with([
            'tiers' => fn ($q) => $q->active()->orderByDesc('is_default')->orderBy('sort_order')->orderBy('name'),
        ])->findOrFail($data['insurance_provider_id']);

        if (! empty($data['insurance_tier_id'])) {
            $selectedTierBelongsToProvider = $provider->tiers->contains(
                fn ($tier) => (int) $tier->id === (int) $data['insurance_tier_id']
            );

            if (! $selectedTierBelongsToProvider) {
                throw ValidationException::withMessages([
                    'insurance_tier_id' => 'Selected insurance tier does not belong to the chosen provider.',
                ]);
            }

            return $data;
        }

        $defaultTierId = $provider->tiers->first()?->id;
        if ($defaultTierId) {
            $data['insurance_tier_id'] = $defaultTierId;
        }

        return $data;
    }

    private function respondError(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors'  => ['_general' => [$message]],
            ], 422);
        }

        return back()->with('error', $message);
    }
}

