<?php

namespace Tests\Feature;

use App\Enums\AdmissionRequestSource;
use App\Enums\AntenatalDangerSign;
use App\Enums\AntenatalReferralType;
use App\Enums\AntenatalRiskFlag;
use App\Enums\AntenatalVisitStatus;
use App\Enums\BedStatus;
use App\Enums\DepartmentType;
use App\Enums\Gender;
use App\Enums\PregnancyProfileStatus;
use App\Enums\VisitStatus;
use App\Models\AdmissionRequest;
use App\Models\AntenatalVisit;
use App\Models\Bed;
use App\Models\Department;
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

class AntenatalCarePhase9Test extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Department $department;

    private Ward $ward;

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
        $role = Role::findOrCreate('Maternity Phase 9 Tester', 'web');
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
            'status' => VisitStatus::ACTIVE,
        ]);

        $this->ward = Ward::create([
            'name' => 'Maternity Ward',
            'code' => 'MW01',
            'department_id' => $this->department->id,
            'capacity' => 20,
            'is_active' => true,
        ]);

        Bed::create([
            'ward_id' => $this->ward->id,
            'bed_number' => 'MAT-001',
            'bed_type' => 'standard',
            'status' => BedStatus::AVAILABLE,
            'daily_rate' => 0,
        ]);
    }

    public function test_anc_visit_can_be_recorded_and_links_context(): void
    {
        $profile = $this->createProfile();

        $this->actingAs($this->user)
            ->post(route('admin.maternity.pregnancies.antenatal.store', $profile), $this->ancPayload())
            ->assertRedirect();

        $anc = AntenatalVisit::firstOrFail();
        $this->assertSame($profile->id, $anc->pregnancy_profile_id);
        $this->assertSame($this->patient->id, $anc->patient_id);
        $this->assertSame($this->visit->id, $anc->visit_id);
        $this->assertSame(AntenatalVisitStatus::HIGH_RISK, $anc->status);
        $this->assertContains(AntenatalDangerSign::REDUCED_FETAL_MOVEMENT->value, $anc->danger_signs);
        $this->assertContains(AntenatalRiskFlag::HIGH_BLOOD_PRESSURE->value, $anc->risk_flags);
        $this->assertSame(PregnancyProfileStatus::HIGH_RISK, $profile->fresh()->profile_status);
    }

    public function test_anc_visit_can_be_updated_and_next_visit_scheduled(): void
    {
        $profile = $this->createProfile();
        $anc = AntenatalVisit::create($this->modelPayload($profile));

        $this->actingAs($this->user)
            ->patch(route('admin.maternity.antenatal.update', $anc), [
                'visit_date' => now()->format('Y-m-d H:i:s'),
                'gestational_age_weeks' => 25,
                'gestational_age_days' => 2,
                'blood_pressure_systolic' => 118,
                'blood_pressure_diastolic' => 76,
                'assessment' => 'Stable review',
                'next_visit_date' => now()->addWeeks(4)->toDateString(),
            ])
            ->assertRedirect(route('admin.maternity.antenatal.show', $anc));

        $anc->refresh();
        $this->assertSame(25, $anc->gestational_age_weeks);
        $this->assertSame(AntenatalVisitStatus::FOLLOW_UP_SCHEDULED, $anc->status);
        $this->assertNotNull($anc->next_visit_date);
    }

    public function test_anc_history_detail_and_dashboard_render(): void
    {
        $profile = $this->createProfile();
        $anc = AntenatalVisit::create($this->modelPayload($profile, [
            'risk_flags' => [AntenatalRiskFlag::LOW_HAEMOGLOBIN->value],
            'haemoglobin' => 9.5,
        ]));

        $this->actingAs($this->user)
            ->get(route('admin.maternity.pregnancies.antenatal.index', $profile))
            ->assertOk()
            ->assertSee('ANC History')
            ->assertSee('24w 3d');

        $this->actingAs($this->user)
            ->get(route('admin.maternity.antenatal.show', $anc))
            ->assertOk()
            ->assertSee('ANC Visit')
            ->assertSee('Low haemoglobin');

        $this->actingAs($this->user)
            ->get(route('admin.maternity.dashboard'))
            ->assertOk()
            ->assertSee('ANC Visits Today')
            ->assertSee('Recent ANC Visits');
    }

    public function test_non_permitted_user_cannot_record_anc_visit(): void
    {
        $profile = $this->createProfile();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.maternity.pregnancies.antenatal.store', $profile), $this->ancPayload())
            ->assertForbidden();
    }

    public function test_anc_referral_and_admission_request_are_explicit_user_actions(): void
    {
        $profile = $this->createProfile();
        $anc = AntenatalVisit::create($this->modelPayload($profile));

        $this->actingAs($this->user)
            ->post(route('admin.maternity.antenatal.referral', $anc), [
                'referral_type' => AntenatalReferralType::MATERNITY_ADMISSION->value,
                'referral_reason' => 'Admit for obstetric observation',
            ])
            ->assertRedirect(route('admin.maternity.antenatal.show', $anc));

        $anc->refresh();
        $this->assertSame(AntenatalReferralType::MATERNITY_ADMISSION, $anc->referral_type);
        $this->assertNotNull($anc->maternity_case_id);

        $this->actingAs($this->user)
            ->post(route('admin.maternity.antenatal.admission-request', $anc), [
                'requested_ward_id' => $this->ward->id,
                'priority' => 'urgent',
                'clinical_summary' => 'ANC admission requested',
            ])
            ->assertRedirect();

        $request = AdmissionRequest::firstOrFail();
        $this->assertSame(AdmissionRequestSource::MATERNITY, $request->source_type);
        $this->assertSame($anc->id, $request->source_id);
        $this->assertSame($this->ward->id, $request->requested_ward_id);
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
            'last_menstrual_period' => now()->subWeeks(24)->subDays(3)->toDateString(),
            'estimated_due_date' => now()->addWeeks(16)->toDateString(),
            'gestational_age_weeks' => 24,
            'gestational_age_days' => 3,
            'profile_status' => PregnancyProfileStatus::ACTIVE,
        ]);
    }

    private function ancPayload(array $overrides = []): array
    {
        return array_merge([
            'visit_date' => now()->format('Y-m-d H:i:s'),
            'gestational_age_weeks' => 24,
            'gestational_age_days' => 3,
            'weight_kg' => 72.5,
            'blood_pressure_systolic' => 150,
            'blood_pressure_diastolic' => 96,
            'fundal_height_cm' => 24,
            'fetal_heart_rate' => 142,
            'danger_signs' => [AntenatalDangerSign::REDUCED_FETAL_MOVEMENT->value],
            'assessment' => 'ANC review with warning sign',
            'plan' => 'Review and monitor',
            'next_visit_date' => now()->addWeeks(2)->toDateString(),
        ], $overrides);
    }

    private function modelPayload(PregnancyProfile $profile, array $overrides = []): array
    {
        return array_merge([
            'pregnancy_profile_id' => $profile->id,
            'patient_id' => $profile->patient_id,
            'visit_id' => $profile->visit_id,
            'department_id' => $profile->department_id,
            'recorded_by' => $this->user->id,
            'visit_number' => 1,
            'visit_date' => now(),
            'gestational_age_weeks' => 24,
            'gestational_age_days' => 3,
            'status' => AntenatalVisitStatus::RECORDED,
            'created_by' => $this->user->id,
        ], $overrides);
    }

    private function permissions(): array
    {
        return [
            'maternity.view',
            'maternity.dashboard.view',
            'maternity.pregnancy.view',
            'maternity.pregnancy.create',
            'maternity.pregnancy.update',
            'maternity.case.view',
            'maternity.case.create',
            'maternity.admission.request',
            'maternity.anc.view',
            'maternity.anc.record',
            'maternity.anc.update',
            'maternity.anc.cancel',
            'maternity.anc.risk.manage',
            'maternity.anc.referral.create',
            'maternity.anc.admission.request',
            'maternity.anc.reports.view',
            'admission.requests.view',
            'admission.requests.create',
        ];
    }
}
