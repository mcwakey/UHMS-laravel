<?php

namespace Tests\Feature;

use App\Models\BloodCrossmatch;
use App\Models\BloodDonor;
use App\Models\BloodRequest;
use App\Models\BloodUnit;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\BloodCrossmatchService;
use App\Services\BloodDonationService;
use App\Services\BloodIssueService;
use App\Services\BloodRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BloodBankWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_donation_creates_quarantined_unit_until_screening_passes(): void
    {
        $user = User::factory()->create();
        $donor = BloodDonor::create([
            'donor_number' => BloodDonor::generateDonorNumber(),
            'first_name' => 'Ama',
            'last_name' => 'Donor',
            'blood_group' => 'O-',
            'status' => BloodDonor::STATUS_ACTIVE,
            'registered_by' => $user->id,
        ]);

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

        app(BloodDonationService::class)->updateScreening($donation, 'PASSED', $user);

        $this->assertDatabaseHas('blood_units', [
            'donation_id' => $donation->id,
            'screening_status' => BloodUnit::SCREENING_PASSED,
            'status' => BloodUnit::STATUS_AVAILABLE,
        ]);
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

        $issue = app(BloodIssueService::class)->issue($request, $unit->refresh(), $user);

        $this->assertDatabaseHas('blood_units', [
            'id' => $unit->id,
            'status' => BloodUnit::STATUS_ISSUED,
        ]);
        $this->assertDatabaseHas('blood_requests', [
            'id' => $request->id,
            'status' => BloodRequest::STATUS_ISSUED,
            'units_issued' => 1,
        ]);

        app(BloodIssueService::class)->recordTransfusion($issue, $user, [
            'notes' => 'No reaction observed.',
        ]);

        $this->assertDatabaseHas('blood_units', [
            'id' => $unit->id,
            'status' => BloodUnit::STATUS_TRANSFUSED,
        ]);
    }

    public function test_blood_bank_and_operational_report_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('admin.blood-bank.dashboard'));
        $this->assertTrue(Route::has('admin.blood-bank.requests.index'));
        $this->assertTrue(Route::has('admin.reports.dashboard'));
        $this->assertTrue(Route::has('admin.reports.consultations'));
        $this->assertTrue(Route::has('admin.reports.blood-bank'));
    }

    private function requestWithScreenedUnit(string $requestGroup, string $unitGroup): array
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create([
            'registered_by' => $user->id,
            'blood_group' => $requestGroup,
        ]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $user->id,
        ]);
        $donor = BloodDonor::create([
            'donor_number' => BloodDonor::generateDonorNumber(),
            'first_name' => 'Kofi',
            'last_name' => 'Donor',
            'blood_group' => $unitGroup,
            'status' => BloodDonor::STATUS_ACTIVE,
            'registered_by' => $user->id,
        ]);

        $donation = app(BloodDonationService::class)->recordDonation($donor, [
            'blood_group' => $unitGroup,
            'component_type' => 'WHOLE_BLOOD',
        ], $user);
        app(BloodDonationService::class)->updateScreening($donation, 'PASSED', $user);
        $unit = $donation->refresh()->unit;

        $request = app(BloodRequestService::class)->createForVisit($visit, [
            'blood_group' => $requestGroup,
            'component_type' => 'WHOLE_BLOOD',
            'units_requested' => 1,
            'priority' => 'URGENT',
            'indication' => 'Anaemia',
        ], $user);
        app(BloodRequestService::class)->approve($request, $user);

        return [$user, $request->refresh(), $unit->refresh()];
    }
}
