<?php

namespace App\Services;

use App\Models\InsuranceProvider;
use App\Models\Patient;
use App\Models\PatientInsurance;

class InsuranceService
{
    /**
     * Resolve the insurance to use for a visit.
     *
     * Logic:
     * 1. If a specific insurance_id is selected, validate and use it
     * 2. Otherwise load patient's primary (default) insurance
     * 3. Check if the default is still valid (date-based)
     * 4. If NOT valid → fall back to Cash & Carry
     *
     * @return array{insurance: ?PatientInsurance, provider: InsuranceProvider, is_fallback: bool}
     */
    public function resolveForVisit(Patient $patient, ?int $selectedInsuranceId = null): array
    {
        // If user specifically selected an insurance
        if ($selectedInsuranceId) {
            $selected = PatientInsurance::with('insuranceProvider')
                ->where('id', $selectedInsuranceId)
                ->where('patient_id', $patient->id)
                ->first();

            if ($selected && $selected->is_valid) {
                return [
                    'insurance' => $selected,
                    'provider' => $selected->insuranceProvider,
                    'is_fallback' => false,
                ];
            }
        }

        // Try to load patient's primary insurance
        $primary = $patient->primaryInsurance;
        if ($primary) {
            $primary->load('insuranceProvider');
            if ($primary->is_valid) {
                return [
                    'insurance' => $primary,
                    'provider' => $primary->insuranceProvider,
                    'is_fallback' => false,
                ];
            }
        }

        // Fallback to Cash & Carry
        return $this->getCashAndCarryResult($patient);
    }

    /**
     * Get the Cash & Carry fallback result.
     */
    public function getCashAndCarryResult(Patient $patient): array
    {
        $cashProvider = InsuranceProvider::where('is_default', true)->first();

        // Find or create the patient's Cash & Carry insurance record
        $cashInsurance = null;
        if ($cashProvider) {
            $cashInsurance = PatientInsurance::firstOrCreate(
                ['patient_id' => $patient->id, 'insurance_provider_id' => $cashProvider->id],
                ['is_primary' => false, 'is_active' => true]
            );
        }

        return [
            'insurance' => $cashInsurance,
            'provider' => $cashProvider,
            'is_fallback' => true,
        ];
    }

    /**
     * Get all insurances for a patient with validation info.
     *
     * @return array of insurance data with validity/coverage info
     */
    public function getPatientInsurances(Patient $patient): array
    {
        $insurances = $patient->insurances()
            ->with('insuranceProvider')
            ->orderByDesc('is_primary')
            ->get();

        return $insurances->map(function (PatientInsurance $ins) {
            $provider = $ins->insuranceProvider;
            return [
                'id' => $ins->id,
                'provider_id' => $provider->id,
                'provider_name' => $provider->name,
                'type' => $provider->type instanceof \BackedEnum ? $provider->type->value : $provider->type,
                'type_label' => $provider->type instanceof \BackedEnum ? $provider->type->label() : ucfirst($provider->type),
                'type_color' => $provider->type instanceof \BackedEnum ? $provider->type->color() : 'secondary',
                'membership_number' => $ins->membership_number,
                'policy_number' => $ins->policy_number,
                'start_date' => $ins->start_date?->format('Y-m-d'),
                'expiry_date' => $ins->expiry_date?->format('Y-m-d'),
                'is_primary' => $ins->is_primary,
                'is_active' => $ins->is_active,
                'is_expired' => $ins->is_expired,
                'is_valid' => $ins->is_valid,
                'is_default' => $provider->is_default,
                'coverage_percentage' => $provider->coverage_percentage,
                'annual_limit' => $provider->annual_limit,
                'per_visit_limit' => $provider->per_visit_limit,
                'remaining_annual_limit' => $ins->remaining_annual_limit,
            ];
        })->values()->toArray();
    }

    /**
     * Calculate insurance coverage amount for a service price.
     */
    public function calculateCoverage(PatientInsurance $insurance, float $price, bool $isNhisCovered = false): float
    {
        $provider = $insurance->insuranceProvider;

        // Cash & Carry → no insurance coverage
        if ($provider->is_default) {
            return 0;
        }

        // Invalid insurance → no coverage
        if (!$insurance->is_valid) {
            return 0;
        }

        // For NHIS-type, only cover if service is NHIS-covered
        if ($provider->type === \App\Enums\InsuranceType::NHIS && !$isNhisCovered) {
            return 0;
        }

        $coverageRate = ($provider->coverage_percentage ?? 100) / 100;
        $coveredAmount = round($price * $coverageRate, 2);

        // Check per-visit limit
        if ($provider->per_visit_limit !== null) {
            $coveredAmount = min($coveredAmount, $provider->per_visit_limit);
        }

        return $coveredAmount;
    }

    /**
     * Get the insurance usage summary for display.
     */
    public function getUsageSummary(PatientInsurance $insurance): array
    {
        $provider = $insurance->insuranceProvider;

        $yearStart = now()->startOfYear();
        $totalBilled = \App\Models\Invoice::where('patient_id', $insurance->patient_id)
            ->whereHas('visit', fn ($q) => $q->where('visit_insurance_id', $insurance->id))
            ->where('created_at', '>=', $yearStart)
            ->sum('total_amount');

        return [
            'annual_limit' => $provider->annual_limit,
            'per_visit_limit' => $provider->per_visit_limit,
            'total_billed' => round($totalBilled, 2),
            'remaining' => $insurance->remaining_annual_limit,
            'coverage_percentage' => $provider->coverage_percentage,
        ];
    }
}
