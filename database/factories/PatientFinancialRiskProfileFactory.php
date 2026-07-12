<?php

namespace Database\Factories;

use App\Enums\PatientFinancialRiskLevel;
use App\Enums\PatientFinancialRiskReason;
use App\Enums\PatientFinancialRiskStatus;
use App\Models\Patient;
use App\Models\PatientFinancialRiskProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatientFinancialRiskProfile>
 *
 * Test/manual-testing data only — never used in production/default seeders.
 */
class PatientFinancialRiskProfileFactory extends Factory
{
    protected $model = PatientFinancialRiskProfile::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'risk_level' => PatientFinancialRiskLevel::WATCHLIST->value,
            'primary_reason' => PatientFinancialRiskReason::PREVIOUS_UNPAID_VISITS->value,
            'reason_details' => null,
            'status' => PatientFinancialRiskStatus::ACTIVE->value,
            'credit_limit' => null,
            'effective_from' => now()->toDateString(),
            'review_due_at' => null,
            'expires_at' => null,
            'reference' => null,
            'set_by' => User::factory(),
        ];
    }

    public function level(PatientFinancialRiskLevel $level): static
    {
        return $this->state(fn () => ['risk_level' => $level->value]);
    }

    public function status(PatientFinancialRiskStatus $status): static
    {
        return $this->state(fn () => ['status' => $status->value]);
    }

    public function highRisk(): static
    {
        return $this->level(PatientFinancialRiskLevel::HIGH_RISK);
    }

    public function blockedCredit(): static
    {
        return $this->level(PatientFinancialRiskLevel::BLOCKED_CREDIT);
    }

    public function dueForReview(): static
    {
        return $this->state(fn () => ['review_due_at' => now()->subDay()->toDateString()]);
    }

    public function expiringOn(string $date): static
    {
        return $this->state(fn () => ['expires_at' => $date]);
    }

    public function cleared(): static
    {
        return $this->state(fn () => [
            'status' => PatientFinancialRiskStatus::CLEARED->value,
            'cleared_at' => now(),
            'clearance_reason' => 'Resolved',
        ]);
    }
}
