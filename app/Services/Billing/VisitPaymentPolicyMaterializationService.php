<?php

namespace App\Services\Billing;

use App\Data\Billing\VisitPaymentPolicyPreview;
use App\Enums\PatientFinancialRiskStatus;
use App\Enums\VisitPaymentPolicyEvent;
use App\Enums\VisitPaymentTimingPolicy;
use App\Models\PatientFinancialRiskProfile;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitPaymentPolicy;
use App\Models\VisitPaymentPolicyHistory;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;

/**
 * Materialises the OBSERVATIONAL visit-payment-policy record (Payment Timing
 * Policy Phase 6).
 *
 * Hard separation: the stored `resolved_policy` always comes from the baseline
 * {@see VisitPaymentTimingResolver}; the risk recommendation is stored in the
 * separate `recommended_policy` field and NEVER substitutes the baseline. This
 * service writes no invoices/payments/overrides, changes no visit and drives no
 * payment gate. Materialisation is idempotent; snapshots are point-in-time and
 * are not silently rewritten (only an explicit `refresh` changes them).
 */
class VisitPaymentPolicyMaterializationService
{
    /** Fields compared to decide whether a refresh materially changed the record. */
    private const MATERIAL_KEYS = [
        'resolved_policy', 'resolution_source', 'resolution_reason_code',
        'recommended_policy', 'recommendation_source', 'recommendation_reason_code',
        'requires_finance_review',
        'global_default_snapshot', 'visit_type_policy_snapshot', 'visit_type_snapshot',
        'emergency_protection_snapshot',
        'compatible_override_type_snapshot', 'compatible_override_scope_snapshot', 'compatible_override_id_snapshot',
        'patient_financial_risk_profile_id', 'patient_risk_level_snapshot',
        'patient_risk_status_snapshot', 'patient_risk_reason_snapshot',
        'resolution_version',
    ];

    public function __construct(
        private readonly VisitPaymentTimingResolver $resolver,
        private readonly PatientFinancialRiskService $riskService,
        private readonly PatientRiskPaymentRecommendationService $recommendationService,
        private readonly PaymentTimingConfigurationService $configuration,
        private readonly ActivityLogService $activityLog,
    ) {}

    /**
     * Create the record if absent; idempotent (never overwrites an existing
     * snapshot — use refresh() for that).
     */
    public function materialize(Visit $visit, ?User $actor = null, bool $logActivity = true): VisitPaymentPolicy
    {
        return DB::transaction(function () use ($visit, $actor, $logActivity) {
            $existing = VisitPaymentPolicy::query()->where('visit_id', $visit->id)->lockForUpdate()->first();
            if ($existing !== null) {
                return $existing; // idempotent — preserve original snapshot
            }

            $attributes = $this->computeAttributes($visit);
            $attributes['materialized_at'] = now();

            $policy = new VisitPaymentPolicy(['visit_id' => $visit->id] + $attributes);
            $policy->save();

            $this->recordHistory($policy, VisitPaymentPolicyEvent::MATERIALIZED, $actor, [], $this->material($attributes), null);
            if ($logActivity) {
                $this->logActivity($policy, VisitPaymentPolicyEvent::MATERIALIZED, $actor, [], $this->material($attributes));
            }

            return $policy->refresh();
        });
    }

    /**
     * Explicitly recompute and, if materially changed, update the record +
     * append one REFRESHED history entry + one activity log. Unchanged refresh
     * writes nothing.
     */
    public function refresh(Visit $visit, ?User $actor = null, ?string $reasonCode = null): VisitPaymentPolicy
    {
        return DB::transaction(function () use ($visit, $actor, $reasonCode) {
            $policy = VisitPaymentPolicy::query()->where('visit_id', $visit->id)->lockForUpdate()->first();
            if ($policy === null) {
                return $this->materialize($visit, $actor);
            }

            $old = $this->material($policy->getAttributes());
            $new = $this->computeAttributes($visit);

            if ($this->material($new) == $old) {
                return $policy; // nothing changed — no history, no activity
            }

            $policy->fill($new);
            $policy->last_refreshed_at = now();
            $policy->save();

            $this->recordHistory($policy, VisitPaymentPolicyEvent::REFRESHED, $actor, $old, $this->material($policy->getAttributes()), $reasonCode);
            $this->logActivity($policy, VisitPaymentPolicyEvent::REFRESHED, $actor, $old, $this->material($policy->getAttributes()));

            return $policy->refresh();
        });
    }

    /** Non-persisted preview — writes nothing. */
    public function preview(Visit $visit): VisitPaymentPolicyPreview
    {
        $decision = $this->resolver->resolve($visit);
        $profile = $this->currentProfile($visit);
        $recommendation = $this->recommendationService->recommendFor($profile);

        return new VisitPaymentPolicyPreview(
            baseline: $decision,
            recommendation: $recommendation,
            baselineContext: $decision->context,
            riskSnapshot: $this->riskSnapshot($profile),
            resolutionVersion: $this->version(),
        );
    }

    /**
     * A snapshot is stale when the observed risk state no longer matches the
     * patient's current risk state. Staleness NEVER changes the visit policy.
     */
    public function snapshotIsStale(VisitPaymentPolicy $policy): bool
    {
        // No visit/patient resolvable — cannot assess; treat as not stale.
        $visit = $policy->visit;
        if ($visit === null || $visit->patient === null) {
            return false;
        }
        $current = $this->riskService->currentFor($visit->patient);
        $snapshotProfileId = $policy->patient_financial_risk_profile_id;

        // A current restrictive profile but no snapshot recorded.
        if ($snapshotProfileId === null) {
            return $current !== null && $current->isRestrictive();
        }

        // Patient now has a different active profile than the one snapshotted.
        if ($current !== null && $current->id !== $snapshotProfileId) {
            return true;
        }

        $snapshotProfile = PatientFinancialRiskProfile::find($snapshotProfileId);
        if ($snapshotProfile === null) {
            return true;
        }

        // Snapshotted profile later left the active slot (suspended/cleared/expired)
        // or its status/level changed since observation.
        if ($snapshotProfile->status !== $policy->patient_risk_status_snapshot) {
            return true;
        }
        if ($snapshotProfile->risk_level !== $policy->patient_risk_level_snapshot) {
            return true;
        }
        if ($policy->patient_risk_observed_at !== null
            && $snapshotProfile->updated_at !== null
            && $snapshotProfile->updated_at->gt($policy->patient_risk_observed_at)) {
            return true;
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */

    /** @return array<string, mixed> */
    private function computeAttributes(Visit $visit): array
    {
        $decision = $this->resolver->resolve($visit);
        $context = $decision->context;

        // Defensive: the resolver never returns `inherit`, but a stored baseline
        // must never be `inherit` — fall back to the operational global default.
        $resolved = $decision->policy === VisitPaymentTimingPolicy::INHERIT
            ? $this->configuration->globalDefault()
            : $decision->policy;

        $profile = $this->currentProfile($visit);
        $recommendation = $this->recommendationService->recommendFor($profile);
        $snapshot = $this->riskSnapshot($profile);

        return [
            'resolved_policy' => $resolved->value,
            'resolution_source' => $decision->source->value,
            'resolution_reason_code' => $decision->reasonCode,

            'recommended_policy' => $recommendation->recommendedPolicy?->value,
            'recommendation_source' => $recommendation->source?->value,
            'recommendation_reason_code' => $recommendation->reasonCode,
            'requires_finance_review' => $recommendation->requiresFinanceReview,

            'global_default_snapshot' => $context['global_default'] ?? null,
            'visit_type_policy_snapshot' => $context['visit_type_configured'] ?? null,
            'visit_type_snapshot' => $context['visit_type'] ?? (is_object($visit->visit_type) ? $visit->visit_type->value : $visit->visit_type),
            'emergency_protection_snapshot' => (bool) ($context['emergency_protection_considered'] ?? false),

            'compatible_override_type_snapshot' => $context['legacy_override_type'] ?? null,
            'compatible_override_scope_snapshot' => $context['legacy_override_scope'] ?? null,
            'compatible_override_id_snapshot' => $context['legacy_override_id'] ?? null,

            'patient_financial_risk_profile_id' => $snapshot['profile_id'],
            'patient_risk_level_snapshot' => $snapshot['level'],
            'patient_risk_status_snapshot' => $snapshot['status'],
            'patient_risk_reason_snapshot' => $snapshot['reason'],
            'patient_risk_observed_at' => $snapshot['observed_at'],

            'resolution_version' => $this->version(),
        ];
    }

    private function currentProfile(Visit $visit): ?PatientFinancialRiskProfile
    {
        $patient = $visit->relationLoaded('patient') ? $visit->patient : $visit->patient()->first();

        return $patient ? $this->riskService->currentFor($patient) : null;
    }

    /**
     * Confidentiality-safe risk snapshot: identifiers/enums only — never reason
     * details, reference, clearance reason, contact info or history.
     *
     * @return array<string, mixed>
     */
    private function riskSnapshot(?PatientFinancialRiskProfile $profile): array
    {
        if ($profile === null) {
            return ['profile_id' => null, 'level' => null, 'status' => null, 'reason' => null, 'observed_at' => null];
        }

        return [
            'profile_id' => $profile->id,
            'level' => $profile->risk_level->value,
            'status' => $profile->status->value,
            'reason' => $profile->primary_reason?->value,
            'observed_at' => now(),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function material(array $attributes): array
    {
        $out = [];
        foreach (self::MATERIAL_KEYS as $key) {
            $value = $attributes[$key] ?? null;
            // Normalise booleans/ints so DB round-trips compare cleanly.
            if ($key === 'requires_finance_review' || $key === 'emergency_protection_snapshot') {
                $value = (bool) $value;
            }
            $out[$key] = $value;
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    private function recordHistory(VisitPaymentPolicy $policy, VisitPaymentPolicyEvent $event, ?User $actor, array $old, array $new, ?string $reasonCode): void
    {
        VisitPaymentPolicyHistory::create([
            'visit_payment_policy_id' => $policy->id,
            'visit_id' => $policy->visit_id,
            'event_type' => $event->value,
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'reason_code' => $reasonCode,
            'performed_by' => $actor?->id,
            'performed_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    private function logActivity(VisitPaymentPolicy $policy, VisitPaymentPolicyEvent $event, ?User $actor, array $old, array $new): void
    {
        $this->activityLog->log(
            \App\Enums\LogModule::BILLING,
            $event->activityAction(),
            [
                'visit_id' => $policy->visit_id,
                'old_values' => $old,
                'new_values' => $new,
                'metadata' => [
                    'policy_id' => $policy->id,
                    'resolved_policy' => $policy->resolved_policy?->value,
                    'recommended_policy' => $policy->recommended_policy?->value,
                    'resolution_source' => $policy->resolution_source?->value,
                    'risk_level_snapshot' => $policy->patient_risk_level_snapshot?->value,
                    'requires_finance_review' => (bool) $policy->requires_finance_review,
                ],
                'causer' => $actor,
            ],
            $policy,
            $actor ? null : 'Visit payment policy materialised by system',
        );
    }

    private function version(): string
    {
        return (string) config('visit_payment_policy.resolution_version', 'payment_timing_v1');
    }
}
