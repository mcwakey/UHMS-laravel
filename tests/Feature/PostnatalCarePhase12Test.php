<?php

namespace Tests\Feature;

use App\Enums\AdmissionStatus;
use App\Enums\BedStatus;
use App\Enums\BleedingStatus;
use App\Enums\BreastfeedingStatus;
use App\Enums\DeliveryMode;
use App\Enums\DeliveryOutcome;
use App\Enums\DeliveryRecordStatus;
use App\Enums\DepartmentType;
use App\Enums\Gender;
use App\Enums\JaundiceStatus;
use App\Enums\LaborEpisodeStatus;
use App\Enums\LaborStage;
use App\Enums\MaternityRiskLevel;
use App\Enums\NewbornBreathingStatus;
use App\Enums\NewbornCordStatus;
use App\Enums\NewbornFeedingStatus;
use App\Enums\NewbornOutcome;
use App\Enums\NewbornRecordStatus;
use App\Enums\NewbornSex;
use App\Enums\PostnatalCaseStatus;
use App\Enums\PostnatalMotherDangerSign;
use App\Enums\PostnatalNewbornDangerSign;
use App\Enums\PregnancyProfileStatus;
use App\Enums\VisitStatus;
use App\Models\Admission;
use App\Models\Bed;
use App\Models\DeliveryRecord;
use App\Models\Department;
use App\Models\LaborEpisode;
use App\Models\NewbornRecord;
use App\Models\Patient;
use App\Models\PostnatalCase;
use App\Models\PostnatalMotherObservation;
use App\Models\PostnatalNewbornObservation;
use App\Models\PregnancyProfile;
use App\Models\User;
use App\Models\Visit;
use App\Models\Ward;
use App\Services\Admissions\AdmissionDischargeReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PostnatalCarePhase12Test extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Department $department;
    private Patient $mother;
    private Visit $visit;
    private Admission $admission;

    protected function setUp(): void
    {
        parent::setUp();

        config(['audit_streaming.async_writes' => false]);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->department = Department::factory()->create([
            'name' => 'Maternity Ward',
            'code' => 'MAT12',
            'type' => DepartmentType::MATERNITY->value,
            'status' => 'active',
        ]);

        $this->user = User::factory()->create(['department_id' => $this->department->id]);
        $role = Role::findOrCreate('Maternity Phase 12 Tester', 'web');
        foreach ($this->permissions() as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->user->assignRole($role);

        $this->mother = Patient::factory()->create([
            'registered_by' => $this->user->id,
            'gender' => Gender::FEMALE,
            'phone' => '0244000000',
            'last_name' => 'Mensah',
        ]);

        $this->visit = Visit::factory()->create([
            'patient_id' => $this->mother->id,
            'current_department_id' => $this->department->id,
            'created_by' => $this->user->id,
            'status' => VisitStatus::ACTIVE,
        ]);

        $ward = Ward::create([
            'name' => 'Maternity Ward',
            'code' => 'MW12',
            'department_id' => $this->department->id,
            'capacity' => 20,
            'is_active' => true,
        ]);

        $bed = Bed::create([
            'ward_id' => $ward->id,
            'bed_number' => 'MAT-012',
            'bed_type' => 'standard',
            'status' => BedStatus::OCCUPIED,
            'daily_rate' => 0,
        ]);

        $this->admission = Admission::create([
            'admission_number' => 'ADM-12-001',
            'visit_id' => $this->visit->id,
            'patient_id' => $this->mother->id,
            'bed_id' => $bed->id,
            'admitted_by' => $this->user->id,
            'admitting_diagnosis' => 'Maternity admission',
            'admission_date' => now()->subDay(),
            'status' => AdmissionStatus::ADMITTED,
            'admission_type' => 'maternity',
        ]);
    }

    public function test_postnatal_case_can_be_opened_from_delivery_and_links_context(): void
    {
        $delivery = $this->createDeliveryWithNewborn();

        $this->actingAs($this->user)
            ->post(route('admin.maternity.deliveries.postnatal.store', $delivery), [
                'follow_up_date' => now()->addWeek()->toDateString(),
                'notes' => 'Routine postnatal care',
            ])
            ->assertRedirect();

        $case = PostnatalCase::firstOrFail();
        $this->assertSame($delivery->id, $case->delivery_record_id);
        $this->assertSame($delivery->pregnancy_profile_id, $case->pregnancy_profile_id);
        $this->assertSame($this->mother->id, $case->mother_patient_id);
        $this->assertSame($this->visit->id, $case->visit_id);
        $this->assertSame($this->admission->id, $case->admission_id);
        $this->assertSame(PostnatalCaseStatus::OPEN, $case->status);

        $this->actingAs($this->user)
            ->get(route('maternity.deliveries.show', $delivery))
            ->assertOk()
            ->assertSee('Postnatal Care')
            ->assertSee('Postnatal Case');
    }

    public function test_mother_observation_stores_vitals_danger_signs_and_updates_case_risk(): void
    {
        $case = $this->createPostnatalCase();

        $this->actingAs($this->user)
            ->post(route('admin.maternity.postnatal.mother-observations.store', $case), [
                'observed_at' => now()->format('Y-m-d H:i:s'),
                'blood_pressure_systolic' => 150,
                'blood_pressure_diastolic' => 95,
                'temperature' => 38.2,
                'bleeding_status' => BleedingStatus::HEAVY->value,
                'uterus_condition' => 'boggy',
                'pain_score' => 8,
                'breastfeeding_status' => BreastfeedingStatus::NEEDS_SUPPORT->value,
                'danger_signs' => [PostnatalMotherDangerSign::SEVERE_HEADACHE->value],
                'assessment' => 'Needs review',
                'plan' => 'Escalate to clinician',
                'counselling' => 'Danger sign advice',
            ])
            ->assertRedirect();

        $observation = PostnatalMotherObservation::firstOrFail();
        $this->assertSame(150, $observation->blood_pressure_systolic);
        $this->assertContains(PostnatalMotherDangerSign::HEAVY_BLEEDING->value, $observation->danger_signs);
        $this->assertContains(PostnatalMotherDangerSign::FEVER->value, $observation->danger_signs);
        $this->assertSame(MaternityRiskLevel::EMERGENCY, $case->fresh()->risk_level);
        $this->assertSame(PostnatalCaseStatus::REFERRAL_REQUIRED, $case->fresh()->status);
    }

    public function test_newborn_observation_stores_feeding_jaundice_cord_and_danger_signs(): void
    {
        $delivery = $this->createDeliveryWithNewborn();
        $case = $this->openCase($delivery);
        $newborn = $delivery->newbornRecords()->firstOrFail();

        $this->actingAs($this->user)
            ->post(route('admin.maternity.postnatal.newborn-observations.store', [$case, $newborn]), [
                'observed_at' => now()->format('Y-m-d H:i:s'),
                'temperature' => 35.9,
                'weight_kg' => 2.3,
                'feeding_status' => NewbornFeedingStatus::DIFFICULTY->value,
                'breathing_status' => NewbornBreathingStatus::DIFFICULTY->value,
                'cord_status' => NewbornCordStatus::INFECTED->value,
                'jaundice_status' => JaundiceStatus::SEVERE->value,
                'danger_signs' => [PostnatalNewbornDangerSign::LETHARGY->value],
                'immunisation_note' => 'BCG pending',
                'assessment' => 'Needs neonatal review',
            ])
            ->assertRedirect();

        $observation = PostnatalNewbornObservation::firstOrFail();
        $this->assertSame($newborn->id, $observation->newborn_record_id);
        $this->assertSame(NewbornFeedingStatus::DIFFICULTY, $observation->feeding_status);
        $this->assertSame(JaundiceStatus::SEVERE, $observation->jaundice_status);
        $this->assertContains(PostnatalNewbornDangerSign::DIFFICULTY_BREATHING->value, $observation->danger_signs);
        $this->assertContains(PostnatalNewbornDangerSign::CORD_INFECTION->value, $observation->danger_signs);
    }

    public function test_readiness_referral_and_follow_up_actions_work(): void
    {
        $case = $this->createPostnatalCase();

        $this->actingAs($this->user)
            ->patch(route('admin.maternity.postnatal.status', $case), ['action' => 'mother_ready'])
            ->assertRedirect();
        $this->assertNotNull($case->fresh()->mother_ready_at);

        $this->actingAs($this->user)
            ->patch(route('admin.maternity.postnatal.status', $case), ['action' => 'newborn_ready'])
            ->assertRedirect();
        $this->assertNotNull($case->fresh()->newborn_ready_at);

        $this->actingAs($this->user)
            ->patch(route('admin.maternity.postnatal.status', $case), ['action' => 'ready_for_discharge'])
            ->assertRedirect();
        $this->assertSame(PostnatalCaseStatus::READY_FOR_DISCHARGE, $case->fresh()->status);

        $this->actingAs($this->user)
            ->patch(route('admin.maternity.postnatal.status', $case), [
                'action' => 'referral_required',
                'referral_reason' => 'Neonatal review',
            ])
            ->assertRedirect();
        $this->assertTrue($case->fresh()->referral_required);
        $this->assertSame('Neonatal review', $case->fresh()->referral_reason);

        $this->actingAs($this->user)
            ->patch(route('admin.maternity.postnatal.update', $case), [
                'follow_up_date' => now()->addDays(3)->toDateString(),
                'follow_up_instructions' => 'Return in three days',
            ])
            ->assertRedirect();
        $this->assertSame('Return in three days', $case->fresh()->follow_up_instructions);
    }

    public function test_profile_dashboard_newborn_page_and_admission_readiness_show_postnatal_summary(): void
    {
        $delivery = $this->createDeliveryWithNewborn();
        $case = $this->openCase($delivery);
        $newborn = $delivery->newbornRecords()->firstOrFail();

        $this->actingAs($this->user)
            ->get(route('maternity.pregnancies.show', $delivery->pregnancyProfile))
            ->assertOk()
            ->assertSee('Postnatal Care');

        $this->actingAs($this->user)
            ->get(route('maternity.dashboard'))
            ->assertOk()
            ->assertSee('Active Postnatal Cases')
            ->assertSee('Recent Postnatal Cases');

        $this->actingAs($this->user)
            ->get(route('maternity.newborns.show', $newborn))
            ->assertOk()
            ->assertSee('Postnatal Care');

        $readiness = app(AdmissionDischargeReadinessService::class)->forAdmission($this->admission);
        $this->assertArrayHasKey('postnatal', $readiness['areas']->all());
        $this->assertSame(1, $readiness['areas']['postnatal']['meta']['case_count']);
        $this->assertFalse($readiness['enforcement']['postnatal_required']);
    }

    public function test_stillbirth_newborn_records_do_not_require_newborn_postnatal_observation(): void
    {
        $delivery = $this->createDeliveryWithNewborn([], [
            'outcome' => NewbornOutcome::STILLBIRTH,
            'status' => NewbornRecordStatus::CLOSED,
            'apgar_1_min' => 0,
            'apgar_5_min' => 0,
        ]);
        $case = $this->openCase($delivery);
        $newborn = $delivery->newbornRecords()->firstOrFail();

        $this->assertNotNull($case->newborn_ready_at);

        $this->actingAs($this->user)
            ->post(route('admin.maternity.postnatal.newborn-observations.store', [$case, $newborn]), [
                'temperature' => 36.8,
            ])
            ->assertSessionHasErrors('newborn_record_id');
    }

    public function test_non_permitted_user_cannot_open_postnatal_case(): void
    {
        $delivery = $this->createDeliveryWithNewborn();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.maternity.deliveries.postnatal.store', $delivery))
            ->assertForbidden();
    }

    private function createProfile(): PregnancyProfile
    {
        return PregnancyProfile::create([
            'patient_id' => $this->mother->id,
            'visit_id' => $this->visit->id,
            'admission_id' => $this->admission->id,
            'department_id' => $this->department->id,
            'created_by' => $this->user->id,
            'gravida' => 2,
            'para' => 1,
            'last_menstrual_period' => now()->subWeeks(39)->toDateString(),
            'estimated_due_date' => now()->toDateString(),
            'gestational_age_weeks' => 39,
            'gestational_age_days' => 0,
            'profile_status' => PregnancyProfileStatus::ACTIVE,
        ]);
    }

    private function createLaborEpisode(PregnancyProfile $profile): LaborEpisode
    {
        return LaborEpisode::create([
            'pregnancy_profile_id' => $profile->id,
            'patient_id' => $this->mother->id,
            'visit_id' => $this->visit->id,
            'admission_id' => $this->admission->id,
            'department_id' => $this->department->id,
            'started_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'started_at' => now()->subHours(4),
            'labor_stage' => LaborStage::COMPLETED,
            'status' => LaborEpisodeStatus::DELIVERED,
            'risk_level' => MaternityRiskLevel::LOW,
        ]);
    }

    private function createDeliveryWithNewborn(array $deliveryOverrides = [], array $newbornOverrides = []): DeliveryRecord
    {
        $profile = $this->createProfile();
        $episode = $this->createLaborEpisode($profile);
        $delivery = DeliveryRecord::create(array_merge([
            'labor_episode_id' => $episode->id,
            'pregnancy_profile_id' => $profile->id,
            'patient_id' => $this->mother->id,
            'visit_id' => $this->visit->id,
            'admission_id' => $this->admission->id,
            'department_id' => $this->department->id,
            'recorded_by' => $this->user->id,
            'delivery_at' => now(),
            'delivery_mode' => DeliveryMode::SPONTANEOUS_VAGINAL_DELIVERY,
            'delivery_outcome' => DeliveryOutcome::LIVE_BIRTH,
            'newborn_count' => 1,
            'newborn_records_pending' => false,
            'status' => DeliveryRecordStatus::COMPLETED,
        ], $deliveryOverrides));

        NewbornRecord::create(array_merge([
            'delivery_record_id' => $delivery->id,
            'labor_episode_id' => $delivery->labor_episode_id,
            'pregnancy_profile_id' => $delivery->pregnancy_profile_id,
            'mother_patient_id' => $delivery->patient_id,
            'visit_id' => $delivery->visit_id,
            'admission_id' => $delivery->admission_id,
            'department_id' => $delivery->department_id,
            'recorded_by' => $this->user->id,
            'created_by' => $this->user->id,
            'baby_number' => 1,
            'birth_order' => 1,
            'sex' => NewbornSex::MALE,
            'birth_time' => now(),
            'birth_weight_kg' => 2.8,
            'apgar_1_min' => 7,
            'apgar_5_min' => 8,
            'feeding_status' => NewbornFeedingStatus::BREASTFEEDING,
            'outcome' => NewbornOutcome::LIVE_BIRTH,
            'status' => NewbornRecordStatus::STABLE,
        ], $newbornOverrides));

        return $delivery->fresh(['pregnancyProfile', 'newbornRecords']);
    }

    private function openCase(DeliveryRecord $delivery): PostnatalCase
    {
        $this->actingAs($this->user)
            ->post(route('admin.maternity.deliveries.postnatal.store', $delivery))
            ->assertRedirect();

        return PostnatalCase::where('delivery_record_id', $delivery->id)->firstOrFail();
    }

    private function createPostnatalCase(): PostnatalCase
    {
        return $this->openCase($this->createDeliveryWithNewborn());
    }

    private function permissions(): array
    {
        return [
            'maternity.view',
            'maternity.dashboard.view',
            'maternity.pregnancy.view',
            'maternity.case.view',
            'maternity.anc.view',
            'maternity.labor.view',
            'maternity.delivery.view',
            'maternity.delivery.record',
            'maternity.newborn.view',
            'maternity.birth_outcome.view',
            'maternity.postnatal.view',
            'maternity.postnatal.open',
            'maternity.postnatal.update',
            'maternity.postnatal.close',
            'maternity.postnatal.cancel',
            'maternity.postnatal.mother.record',
            'maternity.postnatal.mother.update',
            'maternity.postnatal.newborn.record',
            'maternity.postnatal.newborn.update',
            'maternity.postnatal.risk.manage',
            'maternity.postnatal.discharge.manage',
            'maternity.postnatal.referral.manage',
            'maternity.postnatal.reports.view',
        ];
    }
}
