<?php

namespace Tests\Feature;

use App\Enums\PatientFinancialRiskLevel;
use App\Enums\VisitPaymentTimingPolicy;
use App\Enums\VisitType;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\Billing\VisitPaymentArrangementApprovalPolicyService;
use Database\Seeders\PaymentTimingSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitPaymentArrangementApprovalPolicyTest extends TestCase
{
    use RefreshDatabase;

    private VisitPaymentArrangementApprovalPolicyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        User::factory()->create(); // id 1
        (new PaymentTimingSettingsSeeder)->run();
        $this->service = app(VisitPaymentArrangementApprovalPolicyService::class);
    }

    private function visit(VisitType $type = VisitType::OUTPATIENT): Visit
    {
        return Visit::factory()->create(['patient_id' => Patient::factory()->create()->id, 'visit_type' => $type->value]);
    }

    public function test_prepayment_request_requires_approval_but_not_separate_approver(): void
    {
        $req = $this->service->determine($this->visit(), VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE, [
            'baseline_policy' => 'pay_after_all_services',
        ]);

        $this->assertTrue($req->requiresApproval);
        $this->assertFalse($req->requiresSeparateApprover);
        $this->assertSame('pre_service_request', $req->reasonCode);
    }

    public function test_deferral_against_prepay_baseline_requires_separate_finance_manager(): void
    {
        $req = $this->service->determine($this->visit(), VisitPaymentTimingPolicy::PAY_AFTER_ALL_SERVICES, [
            'baseline_policy' => 'pay_before_service',
        ]);

        $this->assertTrue($req->requiresSeparateApprover);
        $this->assertTrue($req->requiresFinanceManager);
        $this->assertContains('deferral_against_prepay_baseline', $req->context);
    }

    public function test_high_risk_deferral_requires_separate_approval(): void
    {
        $req = $this->service->determine($this->visit(), VisitPaymentTimingPolicy::PAY_AFTER_ALL_SERVICES, [
            'baseline_policy' => 'pay_after_all_services',
            'risk_level' => PatientFinancialRiskLevel::HIGH_RISK->value,
        ]);
        $this->assertTrue($req->requiresSeparateApprover);
        $this->assertTrue($req->requiresFinanceManager);
    }

    public function test_blocked_credit_deferral_requires_separate_approval(): void
    {
        $req = $this->service->determine($this->visit(), VisitPaymentTimingPolicy::RUNNING_BILL, [
            'baseline_policy' => 'pay_after_all_services',
            'risk_level' => PatientFinancialRiskLevel::BLOCKED_CREDIT->value,
        ]);
        $this->assertTrue($req->requiresSeparateApprover);
    }

    public function test_watchlist_requires_finance_review_without_forcing_separate_approver(): void
    {
        $req = $this->service->determine($this->visit(), VisitPaymentTimingPolicy::PAY_AFTER_ALL_SERVICES, [
            'baseline_policy' => 'pay_after_all_services',
            'risk_level' => PatientFinancialRiskLevel::WATCHLIST->value,
            'finance_review' => true,
        ]);
        $this->assertFalse($req->requiresSeparateApprover);
        $this->assertContains('finance_review_recommended', $req->context);
    }

    public function test_running_bill_on_outpatient_requires_approval(): void
    {
        $req = $this->service->determine($this->visit(VisitType::OUTPATIENT), VisitPaymentTimingPolicy::RUNNING_BILL, [
            'baseline_policy' => 'pay_after_all_services',
        ]);
        $this->assertTrue($req->requiresSeparateApprover);
        $this->assertContains('running_bill_on_outpatient', $req->context);
    }
}
