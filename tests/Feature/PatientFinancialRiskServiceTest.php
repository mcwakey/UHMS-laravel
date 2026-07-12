<?php

namespace Tests\Feature;

use App\Data\Billing\PatientFinancialRiskData;
use App\Enums\PatientFinancialRiskLevel;
use App\Enums\PatientFinancialRiskStatus;
use App\Exceptions\InvalidFinancialRiskTransitionException;
use App\Models\ActivityLog;
use App\Models\Patient;
use App\Models\PatientFinancialRiskProfile;
use App\Models\User;
use App\Services\Billing\PatientFinancialRiskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientFinancialRiskServiceTest extends TestCase
{
    use RefreshDatabase;

    private PatientFinancialRiskService $service;
    private Patient $patient;
    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        config(['audit_streaming.async_writes' => false]);
        $this->service = app(PatientFinancialRiskService::class);
        $this->actor = User::factory()->create(); // becomes id 1 (PatientFactory registers_by 1)
        $this->patient = Patient::factory()->create();
    }

    private function data(array $overrides = []): PatientFinancialRiskData
    {
        return PatientFinancialRiskData::fromValidated(array_merge([
            'risk_level' => PatientFinancialRiskLevel::HIGH_RISK->value,
            'primary_reason' => 'management_decision',
            'reason_details' => 'Board decision',
            'effective_from' => now()->toDateString(),
        ], $overrides));
    }

    public function test_classification_creates_active_profile_with_history_and_audit(): void
    {
        $profile = $this->service->createOrClassify($this->patient, $this->data(), $this->actor);

        $this->assertSame(PatientFinancialRiskStatus::ACTIVE, $profile->status);
        $this->assertSame(PatientFinancialRiskLevel::HIGH_RISK, $profile->risk_level);
        $this->assertSame($this->actor->id, $profile->set_by);
        $this->assertSame(1, $this->patient->financialRiskHistory()->count());
        $this->assertTrue(ActivityLog::where('event', 'PATIENT_FINANCIAL_RISK_CREATED')->exists());
    }

    public function test_only_one_active_restrictive_profile_can_exist(): void
    {
        $first = $this->service->createOrClassify($this->patient, $this->data(), $this->actor);
        // A second "classify" mutates the current profile rather than creating another.
        $second = $this->service->createOrClassify($this->patient, $this->data(['risk_level' => PatientFinancialRiskLevel::BLOCKED_CREDIT->value]), $this->actor);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, PatientFinancialRiskProfile::where('patient_id', $this->patient->id)->active()->count());
        $this->assertSame(PatientFinancialRiskLevel::BLOCKED_CREDIT, $second->fresh()->risk_level);
    }

    public function test_update_appends_history(): void
    {
        $profile = $this->service->createOrClassify($this->patient, $this->data(), $this->actor);
        $this->service->update($profile, $this->data(['risk_level' => PatientFinancialRiskLevel::WATCHLIST->value]), $this->actor);

        $this->assertSame(2, $this->patient->financialRiskHistory()->count());
        $this->assertTrue(ActivityLog::where('event', 'PATIENT_FINANCIAL_RISK_UPDATED')->exists());
    }

    public function test_review_suspend_reactivate_and_clear_transitions(): void
    {
        $profile = $this->service->createOrClassify($this->patient, $this->data(), $this->actor);

        $this->service->submitForReview($profile, $this->actor);
        $this->assertSame(PatientFinancialRiskStatus::UNDER_REVIEW, $profile->fresh()->status);

        $this->service->completeReview($profile->fresh(), $this->actor);
        $this->assertSame(PatientFinancialRiskStatus::ACTIVE, $profile->fresh()->status);
        $this->assertNotNull($profile->fresh()->reviewed_at);

        $this->service->suspend($profile->fresh(), $this->actor, 'hold');
        $this->assertSame(PatientFinancialRiskStatus::SUSPENDED, $profile->fresh()->status);

        $this->service->reactivate($profile->fresh(), $this->actor, 'resume');
        $this->assertSame(PatientFinancialRiskStatus::ACTIVE, $profile->fresh()->status);

        $this->service->clear($profile->fresh(), $this->actor, 'resolved');
        $cleared = $profile->fresh();
        $this->assertSame(PatientFinancialRiskStatus::CLEARED, $cleared->status);
        $this->assertSame('resolved', $cleared->clearance_reason);
        $this->assertSame($this->actor->id, $cleared->cleared_by);
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $profile = $this->service->createOrClassify($this->patient, $this->data(), $this->actor);
        $this->service->clear($profile, $this->actor, 'done');

        $this->expectException(InvalidFinancialRiskTransitionException::class);
        $this->service->suspend($profile->fresh(), $this->actor, 'nope');
    }

    public function test_clearing_then_reclassifying_creates_new_traceable_profile(): void
    {
        $first = $this->service->createOrClassify($this->patient, $this->data(), $this->actor);
        $this->service->clear($first, $this->actor, 'resolved');

        $second = $this->service->createOrClassify($this->patient, $this->data(), $this->actor);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(2, PatientFinancialRiskProfile::where('patient_id', $this->patient->id)->count());
        $this->assertSame(1, PatientFinancialRiskProfile::where('patient_id', $this->patient->id)->active()->count());
    }

    public function test_no_op_update_creates_no_history(): void
    {
        $profile = $this->service->createOrClassify($this->patient, $this->data(), $this->actor);
        $before = $this->patient->financialRiskHistory()->count();

        $this->service->update($profile->fresh(), $this->data(), $this->actor);

        $this->assertSame($before, $this->patient->financialRiskHistory()->count());
    }

    public function test_expiry_transitions_due_profiles_only_and_is_idempotent(): void
    {
        $due = PatientFinancialRiskProfile::factory()->for($this->patient)
            ->status(PatientFinancialRiskStatus::ACTIVE)
            ->expiringOn(now()->subDay()->toDateString())->create();
        $future = PatientFinancialRiskProfile::factory()->for(Patient::factory())
            ->status(PatientFinancialRiskStatus::ACTIVE)
            ->expiringOn(now()->addWeek()->toDateString())->create();
        $cleared = PatientFinancialRiskProfile::factory()->for(Patient::factory())
            ->cleared()->expiringOn(now()->subDay()->toDateString())->create();

        $count = $this->service->expireDueProfiles();
        $this->assertSame(1, $count);
        $this->assertSame(PatientFinancialRiskStatus::EXPIRED, $due->fresh()->status);
        $this->assertSame(PatientFinancialRiskStatus::ACTIVE, $future->fresh()->status);
        $this->assertSame(PatientFinancialRiskStatus::CLEARED, $cleared->fresh()->status);

        // Idempotent: a second run expires nothing more and adds no duplicate history.
        $historyAfterFirst = $due->history()->count();
        $this->assertSame(0, $this->service->expireDueProfiles());
        $this->assertSame($historyAfterFirst, $due->fresh()->history()->count());
    }
}
