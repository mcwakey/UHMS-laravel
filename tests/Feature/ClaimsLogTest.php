<?php

namespace Tests\Feature;

use App\Enums\ClaimStatus;
use App\Models\Claim;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\ActivityLogService;
use App\Services\ClaimService;
use App\Services\Claims\ClaimStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Claims lifecycle must surface on the patient timeline (module CLAIMS) via the
 * ClaimStatusService funnel + ClaimService non-transition logs — without
 * duplicating billing/payment/invoice logs.
 */
class ClaimsLogTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;
    private Visit $visit;
    private int $providerId;
    private int $insuranceTypeId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->patient = Patient::factory()->create();
        $this->visit = Visit::factory()->create(['patient_id' => $this->patient->id, 'created_by' => $this->user->id]);

        $type = \App\Models\InsuranceType::create(['name' => 'NHIS', 'code' => 'NHIS', 'is_active' => true]);
        $this->insuranceTypeId = $type->id;
        $this->providerId = \App\Models\InsuranceProvider::create([
            'name' => 'NHIA', 'short_name' => 'NHIA', 'code' => 'NHIA', 'type' => 'nhia',
            'insurance_type_id' => $type->id, 'is_active' => true,
        ])->id;
    }

    private function claim(): Claim
    {
        return Claim::create([
            'claim_number' => 'CLM-' . fake()->unique()->numberBetween(10000, 99999),
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'insurance_provider_id' => $this->providerId,
            'insurance_type_id' => $this->insuranceTypeId,
            'claim_date' => now()->toDateString(),
            'period_from' => now()->startOfMonth()->toDateString(),
            'period_to' => now()->endOfMonth()->toDateString(),
            'total_amount' => 500,
            'total_claim_amount' => 500,
            'status' => ClaimStatus::DRAFT,
            'created_by' => $this->user->id,
        ]);
    }

    private function timeline(): \Illuminate\Support\Collection
    {
        return app(ActivityLogService::class)->getPatientTimeline($this->patient)->get();
    }

    public function test_claim_status_lifecycle_logs_on_patient_timeline(): void
    {
        $claim = $this->claim();
        $status = app(ClaimStatusService::class);

        $status->transition($claim, ClaimStatus::SUBMITTED, $this->user, 'Submitted to NHIA');
        $status->transition($claim->fresh(), ClaimStatus::UNDER_REVIEW, $this->user);
        $status->transition($claim->fresh(), ClaimStatus::APPROVED, $this->user);
        $status->transition($claim->fresh(), ClaimStatus::PAID, $this->user);

        $events = $this->timeline()->pluck('event')->all();
        $this->assertContains('CLAIM_SUBMITTED', $events);
        $this->assertContains('CLAIM_REVIEWED', $events);
        $this->assertContains('CLAIM_APPROVED', $events);
        $this->assertContains('CLAIM_PAYMENT_RECORDED', $events);

        $submitted = $this->timeline()->firstWhere('event', 'CLAIM_SUBMITTED');
        $this->assertSame('CLAIMS', $submitted->log_name);
        $this->assertSame($this->patient->id, (int) $submitted->patient_id);
        $this->assertSame($claim->id, (int) $submitted->properties['claim_id']);
        $this->assertSame('draft', $submitted->properties['old']['status']);
        $this->assertSame('submitted', $submitted->properties['attributes']['status']);
    }

    public function test_claim_rejection_logs_reason(): void
    {
        $claim = $this->claim();
        $status = app(ClaimStatusService::class);
        $status->transition($claim, ClaimStatus::SUBMITTED, $this->user);
        $status->transition($claim->fresh(), ClaimStatus::REJECTED, $this->user, 'Missing CCC code');

        $log = $this->timeline()->firstWhere('event', 'CLAIM_REJECTED');
        $this->assertNotNull($log);
        $this->assertSame('Missing CCC code', $log->properties['reason']);
        $this->assertStringContainsString('Missing CCC code', $log->description);
    }

    public function test_verification_code_and_item_actions_log(): void
    {
        $claim = $this->claim();
        $claims = app(ClaimService::class);

        $claims->updateVerificationCode($claim, 'CCC-001');
        $item = $claims->addItem($claim->fresh(), [
            'service_name' => 'Consultation fee', 'quantity' => 1, 'unit_price' => 80,
        ]);

        $events = $this->timeline()->pluck('event')->all();
        $this->assertContains('CCC_CODE_UPDATED', $events);
        $this->assertContains('CLAIM_ITEM_ADDED', $events);

        $itemLog = $this->timeline()->firstWhere('event', 'CLAIM_ITEM_ADDED');
        $this->assertSame((int) $item->id, (int) $itemLog->properties['claim_item_id']);
        $this->assertStringContainsString('Consultation fee', $itemLog->description);
    }
}
