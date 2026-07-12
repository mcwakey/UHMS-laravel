<?php

namespace App\Services\Billing;

use App\Enums\VisitFinancialClearanceExceptionStatus as Status;
use App\Enums\VisitFinancialClearanceExceptionType as Type;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitFinancialClearanceException as ExceptionRecord;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VisitFinancialClearanceExceptionService
{
    public function __construct(private VisitFinancialSummaryService $summaries, private VisitFinancialClearanceService $clearances, private VisitFinancialClearanceApprovalPolicyService $approvalPolicy) {}

    public function request(Visit $visit, Type $type, string $amount, string $reason, User $actor, ?string $reference = null, ?string $expiresAt = null): ExceptionRecord
    {
        if (! $actor->can('visits.financial_clearance_exception.request')) throw new AuthorizationException;
        return DB::transaction(function () use ($visit, $type, $amount, $reason, $actor, $reference, $expiresAt) {
            $visit->financialClearanceExceptions()->lockForUpdate()->get();
            if ($visit->financialClearanceExceptions()->pending()->exists()) throw ValidationException::withMessages(['exception' => __('visit_financial_clearance.errors.pending_exists')]);
            $summary = $this->summaries->summarize($visit);
            $requested = round((float) $amount, 2);
            if ($requested <= 0 || $requested > (float) $summary->patientOutstanding + .01) throw ValidationException::withMessages(['requested_amount' => __('visit_financial_clearance.errors.amount_exceeds_outstanding')]);
            if (trim($reason) === '') throw ValidationException::withMessages(['request_reason' => __('validation.required', ['attribute' => 'reason'])]);
            $requirement = $this->approvalPolicy->requirement($type);
            if ($requirement->requiresSupportingReference && blank($reference)) throw ValidationException::withMessages(['supporting_reference' => __('validation.required', ['attribute' => 'supporting reference'])]);
            $policy = $visit->paymentPolicy()->first();
            $record = $visit->financialClearanceExceptions()->create([
                'visit_financial_clearance_id' => $visit->financialClearance?->id, 'type' => $type, 'status' => Status::PENDING,
                'requested_amount' => number_format($requested, 2, '.', ''), 'reason_code' => 'manual_request',
                'request_reason' => mb_substr($reason, 0, 1000), 'supporting_reference' => $reference ? mb_substr($reference, 0, 191) : null,
                'requested_by' => $actor->id, 'requested_at' => now(), 'expires_at' => $expiresAt,
                'financial_summary_snapshot' => $summary->snapshot(), 'payment_policy_snapshot' => $policy?->resolved_policy?->value,
                'arrangement_id_snapshot' => $policy?->current_approved_arrangement_id,
                'risk_level_snapshot' => $policy?->patient_risk_level_snapshot?->value,
            ]);
            $this->history($record, 'requested', [], $this->snapshot($record), $actor);
            $this->log($record, 'VISIT_FINANCIAL_CLEARANCE_EXCEPTION_REQUESTED', $actor);
            return $record;
        });
    }

    public function approve(ExceptionRecord $record, string $amount, string $reason, User $actor): ExceptionRecord
    {
        if (! $actor->can('visits.financial_clearance_exception.approve')) throw new AuthorizationException;
        $record = DB::transaction(function () use ($record, $amount, $reason, $actor) {
            $record = ExceptionRecord::whereKey($record->id)->lockForUpdate()->firstOrFail();
            if ($record->status !== Status::PENDING) throw ValidationException::withMessages(['exception' => __('visit_financial_clearance.errors.not_pending')]);
            if ($this->approvalPolicy->requirement($record->type)->requiresSeparateApprover && $record->requested_by === $actor->id) throw ValidationException::withMessages(['exception' => __('visit_financial_clearance.errors.self_approval')]);
            $record->visit->financialClearanceExceptions()->approved()->lockForUpdate()->get();
            if ($record->visit->financialClearanceExceptions()->current()->exists()) throw ValidationException::withMessages(['exception' => __('visit_financial_clearance.errors.approved_exists')]);
            $outstanding = (float) $this->summaries->summarize($record->visit)->patientOutstanding;
            $approved = round((float) $amount, 2);
            if ($approved <= 0 || $approved > $outstanding + .01) throw ValidationException::withMessages(['approved_amount' => __('visit_financial_clearance.errors.amount_exceeds_outstanding')]);
            $old = $this->snapshot($record);
            $record->forceFill(['status' => Status::APPROVED, 'approved_amount' => number_format($approved, 2, '.', ''), 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'review_decision_reason' => mb_substr($reason, 0, 1000), 'approved_by' => $actor->id, 'approved_at' => now(), 'effective_from' => $record->effective_from ?? today()])->save();
            $this->history($record, 'approved', $old, $this->snapshot($record), $actor); $this->log($record, 'VISIT_FINANCIAL_CLEARANCE_EXCEPTION_APPROVED', $actor);
            return $record;
        });
        $this->clearances->refresh($record->visit, $actor, 'exception_approved');
        return $record->fresh();
    }

    public function reject(ExceptionRecord $record, string $reason, User $actor): ExceptionRecord { return $this->terminal($record, Status::REJECTED, 'rejected', $reason, $actor, 'visits.financial_clearance_exception.reject'); }
    public function withdraw(ExceptionRecord $record, string $reason, User $actor): ExceptionRecord { if ($record->requested_by !== $actor->id && ! $actor->can('visits.financial_clearance_exception.withdraw')) throw new AuthorizationException; return $this->terminal($record, Status::WITHDRAWN, 'withdrawn', $reason, $actor, null); }
    public function revoke(ExceptionRecord $record, string $reason, User $actor): ExceptionRecord
    {
        $record = $this->terminal($record, Status::REVOKED, 'revoked', $reason, $actor, 'visits.financial_clearance_exception.revoke', Status::APPROVED);
        $this->clearances->markStale($record->visit, 'exception_revoked', $actor);
        return $record;
    }

    public function expireDue(bool $commit = false): int
    {
        $due = ExceptionRecord::approved()->whereNotNull('expires_at')->whereDate('expires_at', '<', today())->get();
        if (! $commit) return $due->count();
        foreach ($due as $record) DB::transaction(function () use ($record) {
            $locked = ExceptionRecord::whereKey($record->id)->lockForUpdate()->first();
            if (! $locked || $locked->status !== Status::APPROVED) return;
            $old = $this->snapshot($locked); $locked->forceFill(['status' => Status::EXPIRED, 'reviewed_at' => now()])->save();
            $this->history($locked, 'expired', $old, $this->snapshot($locked), null); $this->log($locked, 'VISIT_FINANCIAL_CLEARANCE_EXCEPTION_EXPIRED', null);
            $this->clearances->markStale($locked->visit, 'exception_expired');
        });
        return $due->count();
    }

    private function terminal(ExceptionRecord $record, Status $status, string $event, string $reason, User $actor, ?string $permission, Status $required = Status::PENDING): ExceptionRecord
    {
        if ($permission && ! $actor->can($permission)) throw new AuthorizationException;
        return DB::transaction(function () use ($record, $status, $event, $reason, $actor, $required) {
            $record = ExceptionRecord::whereKey($record->id)->lockForUpdate()->firstOrFail();
            if ($record->status !== $required) throw ValidationException::withMessages(['exception' => __('visit_financial_clearance.errors.invalid_transition')]);
            $old = $this->snapshot($record);
            $transition = match ($status) {
                Status::WITHDRAWN => ['withdrawn_by' => $actor->id, 'withdrawn_at' => now(), 'withdrawal_reason' => mb_substr($reason, 0, 1000)],
                Status::REVOKED => ['revoked_by' => $actor->id, 'revoked_at' => now(), 'revocation_reason' => mb_substr($reason, 0, 1000)],
                default => ['reviewed_by' => $actor->id, 'reviewed_at' => now(), 'review_decision_reason' => mb_substr($reason, 0, 1000)],
            };
            $record->forceFill(['status' => $status] + $transition)->save();
            $this->history($record, $event, $old, $this->snapshot($record), $actor); $this->log($record, 'VISIT_FINANCIAL_CLEARANCE_EXCEPTION_'.strtoupper($event), $actor);
            return $record;
        });
    }

    private function snapshot(ExceptionRecord $r): array { return ['status' => $r->status?->value, 'type' => $r->type?->value, 'requested_amount' => (string) $r->requested_amount, 'approved_amount' => $r->approved_amount ? (string) $r->approved_amount : null]; }
    private function history(ExceptionRecord $r, string $event, array $old, array $new, ?User $actor): void { $r->history()->create(['visit_id' => $r->visit_id, 'event_type' => $event, 'old_values' => $old ?: null, 'new_values' => $new, 'performed_by' => $actor?->id, 'performed_at' => now()]); }
    private function log(ExceptionRecord $r, string $action, ?User $actor): void { app(\App\Services\ActivityLogService::class)->log('billing', $action, ['visit_id' => $r->visit_id, 'causer' => $actor, 'metadata' => ['exception_id' => $r->id, 'type' => $r->type?->value, 'status' => $r->status?->value]], $r); }
}
