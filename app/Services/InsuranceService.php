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
     * Six hard constraints — whichever is hit first wins:
     *   1. max_per_visit            — cumulative insurance spend on this visit
     *   2. max_per_month            — cumulative insurance spend this calendar month
     *   3. annual_limit             — cumulative insurance spend this calendar year
     *   4. max_visits_per_month     — number of distinct covered visits this month
     *   5. min_visit_interval_days  — minimum days required since the last covered visit
     *   6. member_type overrides    — holder vs beneficiary specific limits
     *
     * All constraints are resolved from the assigned InsuranceTier and member_type;
     * not from the InsuranceProvider directly.
     *
     * @param  float $sessionOffset  Extra uncommitted spend from earlier items in the
     *                               same billing pass (not yet in insurance_usages).
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

        // ── Guard: Cash & Carry → always full patient payment ─────────────────
        if ($provider->is_default) {
            return $this->cashResult($incomingAmount, null);
        }

        // ── Guard: insurance must be valid (active + not expired) ─────────────
        if (! $insurance->is_valid) {
            return $this->cashResult($incomingAmount, 'Insurance is expired or inactive');
        }

        // ── Resolve tier & effective constraints for this member type ─────────
        $tier = $insurance->insuranceTier;
        if (! $tier) {
            return $this->cashResult($incomingAmount, 'No insurance tier assigned');
        }

        $memberType  = $insurance->member_type?->value ?? 'holder';
        $constraints = $tier->effectiveConstraints($memberType);

        // ── Constraint 5: minimum visit interval ──────────────────────────────
        // Only checked on the first item of this visit (before any usages exist for it).
        if ($constraints['min_visit_interval_days'] !== null && $sessionOffset === 0.0) {
            $hasUsagesForThisVisit = InsuranceUsage::where('patient_insurance_id', $insurance->id)
                ->where('visit_id', $visit->id)
                ->exists();

            if (! $hasUsagesForThisVisit) {
                $lastUsage = InsuranceUsage::where('patient_insurance_id', $insurance->id)
                    ->where('visit_id', '!=', $visit->id)
                    ->latest('created_at')
                    ->first();

                if ($lastUsage) {
                    $daysSinceLast = (int) $lastUsage->created_at->diffInDays(now(), true);
                    $minDays       = $constraints['min_visit_interval_days'];

                    if ($daysSinceLast < $minDays) {
                        return $this->cashResult(
                            $incomingAmount,
                            "Visit interval too short ({$daysSinceLast}d < {$minDays}d minimum between covered visits)"
                        );
                    }
                }
            }
        }

        // ── Collect current usage from insurance_usages ───────────────────────
        $monthStart = now()->startOfMonth();
        $yearStart  = now()->startOfYear();

        $usedThisVisit = (float) InsuranceUsage::where('patient_insurance_id', $insurance->id)
            ->where('visit_id', $visit->id)
            ->sum('amount_covered') + $sessionOffset;

        $usedThisMonth = (float) InsuranceUsage::where('patient_insurance_id', $insurance->id)
            ->where('created_at', '>=', $monthStart)
            ->sum('amount_covered') + $sessionOffset;

        $usedThisYear  = (float) InsuranceUsage::where('patient_insurance_id', $insurance->id)
            ->where('created_at', '>=', $yearStart)
            ->sum('amount_covered') + $sessionOffset;

        // Distinct visits covered this month
        $visitIdsThisMonth   = InsuranceUsage::where('patient_insurance_id', $insurance->id)
            ->where('created_at', '>=', $monthStart)
            ->distinct()
            ->pluck('visit_id');

        $visitsThisMonth     = $visitIdsThisMonth->count();
        $visitAlreadyCounted = $visitIdsThisMonth->contains($visit->id);

        // ── Constraint 4: max visits per month ────────────────────────────────
        $maxVisitsPerMonth = $constraints['max_visits_per_month'];
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

        $remainingPerVisit = $constraints['per_visit_limit'] !== null
            ? max(0.0, (float) $constraints['per_visit_limit'] - $usedThisVisit)
            : $INF;

        $remainingMonthly  = $constraints['max_per_month'] !== null
            ? max(0.0, (float) $constraints['max_per_month'] - $usedThisMonth)
            : $INF;

        $remainingAnnual   = $constraints['annual_limit'] !== null
            ? max(0.0, (float) $constraints['annual_limit'] - $usedThisYear)
            : $INF;

        $capacityByLimits = min($remainingPerVisit, $remainingMonthly, $remainingAnnual);

        if ($capacityByLimits <= 0.0) {
            $exhaustedLimit = $this->identifyExhaustedLimit(
                $remainingPerVisit, $remainingMonthly, $remainingAnnual,
                $constraints['per_visit_limit'], $constraints['max_per_month'], $constraints['annual_limit']
            );
            return $this->cashResult($incomingAmount, "Insurance limit exhausted ({$exhaustedLimit})");
        }

        // ── Coverage of payer-specific price ───────────────────────────────────
        // IMPORTANT: $incomingAmount is already the payer-specific (insurance) price
        // resolved via ServicePriceResolver / ServiceCatalog::getPriceForInsurance().
        // The tier coverage percentage applies to that selected amount, subject to
        // the hard caps above (per-visit / monthly / annual). Cash/base price is
        // not used to calculate the covered amount.
        $coveragePercent = min(100.0, max(0.0, (float) ($constraints['coverage_percentage'] ?? 100)));
        $requestedCoverage = round($incomingAmount * ($coveragePercent / 100), 2);

        $finalCovered = min($requestedCoverage, $capacityByLimits, $incomingAmount);
        $finalCovered = round(max(0.0, $finalCovered), 2);
        $patientPays  = round($incomingAmount - $finalCovered, 2);

        // ── Build remaining limits after this line item ───────────────────────
        $remaining = [
            'per_visit'            => $remainingPerVisit  === $INF ? null : round($remainingPerVisit  - $finalCovered, 2),
            'monthly'              => $remainingMonthly   === $INF ? null : round($remainingMonthly   - $finalCovered, 2),
            'annual'               => $remainingAnnual    === $INF ? null : round($remainingAnnual    - $finalCovered, 2),
            'visits_this_month'    => $visitAlreadyCounted ? $visitsThisMonth : $visitsThisMonth + 1,
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
    //  VISIT RESOLUTION
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * @return array{insurance: ?PatientInsurance, provider: InsuranceProvider, is_fallback: bool}
     */
    public function resolveForVisit(Patient $patient, ?int $selectedInsuranceId = null): array
    {
        if ($selectedInsuranceId) {
            $selected = PatientInsurance::with(['insuranceProvider', 'insuranceTier'])
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
            $primary->load(['insuranceProvider', 'insuranceTier']);
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

    public function getCashAndCarryResult(Patient $patient): array
    {
        $cashProvider = InsuranceProvider::where('is_default', true)->first();

        $cashInsurance = null;
        if ($cashProvider) {
            $cashInsurance = PatientInsurance::firstOrCreate(
                ['patient_id' => $patient->id, 'insurance_provider_id' => $cashProvider->id],
                ['is_primary' => false, 'is_active' => true, 'member_type' => 'holder']
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

    public function getPatientInsurances(Patient $patient): array
    {
        $insurances = $patient->insurances()
            ->with(['insuranceProvider', 'insuranceTier'])
            ->orderByDesc('is_primary')
            ->get();

        $hasCashAndCarry = false;

        $rows = $insurances->map(function (PatientInsurance $ins) use (&$hasCashAndCarry) {
            $provider    = $ins->insuranceProvider;
            $tier        = $ins->insuranceTier;
            $memberType  = $ins->member_type?->value ?? 'holder';
            $constraints = $tier ? $tier->effectiveConstraints($memberType) : null;

            if ($provider && $provider->is_default) {
                $hasCashAndCarry = true;
            }

            return [
                'id'                       => $ins->id,
                'provider_id'              => $provider->id,
                'provider_name'            => $provider->name,
                'type'                     => $provider->type?->value ?? $provider->type,
                'type_label'               => $provider->type?->label() ?? ucfirst((string) $provider->type),
                'type_color'               => $provider->type?->color() ?? 'secondary',
                'tier_id'                  => $tier?->id,
                'tier_name'                => $tier?->name,
                'member_type'              => $memberType,
                'member_type_label'        => $ins->member_type?->label() ?? 'Card Holder',
                'card_holder_insurance_id' => $ins->card_holder_insurance_id,
                'membership_number'        => $ins->membership_number,
                'policy_number'            => $ins->policy_number,
                'start_date'               => $ins->start_date?->format('Y-m-d'),
                'expiry_date'              => $ins->expiry_date?->format('Y-m-d'),
                'is_primary'               => $ins->is_primary,
                'is_active'                => $ins->is_active,
                'is_expired'               => $ins->is_expired,
                'is_valid'                 => $ins->is_valid,
                'is_default'               => $provider->is_default,
                'coverage_percentage'      => $constraints['coverage_percentage'] ?? null,
                'annual_limit'             => $constraints['annual_limit'] ?? null,
                'per_visit_limit'          => $constraints['per_visit_limit'] ?? null,
                'max_per_month'            => $constraints['max_per_month'] ?? null,
                'max_visits_per_month'     => $constraints['max_visits_per_month'] ?? null,
                'min_visit_interval_days'  => $constraints['min_visit_interval_days'] ?? null,
                'remaining_annual_limit'   => $ins->remaining_annual_limit,
                'remaining_monthly_limit'  => $ins->remaining_monthly_limit,
            ];
        })->values()->toArray();

        // ── Always offer Cash & Carry as a payment option ───────────────────
        // If the patient has no record against the default provider, we
        // ensure the option still appears so the desk can always pick it.
        if (! $hasCashAndCarry) {
            $cashProvider = InsuranceProvider::where('is_default', true)->first();
            if ($cashProvider) {
                $cashInsurance = PatientInsurance::firstOrCreate(
                    [
                        'patient_id'            => $patient->id,
                        'insurance_provider_id' => $cashProvider->id,
                    ],
                    [
                        'is_primary'  => false,
                        'is_active'   => true,
                        'member_type' => 'holder',
                    ]
                );

                $rows[] = [
                    'id'                       => $cashInsurance->id,
                    'provider_id'              => $cashProvider->id,
                    'provider_name'            => $cashProvider->name,
                    'type'                     => $cashProvider->type?->value ?? $cashProvider->type,
                    'type_label'               => $cashProvider->type?->label() ?? __('visits.cash_and_carry'),
                    'type_color'               => $cashProvider->type?->color() ?? 'secondary',
                    'tier_id'                  => null,
                    'tier_name'                => null,
                    'member_type'              => 'holder',
                    'member_type_label'        => 'Card Holder',
                    'card_holder_insurance_id' => null,
                    'membership_number'        => null,
                    'policy_number'            => null,
                    'start_date'               => null,
                    'expiry_date'              => null,
                    'is_primary'               => false,
                    'is_active'                => true,
                    'is_expired'               => false,
                    'is_valid'                 => true,
                    'is_default'               => true,
                    'coverage_percentage'      => 0,
                    'annual_limit'             => null,
                    'per_visit_limit'          => null,
                    'max_per_month'            => null,
                    'max_visits_per_month'     => null,
                    'min_visit_interval_days'  => null,
                    'remaining_annual_limit'   => null,
                    'remaining_monthly_limit'  => null,
                ];
            }
        }

        return $rows;
    }

    public function getUsageSummary(PatientInsurance $insurance): array
    {
        $tier        = $insurance->insuranceTier;
        $memberType  = $insurance->member_type?->value ?? 'holder';
        $constraints = $tier ? $tier->effectiveConstraints($memberType) : [];

        return [
            'tier_name'               => $tier?->name,
            'member_type'             => $memberType,
            'coverage_percentage'     => $constraints['coverage_percentage'] ?? null,
            'annual_limit'            => $constraints['annual_limit'] ?? null,
            'per_visit_limit'         => $constraints['per_visit_limit'] ?? null,
            'max_per_month'           => $constraints['max_per_month'] ?? null,
            'max_visits_per_month'    => $constraints['max_visits_per_month'] ?? null,
            'min_visit_interval_days' => $constraints['min_visit_interval_days'] ?? null,
            'used_this_year'          => $insurance->usedThisYear(),
            'used_this_month'         => $insurance->usedThisMonth(),
            'remaining_annual'        => $insurance->remaining_annual_limit,
            'remaining_monthly'       => $insurance->remaining_monthly_limit,
            'visits_this_month'       => $insurance->visitsThisMonth(),
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
