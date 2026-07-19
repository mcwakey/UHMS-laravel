<?php

namespace Tests\Feature;

use App\Enums\AdmissionStatus;
use App\Enums\BedStatus;
use App\Enums\DeliveryMode;
use App\Enums\DeliveryOutcome;
use App\Enums\DeliveryRecordStatus;
use App\Enums\DepartmentType;
use App\Enums\Gender;
use App\Enums\LaborEpisodeStatus;
use App\Enums\LaborStage;
use App\Enums\NewbornDangerSign;
use App\Enums\NewbornFeedingStatus;
use App\Enums\NewbornOutcome;
use App\Enums\NewbornRecordStatus;
use App\Enums\NewbornRiskFlag;
use App\Enums\NewbornSex;
use App\Enums\PregnancyProfileStatus;
use App\Enums\VisitStatus;
use App\Models\Admission;
use App\Models\Bed;
use App\Models\DeliveryRecord;
use App\Models\Department;
use App\Models\LaborEpisode;
use App\Models\NewbornRecord;
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

class NewbornBirthOutcomePhase11Test extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Department $department;
    private Patient $mother;
    private Visit $visit;
    private Admission $admission;
    private Ward $ward;

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
        $role = Role::findOrCreate('Maternity Phase 11 Tester', 'web');
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

        $this->ward = Ward::create([
            'name' => 'Maternity Ward',
            'code' => 'MW11',
            'department_id' => $this->department->id,
            'capacity' => 20,
            'is_active' => true,
        ]);

        $bed = Bed::create([
            'ward_id' => $this->ward->id,
            'bed_number' => 'MAT-011',
            'bed_type' => 'standard',
            'status' => BedStatus::OCCUPIED,
            'daily_rate' => 0,
        ]);

        $this->admission = Admission::create([
            'admission_number' => 'ADM-11-001',
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

    public function test_newborn_record_can_be_created_from_delivery_and_links_context(): void
    {
        $delivery = $this->createDelivery();

        $this->actingAs($this->user)
            ->post(route('admin.maternity.deliveries.newborns.store', $delivery), $this->newbornPayload())
            ->assertRedirect();

        $newborn = NewbornRecord::firstOrFail();
        $this->assertSame($delivery->id, $newborn->delivery_record_id);
        $this->assertSame($delivery->labor_episode_id, $newborn->labor_episode_id);
        $this->assertSame($delivery->pregnancy_profile_id, $newborn->pregnancy_profile_id);
        $this->assertSame($this->mother->id, $newborn->mother_patient_id);
        $this->assertSame($this->visit->id, $newborn->visit_id);
        $this->assertSame($this->admission->id, $newborn->admission_id);
        $this->assertSame(NewbornOutcome::LIVE_BIRTH, $newborn->outcome);
        $this->assertSame(2.8, (float) $newborn->birth_weight_kg);
        $this->assertSame(8, $newborn->apgar_5_min);
    }

    public function test_multiple_newborn_records_can_be_bulk_created_and_duplicate_birth_order_is_prevented(): void
    {
        $delivery = $this->createDelivery(['newborn_count' => 2]);

        $this->actingAs($this->user)
            ->post(route('admin.maternity.deliveries.newborns.bulk-create', $delivery))
            ->assertRedirect(route('admin.maternity.deliveries.show', $delivery));

        $this->assertSame(2, NewbornRecord::where('delivery_record_id', $delivery->id)->count());

        $this->actingAs($this->user)
            ->from(route('admin.maternity.deliveries.newborns.create', $delivery))
            ->post(route('admin.maternity.deliveries.newborns.store', $delivery), $this->newbornPayload(['birth_order' => 1]))
            ->assertSessionHasErrors('birth_order');
    }

    public function test_newborn_risk_fields_resuscitation_and_status_update_work(): void
    {
        $delivery = $this->createDelivery();

        $this->actingAs($this->user)
            ->post(route('admin.maternity.deliveries.newborns.store', $delivery), $this->newbornPayload([
                'birth_weight_kg' => 2.1,
                'apgar_5_min' => 5,
                'resuscitation_required' => 1,
                'resuscitation_details' => 'Bag and mask ventilation',
                'feeding_status' => NewbornFeedingStatus::DIFFICULTY->value,
                'danger_signs' => [NewbornDangerSign::DIFFICULTY_BREATHING->value],
                'risk_flags' => [NewbornRiskFlag::MULTIPLE_BIRTH->value],
            ]))
            ->assertRedirect();

        $newborn = NewbornRecord::firstOrFail();
        $this->assertSame(NewbornRecordStatus::AT_RISK, $newborn->status);
        $this->assertTrue($newborn->resuscitation_required);
        $this->assertContains(NewbornRiskFlag::LOW_BIRTH_WEIGHT->value, $newborn->risk_flags);
        $this->assertContains(NewbornDangerSign::DIFFICULTY_BREATHING->value, $newborn->danger_signs);

        $this->actingAs($this->user)
            ->patch(route('admin.maternity.newborns.status', $newborn), [
                'status' => NewbornRecordStatus::UNDER_OBSERVATION->value,
                'outcome' => NewbornOutcome::LIVE_BIRTH->value,
            ])
            ->assertRedirect(route('admin.maternity.newborns.show', $newborn));

        $this->assertSame(NewbornRecordStatus::UNDER_OBSERVATION, $newborn->fresh()->status);
    }

    public function test_delivery_pending_flag_updates_when_birth_outcome_is_complete(): void
    {
        $delivery = $this->createDelivery(['newborn_count' => 1]);

        $this->actingAs($this->user)
            ->post(route('admin.maternity.deliveries.newborns.store', $delivery), $this->newbornPayload([
                'status' => NewbornRecordStatus::STABLE->value,
                'outcome' => NewbornOutcome::LIVE_BIRTH->value,
            ]))
            ->assertRedirect();

        $this->assertFalse($delivery->fresh()->newborn_records_pending);

        $this->actingAs($this->user)
            ->get(route('maternity.deliveries.show', $delivery))
            ->assertOk()
            ->assertSee('Newborn Records Complete')
            ->assertSee('Live birth');
    }

    public function test_profile_and_dashboard_show_newborn_summary(): void
    {
        $delivery = $this->createDelivery();
        NewbornRecord::create($this->newbornModelPayload($delivery));

        $this->actingAs($this->user)
            ->get(route('maternity.pregnancies.show', $delivery->pregnancyProfile))
            ->assertOk()
            ->assertSee('Newborn Records')
            ->assertSee('Live birth');

        $this->actingAs($this->user)
            ->get(route('maternity.dashboard'))
            ->assertOk()
            ->assertSee('Newborns Recorded Today')
            ->assertSee('Recent Newborn Records');
    }

    public function test_newborn_patient_can_be_created_and_linked_explicitly(): void
    {
        $delivery = $this->createDelivery();
        $newborn = NewbornRecord::create($this->newbornModelPayload($delivery));

        $this->actingAs($this->user)
            ->post(route('admin.maternity.newborns.create-patient', $newborn))
            ->assertRedirect();

        $newborn->refresh();
        $this->assertNotNull($newborn->newborn_patient_id);
        $this->assertSame('Baby '.$newborn->birth_order, $newborn->newbornPatient->first_name);
        $this->assertSame('Mensah', $newborn->newbornPatient->last_name);

        $otherPatient = Patient::factory()->create(['registered_by' => $this->user->id, 'date_of_birth' => today()]);
        $newborn2 = NewbornRecord::create($this->newbornModelPayload($delivery, ['birth_order' => 2, 'baby_number' => 2, 'newborn_patient_id' => null]));

        $this->actingAs($this->user)
            ->post(route('admin.maternity.newborns.link-patient', $newborn2), ['newborn_patient_id' => $otherPatient->id])
            ->assertRedirect(route('admin.maternity.newborns.show', $newborn2));

        $this->assertSame($otherPatient->id, $newborn2->fresh()->newborn_patient_id);
    }

    public function test_stillbirth_outcome_is_supported_without_patient_creation(): void
    {
        $delivery = $this->createDelivery();

        $this->actingAs($this->user)
            ->post(route('admin.maternity.deliveries.newborns.store', $delivery), $this->newbornPayload([
                'outcome' => NewbornOutcome::STILLBIRTH->value,
                'status' => NewbornRecordStatus::CLOSED->value,
                'apgar_1_min' => 0,
                'apgar_5_min' => 0,
            ]))
            ->assertRedirect();

        $newborn = NewbornRecord::firstOrFail();
        $this->assertSame(NewbornOutcome::STILLBIRTH, $newborn->outcome);

        $this->actingAs($this->user)
            ->post(route('admin.maternity.newborns.create-patient', $newborn))
            ->assertSessionHasErrors('outcome');
    }

    public function test_non_permitted_user_cannot_create_newborn_record(): void
    {
        $delivery = $this->createDelivery();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.maternity.deliveries.newborns.store', $delivery), $this->newbornPayload())
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
            'risk_level' => 'low',
        ]);
    }

    private function createDelivery(array $overrides = []): DeliveryRecord
    {
        $profile = $this->createProfile();
        $episode = $this->createLaborEpisode($profile);

        return DeliveryRecord::create(array_merge([
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
            'newborn_records_pending' => true,
            'status' => DeliveryRecordStatus::COMPLETED,
        ], $overrides));
    }

    private function newbornPayload(array $overrides = []): array
    {
        return array_merge([
            'baby_number' => 1,
            'birth_order' => 1,
            'sex' => NewbornSex::MALE->value,
            'birth_time' => now()->format('Y-m-d H:i:s'),
            'birth_weight_kg' => 2.8,
            'length_cm' => 49,
            'head_circumference_cm' => 34,
            'apgar_1_min' => 7,
            'apgar_5_min' => 8,
            'apgar_10_min' => 9,
            'cried_at_birth' => 1,
            'resuscitation_required' => 0,
            'feeding_status' => NewbornFeedingStatus::BREASTFEEDING->value,
            'outcome' => NewbornOutcome::LIVE_BIRTH->value,
            'notes' => 'Newborn stable',
        ], $overrides);
    }

    private function newbornModelPayload(DeliveryRecord $delivery, array $overrides = []): array
    {
        return array_merge([
            'delivery_record_id' => $delivery->id,
            'labor_episode_id' => $delivery->labor_episode_id,
            'pregnancy_profile_id' => $delivery->pregnancy_profile_id,
            'maternity_case_id' => $delivery->maternity_case_id,
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
            'outcome' => NewbornOutcome::LIVE_BIRTH,
            'status' => NewbornRecordStatus::STABLE,
        ], $overrides);
    }

    private function permissions(): array
    {
        return [
            'maternity.view',
            'maternity.dashboard.view',
            'maternity.pregnancy.view',
            'maternity.pregnancy.create',
            'maternity.case.view',
            'maternity.anc.view',
            'maternity.labor.view',
            'maternity.delivery.view',
            'maternity.delivery.record',
            'maternity.delivery.update',
            'maternity.delivery.complete',
            'maternity.newborn.view',
            'maternity.newborn.record',
            'maternity.newborn.update',
            'maternity.newborn.close',
            'maternity.newborn.link_patient',
            'maternity.newborn.create_patient',
            'maternity.newborn.risk.manage',
            'maternity.newborn.reports.view',
            'maternity.birth_outcome.view',
            'maternity.birth_outcome.manage',
        ];
    }
}
