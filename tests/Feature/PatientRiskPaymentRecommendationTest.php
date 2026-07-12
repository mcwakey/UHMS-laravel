<?php

namespace Tests\Feature;

use App\Data\Billing\PatientRiskPaymentRecommendation;
use App\Enums\PatientFinancialRiskLevel;
use App\Enums\PatientFinancialRiskStatus;
use App\Enums\VisitPaymentTimingPolicy;
use App\Models\PatientFinancialRiskProfile;
use App\Services\Billing\PatientRiskPaymentRecommendationService;
use Tests\TestCase;

class PatientRiskPaymentRecommendationTest extends TestCase
{
    private PatientRiskPaymentRecommendationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PatientRiskPaymentRecommendationService::class);
    }

    private function profile(PatientFinancialRiskLevel $level, PatientFinancialRiskStatus $status): PatientFinancialRiskProfile
    {
        return new PatientFinancialRiskProfile([
            'risk_level' => $level->value,
            'status' => $status->value,
            'primary_reason' => 'management_decision',
        ]);
    }

    public function test_no_active_profile_produces_no_recommendation(): void
    {
        $rec = $this->service->recommendFor(null);
        $this->assertNull($rec->recommendedPolicy);
        $this->assertFalse($rec->requiresFinanceReview);
        $this->assertFalse($rec->approvedForResolution);
        $this->assertSame(PatientRiskPaymentRecommendation::NO_ACTIVE_RISK_PROFILE, $rec->reasonCode);
    }

    public function test_normal_produces_no_recommendation(): void
    {
        $rec = $this->service->recommendFor($this->profile(PatientFinancialRiskLevel::NORMAL, PatientFinancialRiskStatus::ACTIVE));
        $this->assertNull($rec->recommendedPolicy);
        $this->assertFalse($rec->requiresFinanceReview);
    }

    public function test_watchlist_requires_review_without_forcing_policy(): void
    {
        $rec = $this->service->recommendFor($this->profile(PatientFinancialRiskLevel::WATCHLIST, PatientFinancialRiskStatus::ACTIVE));
        $this->assertNull($rec->recommendedPolicy);
        $this->assertTrue($rec->requiresFinanceReview);
        $this->assertSame(PatientRiskPaymentRecommendation::WATCHLIST_REVIEW_RECOMMENDED, $rec->reasonCode);
        $this->assertFalse($rec->approvedForResolution);
    }

    public function test_high_risk_recommends_prepayment_but_unapproved(): void
    {
        $rec = $this->service->recommendFor($this->profile(PatientFinancialRiskLevel::HIGH_RISK, PatientFinancialRiskStatus::ACTIVE));
        $this->assertSame(VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE, $rec->recommendedPolicy);
        $this->assertTrue($rec->requiresFinanceReview);
        $this->assertFalse($rec->approvedForResolution);
        $this->assertSame(PatientRiskPaymentRecommendation::HIGH_RISK_PREPAYMENT_RECOMMENDED, $rec->reasonCode);
    }

    public function test_blocked_credit_recommends_prepayment_but_unapproved(): void
    {
        $rec = $this->service->recommendFor($this->profile(PatientFinancialRiskLevel::BLOCKED_CREDIT, PatientFinancialRiskStatus::ACTIVE));
        $this->assertSame(VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE, $rec->recommendedPolicy);
        $this->assertTrue($rec->requiresFinanceReview);
        $this->assertFalse($rec->approvedForResolution);
    }

    public function test_suspended_expired_do_not_produce_active_recommendation(): void
    {
        $suspended = $this->service->recommendFor($this->profile(PatientFinancialRiskLevel::HIGH_RISK, PatientFinancialRiskStatus::SUSPENDED));
        $this->assertNull($suspended->recommendedPolicy);
        $this->assertFalse($suspended->requiresFinanceReview);
        $this->assertSame(PatientRiskPaymentRecommendation::RISK_PROFILE_SUSPENDED, $suspended->reasonCode);

        $expired = $this->service->recommendFor($this->profile(PatientFinancialRiskLevel::HIGH_RISK, PatientFinancialRiskStatus::EXPIRED));
        $this->assertNull($expired->recommendedPolicy);
        $this->assertSame(PatientRiskPaymentRecommendation::RISK_PROFILE_EXPIRED, $expired->reasonCode);
    }

    public function test_under_review_requires_finance_review_without_forcing_policy(): void
    {
        $rec = $this->service->recommendFor($this->profile(PatientFinancialRiskLevel::HIGH_RISK, PatientFinancialRiskStatus::UNDER_REVIEW));
        $this->assertNull($rec->recommendedPolicy);
        $this->assertTrue($rec->requiresFinanceReview);
        $this->assertSame(PatientRiskPaymentRecommendation::RISK_PROFILE_UNDER_REVIEW, $rec->reasonCode);
    }

    public function test_all_rules_remain_unapproved_for_operational_resolution(): void
    {
        foreach (PatientFinancialRiskLevel::cases() as $level) {
            $rec = $this->service->recommendFor($this->profile($level, PatientFinancialRiskStatus::ACTIVE));
            $this->assertFalse($rec->approvedForResolution, "{$level->value} must not be approved for resolution in Phase 6");
        }
    }
}
