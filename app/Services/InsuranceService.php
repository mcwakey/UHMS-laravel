<?php

namespace App\Services;

use App\Models\InsuranceProvider;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Models\ServiceCatalog;

class InsuranceService
{
    /**
     * Resolve the best insurance to apply for a visit.
     *
     * Priority order:
     *  1. Explicitly requested insurance (if valid)
     *  2. Patient's primary insurance (if valid, non-Cash & Carry)
     *  3. Any valid active non-default insurance
     *  4. Cash & Carry fallback (auto-creates if missing)
     */
    public function resolveForPatient(Patient $patient, ?int $requestedInsuranceId = null): PatientInsurance
    {
        $patient->loadMissing('insurances.insuranceProvider');

        // 1. Honour explicit selection if valid
        if ($requestedInsuranceId) {
            $requested = $patient->insurances->firstWhere('id', $requestedInsuranceId);
            if ($requested && $requested->is_valid) {
                return $requested;
            }
            // Fall through — requested insurance is expired/inactive
        }

        // 2. Primary insurance (skip Cash & Carry defaults)
        $primary = $patient->insurances
            ->where('is_primary', true)
            ->first();

        if ($primary && $primary->is_valid && !$primary->insuranceProvider->is_default) {
            return $primary;
        }

        // 3. Any valid, non-default insurance
        $anyValid = $patient->insurances
            ->filter(fn ($ins) => $ins->is_valid && !$ins->insuranceProvider->is_default)
            ->first();

        if ($anyValid) {
            return $anyValid;
        }

        // 4. Cash & Carry fallback
        return $this->ensureCashAndCarry($patient);
    }

    /**
     * Get or create the Cash & Carry (default) insurance record for a patient.
     */
    public function ensureCashAndCarry(Patient $patient): PatientInsurance
    {
        $provider = InsuranceProvider::where('is_default', true)->first();

        if (!$provider) {
            throw new \RuntimeException('Cash & Carry insurance provider is not configured. Run the CashAndCarrySeeder.');
        }

        return PatientInsurance::firstOrCreate(
            ['patient_id' => $patient->id, 'insurance_provider_id' => $provider->id],
            ['is_active' => true, 'is_primary' => false]
        );
    }

    /**
     * Return a structured display payload for a PatientInsurance (used by AJAX / view rendering).
     */
    public function getDisplayData(PatientInsurance $insurance): array
    {
        $insurance->loadMissing('insuranceProvider');
        $provider  = $insurance->insuranceProvider;
        $remaining = $insurance->remaining_annual_limit;

        $typeRaw = $provider->type instanceof \BackedEnum ? $provider->type->value : (string) $provider->type;

        return [
            'id'                    => $insurance->id,
            'provider_id'           => $provider->id,
            'provider_name'         => $provider->name,
            'provider_short'        => $provider->short_name ?? $provider->name,
            'type'                  => $typeRaw,
            'type_label'            => $provider->type instanceof \App\Enums\InsuranceType
                                           ? $provider->type->label()
                                           : strtoupper($typeRaw),
            'type_color'            => $provider->type instanceof \App\Enums\InsuranceType
                                           ? $provider->type->color()
                                           : 'secondary',
            'membership_number'     => $insurance->membership_number,
            'policy_number'         => $insurance->policy_number,
            'is_valid'              => $insurance->is_valid,
            'is_expired'            => $insurance->is_expired,
            'is_default_provider'   => (bool) $provider->is_default,
            'expiry_date'           => $insurance->expiry_date?->format('d M Y'),
            'expiry_date_raw'       => $insurance->expiry_date?->format('Y-m-d'),
            'coverage_percentage'   => (float) ($provider->coverage_percentage ?? 0),
            'annual_limit'          => $provider->annual_limit ? (float) $provider->annual_limit : null,
            'per_visit_limit'       => $provider->per_visit_limit ? (float) $provider->per_visit_limit : null,
            'remaining_annual_limit'=> $remaining,
        ];
    }

    /**
     * Return display data for all insurances belonging to a patient.
     */
    public function getAllForPatient(Patient $patient): array
    {
        $patient->loadMissing('insurances.insuranceProvider');

        return $patient->insurances->map(fn ($ins) => $this->getDisplayData($ins))->values()->toArray();
    }

    /**
     * Calculate the service line pricing for a given PatientInsurance.
     * Delegates to ServiceCatalog::getPricingForInsurance().
     */
    public function calculateServicePricing(ServiceCatalog $service, PatientInsurance $insurance, int $quantity = 1): array
    {
        $pricing     = $service->getPricingForInsurance($insurance);
        $totalPrice  = round($pricing['unit_price'] * $quantity, 2);
        $covered     = round($pricing['insurance_covered'] * $quantity, 2);
        $payable     = round($pricing['patient_payable'] * $quantity, 2);

        return [
            'service_catalog_id' => $service->id,
            'service_name'       => $service->name,
            'service_code'       => $service->code,
            'department_id'      => $service->department_id,
            'quantity'           => $quantity,
            'unit_price'         => $pricing['unit_price'],
            'total_price'        => $totalPrice,
            'insurance_covered'  => $covered,
            'patient_payable'    => $payable,
        ];
    }

    /**
     * Calculate billing totals for multiple service lines.
     */
    public function calculateTotal(array $serviceLines): array
    {
        $subtotal          = 0;
        $totalCovered      = 0;
        $totalPatientOwes  = 0;

        foreach ($serviceLines as $line) {
            $subtotal         += $line['total_price'];
            $totalCovered     += $line['insurance_covered'];
            $totalPatientOwes += $line['patient_payable'];
        }

        return [
            'subtotal'          => round($subtotal, 2),
            'insurance_covered' => round($totalCovered, 2),
            'patient_payable'   => round($totalPatientOwes, 2),
        ];
    }
}
