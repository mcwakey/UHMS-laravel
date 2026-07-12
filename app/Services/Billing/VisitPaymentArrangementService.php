<?php

namespace App\Services\Billing;

use App\Data\Billing\VisitPaymentArrangementApprovalData;
use App\Data\Billing\VisitPaymentArrangementData;
use App\Enums\LogModule;
use App\Enums\VisitPaymentArrangementEvent;
use App\Enums\VisitPaymentArrangementSource;
use App\Enums\VisitPaymentArrangementStatus;
use App\Enums\VisitPaymentTimingPolicy;
use App\Exceptions\VisitPaymentArrangementException;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitPaymentArrangement;
use App\Models\VisitPaymentArrangementHistory;
use App\Models\VisitPaymentPolicy;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates the per-visit payment-arrangement workflow (Payment Timing Policy
 * Phase 7).
 *
 * Every mutation runs in a transaction with row locks + status rechecks, appends
 * one immutable history row and writes exactly one activity log. An approved
 * arrangement is ADMINISTRATIVE only: this service never calls the payment gate,
 * resolver or override services, never creates/modifies invoices/receivables,
 * and never changes visit status. It only links the observational visit-policy
 * record to the current approved arrangement (leaving baseline/recommendation
 * untouched).
 */
class VisitPaymentArrangementService
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
        private readonly VisitPaymentArrangementApprovalPolicyService $approvalPolicy,
        private readonly VisitPaymentPolicyMaterializationService $materialization,
        private readonly PatientFinancialRiskService $riskService,
    ) {}

    public function request(Visit $visit, VisitPaymentArrangementData $data, User $actor): VisitPaymentArrangement
    {
        if ($data->requestedPolicy === VisitPaymentTimingPolicy::INHERIT) {
            throw VisitPaymentArrangementException::visitNotEligible();
        }

        return DB::transaction(function () use ($visit, $data, $actor) {
            $this->assertNoPending($visit);

            $policy = $this->ensurePolicy($visit);
            $snapshot = $this->snapshot($policy);
            $requirement = $this->approvalPolicy->determine($visit, $data->requestedPolicy, $snapshot);

            $arrangement = new VisitPaymentArrangement(array_merge($data->toAttributes(), [
                'visit_id' => $visit->id,
                'visit_payment_policy_id' => $policy?->id,
                'status' => VisitPaymentArrangementStatus::PENDING->value,
                'requested_by' => $actor->id,
                'requested_at' => now(),
                'requires_approval' => $requirement->requiresApproval,
                'requires_separate_approver' => $requirement->requiresSeparateApprover,
                'risk_level_snapshot' => $snapshot['risk_level'],
                'risk_status_snapshot' => $snapshot['risk_status'],
                'baseline_policy_snapshot' => $snapshot['baseline_policy'],
                'recommended_policy_snapshot' => $snapshot['recommended_policy'],
                'finance_review_snapshot' => (bool) $snapshot['finance_review'],
            ]));
            $arrangement->save();

            $this->record($arrangement, VisitPaymentArrangementEvent::REQUESTED, $actor, [], $this->material($arrangement), $data->requestReasonCode);

            return $arrangement->refresh();
        });
    }

    public function update(VisitPaymentArrangement $arrangement, VisitPaymentArrangementData $data, User $actor): VisitPaymentArrangement
    {
        if ($data->requestedPolicy === VisitPaymentTimingPolicy::INHERIT) {
            throw VisitPaymentArrangementException::visitNotEligible();
        }

        return DB::transaction(function () use ($arrangement, $data, $actor) {
            $arrangement = $this->lock($arrangement);
            if (! $arrangement->isPending()) {
                throw VisitPaymentArrangementException::notPending();
            }

            $old = $this->material($arrangement);
            $arrangement->fill($data->toAttributes());

            // Recalculate approval requirements against the (unchanged) snapshot.
            $requirement = $this->approvalPolicy->determine($arrangement->visit, $data->requestedPolicy, $this->snapshotFromArrangement($arrangement));
            $arrangement->requires_approval = $requirement->requiresApproval;
            $arrangement->requires_separate_approver = $requirement->requiresSeparateApprover;

            if (! $arrangement->isDirty()) {
                return $arrangement;
            }
            $arrangement->save();

            $this->record($arrangement, VisitPaymentArrangementEvent::UPDATED_BEFORE_DECISION, $actor, $old, $this->material($arrangement), $data->requestReasonCode);

            return $arrangement->refresh();
        });
    }

    public function approve(VisitPaymentArrangement $arrangement, VisitPaymentArrangementApprovalData $data, User $actor, bool $selfApprovalAllowed = false): VisitPaymentArrangement
    {
        return DB::transaction(function () use ($arrangement, $data, $actor, $selfApprovalAllowed) {
            $arrangement = $this->lock($arrangement);
            if (! $arrangement->isPending()) {
                throw VisitPaymentArrangementException::notPending();
            }
            if ($arrangement->requested_by === $actor->id && ! $selfApprovalAllowed) {
                throw VisitPaymentArrangementException::selfApproval();
            }

            // Stale-risk guard: do not silently approve on outdated risk data.
            if ($this->riskIsStale($arrangement) && ! $data->confirmStaleRisk) {
                throw VisitPaymentArrangementException::staleRisk();
            }

            $visit = $arrangement->visit;

            // Replace any existing current approved arrangement for the visit.
            $existing = VisitPaymentArrangement::query()
                ->where('visit_id', $visit->id)
                ->where('status', VisitPaymentArrangementStatus::APPROVED->value)
                ->where('id', '!=', $arrangement->id)
                ->lockForUpdate()
                ->get();
            foreach ($existing as $prior) {
                $priorOld = $this->material($prior);
                $prior->status = VisitPaymentArrangementStatus::REPLACED;
                $prior->replaced_by_arrangement_id = $arrangement->id;
                $prior->save();
                $this->record($prior, VisitPaymentArrangementEvent::REPLACED, $actor, $priorOld, $this->material($prior), 'replaced_by_new_approval');
            }

            $old = $this->material($arrangement);
            $arrangement->status = VisitPaymentArrangementStatus::APPROVED;
            $arrangement->approved_policy = $arrangement->requested_policy->value;
            $arrangement->approved_by = $actor->id;
            $arrangement->approved_at = now();
            $arrangement->reviewed_by = $actor->id;
            $arrangement->reviewed_at = now();
            $arrangement->review_decision_reason = $data->decisionReason;
            $arrangement->effective_from = ($data->effectiveFrom ?? $arrangement->effective_from ?? now())->toDateString();
            if ($data->expiresAt !== null) {
                $arrangement->expires_at = $data->expiresAt->toDateString();
            }
            $arrangement->save();

            $this->linkApprovedToPolicy($arrangement);
            $this->record($arrangement, VisitPaymentArrangementEvent::APPROVED, $actor, $old, $this->material($arrangement), $data->decisionReason ? null : 'approved');

            return $arrangement->refresh();
        });
    }

    public function reject(VisitPaymentArrangement $arrangement, string $reason, User $actor): VisitPaymentArrangement
    {
        return $this->terminatePending($arrangement, VisitPaymentArrangementStatus::REJECTED, VisitPaymentArrangementEvent::REJECTED, $actor, $reason, fn ($a) => $a->forceFill([
            'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'review_decision_reason' => $reason,
        ]));
    }

    public function withdraw(VisitPaymentArrangement $arrangement, string $reason, User $actor): VisitPaymentArrangement
    {
        return $this->terminatePending($arrangement, VisitPaymentArrangementStatus::WITHDRAWN, VisitPaymentArrangementEvent::WITHDRAWN, $actor, $reason, fn ($a) => $a->forceFill([
            'withdrawn_by' => $actor->id, 'withdrawn_at' => now(), 'withdrawal_reason' => $reason,
        ]));
    }

    public function revoke(VisitPaymentArrangement $arrangement, string $reason, User $actor): VisitPaymentArrangement
    {
        return DB::transaction(function () use ($arrangement, $reason, $actor) {
            $arrangement = $this->lock($arrangement);
            if (! $arrangement->isApproved()) {
                throw VisitPaymentArrangementException::notApproved();
            }

            $old = $this->material($arrangement);
            $arrangement->status = VisitPaymentArrangementStatus::REVOKED;
            $arrangement->revoked_by = $actor->id;
            $arrangement->revoked_at = now();
            $arrangement->revocation_reason = $reason;
            $arrangement->save();

            $this->unlinkFromPolicy($arrangement);
            $this->record($arrangement, VisitPaymentArrangementEvent::REVOKED, $actor, $old, $this->material($arrangement), $reason);

            return $arrangement->refresh();
        });
    }

    /**
     * Directly remove the current approved arrangement and return the visit to
     * its observational baseline. Requires a reason; appends history + audit.
     */
    public function restoreBaseline(Visit $visit, string $reason, User $actor): VisitPaymentArrangement
    {
        return DB::transaction(function () use ($visit, $reason, $actor) {
            $current = VisitPaymentArrangement::query()
                ->where('visit_id', $visit->id)
                ->where('status', VisitPaymentArrangementStatus::APPROVED->value)
                ->lockForUpdate()
                ->latest('id')
                ->first();
            if ($current === null) {
                throw VisitPaymentArrangementException::nothingToRestore();
            }

            $old = $this->material($current);
            $current->status = VisitPaymentArrangementStatus::REVOKED;
            $current->revoked_by = $actor->id;
            $current->revoked_at = now();
            $current->revocation_reason = $reason;
            $current->source = VisitPaymentArrangementSource::BASELINE_RESTORATION->value;
            $current->save();

            $this->unlinkFromPolicy($current);
            $this->record($current, VisitPaymentArrangementEvent::BASELINE_RESTORED, $actor, $old, $this->material($current), $reason);

            return $current->refresh();
        });
    }

    /**
     * Expire approved arrangements whose expiry has passed. Idempotent; touches
     * no terminal records. Returns the number expired.
     */
    public function expireDue(?\DateTimeInterface $asOf = null): int
    {
        $asOf ??= now();
        $count = 0;

        VisitPaymentArrangement::query()->expired($asOf)->orderBy('id')->each(function (VisitPaymentArrangement $arrangement) use ($asOf, &$count): void {
            DB::transaction(function () use ($arrangement, $asOf, &$count): void {
                $locked = $this->lock($arrangement);
                if (! $locked->isApproved() || $locked->expires_at === null || $locked->expires_at->gt($asOf)) {
                    return;
                }

                $old = $this->material($locked);
                $locked->status = VisitPaymentArrangementStatus::EXPIRED;
                $locked->save();

                $this->unlinkFromPolicy($locked);
                $this->record($locked, VisitPaymentArrangementEvent::EXPIRED, null, $old, $this->material($locked), 'expiry_reached');
                $count++;
            });
        });

        return $count;
    }

    /** Whether the request-time risk snapshot differs from the patient's current risk state. */
    public function riskIsStale(VisitPaymentArrangement $arrangement): bool
    {
        $visit = $arrangement->visit;
        if ($visit === null || $visit->patient === null) {
            return false;
        }
        $current = $this->riskService->currentFor($visit->patient);

        $currentLevel = $current?->risk_level?->value;
        $currentStatus = $current?->status?->value;
        $snapLevel = $arrangement->risk_level_snapshot?->value;
        $snapStatus = $arrangement->risk_status_snapshot?->value;

        return $currentLevel !== $snapLevel || $currentStatus !== $snapStatus;
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */

    private function terminatePending(VisitPaymentArrangement $arrangement, VisitPaymentArrangementStatus $to, VisitPaymentArrangementEvent $event, User $actor, string $reason, callable $mutate): VisitPaymentArrangement
    {
        return DB::transaction(function () use ($arrangement, $to, $event, $actor, $reason, $mutate) {
            $arrangement = $this->lock($arrangement);
            if (! $arrangement->isPending()) {
                throw VisitPaymentArrangementException::notPending();
            }

            $old = $this->material($arrangement);
            $arrangement->status = $to;
            $mutate($arrangement);
            $arrangement->save();

            $this->record($arrangement, $event, $actor, $old, $this->material($arrangement), $reason);

            return $arrangement->refresh();
        });
    }

    private function assertNoPending(Visit $visit): void
    {
        $pending = VisitPaymentArrangement::query()
            ->where('visit_id', $visit->id)
            ->where('status', VisitPaymentArrangementStatus::PENDING->value)
            ->lockForUpdate()
            ->exists();
        if ($pending) {
            throw VisitPaymentArrangementException::duplicatePending();
        }
    }

    private function lock(VisitPaymentArrangement $arrangement): VisitPaymentArrangement
    {
        return $arrangement->newQuery()->with('visit.patient')->lockForUpdate()->findOrFail($arrangement->id);
    }

    private function ensurePolicy(Visit $visit): ?VisitPaymentPolicy
    {
        $policy = $visit->paymentPolicy()->first();
        if ($policy === null) {
            $policy = $this->materialization->materialize($visit, actor: null, logActivity: false);
        }

        return $policy;
    }

    /** @return array<string, mixed> */
    private function snapshot(?VisitPaymentPolicy $policy): array
    {
        return [
            'baseline_policy' => $policy?->getRawOriginal('resolved_policy'),
            'recommended_policy' => $policy?->getRawOriginal('recommended_policy'),
            'risk_level' => $policy?->getRawOriginal('patient_risk_level_snapshot'),
            'risk_status' => $policy?->getRawOriginal('patient_risk_status_snapshot'),
            'finance_review' => (bool) ($policy?->requires_finance_review ?? false),
        ];
    }

    /** @return array<string, mixed> */
    private function snapshotFromArrangement(VisitPaymentArrangement $arrangement): array
    {
        return [
            'baseline_policy' => $arrangement->baseline_policy_snapshot?->value,
            'recommended_policy' => $arrangement->recommended_policy_snapshot?->value,
            'risk_level' => $arrangement->risk_level_snapshot?->value,
            'risk_status' => $arrangement->risk_status_snapshot?->value,
            'finance_review' => (bool) $arrangement->finance_review_snapshot,
        ];
    }

    private function linkApprovedToPolicy(VisitPaymentArrangement $arrangement): void
    {
        $policy = $arrangement->visit?->paymentPolicy()->first();
        if ($policy === null) {
            return;
        }
        // Baseline + recommendation stay untouched; only the administrative link.
        $policy->forceFill([
            'current_approved_arrangement_id' => $arrangement->id,
            'approved_policy_snapshot' => $arrangement->approved_policy?->value,
            'approved_arrangement_observed_at' => now(),
        ])->save();
    }

    private function unlinkFromPolicy(VisitPaymentArrangement $arrangement): void
    {
        $policy = $arrangement->visit?->paymentPolicy()->first();
        if ($policy === null || $policy->current_approved_arrangement_id !== $arrangement->id) {
            return;
        }
        $policy->forceFill([
            'current_approved_arrangement_id' => null,
            'approved_policy_snapshot' => null,
            'approved_arrangement_observed_at' => null,
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    private function record(VisitPaymentArrangement $arrangement, VisitPaymentArrangementEvent $event, ?User $actor, array $old, array $new, ?string $reasonCode): void
    {
        VisitPaymentArrangementHistory::create([
            'visit_payment_arrangement_id' => $arrangement->id,
            'visit_id' => $arrangement->visit_id,
            'event_type' => $event->value,
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'reason_code' => $reasonCode,
            'performed_by' => $actor?->id,
            'performed_at' => now(),
        ]);

        $this->activityLog->log(
            LogModule::BILLING,
            $event->activityAction(),
            [
                'visit_id' => $arrangement->visit_id,
                'old_values' => $old,
                'new_values' => $new,
                'metadata' => [
                    'arrangement_id' => $arrangement->id,
                    'requested_policy' => $arrangement->requested_policy?->value,
                    'approved_policy' => $arrangement->approved_policy?->value,
                    'status' => $arrangement->status->value,
                    'requester_id' => $arrangement->requested_by,
                    'approver_id' => $arrangement->approved_by,
                    'risk_level_snapshot' => $arrangement->risk_level_snapshot?->value,
                    'requires_finance_review' => (bool) $arrangement->finance_review_snapshot,
                ],
                'causer' => $actor,
            ],
            $arrangement,
            $actor ? null : 'Visit payment arrangement expired by system',
        );
    }

    /**
     * Material arrangement fields for history/audit (no free-text risk details or
     * contact/clinical data).
     *
     * @return array<string, mixed>
     */
    private function material(VisitPaymentArrangement $a): array
    {
        return [
            'status' => $a->status?->value,
            'requested_policy' => $a->requested_policy?->value,
            'approved_policy' => $a->approved_policy?->value,
            'source' => $a->source?->value,
            'effective_from' => optional($a->effective_from)->toDateString(),
            'expires_at' => optional($a->expires_at)->toDateString(),
            'requires_separate_approver' => (bool) $a->requires_separate_approver,
            'risk_level_snapshot' => $a->risk_level_snapshot?->value,
            'risk_status_snapshot' => $a->risk_status_snapshot?->value,
            'baseline_policy_snapshot' => $a->baseline_policy_snapshot?->value,
            'recommended_policy_snapshot' => $a->recommended_policy_snapshot?->value,
        ];
    }
}
