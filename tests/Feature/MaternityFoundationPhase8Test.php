<?php

namespace Tests\Feature;

use App\Enums\AdmissionRequestSource;
use App\Enums\AdmissionStatus;
use App\Enums\BedStatus;
use App\Enums\DepartmentType;
use App\Enums\Gender;
use App\Enums\MaternityCaseStatus;
use App\Enums\PregnancyProfileStatus;
use App\Enums\VisitStatus;
use App\Models\AdmissionRequest;
use App\Models\Bed;
use App\Models\Department;
use App\Models\MaternityCase;
use App\Models\Patient;
use App\Models\PregnancyProfile;
use App\Models\User;
use App\Models\Visit;
use App\Models\Ward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MaternityFoundationPhase8Test extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Department $department;

    private Ward $ward;

    private Bed $bed;

    private Patient $patient;

    private Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();

        config(['audit_streaming.async_writes' => false]);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->department = Department::factory()->create([
            'name' => 'Maternity Ward',
            'code' => 'MAT',
            'type' => DepartmentType::MATERNITY->value,
            'status' => 'active',
        ]);

        $this->user = User::factory()->create(['department_id' => $this->department->id]);
        $role = Role::findOrCreate('Maternity Phase 8 Tester', 'web');
        foreach ($this->permissions() as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->user->assignRole($role);

        $this->patient = Patient::factory()->create([
            'registered_by' => $this->user->id,
            'gender' => Gender::FEMALE,
        ]);

        $this->visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'current_department_id' => $this->department->id,
            'created_by' => $this->user->id,
            'status' => VisitStatus::ADMITTING,
        ]);

        $this->ward = Ward::create([
            'name' => 'Maternity Ward',
            'code' => 'MW01',
            'department_id' => $this->department->id,
            'capacity' => 20,
            'is_active' => true,
        ]);

        $this->bed = Bed::create([
            'ward_id' => $this->ward->id,
            'bed_number' => 'MAT-001',
            'bed_type' => 'standard',
            'status' => BedStatus::AVAILABLE,
            'daily_rate' => 0,
        ]);
    }

    public function test_pregnancy_profile_can_be_created_and_calculates_dates(): void
    {
        $lmp = now()->subWeeks(12)->subDays(3)->toDateString();

        $response = $this->actingAs($this->user)->post(route('admin.maternity.pregnancies.store'), [
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'department_id' => $this->department->id,
            'gravida' => 2,
            'para' => 1,
            'abortions' => 0,
            'living_children' => 1,
            'last_menstrual_period' => $lmp,
            'blood_group' => 'O+',
            'rhesus_status' => 'positive',
            'known_risks' => "Anaemia\nPrevious CS",
            'previous_caesarean' => 1,
        ]);

        $profile = PregnancyProfile::firstOrFail();

        $response->assertRedirect(route('admin.maternity.pregnancies.show', $profile));
        $this->assertSame(PregnancyProfileStatus::ACTIVE, $profile->profile_status);
        $this->assertSame(now()->parse($lmp)->addDays(280)->toDateString(), $profile->estimated_due_date->toDateString());
        $this->assertSame(12, $profile->gestational_age_weeks);
        $this->assertSame(3, $profile->gestational_age_days);
        $this->assertTrue($profile->previous_caesarean);
        $this->assertSame(['Anaemia', 'Previous CS'], $profile->known_risks);
    }

    public function test_pregnancy_profile_can_be_marked_high_risk_and_closed(): void
    {
        $profile = $this->createProfile();

        $this->actingAs($this->user)
            ->patch(route('admin.maternity.pregnancies.status', $profile), [
                'status' => PregnancyProfileStatus::HIGH_RISK->value,
                'reason' => 'Severe hypertension',
            ])
            ->assertRedirect(route('admin.maternity.pregnancies.show', $profile));

        $profile->refresh();
        $this->assertSame(PregnancyProfileStatus::HIGH_RISK, $profile->profile_status);
        $this->assertContains('Severe hypertension', $profile->known_risks);

        $this->actingAs($this->user)
            ->patch(route('admin.maternity.pregnancies.status', $profile), [
                'status' => PregnancyProfileStatus::CLOSED->value,
                'reason' => 'Transferred out',
            ])
            ->assertRedirect(route('admin.maternity.pregnancies.show', $profile));

        $profile->refresh();
        $this->assertSame(PregnancyProfileStatus::CLOSED, $profile->profile_status);
        $this->assertSame('Transferred out', $profile->closure_reason);
        $this->assertNotNull($profile->closed_at);
    }

    public function test_maternity_case_tracks_profile_and_creates_maternity_admission_request(): void
    {
        $profile = $this->createProfile();

        $this->actingAs($this->user)
            ->post(route('admin.maternity.cases.store'), [
                'pregnancy_profile_id' => $profile->id,
                'patient_id' => $profile->patient_id,
                'case_type' => 'maternity_admission',
                'priority' => 'high',
                'risk_level' => 'high',
                'clinical_summary' => 'Observation required',
            ])
            ->assertRedirect();

        $case = MaternityCase::firstOrFail();
        $this->assertSame($profile->id, $case->pregnancy_profile_id);
        $this->assertSame($profile->patient_id, $case->patient_id);
        $this->assertSame($profile->visit_id, $case->visit_id);
        $this->assertSame(MaternityCaseStatus::OPEN, $case->status);

        $this->actingAs($this->user)
            ->post(route('admin.maternity.cases.admission-request', $case), [
                'requested_ward_id' => $this->ward->id,
                'priority' => 'urgent',
                'provisional_diagnosis' => 'Obstetric observation',
                'clinical_summary' => 'Maternity admission requested',
            ])
            ->assertRedirect();

        $request = AdmissionRequest::firstOrFail();
        $this->assertSame(AdmissionRequestSource::MATERNITY, $request->source_type);
        $this->assertSame($case->id, $request->source_id);
        $this->assertSame($this->ward->id, $request->requested_ward_id);
    }

    public function test_dashboard_and_profile_pages_render_future_workflow_boundaries(): void
    {
        $profile = $this->createProfile();

        $this->actingAs($this->user)
            ->get(route('admin.maternity.dashboard'))
            ->assertOk()
            ->assertSee('Maternity Dashboard')
            ->assertSee('ANC Visits Today');

        $this->actingAs($this->user)
            ->get(route('admin.maternity.pregnancies.show', $profile))
            ->assertOk()
            ->assertSee($this->patient->first_name)
            ->assertSee('Open Maternity Case');
    }

    public function test_user_without_maternity_permission_cannot_create_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.maternity.pregnancies.store'), [
                'patient_id' => $this->patient->id,
            ])
            ->assertForbidden();
    }

    public function test_existing_general_admission_still_does_not_require_pregnancy_profile(): void
    {
        $response = $this->actingAs($this->user)->post(route('admin.admissions.store'), [
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'bed_id' => $this->bed->id,
            'admission_type' => 'admission',
            'admitting_diagnosis' => 'Observation',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('admissions', [
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'bed_id' => $this->bed->id,
            'status' => AdmissionStatus::ADMITTED->value,
        ]);
        $this->assertDatabaseCount('pregnancy_profiles', 0);
    }

    private function createProfile(): PregnancyProfile
    {
        return PregnancyProfile::create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'department_id' => $this->department->id,
            'created_by' => $this->user->id,
            'gravida' => 2,
            'para' => 1,
            'last_menstrual_period' => now()->subWeeks(16)->toDateString(),
            'estimated_due_date' => now()->addWeeks(24)->toDateString(),
            'gestational_age_weeks' => 16,
            'gestational_age_days' => 0,
            'blood_group' => 'O+',
            'rhesus_status' => 'positive',
            'profile_status' => PregnancyProfileStatus::ACTIVE,
        ]);
    }

    private function permissions(): array
    {
        return [
            'maternity.view',
            'maternity.dashboard.view',
            'maternity.pregnancy.view',
            'maternity.pregnancy.create',
            'maternity.pregnancy.update',
            'maternity.pregnancy.close',
            'maternity.pregnancy.risk.manage',
            'maternity.case.view',
            'maternity.case.create',
            'maternity.case.update',
            'maternity.case.close',
            'maternity.admission.request',
            'ward.view',
            'ward.admit',
            'admission.requests.view',
            'admission.requests.create',
            'admission.requests.accept',
            'admission.requests.convert',
        ];
    }
}
