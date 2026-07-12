<?php

namespace Tests\Feature;

use App\Data\Billing\PatientFinancialRiskData;
use App\Data\Billing\VisitPaymentArrangementApprovalData;
use App\Data\Billing\VisitPaymentArrangementData;
use App\Enums\PatientFinancialRiskLevel;
use App\Enums\VisitPaymentArrangementStatus;
use App\Enums\VisitPaymentTimingPolicy;
use App\Enums\VisitType;
use App\Exceptions\VisitPaymentArrangementException;
use App\Models\ActivityLog;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitPaymentArrangement;
use App\Services\Billing\PatientFinancialRiskService;
use App\Services\Billing\VisitPaymentArrangementService;
use Database\Seeders\PaymentTimingSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitPaymentArrangementServiceTest extends TestCase
{
    use RefreshDatabase;

    private VisitPaymentArrangementService $service;
    private User $requester;
    private User $approver;

    protected function setUp(): void
    {
        parent::setUp();
        config(['audit_streaming.async_writes' => false]);
        $this->requester = User::factory()->create(); // id 1
        $this->approver = User::factory()->create();
        (new PaymentTimingSettingsSeeder)->run();
        $this->service = app(VisitPaymentArrangementService::class);
    }

    private function visit(VisitType $type = VisitType::OUTPATIENT, ?Patient $patient = null): Visit
    {
        return Visit::factory()->create([
            'patient_id' => ($patient ?? Patient::factory()->create())->id,
            'visit_type' => $type->value,
        ]);
    }

    private function data(string $policy = 'pay_after_all_services'): VisitPaymentArrangementData
    {
        return VisitPaymentArrangementData::fromValidated([
            'requested_policy' => $policy,
            'request_reason' => 'test reason',
            'effective_from' => now()->toDateString(),
        ]);
    }

    private function request(Visit $visit, string $policy = 'pay_after_all_services'): VisitPaymentArrangement
    {
        return $this->service->request($visit, $this->data($policy), $this->requester);
    }

    public function test_request_creates_pending_with_snapshots(): void
    {
        $visit = $this->visit();
        $a = $this->request($visit);

        $this->assertSame(VisitPaymentArrangementStatus::PENDING, $a->status);
        $this->assertSame(VisitPaymentTimingPolicy::PAY_AFTER_ALL_SERVICES, $a->requested_policy);
        $this->assertNotNull($a->baseline_policy_snapshot); // baseline snapshotted
        $this->assertSame(1, $a->history()->count());
        $this->assertTrue(ActivityLog::where('event', 'VISIT_PAYMENT_ARRANGEMENT_REQUESTED')->exists());
    }

    public function test_duplicate_pending_request_is_rejected(): void
    {
        $visit = $this->visit();
        $this->request($visit);
        $this->expectException(VisitPaymentArrangementException::class);
        $this->request($visit);
    }

    public function test_inherit_request_is_rejected(): void
    {
        $this->expectException(VisitPaymentArrangementException::class);
        $this->service->request($this->visit(), $this->data('inherit'), $this->requester);
    }

    public function test_requester_cannot_approve_own_request(): void
    {
        $a = $this->request($this->visit());
        $this->expectException(VisitPaymentArrangementException::class);
        $this->service->approve($a, VisitPaymentArrangementApprovalData::fromValidated([]), $this->requester);
    }

    public function test_approval_sets_approved_policy_and_links_policy_without_changing_baseline(): void
    {
        $visit = $this->visit();
        $a = $this->request($visit);
        $baselineBefore = $visit->paymentPolicy->resolved_policy;

        $a = $this->service->approve($a, VisitPaymentArrangementApprovalData::fromValidated(['decision_reason' => 'ok']), $this->approver);

        $this->assertSame(VisitPaymentArrangementStatus::APPROVED, $a->status);
        $this->assertSame(VisitPaymentTimingPolicy::PAY_AFTER_ALL_SERVICES, $a->approved_policy);
        $policy = $visit->paymentPolicy->fresh();
        $this->assertSame($a->id, $policy->current_approved_arrangement_id);
        $this->assertSame($baselineBefore, $policy->resolved_policy); // baseline untouched
        $this->assertTrue(ActivityLog::where('event', 'VISIT_PAYMENT_ARRANGEMENT_APPROVED')->exists());
    }

    public function test_approving_new_arrangement_replaces_previous(): void
    {
        $visit = $this->visit();
        $first = $this->service->approve($this->request($visit), VisitPaymentArrangementApprovalData::fromValidated([]), $this->approver);

        $second = $this->service->approve($this->request($visit, 'running_bill'), VisitPaymentArrangementApprovalData::fromValidated([]), $this->approver);

        $this->assertSame(VisitPaymentArrangementStatus::REPLACED, $first->fresh()->status);
        $this->assertSame($second->id, $first->fresh()->replaced_by_arrangement_id);
        $this->assertSame(1, VisitPaymentArrangement::where('visit_id', $visit->id)->approved()->count());
        $this->assertSame($second->id, $visit->paymentPolicy->fresh()->current_approved_arrangement_id);
    }

    public function test_reject_withdraw_and_revoke(): void
    {
        $visit = $this->visit();
        $rejected = $this->service->reject($this->request($visit), 'no', $this->approver);
        $this->assertSame(VisitPaymentArrangementStatus::REJECTED, $rejected->status);

        $visit2 = $this->visit();
        $withdrawn = $this->service->withdraw($this->request($visit2), 'changed mind', $this->requester);
        $this->assertSame(VisitPaymentArrangementStatus::WITHDRAWN, $withdrawn->status);

        $visit3 = $this->visit();
        $approved = $this->service->approve($this->request($visit3), VisitPaymentArrangementApprovalData::fromValidated([]), $this->approver);
        $revoked = $this->service->revoke($approved, 'credit cancelled', $this->approver);
        $this->assertSame(VisitPaymentArrangementStatus::REVOKED, $revoked->status);
        $this->assertNull($visit3->paymentPolicy->fresh()->current_approved_arrangement_id);
    }

    public function test_terminal_record_cannot_be_reused(): void
    {
        $rejected = $this->service->reject($this->request($this->visit()), 'no', $this->approver);
        $this->expectException(VisitPaymentArrangementException::class);
        $this->service->approve($rejected, VisitPaymentArrangementApprovalData::fromValidated([]), $this->approver);
    }

    public function test_restore_baseline_removes_current_arrangement(): void
    {
        $visit = $this->visit();
        $this->service->approve($this->request($visit), VisitPaymentArrangementApprovalData::fromValidated([]), $this->approver);

        $restored = $this->service->restoreBaseline($visit, 'return to baseline', $this->approver);

        $this->assertSame(VisitPaymentArrangementStatus::REVOKED, $restored->status);
        $this->assertNull($visit->paymentPolicy->fresh()->current_approved_arrangement_id);
        $this->assertTrue(ActivityLog::where('event', 'VISIT_PAYMENT_BASELINE_RESTORED')->exists());
    }

    public function test_expiry_is_idempotent(): void
    {
        $visit = $this->visit();
        $a = $this->service->approve($this->request($visit), VisitPaymentArrangementApprovalData::fromValidated([
            'expires_at' => now()->subDay()->toDateString(),
        ]), $this->approver);

        $this->assertSame(1, $this->service->expireDue());
        $this->assertSame(VisitPaymentArrangementStatus::EXPIRED, $a->fresh()->status);
        $this->assertSame(0, $this->service->expireDue());
    }

    public function test_stale_risk_blocks_silent_approval(): void
    {
        $patient = Patient::factory()->create();
        $visit = $this->visit(VisitType::OUTPATIENT, $patient);
        $a = $this->request($visit);

        // Change the patient's risk after the request was snapshotted.
        app(PatientFinancialRiskService::class)->createOrClassify($patient, PatientFinancialRiskData::fromValidated([
            'risk_level' => PatientFinancialRiskLevel::HIGH_RISK->value,
            'primary_reason' => 'management_decision', 'reason_details' => 'x',
            'effective_from' => now()->toDateString(),
        ]), $this->approver);

        $this->assertTrue($this->service->riskIsStale($a->fresh()));

        try {
            $this->service->approve($a->fresh(), VisitPaymentArrangementApprovalData::fromValidated([]), $this->approver);
            $this->fail('Stale approval should have been blocked.');
        } catch (VisitPaymentArrangementException $e) {
            $this->assertSame('stale_risk', $e->errorCode);
        }

        // With explicit confirmation it proceeds.
        $approved = $this->service->approve($a->fresh(), VisitPaymentArrangementApprovalData::fromValidated(['confirm_stale_risk' => true]), $this->approver);
        $this->assertSame(VisitPaymentArrangementStatus::APPROVED, $approved->status);
    }
}
