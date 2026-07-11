<?php

namespace App\Services\Billing;

use App\Data\Billing\VisitPaymentTimingDecision;
use App\Enums\VisitPaymentPolicySource;
use App\Enums\VisitPaymentTimingPolicy;
use App\Enums\VisitType;
use App\Models\Visit;
use App\Models\VisitBillingOverride;
use Illuminate\Support\Collection;

class VisitPaymentTimingResolver
{
    /** @var array<int, Collection<int, VisitBillingOverride>> */
    private array $activeOverrides = [];

    public function __construct(private PaymentTimingConfigurationService $configuration) {}

    public function resolve(Visit $visit): VisitPaymentTimingDecision
    {
        $visitType = $visit->visit_type instanceof VisitType
            ? $visit->visit_type
            : VisitType::tryFrom((string) $visit->visit_type);
        $configured = $visitType
            ? $this->configuration->configuredPolicyForVisitType($visitType)
            : VisitPaymentTimingPolicy::INHERIT;
        $global = $this->configuration->globalDefault();
        $overrides = $this->overridesFor($visit);
        $previousBalanceOverride = $overrides->firstWhere('override_type', VisitBillingOverride::TYPE_PREVIOUS_BALANCE_OVERRIDE);

        $baseContext = [
            'visit_type' => $visitType?->value,
            'global_default' => $global->value,
            'visit_type_configured' => $configured->value,
            'emergency_protection_considered' => $visitType === VisitType::EMERGENCY,
            'legacy_override_considered' => $overrides->isNotEmpty(),
            'previous_balance_override_present' => $previousBalanceOverride !== null,
            'integration_mode' => $this->configuration->integrationMode()->value,
        ];

        if ($visitType === VisitType::EMERGENCY && $this->configuration->neverBlockEmergencyStabilisation()) {
            return new VisitPaymentTimingDecision(
                VisitPaymentTimingPolicy::RUNNING_BILL,
                VisitPaymentPolicySource::EMERGENCY_POLICY,
                'emergency_stabilisation_protection',
                $baseContext,
            );
        }

        if ($override = $this->fullTimingOverride($overrides)) {
            return new VisitPaymentTimingDecision(
                VisitPaymentTimingPolicy::PAY_AFTER_ALL_SERVICES,
                VisitPaymentPolicySource::MANUAL_OVERRIDE,
                'legacy_visit_override',
                $baseContext + [
                    'legacy_override_id' => $override->id,
                    'legacy_override_type' => $override->override_type,
                    'legacy_override_scope' => $override->scope,
                ],
            );
        }

        if ($visitType && $configured !== VisitPaymentTimingPolicy::INHERIT) {
            return new VisitPaymentTimingDecision(
                $configured,
                VisitPaymentPolicySource::VISIT_TYPE,
                'visit_type_policy',
                $baseContext,
            );
        }

        return new VisitPaymentTimingDecision(
            $global,
            VisitPaymentPolicySource::GLOBAL_DEFAULT,
            'global_default',
            $baseContext,
        );
    }

    /** @return Collection<int, VisitBillingOverride> */
    private function overridesFor(Visit $visit): Collection
    {
        if ($visit->relationLoaded('billingOverrides')) {
            return $visit->billingOverrides
                ->filter(fn (VisitBillingOverride $override) => $override->isCurrentlyActive())
                ->values();
        }

        if (! $visit->exists) {
            return collect();
        }

        return $this->activeOverrides[$visit->id] ??= $visit->billingOverrides()
            ->active()
            ->get(['id', 'visit_id', 'override_type', 'scope', 'scope_id', 'status', 'starts_at', 'expires_at']);
    }

    private function fullTimingOverride(Collection $overrides): ?VisitBillingOverride
    {
        foreach ([
            VisitBillingOverride::TYPE_DEFERRED_OPD_SETTLEMENT,
            VisitBillingOverride::TYPE_CREDIT_APPROVAL,
            VisitBillingOverride::TYPE_PAYMENT_GATE_BYPASS,
        ] as $type) {
            $override = $overrides->first(fn (VisitBillingOverride $candidate) => $candidate->override_type === $type && $candidate->scope === VisitBillingOverride::SCOPE_VISIT
            );
            if ($override) {
                return $override;
            }
        }

        return null;
    }
}
