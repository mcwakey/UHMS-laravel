<?php

namespace App\Services\Billing;

use App\Enums\VisitFinancialClearanceEvent;
use App\Enums\VisitFinancialClearanceMode;
use App\Enums\VisitFinancialClearanceStatus;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitFinancialClearance;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VisitFinancialClearanceService
{
    public function __construct(
        private readonly VisitFinancialSummaryService $summaries,
        private readonly VisitFinancialClearanceDecisionService $decisions,
        private readonly VisitFinancialClearanceConfigurationService $configuration,
    ) {}

    public function assess(Visit $visit, ?User $actor = null, bool $logActivity = false): VisitFinancialClearance
    {
        return DB::transaction(function () use ($visit, $actor, $logActivity) {
            $clearance = VisitFinancialClearance::where('visit_id', $visit->id)->lockForUpdate()->first();
            $decision = $this->decisions->decide($visit, $this->summaries->summarize($visit));
            $old = $clearance ? $this->snapshot($clearance) : [];
            $policy = $visit->paymentPolicy()->first();
            $values = [
                'status' => $decision->status, 'basis' => $decision->basis,
                'operational_policy_snapshot' => $policy?->approved_policy_snapshot?->value ?? $policy?->resolved_policy?->value,
                'policy_source_snapshot' => $policy?->resolution_source?->value,
                'approved_arrangement_id_snapshot' => $policy?->current_approved_arrangement_id,
                'patient_responsibility_snapshot' => $decision->summary->patientResponsibility,
                'patient_paid_snapshot' => $decision->summary->patientPaid,
                'patient_outstanding_snapshot' => $decision->summary->patientOutstanding,
                'insurance_responsibility_snapshot' => $decision->summary->insuranceResponsibility,
                'sponsor_responsibility_snapshot' => $decision->summary->sponsorResponsibility,
                'corporate_responsibility_snapshot' => $decision->summary->corporateResponsibility,
                'invoice_count_snapshot' => $decision->summary->invoiceCount,
                'invoice_item_count_snapshot' => $decision->summary->invoiceItemCount,
                'receivable_count_snapshot' => $decision->summary->receivableCount,
                'current_exception_id' => $decision->exceptionId,
                'requires_finance_action' => $decision->requiresFinanceAction,
                'assessed_at' => now(), 'last_refreshed_at' => now(), 'last_refreshed_by' => $actor?->id,
                'stale_at' => null,
            ];
            $clearance ??= new VisitFinancialClearance(['visit_id' => $visit->id, 'created_by' => $actor?->id]);
            $clearance->fill($values);
            if ($decision->status === VisitFinancialClearanceStatus::CLEARED) $clearance->cleared_at = now();
            if ($decision->status === VisitFinancialClearanceStatus::CONDITIONALLY_CLEARED) $clearance->conditionally_cleared_at = now();
            $clearance->assessment_version = max(1, (int) $clearance->assessment_version + ($clearance->exists ? 1 : 0));
            $clearance->save();
            $new = $this->snapshot($clearance);
            if ($old !== $new) $this->history($clearance, $old ? VisitFinancialClearanceEvent::REFRESHED : VisitFinancialClearanceEvent::ASSESSED, $old, $new, $decision->reasonCode, $actor);
            if ($logActivity) app(\App\Services\ActivityLogService::class)->log('billing', 'VISIT_FINANCIAL_CLEARANCE_ASSESSED', ['visit_id' => $visit->id, 'metadata' => ['status' => $decision->status->value, 'basis' => $decision->basis?->value]], $clearance);
            return $clearance->fresh(['currentException']);
        });
    }

    public function refresh(Visit $visit, ?User $actor = null, ?string $reasonCode = null): VisitFinancialClearance { return $this->assess($visit, $actor); }

    public function financiallyClose(Visit $visit, User $actor, string $reason): VisitFinancialClearance
    {
        if ($this->configuration->effectiveMode() !== VisitFinancialClearanceMode::ACTIVE) throw ValidationException::withMessages(['mode' => __('visit_financial_clearance.errors.active_required')]);
        if (! $actor->can('visits.financial_clearance.close')) throw new AuthorizationException;
        if (config('visit_financial_clearance.financial_close.require_reason') && trim($reason) === '') throw ValidationException::withMessages(['reason' => __('validation.required', ['attribute' => 'reason'])]);

        return DB::transaction(function () use ($visit, $actor, $reason) {
            $clearance = $this->assess($visit, $actor);
            if (! in_array($clearance->status, [VisitFinancialClearanceStatus::CLEARED, VisitFinancialClearanceStatus::CONDITIONALLY_CLEARED], true)) {
                throw ValidationException::withMessages(['clearance' => __('visit_financial_clearance.errors.not_clear')]);
            }
            $old = $this->snapshot($clearance);
            $clearance->forceFill(['status' => VisitFinancialClearanceStatus::FINANCIALLY_CLOSED, 'financially_closed_at' => now(), 'financially_closed_by' => $actor->id, 'requires_finance_action' => false])->save();
            $this->history($clearance, VisitFinancialClearanceEvent::FINANCIALLY_CLOSED, $old, $this->snapshot($clearance), 'manual_financial_close', $actor);
            app(\App\Services\ActivityLogService::class)->log('billing', 'VISIT_FINANCIALLY_CLOSED', ['visit_id' => $visit->id, 'metadata' => ['basis' => $clearance->basis?->value, 'reason' => mb_substr($reason, 0, 500)]], $clearance);
            return $clearance->fresh();
        });
    }

    public function markStale(Visit $visit, string $reasonCode, ?User $actor = null): ?VisitFinancialClearance
    {
        return DB::transaction(function () use ($visit, $reasonCode, $actor) {
            $clearance = VisitFinancialClearance::where('visit_id', $visit->id)->lockForUpdate()->first();
            if (! $clearance || $clearance->status === VisitFinancialClearanceStatus::STALE) return $clearance;
            $old = $this->snapshot($clearance);
            $clearance->forceFill(['status' => VisitFinancialClearanceStatus::STALE, 'requires_finance_action' => true, 'stale_at' => now()])->save();
            $this->history($clearance, VisitFinancialClearanceEvent::MARKED_STALE, $old, $this->snapshot($clearance), $reasonCode, $actor);
            return $clearance;
        });
    }

    public function reopenFinancialClearance(Visit $visit, string $reasonCode, ?User $actor = null): VisitFinancialClearance
    {
        $clearance = $this->markStale($visit, $reasonCode, $actor) ?? $this->assess($visit, $actor);
        $clearance->forceFill(['reopened_at' => now()])->save();
        return $clearance;
    }

    public function snapshotIsStale(VisitFinancialClearance $clearance): bool
    {
        $decision = $this->decisions->decide($clearance->visit);
        $summary = $decision->summary;

        return (string) $clearance->patient_responsibility_snapshot !== $summary->patientResponsibility
            || (string) $clearance->patient_paid_snapshot !== $summary->patientPaid
            || (string) $clearance->patient_outstanding_snapshot !== $summary->patientOutstanding
            || (int) $clearance->invoice_count_snapshot !== $summary->invoiceCount
            || (int) $clearance->invoice_item_count_snapshot !== $summary->invoiceItemCount
            || (int) $clearance->receivable_count_snapshot !== $summary->receivableCount
            || $clearance->current_exception_id !== $decision->exceptionId;
    }

    private function snapshot(VisitFinancialClearance $c): array
    {
        return ['status' => $c->status?->value, 'basis' => $c->basis?->value, 'patient_responsibility' => (string) $c->patient_responsibility_snapshot, 'patient_paid' => (string) $c->patient_paid_snapshot, 'patient_outstanding' => (string) $c->patient_outstanding_snapshot, 'current_exception_id' => $c->current_exception_id, 'requires_finance_action' => (bool) $c->requires_finance_action];
    }

    private function history(VisitFinancialClearance $c, VisitFinancialClearanceEvent $event, array $old, array $new, ?string $reason, ?User $actor): void
    {
        $c->history()->create(['visit_id' => $c->visit_id, 'event_type' => $event, 'old_values' => $old ?: null, 'new_values' => $new, 'reason_code' => $reason, 'performed_by' => $actor?->id, 'performed_at' => now()]);
    }
}
