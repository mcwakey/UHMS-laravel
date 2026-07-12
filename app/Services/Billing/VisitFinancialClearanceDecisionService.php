<?php

namespace App\Services\Billing;

use App\Data\Billing\VisitFinancialClearanceDecision;
use App\Data\Billing\VisitFinancialSummary;
use App\Enums\VisitFinancialClearanceBasis;
use App\Enums\VisitFinancialClearanceStatus;
use App\Models\Visit;
use App\Models\VisitFinancialClearanceException;

class VisitFinancialClearanceDecisionService
{
    public function __construct(private readonly VisitFinancialSummaryService $summaries) {}

    public function decide(Visit $visit, ?VisitFinancialSummary $summary = null): VisitFinancialClearanceDecision
    {
        $summary ??= $this->summaries->summarize($visit);
        if ($summary->hasUnbilledBillableItems) return $this->pending($summary, 'unbilled_billable_items');
        if ($summary->hasPendingFinancialAdjustments) return $this->pending($summary, 'pending_financial_adjustments');

        $tolerance = (float) config('visit_financial_clearance.settlement.currency_tolerance', '0.01');
        if ((float) $summary->patientResponsibility <= $tolerance) {
            $basis = match (true) {
                (float) $summary->insuranceResponsibility > $tolerance => VisitFinancialClearanceBasis::FULLY_INSURED,
                (float) $summary->sponsorResponsibility > $tolerance => VisitFinancialClearanceBasis::FULLY_SPONSORED,
                default => VisitFinancialClearanceBasis::ZERO_PATIENT_RESPONSIBILITY,
            };
            return new VisitFinancialClearanceDecision(VisitFinancialClearanceStatus::CLEARED, $basis, true, false, 'zero_patient_responsibility', $summary);
        }
        if ((float) $summary->patientOutstanding <= $tolerance) {
            return new VisitFinancialClearanceDecision(VisitFinancialClearanceStatus::CLEARED, VisitFinancialClearanceBasis::FULLY_SETTLED, true, false, 'patient_responsibility_settled', $summary);
        }

        $exception = $visit->financialClearanceExceptions()->current()->latest('approved_at')->first();
        if ($exception && (float) $exception->approved_amount + $tolerance >= (float) $summary->patientOutstanding) {
            return new VisitFinancialClearanceDecision(VisitFinancialClearanceStatus::CONDITIONALLY_CLEARED, $exception->type->basis(), true, false, 'approved_financial_clearance_exception', $summary, $exception->id);
        }
        return $this->pending($summary, 'patient_balance_outstanding');
    }

    private function pending(VisitFinancialSummary $summary, string $reason): VisitFinancialClearanceDecision
    {
        return new VisitFinancialClearanceDecision(VisitFinancialClearanceStatus::PENDING, null, false, true, $reason, $summary);
    }
}
