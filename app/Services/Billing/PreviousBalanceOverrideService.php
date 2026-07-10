<?php

namespace App\Services\Billing;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitBillingOverride;
use App\Services\ActivityLogService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * The previous-balance OPD gate: for NON-emergency, non-admission care, a
 * patient's previous-visit debt above the configured threshold requires an
 * authorised billing/supervisor override before service proceeds.
 *
 * It runs SEPARATELY from the current-visit payment gate (BillingPolicyService)
 * and it NEVER blocks emergency or admission care — those care contexts always
 * return "not required" here, honouring
 * config('billing.previous_balance_policy.emergency_never_blocked_by_previous_balance').
 */
class PreviousBalanceOverrideService
{
    public function __construct(
        protected PatientOutstandingBalanceService $balances,
        protected BillingPolicyService $policy,
        protected ActivityLogService $activityLog,
    ) {}

    public function enabled(): bool
    {
        return (bool) config('billing.previous_balance_policy.enabled', true);
    }

    /**
     * Evaluate the previous-balance gate for a visit.
     *
     * @return array{
     *     enabled:bool, context:string, previous_balance:float, threshold:float,
     *     requires_override:bool, has_active_override:bool, blocked:bool,
     *     reason:?string
     * }
     */
    public function evaluate(Visit $visit): array
    {
        $context = $this->policy->careContext($visit);
        $previousBalance = $this->previousBalance($visit);
        $threshold = (float) config('billing.previous_balance_policy.opd_requires_override_above_amount', 100.00);
        $requiresOverride = $this->requiresOverride($visit, $previousBalance, $context);
        $hasActive = $this->hasActiveOverride($visit);

        return [
            'enabled' => $this->enabled(),
            'context' => $context,
            'previous_balance' => $previousBalance,
            'threshold' => $threshold,
            'requires_override' => $requiresOverride,
            'has_active_override' => $hasActive,
            'blocked' => $requiresOverride && ! $hasActive,
            'reason' => $this->reasonCode($context, $previousBalance, $threshold, $requiresOverride),
        ];
    }

    /**
     * Whether an authorised override is required before non-emergency service
     * may proceed for this visit.
     */
    public function requiresOverride(Visit $visit, ?float $previousBalance = null, ?string $context = null): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        $context ??= $this->policy->careContext($visit);

        // Emergency & admission (running-bill) care is NEVER gated by old debt.
        if ($context !== BillingPolicyService::CONTEXT_OPD) {
            return false;
        }

        $previousBalance ??= $this->previousBalance($visit);
        if ($previousBalance <= 0.009) {
            return false;
        }

        if ((bool) config('billing.previous_balance_policy.opd_requires_override_if_any_previous_balance', false)) {
            return true;
        }

        $threshold = (float) config('billing.previous_balance_policy.opd_requires_override_above_amount', 100.00);

        return $previousBalance > $threshold;
    }

    /** OPD service is blocked when an override is required but none is active. */
    public function isBlocked(Visit $visit): bool
    {
        return $this->requiresOverride($visit) && ! $this->hasActiveOverride($visit);
    }

    public function hasActiveOverride(Visit $visit): bool
    {
        return VisitBillingOverride::query()
            ->where('visit_id', $visit->id)
            ->where('override_type', VisitBillingOverride::TYPE_PREVIOUS_BALANCE_OVERRIDE)
            ->active()
            ->exists();
    }

    public function activeOverride(Visit $visit): ?VisitBillingOverride
    {
        return VisitBillingOverride::query()
            ->where('visit_id', $visit->id)
            ->where('override_type', VisitBillingOverride::TYPE_PREVIOUS_BALANCE_OVERRIDE)
            ->active()
            ->latest('id')
            ->first();
    }

    /**
     * Approve "proceed despite previous-visit debt" for an OPD visit. Idempotent:
     * returns any existing active override.
     */
    public function approveOverride(Visit $visit, User $authorizedBy, string $reason, ?Carbon $expiresAt = null): VisitBillingOverride
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new RuntimeException('A reason is required to override the previous-balance policy.');
        }

        if ($existing = $this->activeOverride($visit)) {
            return $existing;
        }

        $previousBalance = $this->previousBalance($visit);

        $override = VisitBillingOverride::create([
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'override_type' => VisitBillingOverride::TYPE_PREVIOUS_BALANCE_OVERRIDE,
            'scope' => VisitBillingOverride::SCOPE_VISIT,
            'reason' => $reason,
            'authorized_by' => $authorizedBy->id,
            'starts_at' => now(),
            'expires_at' => $expiresAt,
            'status' => VisitBillingOverride::STATUS_ACTIVE,
            'created_by' => Auth::id() ?? $authorizedBy->id,
        ]);

        $this->activityLog->log(LogModule::BILLING, 'PREVIOUS_BALANCE_OVERRIDE_APPROVED', [
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'severity' => LogSeverity::WARNING,
            'reason' => $reason,
            'metadata' => [
                'previous_balance_amount' => $previousBalance,
                'threshold' => (float) config('billing.previous_balance_policy.opd_requires_override_above_amount', 100.00),
                'authorized_by' => $authorizedBy->id,
                'expires_at' => $expiresAt?->toIso8601String(),
            ],
        ], $override, 'Previous balance override approved');

        return $override;
    }

    public function revokeOverride(VisitBillingOverride $override, User $user, string $reason): void
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new RuntimeException('A reason is required to revoke a previous-balance override.');
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

        $this->activityLog->log(LogModule::BILLING, 'PREVIOUS_BALANCE_OVERRIDE_REJECTED', [
            'visit_id' => $override->visit_id,
            'patient_id' => $override->patient_id,
            'severity' => LogSeverity::WARNING,
            'reason' => $reason,
            'metadata' => ['revoked_by' => $user->id],
        ], $override, 'Previous balance override revoked');
    }

    /**
     * Record (once per request context) that the previous-balance warning was
     * surfaced to a user — for the audit trail at registration / billing.
     */
    public function logWarningShown(Visit $visit, array $summary = []): void
    {
        $this->activityLog->log(LogModule::BILLING, 'PREVIOUS_BALANCE_WARNING_SHOWN', [
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'severity' => LogSeverity::INFO,
            'metadata' => [
                'previous_balance' => $summary['previous_outstanding'] ?? $this->previousBalance($visit),
                'total_balance' => $summary['total_outstanding'] ?? null,
                'context' => $this->policy->careContext($visit),
            ],
        ], $visit, 'Previous balance warning shown');
    }

    private function previousBalance(Visit $visit): float
    {
        $patient = $visit->patient ?: \App\Models\Patient::find($visit->patient_id);
        if (! $patient) {
            return 0.0;
        }

        return $this->balances->getPreviousOutstandingBalance($patient, $visit);
    }

    private function reasonCode(string $context, float $previousBalance, float $threshold, bool $requiresOverride): ?string
    {
        if ($context === BillingPolicyService::CONTEXT_EMERGENCY) {
            return 'EMERGENCY_NEVER_BLOCKED';
        }
        if ($context === BillingPolicyService::CONTEXT_ADMISSION) {
            return 'ADMISSION_RUNNING_BILL';
        }
        if ($previousBalance <= 0.009) {
            return 'NO_PREVIOUS_BALANCE';
        }

        return $requiresOverride ? 'OVERRIDE_REQUIRED' : 'BELOW_THRESHOLD';
    }
}
