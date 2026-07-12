<?php

namespace Database\Factories;

use App\Enums\VisitPaymentArrangementSource;
use App\Enums\VisitPaymentArrangementStatus;
use App\Enums\VisitPaymentTimingPolicy;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitPaymentArrangement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisitPaymentArrangement>
 *
 * Test/manual-testing data only — never used in production/default seeders.
 */
class VisitPaymentArrangementFactory extends Factory
{
    protected $model = VisitPaymentArrangement::class;

    public function definition(): array
    {
        return [
            'visit_id' => Visit::factory(),
            'requested_policy' => VisitPaymentTimingPolicy::PAY_AFTER_ALL_SERVICES->value,
            'source' => VisitPaymentArrangementSource::MANUAL_REQUEST->value,
            'status' => VisitPaymentArrangementStatus::PENDING->value,
            'request_reason' => 'Test arrangement',
            'requested_by' => User::factory(),
            'requested_at' => now(),
            'effective_from' => now()->toDateString(),
            'requires_approval' => true,
            'requires_separate_approver' => false,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => VisitPaymentArrangementStatus::APPROVED->value,
            'approved_policy' => VisitPaymentTimingPolicy::PAY_AFTER_ALL_SERVICES->value,
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    public function expiringOn(string $date): static
    {
        return $this->state(fn () => ['expires_at' => $date]);
    }
}
