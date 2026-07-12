<?php

namespace Database\Factories;

use App\Enums\VisitPaymentPolicySource;
use App\Enums\VisitPaymentTimingPolicy;
use App\Models\Visit;
use App\Models\VisitPaymentPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisitPaymentPolicy>
 *
 * Test/manual-testing data only — never used in production/default seeders.
 */
class VisitPaymentPolicyFactory extends Factory
{
    protected $model = VisitPaymentPolicy::class;

    public function definition(): array
    {
        return [
            'visit_id' => Visit::factory(),
            'resolved_policy' => VisitPaymentTimingPolicy::PAY_AFTER_ALL_SERVICES->value,
            'resolution_source' => VisitPaymentPolicySource::GLOBAL_DEFAULT->value,
            'resolution_reason_code' => 'global_default',
            'recommended_policy' => null,
            'recommendation_source' => null,
            'recommendation_reason_code' => null,
            'requires_finance_review' => false,
            'global_default_snapshot' => VisitPaymentTimingPolicy::PAY_AFTER_ALL_SERVICES->value,
            'visit_type_policy_snapshot' => VisitPaymentTimingPolicy::INHERIT->value,
            'visit_type_snapshot' => 'outpatient',
            'emergency_protection_snapshot' => false,
            'resolution_version' => 'payment_timing_v1',
            'materialized_at' => now(),
        ];
    }

    public function financeReview(): static
    {
        return $this->state(fn () => [
            'requires_finance_review' => true,
            'recommendation_source' => VisitPaymentPolicySource::PATIENT_RISK->value,
            'recommendation_reason_code' => 'high_risk_prepayment_recommended',
            'recommended_policy' => VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE->value,
        ]);
    }
}
