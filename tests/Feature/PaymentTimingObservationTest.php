<?php

namespace Tests\Feature;

use App\Data\Billing\LegacyPaymentGateSnapshot;
use App\Data\Billing\VisitPaymentTimingDecision;
use App\Enums\PaymentTimingComparisonOutcome;
use App\Enums\VisitPaymentPolicySource;
use App\Enums\VisitPaymentTimingPolicy;
use App\Enums\VisitType;
use App\Models\Visit;
use App\Services\Billing\PaymentTimingPolicyComparisonService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class PaymentTimingObservationTest extends TestCase
{
    private function visit(): Visit
    {
        return new Visit(['visit_type' => VisitType::OUTPATIENT]);
    }

    private function typed(VisitPaymentTimingPolicy $policy): VisitPaymentTimingDecision
    {
        return new VisitPaymentTimingDecision($policy, VisitPaymentPolicySource::GLOBAL_DEFAULT, 'global_default');
    }

    private function legacy(bool $allowed, bool $comparable = true): LegacyPaymentGateSnapshot
    {
        return new LegacyPaymentGateSnapshot(
            $allowed,
            false,
            $comparable,
            $allowed ? VisitPaymentTimingPolicy::RUNNING_BILL : VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE,
            $allowed ? 'RUNNING_BILL' : 'ITEM_UNPAID',
        );
    }

    public function test_comparison_classifies_matches_mismatches_and_non_comparable_cases(): void
    {
        config(['payment_timing.integration.log_mismatches' => false]);
        $service = app(PaymentTimingPolicyComparisonService::class);

        $this->assertSame(
            PaymentTimingComparisonOutcome::MATCH,
            $service->compare($this->visit(), $this->legacy(false), $this->typed(VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE))->outcome,
        );
        $this->assertSame(
            PaymentTimingComparisonOutcome::LEGACY_MORE_RESTRICTIVE,
            $service->compare($this->visit(), $this->legacy(false), $this->typed(VisitPaymentTimingPolicy::RUNNING_BILL))->outcome,
        );
        $this->assertSame(
            PaymentTimingComparisonOutcome::TYPED_MORE_RESTRICTIVE,
            $service->compare($this->visit(), $this->legacy(true), $this->typed(VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE))->outcome,
        );
        $this->assertSame(
            PaymentTimingComparisonOutcome::NOT_COMPARABLE,
            $service->compare($this->visit(), $this->legacy(true, false), $this->typed(VisitPaymentTimingPolicy::RUNNING_BILL))->outcome,
        );
    }

    public function test_mismatch_log_is_structured_bounded_and_excludes_sensitive_patient_data(): void
    {
        config([
            'payment_timing.integration.log_mismatches' => true,
            'payment_timing.integration.deduplication_seconds' => 0,
        ]);
        Log::shouldReceive('warning')->once()->with(
            'payment_timing_policy_mismatch',
            Mockery::on(function (array $payload): bool {
                $json = json_encode($payload);

                return $payload['outcome'] === 'legacy_more_restrictive'
                    && ! str_contains($json, 'patient_name')
                    && ! str_contains($json, 'phone')
                    && ! str_contains($json, 'email')
                    && ! str_contains($json, 'insurance');
            }),
        );

        $result = app(PaymentTimingPolicyComparisonService::class)->compare(
            $this->visit(),
            $this->legacy(false),
            $this->typed(VisitPaymentTimingPolicy::RUNNING_BILL),
        );
        $this->assertSame(PaymentTimingComparisonOutcome::LEGACY_MORE_RESTRICTIVE, $result->outcome);
    }

    public function test_matches_are_not_logged_by_default_and_logging_failure_is_non_fatal(): void
    {
        config(['payment_timing.integration.log_matches' => false]);
        Log::spy();
        app(PaymentTimingPolicyComparisonService::class)->compare(
            $this->visit(),
            $this->legacy(false),
            $this->typed(VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE),
        );
        Log::shouldNotHaveReceived('warning');

        config([
            'payment_timing.integration.log_mismatches' => true,
            'payment_timing.integration.deduplication_seconds' => 0,
        ]);
        Log::shouldReceive('warning')->once()->andThrow(new \RuntimeException('logger unavailable'));
        Log::shouldReceive('debug')->once()->andThrow(new \RuntimeException('fallback logger unavailable'));
        $result = app(PaymentTimingPolicyComparisonService::class)->compare(
            $this->visit(),
            $this->legacy(false),
            $this->typed(VisitPaymentTimingPolicy::RUNNING_BILL),
        );
        $this->assertTrue($result->isMismatch());
    }
}
