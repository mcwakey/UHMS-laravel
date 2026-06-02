<?php

namespace Tests\Feature;

use App\Models\BloodCrossmatch;
use App\Models\BloodDonation;
use App\Models\BloodDonationTest;
use App\Models\BloodDonor;
use App\Models\BloodDonorScreening;
use App\Models\BloodRequest;
use App\Models\BloodUnit;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\BloodBankCompatibilityService;
use App\Services\BloodCrossmatchService;
use App\Services\BloodDonationService;
use App\Services\BloodDonorScreeningService;
use App\Services\BloodIssueService;
use App\Services\BloodRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BloodBankWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /* ───────────── Donation + screening ───────────── */

    public function test_donation_creates_quarantined_unit_until_screening_passes(): void
    {
        $user = User::factory()->create();
        $donor = $this->eligibleDonor($user, 'O-');

        $donation = app(BloodDonationService::class)->recordDonation($donor, [
            'blood_group' => 'O-',
            'component_type' => 'WHOLE_BLOOD',
            'volume_ml' => 450,
        ], $user);

        $this->assertDatabaseHas('blood_units', [
            'donation_id' => $donation->id,
            'screening_status' => BloodUnit::SCREENING_PENDING,
            'status' => BloodUnit::STATUS_QUARANTINED,
        ]);

        // Seeds the configured infectious-disease panel.
        $this->assertGreaterThan(0, $donation->tests()->count());

        app(BloodDonationService::class)->updateScreening($donation, 'PASSED', $user);

        $this->assertDatabaseHas('blood_units', [
            'donation_id' => $donation->id,
            'screening_status' => BloodUnit::SCREENING_PASSED,
            'status' => BloodUnit::STATUS_AVAILABLE,
        ]);
    }

    public function test_failing_screening_test_rejects_unit(): void
    {
        $user = User::factory()->create();
        $donor = $this->eligibleDonor($user, 'A+');
        $donation = app(BloodDonationService::class)->recordDonation($donor, ['blood_group' => 'A+'], $user);

        app(BloodDonationService::class)->recordTest($donation, 'HIV', BloodDonationTest::RESULT_REACTIVE, $user);

        $this->assertDatabaseHas('blood_donations', ['id' => $donation->id, 'screening_status' => BloodDonation::SCREENING_FAILED]);
        $this->assertDatabaseHas('blood_units', ['donation_id' => $donation->id, 'status' => BloodUnit::STATUS_REJECTED]);
    }

    public function test_all_negative_verified_tests_make_unit_available(): void
    {
        $user = User::factory()->create();
        $donor = $this->eligibleDonor($user, 'A+');
        $donation = app(BloodDonationService::class)->recordDonation($donor, ['blood_group' => 'A+'], $user);

        foreach ($donation->tests()->where('mandatory', true)->get() as $test) {
            app(BloodDonationService::class)->recordTest($donation, $test->test_code, BloodDonationTest::RESULT_NEGATIVE, $user);
        }
        // Not yet available — verification required.
        $this->assertDatabaseMissing('blood_units', ['donation_id' => $donation->id, 'status' => BloodUnit::STATUS_AVAILABLE]);

        foreach ($donation->tests()->where('mandatory', true)->get() as $test) {
            app(BloodDonationService::class)->verifyTest($test, $user);
        }

        $this->assertDatabaseHas('blood_units', ['donation_id' => $donation->id, 'status' => BloodUnit::STATUS_AVAILABLE]);
    }

    /* ───────────── Donor screening / eligibility ───────────── */

    public function test_low_hemoglobin_suggests_temporary_deferral_and_blocks_donation(): void
    {
        $user = User::factory()->create();
        $donor = BloodDonor::create([
            'donor_number' => BloodDonor::generateDonorNumber(),
            'first_name' => 'Yaa', 'last_name' => 'Test', 'gender' => 'FEMALE',
            'date_of_birth' => now()->subYears(30),
            'blood_group' => 'O+', 'status' => BloodDonor::STATUS_ACTIVE,
            'screening_status' => BloodDonor::SCREENING_REGISTERED, 'registered_by' => $user->id,
        ]);

        $svc = app(BloodDonorScreeningService::class);
        $screening = $svc->startScreening($donor, $user);
        $svc->recordQuestionnaire($screening, [], $user);
        $svc->recordPhysicalAssessment($screening, ['weight_kg' => 60, 'hemoglobin' => 9.0, 'temperature_c' => 36.8, 'pulse' => 72], $user);

        $suggestion = $svc->suggestEligibility($screening->refresh());
        $this->assertSame(BloodDonorScreening::DECISION_TEMP_DEFERRED, $suggestion['decision']);

        $svc->decideEligibility($screening, $user);
        $donor->refresh();
        $this->assertSame(BloodDonor::SCREENING_TEMP_DEFERRED, $donor->screening_status);
        $this->assertFalse($donor->canDonate());

        $this->expectException(ValidationException::class);
        app(BloodDonationService::class)->recordDonation($donor, ['blood_group' => 'O+'], $user);
    }

    public function test_override_eligibility_requires_reason(): void
    {
        $user = User::factory()->create();
        $donor = BloodDonor::create([
            'donor_number' => BloodDonor::generateDonorNumber(),
            'first_name' => 'Kojo', 'last_name' => 'Test', 'gender' => 'MALE',
            'date_of_birth' => now()->subYears(40), 'blood_group' => 'B+',
            'status' => BloodDonor::STATUS_ACTIVE, 'screening_status' => BloodDonor::SCREENING_REGISTERED,
            'registered_by' => $user->id,
        ]);

        $svc = app(BloodDonorScreeningService::class);
        $screening = $svc->startScreening($donor, $user);
        $svc->recordPhysicalAssessment($screening, ['weight_kg' => 40, 'hemoglobin' => 15], $user); // underweight

        $this->expectException(ValidationException::class);
        $svc->decideEligibility($screening, $user, BloodDonorScreening::DECISION_ELIGIBLE, [], true);
    }

    /* ───────────── Compatibility engine ───────────── */

    public function test_red_cell_compatibility_rules(): void
    {
        $c = app(BloodBankCompatibilityService::class);

        // A+ recipient may receive A+, A-, O+, O-
        $this->assertEqualsCanonicalizing(['A-', 'A+', 'O-', 'O+'], $c->compatibleGroupsFor('A+', 'WHOLE_BLOOD'));
        // O- recipient may receive only O-
        $this->assertSame(['O-'], $c->compatibleGroupsFor('O-', 'WHOLE_BLOOD'));
        // AB+ universal recipient
        $this->assertCount(8, $c->compatibleGroupsFor('AB+', 'WHOLE_BLOOD'));

        $this->assertTrue($c->isCompatible('O-', 'A+', 'WHOLE_BLOOD'));   // donor O- to recipient A+
        $this->assertFalse($c->isCompatible('A+', 'O-', 'WHOLE_BLOOD'));  // donor A+ to recipient O- (incompatible)
        // Rh-negative recipient cannot take Rh-positive
        $this->assertFalse($c->isCompatible('O+', 'O-', 'WHOLE_BLOOD'));  // donor O+ to recipient O-
    }

    public function test_plasma_compatibility_uses_reverse_matrix(): void
    {
        $c = app(BloodBankCompatibilityService::class);
        // AB plasma to everyone; donor AB plasma to O recipient is compatible.
        $this->assertSame(BloodBankCompatibilityService::COMPATIBLE, $c->evaluate('O+', 'AB+', 'PLASMA')['status']);
        // A plasma to B recipient is NOT compatible.
        $this->assertSame(BloodBankCompatibilityService::INCOMPATIBLE, $c->evaluate('B+', 'A+', 'PLASMA')['status']);
    }

    /* ───────────── Recipient + crossmatch + issue ───────────── */

    public function test_blood_request_captures_recipient_details(): void
    {
        [$user, $request] = $this->approvedRequest('A+', 'O-');

        $this->assertNotNull($request->recipient);
        $this->assertSame('A+', $request->recipient->patient_blood_group);
        $this->assertDatabaseHas('blood_recipients', ['blood_request_id' => $request->id]);
    }

    public function test_incompatible_crossmatch_cannot_be_issued(): void
    {
        [$user, $request, $unit] = $this->requestWithScreenedUnit('O-', 'A+');

        $crossmatch = app(BloodCrossmatchService::class)->perform($request, $unit, $user);
        $this->assertSame(BloodCrossmatch::RESULT_INCOMPATIBLE, $crossmatch->result);

        $this->expectException(ValidationException::class);
        app(BloodIssueService::class)->issue($request, $unit, $user);
    }

    public function test_compatible_crossmatch_allows_single_issue_and_transfusion_record(): void
    {
        [$user, $request, $unit] = $this->requestWithScreenedUnit('A+', 'O-');

        $crossmatch = app(BloodCrossmatchService::class)->perform($request, $unit, $user);
        $this->assertSame(BloodCrossmatch::RESULT_COMPATIBLE, $crossmatch->result);
        $this->assertSame('A+', $crossmatch->recipient_blood_group);

        $issue = app(BloodIssueService::class)->issue($request, $unit->refresh(), $user);

        $this->assertDatabaseHas('blood_units', ['id' => $unit->id, 'status' => BloodUnit::STATUS_ISSUED]);
        $this->assertDatabaseHas('blood_requests', [
            'id' => $request->id, 'status' => BloodRequest::STATUS_ISSUED, 'units_issued' => 1,
        ]);

        app(BloodIssueService::class)->recordTransfusion($issue, $user, ['notes' => 'No reaction observed.']);
        $this->assertDatabaseHas('blood_units', ['id' => $unit->id, 'status' => BloodUnit::STATUS_TRANSFUSED]);
    }

    public function test_transfusion_reaction_is_recorded(): void
    {
        [$user, $request, $unit] = $this->requestWithScreenedUnit('A+', 'O-');
        app(BloodCrossmatchService::class)->perform($request, $unit, $user);
        $issue = app(BloodIssueService::class)->issue($request, $unit->refresh(), $user);

        app(BloodIssueService::class)->recordReaction($issue, $user, [
            'reaction_type' => 'FEVER',
            'reaction_notes' => 'Febrile non-haemolytic reaction.',
            'outcome' => 'STOPPED_DUE_TO_REACTION',
        ]);

        $this->assertDatabaseHas('blood_issues', [
            'id' => $issue->id, 'reaction_occurred' => true,
            'reaction_type' => 'FEVER', 'outcome' => 'STOPPED_DUE_TO_REACTION',
        ]);
    }

    public function test_expired_unit_cannot_be_issued(): void
    {
        [$user, $request, $unit] = $this->requestWithScreenedUnit('A+', 'O-');
        app(BloodCrossmatchService::class)->perform($request, $unit, $user);
        $unit->update(['expiry_date' => now()->subDay()->toDateString()]);

        $this->expectException(ValidationException::class);
        app(BloodIssueService::class)->issue($request, $unit->refresh(), $user);
    }

    public function test_emergency_release_bypasses_crossmatch_with_reason(): void
    {
        [$user, $request, $unit] = $this->requestWithScreenedUnit('O-', 'A+'); // incompatible

        $issue = app(BloodIssueService::class)->issue($request, $unit, $user, [
            'emergency' => true,
            'emergency_release_type' => 'EMERGENCY_INCOMPATIBLE_OVERRIDE',
            'emergency_release_reason' => 'Massive haemorrhage, no compatible stock.',
        ]);

        $this->assertTrue((bool) $issue->is_emergency_release);
        $this->assertDatabaseHas('blood_units', ['id' => $unit->id, 'status' => BloodUnit::STATUS_ISSUED]);
    }

    public function test_emergency_release_requires_reason(): void
    {
        [$user, $request, $unit] = $this->requestWithScreenedUnit('O-', 'A+');

        $this->expectException(ValidationException::class);
        app(BloodIssueService::class)->issue($request, $unit, $user, [
            'emergency' => true,
            'emergency_release_type' => 'EMERGENCY_INCOMPATIBLE_OVERRIDE',
        ]);
    }

    /* ───────────── Routes ───────────── */

    public function test_blood_bank_and_operational_report_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('admin.blood-bank.dashboard'));
        $this->assertTrue(Route::has('admin.blood-bank.requests.index'));
        $this->assertTrue(Route::has('admin.blood-bank.donors.screening.questionnaire'));
        $this->assertTrue(Route::has('admin.blood-bank.requests.recipient.update'));
        $this->assertTrue(Route::has('admin.blood-bank.crossmatches.verify'));
        $this->assertTrue(Route::has('admin.reports.dashboard'));
        $this->assertTrue(Route::has('admin.reports.blood-bank'));
    }

    /* ───────────── Helpers ───────────── */

    private function eligibleDonor(User $user, string $group): BloodDonor
    {
        return BloodDonor::create([
            'donor_number' => BloodDonor::generateDonorNumber(),
            'first_name' => 'Eligible', 'last_name' => 'Donor', 'gender' => 'MALE',
            'date_of_birth' => now()->subYears(30), 'blood_group' => $group,
            'status' => BloodDonor::STATUS_ACTIVE,
            'screening_status' => BloodDonor::SCREENING_ELIGIBLE,
            'registered_by' => $user->id,
        ]);
    }

    private function approvedRequest(string $requestGroup, string $unitGroup): array
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['registered_by' => $user->id, 'blood_group' => $requestGroup]);
        $visit = Visit::factory()->create(['patient_id' => $patient->id, 'created_by' => $user->id]);

        $request = app(BloodRequestService::class)->createForVisit($visit, [
            'blood_group' => $requestGroup,
            'component_type' => 'WHOLE_BLOOD',
            'units_requested' => 1,
            'priority' => 'URGENT',
            'clinical_indication' => 'Severe anemia',
        ], $user);
        app(BloodRequestService::class)->approve($request, $user);

        return [$user, $request->refresh()];
    }

    private function requestWithScreenedUnit(string $requestGroup, string $unitGroup): array
    {
        [$user, $request] = $this->approvedRequest($requestGroup, $unitGroup);

        $donor = $this->eligibleDonor($user, $unitGroup);
        $donation = app(BloodDonationService::class)->recordDonation($donor, [
            'blood_group' => $unitGroup, 'component_type' => 'WHOLE_BLOOD',
        ], $user);
        app(BloodDonationService::class)->updateScreening($donation, 'PASSED', $user);
        $unit = $donation->refresh()->unit;

        return [$user, $request->refresh(), $unit->refresh()];
    }
}
