<?php

namespace App\Services;

use App\Models\InsuranceProvider;
use App\Models\InsuranceUsage;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Models\Visit;

class InsuranceService
{
    // ──────────────────────────────────────────────────────────────────────────
    //  CORE ENGINE
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Evaluate how much insurance can cover for one billing line item.
     *
     * Four hard constraints — whichever is hit first wins:
     *   1. max_per_visit       — cumulative insurance spend on this visit
     *   2. max_per_month       — cumulative insurance spend this calendar month
     *   3. annual_limit        — cumulative insurance spend this calendar year
     *   4. max_visits_per_month — number of distinct covered visits this month
     *
     * coverage_percentage is applied BEFORE limit caps, so the limit caps refer
     * to actual insurance-paid amounts (not service prices).
     *
     * @param float $sessionOffset  Extra uncommitted spend from earlier items in the
     *                              same billing session (not yet in insurance_usages).
     *                              Pass this when evaluating multiple items in one pass
     *                              without recording between them.
     * @return array{
     *   can_use: bool,
     *   covered_amount: float,
     *   patient_amount: float,
     *   reason: string|null,
     *   remaining_limits: array
     * }
     */
    public function evaluateCoverage(
        PatientInsurance $insurance,
        Visit $visit,
        float $incomingAmount,
        float $sessionOffset = 0.0
    ): array {
        $provider = $insurance->insuranceProvider;

        // ── Guard: Cash & Carry is always full patient payment ────────────────
        if ($provider->is_default) {
            return $this->cashResult($incomingAmount, null);
        }

        // ── Guard: insurance must be valid (active + not expired) ─────────────
        if (! $insurance->is_valid) {
            return $this->cashResult($incomingAmount, 'Insurance is expired or inactive');
        }

        // ── Collect current usage from insurance_usages ───────────────────────
        $monthStart = now()->startOfMonth();
        $yearStart  = now()->startOfYear();

        $usedThisVisit  = (float) InsuranceUsage::where('patient_insurance_id', $insurance->id)
            ->where('visit_id', $visit->id)
            ->sum('amount_covered') + $sessionOffset;

        $usedThisMonth  = (float) InsuranceUsage::where('patient_insurance_id', $insurance->id)
            ->where('created_at', '>=', $monthStart)
            ->sum('amount_covered') + $sessionOffset;

        $usedThisYear   = (float) InsuranceUsage::where('patient_insurance_id', $insurance->id)
            ->where('created_at', '>=', $yearStart)
            ->sum('amount_covered') + $sessionOffset;

        // Distinct visits covered this month (visits that already have usage)
        $visitIdsThisMonth  = InsuranceUsage::where('patient_insurance_id', $insurance->id)
            ->where('created_at', '>=', $monthStart)
            ->distinct()
            ->pluck('visit_id');

        $visitsThisMonth    = $visitIdsThisMonth->count();
        $visitAlreadyCounted = $visitIdsThisMonth->contains($visit->id);

        // ── Constraint 4: max visits per month ────────────────────────────────
        $maxVisitsPerMonth = $provider->max_visits_per_month;
        if ($maxVisitsPerMonth !== null && ! $visitAlreadyCounted) {
            if ($visitsThisMonth >= $maxVisitsPerMonth) {
                return $this->cashResult(
                    $incomingAmount,
                    "Monthly visit limit reached ({$visitsThisMonth}/{$maxVisitsPerMonth} visits used)"
                );
            }
        }

        // ── Compute remaining budget under each limit (null = no limit) ───────
        $INF = PHP_FLOAT_MAX;

        $remainingPerVisit = $provider->per_visit_limit !== null
            ? max(0.0, (float) $provider->per_visit_limit - $usedThisVisit)
            : $INF;

        $remainingMonthly  = $provider->max_per_month !== null
            ? max(0.0, (float) $provider->max_per_month - $usedThisMonth)
            : $INF;

        $remainingAnnual   = $provider->annual_limit !== null
            ? max(0.0, (float) $provider->annual_limit - $usedThisYear)
            : $INF;

        // Tightest remaining capacity across all active limits
        $capacityByLimits = min($remainingPerVisit, $remainingMonthly, $remainingAnnual);

        if ($capacityByLimits <= 0.0) {
            $exhaustedLimit = $this->identifyExhaustedLimit(
                $remainingPerVisit, $remainingMonthly, $remainingAnnual,
                $provider->per_visit_limit, $provider->max_per_month, $provider->annual_limit
            );
            return $this->cashResult($incomingAmount, "Insurance limit exhausted ({$exhaustedLimit})");
        }

        // ── Apply coverage percentage ──────────────────────────────────────────
        $coverageRate      = ((float) ($provider->coverage_percentage ?? 100)) / 100;
        $requestedCoverage = round($incomingAmount * $coverageRate, 2);

        // Final coverable = what coverage% wants, capped by remaining limits
        $finalCovered = min($requestedCoverage, $capacityByLimits);
        $finalCovered = round(max(0.0, $finalCovered), 2);
        $patientPays  = round($incomingAmount - $finalCovered, 2);

        // ── Build remaining limits (after this line item) ─────────────────────
        $remaining = [
            'per_visit'          => $remainingPerVisit  === $INF ? null : round($remainingPerVisit  - $finalCovered, 2),
            'monthly'            => $remainingMonthly   === $INF ? null : round($remainingMonthly   - $finalCovered, 2),
            'annual'             => $remainingAnnual    === $INF ? null : round($remainingAnnual    - $finalCovered, 2),
            'visits_this_month'  => $visitAlreadyCounted ? $visitsThisMonth : $visitsThisMonth + 1,
            'max_visits_per_month' => $maxVisitsPerMonth,
        ];

        return [
            'can_use'          => true,
            'covered_amount'   => $finalCovered,
            'patient_amount'   => $patientPays,
            'reason'           => $finalCovered < $requestedCoverage ? 'Partial: limit cap applied' : null,
            'remaining_limits' => $remaining,
        ];
    }

    /**
     * Record a coverage decision into insurance_usages.
     * Call this immediately after evaluateCoverage() confirms coverage.
     */
    public function recordUsage(
        PatientInsurance $insurance,
        Visit $visit,
        float $coveredAmount,
        float $patientAmount,
        ?string $reason = null,
        ?int $invoiceId = null
    ): InsuranceUsage {
        return InsuranceUsage::create([
            'patient_insurance_id' => $insurance->id,
            'visit_id'             => $visit->id,
            'invoice_id'           => $invoiceId,
            'amount_covered'       => $coveredAmount,
            'patient_amount'       => $patientAmount,
            'reason'               => $reason,
        ]);
    }

    /**
     * Attach invoice_id to all usage records for a visit (called on invoice commit).
     */
    public function linkInvoiceToUsages(int $visitId, int $invoiceId, int $patientInsuranceId): void
    {
        InsuranceUsage::where('visit_id', $visitId)
            ->where('patient_insurance_id', $patientInsuranceId)
            ->whereNull('invoice_id')
            ->update(['invoice_id' => $invoiceId]);
    }

    /**
     * Delete all usage records for a visit (call when visit is cancelled).
     */
    public function voidVisitUsages(int $visitId): void
    {
        InsuranceUsage::where('visit_id', $visitId)->delete();
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  VISIT RESOLUTION (unchanged API)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Resolve the insurance to use for a visit.
     *
     * 1. If a specific insurance_id is selected, validate and use it
     * 2. Otherwise load patient's primary insurance
     * 3. If NOT valid → fall back to Cash & Carry
     *
     * @return array{insurance: ?PatientInsurance, provider: InsuranceProvider, is_fallback: bool}
     */
    public function resolveForVisit(Patient $patient, ?int $selectedInsuranceId = null): array
    {
        if ($selectedInsuranceId) {
            $selected = PatientInsurance::with('insuranceProvider')
                ->where('id', $selectedInsuranceId)
                ->where('patient_id', $patient->id)
                ->first();

            if ($selected && $selected->is_valid) {
                return [
                    'insurance'   => $selected,
                    'provider'    => $selected->insuranceProvider,
                    'is_fallback' => false,
                ];
            }
        }

        $primary = $patient->primaryInsurance;
        if ($primary) {
            $primary->load('insuranceProvider');
            if ($primary->is_valid) {
                return [
                    'insurance'   => $primary,
                    'provider'    => $primary->insuranceProvider,
                    'is_fallback' => false,
                ];
            }
        }

        return $this->getCashAndCarryResult($patient);
    }

    /**
     * Get the Cash & Carry fallback result.
     */
    public function getCashAndCarryResult(Patient $patient): array
    {
        $cashProvider = InsuranceProvider::where('is_default', true)->first();

        $cashInsurance = null;
        if ($cashProvider) {
            $cashInsurance = PatientInsurance::firstOrCreate(
                ['patient_id' => $patient->id, 'insurance_provider_id' => $cashProvider->id],
                ['is_primary' => false, 'is_active' => true]
            );
        }

        return [
            'insurance'   => $cashInsurance,
            'provider'    => $cashProvider,
            'is_fallback' => true,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  DISPLAY HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Get all insurances for a patient with validation info.
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
                'id'                    => $ins->id,
                'provider_id'           => $provider->id,
                'provider_name'         => $provider->name,
                'type'                  => $provider->type instanceof \BackedEnum ? $provider->type->value : $provider->type,
                'type_label'            => $provider->type instanceof \BackedEnum ? $provider->type->label() : ucfirst($provider->type),
                'type_color'            => $provider->type instanceof \BackedEnum ? $provider->type->color() : 'secondary',
                'membership_number'     => $ins->membership_number,
                'policy_number'         => $ins->policy_number,
                'start_date'            => $ins->start_date?->format('Y-m-d'),
                'expiry_date'           => $ins->expiry_date?->format('Y-m-d'),
                'is_primary'            => $ins->is_primary,
                'is_active'             => $ins->is_active,
                'is_expired'            => $ins->is_expired,
                'is_valid'              => $ins->is_valid,
                'is_default'            => $provider->is_default,
                'coverage_percentage'   => $provider->coverage_percentage,
                'annual_limit'          => $provider->annual_limit,
                'per_visit_limit'       => $provider->per_visit_limit,
                'max_per_month'         => $provider->max_per_month,
                'max_visits_per_month'  => $provider->max_visits_per_month,
                'remaining_annual_limit'  => $ins->remaining_annual_limit,
                'remaining_monthly_limit' => $ins->remaining_monthly_limit,
            ];
        })->values()->toArray();
    }

    /**
     * Get the insurance usage summary for display.
     */
    public function getUsageSummary(PatientInsurance $insurance): array
    {
        $provider = $insurance->insuranceProvider;

        return [
            'annual_limit'          => $provider->annual_limit,
            'per_visit_limit'       => $provider->per_visit_limit,
            'max_per_month'         => $provider->max_per_month,
            'max_visits_per_month'  => $provider->max_visits_per_month,
            'coverage_percentage'   => $provider->coverage_percentage,
            'used_this_year'        => $insurance->usedThisYear(),
            'used_this_month'       => $insurance->usedThisMonth(),
            'remaining_annual'      => $insurance->remaining_annual_limit,
            'remaining_monthly'     => $insurance->remaining_monthly_limit,
            'visits_this_month'     => $insurance->visitsThisMonth(),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    private function cashResult(float $amount, ?string $reason): array
    {
        return [
            'can_use'          => false,
            'covered_amount'   => 0.0,
            'patient_amount'   => $amount,
            'reason'           => $reason,
            'remaining_limits' => [],
        ];
    }

    private function identifyExhaustedLimit(
        float $remainingPerVisit,
        float $remainingMonthly,
        float $remainingAnnual,
        $perVisitCap,
        $monthlyCap,
        $annualCap
    ): string {
        if ($perVisitCap !== null && $remainingPerVisit <= 0) return 'per-visit';
        if ($monthlyCap  !== null && $remainingMonthly  <= 0) return 'monthly';
        if ($annualCap   !== null && $remainingAnnual   <= 0) return 'annual';
        return 'unknown';
    }
}
