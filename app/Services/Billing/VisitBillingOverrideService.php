<?php

namespace App\Services\Billing;

use App\Enums\LogModule;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitBillingOverride;
use App\Services\ActivityLogService;
use App\Services\VisitPathwayService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * Lifecycle of authorised billing exceptions — chiefly the deferred OPD
 * settlement override (render the whole visit, pay once at the end). Every
 * change is recorded to the visit pathway and the activity log.
 */
class VisitBillingOverrideService
{
    public function __construct(
        protected BillingPolicyService $policy,
        protected VisitPathwayService $pathway,
        protected ActivityLogService $activityLog,
    ) {}

    /**
     * Approve "render now, settle the whole visit later" for an OPD visit.
     * Idempotent: returns the existing active override if one already exists.
     */
    public function approveDeferredSettlement(
        Visit $visit,
        User $authorizedBy,
        string $reason,
        ?Carbon $expiresAt = null,
    ): VisitBillingOverride {
        $reason = trim($reason);
        if ($reason === '') {
            throw new RuntimeException('A reason is required to approve deferred settlement.');
        }
        if (! $this->policy->isDeferredSettlementAllowed($visit)) {
            throw new RuntimeException('Deferred settlement only applies to OPD visits.');
        }

        // Only one active deferred override per visit.
        if ($existing = $this->activeDeferredSettlement($visit)) {
            return $existing;
        }

        $override = VisitBillingOverride::create([
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'override_type' => VisitBillingOverride::TYPE_DEFERRED_OPD_SETTLEMENT,
            'scope' => VisitBillingOverride::SCOPE_VISIT,
            'reason' => $reason,
            'authorized_by' => $authorizedBy->id,
            'starts_at' => now(),
            'expires_at' => $expiresAt,
            'status' => VisitBillingOverride::STATUS_ACTIVE,
            'created_by' => Auth::id() ?? $authorizedBy->id,
        ]);

        $this->pathway->record($visit, 'DEFERRED_SETTLEMENT_APPROVED', [
            'title' => 'Deferred settlement approved',
            'description' => $reason,
        ]);

        $this->activityLog->log(LogModule::BILLING, 'DEFERRED_SETTLEMENT_APPROVED', [
            'reason' => $reason,
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'authorized_by' => $authorizedBy->id,
            'expires_at' => $expiresAt?->toIso8601String(),
            'severity' => 'WARNING',
        ], $override, 'OPD deferred settlement approved');

        return $override;
    }

    public function revokeOverride(VisitBillingOverride $override, User $user, string $reason): void
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new RuntimeException('A reason is required to revoke a billing override.');
        }
        if ($override->status !== VisitBillingOverride::STATUS_ACTIVE) {
            return;
        }

        $override->forceFill([
            'status' => VisitBillingOverride::STATUS_REVOKED,
            'revoked_by' => $user->id,
            'revoked_at' => now(),
            'revoke_reason' => $reason,
        ])->save();

        if ($visit = $override->visit) {
            $this->pathway->record($visit, 'DEFERRED_SETTLEMENT_REVOKED', [
                'title' => 'Deferred settlement revoked',
                'description' => $reason,
            ]);
        }

        $this->activityLog->log(LogModule::BILLING, 'BILLING_OVERRIDE_REVOKED', [
            'reason' => $reason,
            'override_type' => $override->override_type,
            'visit_id' => $override->visit_id,
            'revoked_by' => $user->id,
            'severity' => 'WARNING',
        ], $override, 'Billing override revoked');
    }

    public function activeDeferredSettlement(Visit $visit): ?VisitBillingOverride
    {
        return VisitBillingOverride::query()
            ->where('visit_id', $visit->id)
            ->where('override_type', VisitBillingOverride::TYPE_DEFERRED_OPD_SETTLEMENT)
            ->active()
            ->latest('id')
            ->first();
    }

    public function hasActiveDeferredSettlement(Visit $visit): bool
    {
        return $this->activeDeferredSettlement($visit) !== null;
    }

    /**
     * Mark the visit's deferred settlement as fulfilled (used at end-of-visit
     * once the balance is cleared / authorised).
     */
    public function completeDeferredSettlement(Visit $visit, User $user): void
    {
        $override = $this->activeDeferredSettlement($visit);
        if (! $override) {
            return;
        }

        $override->forceFill([
            'status' => VisitBillingOverride::STATUS_COMPLETED,
            'completed_at' => now(),
        ])->save();

        $this->activityLog->log(LogModule::BILLING, 'DEFERRED_SETTLEMENT_COMPLETED', [
            'visit_id' => $visit->id,
            'completed_by' => $user->id,
        ], $override, 'OPD deferred settlement completed');
    }
}
