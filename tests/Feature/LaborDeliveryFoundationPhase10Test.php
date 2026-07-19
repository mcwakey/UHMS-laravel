<?php

namespace Tests\Feature;

use App\Enums\AdmissionRequestSource;
use App\Enums\AdmissionStatus;
use App\Enums\BedStatus;
use App\Enums\DeliveryMode;
use App\Enums\DeliveryOutcome;
use App\Enums\DeliveryRecordStatus;
use App\Enums\DepartmentType;
use App\Enums\Gender;
use App\Enums\LaborDangerSign;
use App\Enums\LaborEpisodeStatus;
use App\Enums\LaborObservationStatus;
use App\Enums\LaborRiskFlag;
use App\Enums\LaborStage;
use App\Enums\LiquorColour;
use App\Enums\MembranesStatus;
use App\Enums\PregnancyProfileStatus;
use App\Enums\VisitStatus;
use App\Models\Admission;
use App\Models\AdmissionRequest;
use App\Models\AntenatalVisit;
use App\Models\Bed;
use App\Models\DeliveryRecord;
use App\Models\Department;
use App\Models\LaborEpisode;
use App\Models\LaborObservation;
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

class LaborDeliveryFoundationPhase10Test extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Department $department;
    private Ward $ward;
    private Bed $bed;
    private Patient $patient;
    private Visit $visit;
    private Admission $admission;

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
        $role = Role::findOrCreate('Maternity Phase 10 Tester', 'web');
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

        $this->bed = Bed::create([
            'ward_id' => $this->ward->id,
            'bed_number' => 'MAT-001',
            'bed_type' => 'standard',
            'status' => BedStatus::OCCUPIED,
            'daily_rate' => 0,
        ]);

        $this->admission = Admission::create([
            'admission_number' => 'ADM-10-001',
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'bed_id' => $this->bed->id,
            'admitted_by' => $this->user->id,
            'admitting_diagnosis' => 'Maternity admission',
            'admission_date' => now()->subDay(),
            'status' => AdmissionStatus::ADMITTED,
            'admission_type' => 'maternity',
        ]);
    }

    public function test_labor_episode_can_be_started_from_pregnancy_profile_and_links_context(): void
    {
        $profile = $this->createProfile(['admission_id' => $this->admission->id]);

        $this->actingAs($this->user)
            ->post(route('admin.maternity.pregnancies.labor.store', $profile), $this->episodePayload())
            ->assertRedirect();

        $episode = LaborEpisode::firstOrFail();
        $this->assertSame($profile->id, $episode->pregnancy_profile_id);
        $this->assertSame($this->patient->id, $episode->patient_id);
        $this->assertSame($this->visit->id, $episode->visit_id);
        $this->assertSame($this->admission->id, $episode->admission_id);
        $this->assertSame(LaborStage::FIRST_STAGE, $episode->labor_stage);
        $this->assertSame(LaborEpisodeStatus::ACTIVE, $episode->status);
        $this->assertNotNull($episode->maternity_case_id);
    }

    public function test_labor_episode_can_be_started_from_anc_visit(): void
    {
        $profile = $this->createProfile();
        $anc = $this->createAntenatalVisit($profile);

        $this->actingAs($this->user)
            ->post(route('admin.maternity.pregnancies.labor.store', $profile), $this->episodePayload([
                'antenatal_visit_id' => $anc->id,
            ]))
            ->assertRedirect();

        $episode = LaborEpisode::firstOrFail();
        $this->assertSame($anc->id, $episode->antenatal_visit_id);

        $this->actingAs($this->user)
            ->get(route('maternity.antenatal.show', $anc))
            ->assertOk()
            ->assertSee('Start Labor Episode');
    }

    public function test_labor_episode_can_be_updated_stage_changed_and_escalated(): void
    {
        $episode = $this->createLaborEpisode($this->createProfile());

        $this->actingAs($this->user)
            ->patch(route('admin.maternity.labor.update', $episode), $this->episodePayload([
                'presentation' => 'cephalic',
                'clinical_summary' => 'Active labor monitoring',
            ]))
            ->assertRedirect(route('admin.maternity.labor.show', $episode));

        $this->actingAs($this->user)
            ->patch(route('admin.maternity.labor.stage', $episode), [
                'labor_stage' => LaborStage::SECOND_STAGE->value,
                'status' => LaborEpisodeStatus::DELIVERY_PENDING->value,
            ])
            ->assertRedirect(route('admin.maternity.labor.show', $episode));

        $this->actingAs($this->user)
            ->post(route('admin.maternity.labor.theatre-escalation', $episode))
            ->assertRedirect(route('admin.maternity.labor.show', $episode));

        $this->actingAs($this->user)
            ->post(route('admin.maternity.labor.emergency-escalation', $episode))
            ->assertRedirect(route('admin.maternity.labor.show', $episode));

        $episode->refresh();
        $this->assertSame(LaborStage::SECOND_STAGE, $episode->labor_stage);
        $this->assertTrue($episode->theatre_escalation_required);
        $this->assertTrue($episode->emergency_escalation_required);
    }

    public function test_labor_observation_records_maternal_fetal_fields_and_risk_markers(): void
    {
        $episode = $this->createLaborEpisode($this->createProfile());

        $this->actingAs($this->user)
            ->post(route('admin.maternity.labor.observations.store', $episode), $this->observationPayload())
            ->assertRedirect(route('admin.maternity.labor.show', $episode));

        $observation = LaborObservation::firstOrFail();
        $this->assertSame($episode->id, $observation->labor_episode_id);
        $this->assertSame(170, $observation->fetal_heart_rate);
        $this->assertSame(150, $observation->blood_pressure_systolic);
        $this->assertSame(LaborObservationStatus::ESCALATED, $observation->status);
        $this->assertContains(LaborDangerSign::FETAL_DISTRESS->value, $observation->danger_signs);
        $this->assertContains(LaborRiskFlag::ABNORMAL_FETAL_HEART_RATE->value, $observation->risk_flags);

        $this->actingAs($this->user)
            ->get(route('maternity.labor.show', $episode))
            ->assertOk()
            ->assertSee('Labor Observations')
            ->assertSee('170');
    }

    public function test_delivery_record_can_be_created_updated_and_completed(): void
    {
        $episode = $this->createLaborEpisode($this->createProfile());

        $this->actingAs($this->user)
            ->post(route('admin.maternity.labor.delivery.store', $episode), $this->deliveryPayload())
            ->assertRedirect();

        $record = DeliveryRecord::firstOrFail();
        $this->assertSame(DeliveryRecordStatus::DRAFT, $record->status);
        $this->assertTrue($record->newborn_records_pending);

        $this->actingAs($this->user)
            ->patch(route('admin.maternity.deliveries.update', $record), $this->deliveryPayload([
                'estimated_blood_loss_ml' => 350,
                'notes' => 'Delivery record updated',
            ]))
            ->assertRedirect(route('admin.maternity.deliveries.show', $record));

        $this->actingAs($this->user)
            ->patch(route('admin.maternity.deliveries.complete', $record), $this->deliveryPayload([
                'status' => DeliveryRecordStatus::COMPLETED->value,
            ]))
            ->assertRedirect(route('admin.maternity.deliveries.show', $record));

        $record->refresh();
        $episode->refresh();
        $this->assertSame(DeliveryRecordStatus::COMPLETED, $record->status);
        $this->assertSame(LaborEpisodeStatus::DELIVERED, $episode->status);
        $this->assertSame(LaborStage::COMPLETED, $episode->labor_stage);
        $this->assertTrue($record->newborn_records_pending);
    }

    public function test_labor_admission_request_is_explicit_user_action(): void
    {
        $episode = $this->createLaborEpisode($this->createProfile(['admission_id' => null]));

        $this->actingAs($this->user)
            ->post(route('admin.maternity.labor.admission-request', $episode), [
                'requested_ward_id' => $this->ward->id,
                'priority' => 'urgent',
                'clinical_summary' => 'Admit from labor episode',
            ])
            ->assertRedirect();

        $request = AdmissionRequest::firstOrFail();
        $this->assertSame(AdmissionRequestSource::MATERNITY, $request->source_type);
        $this->assertSame($episode->id, $request->source_id);
        $this->assertSame($this->ward->id, $request->requested_ward_id);
    }

    public function test_dashboard_and_pregnancy_profile_render_labor_metrics(): void
    {
        $profile = $this->createProfile();
        $episode = $this->createLaborEpisode($profile);
        LaborObservation::create($this->observationModelPayload($episode));

        $this->actingAs($this->user)
            ->get(route('maternity.dashboard'))
            ->assertOk()
            ->assertSee('Active Labor Episodes')
            ->assertSee('Recent Labor Observations');

        $this->actingAs($this->user)
            ->get(route('maternity.pregnancies.show', $profile))
            ->assertOk()
            ->assertSee('Labor and Delivery')
            ->assertSee('Active Labor Episode');
    }

    public function test_non_permitted_user_cannot_start_labor_episode(): void
    {
        $profile = $this->createProfile();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.maternity.pregnancies.labor.store', $profile), $this->episodePayload())
            ->assertForbidden();
    }

    private function createProfile(array $overrides = []): PregnancyProfile
    {
        return PregnancyProfile::create(array_merge([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'admission_id' => $this->admission->id,
            'department_id' => $this->department->id,
            'created_by' => $this->user->id,
            'gravida' => 2,
            'para' => 1,
            'last_menstrual_period' => now()->subWeeks(39)->toDateString(),
            'estimated_due_date' => now()->addWeek()->toDateString(),
            'gestational_age_weeks' => 39,
            'gestational_age_days' => 0,
            'profile_status' => PregnancyProfileStatus::ACTIVE,
        ], $overrides));
    }

    private function createAntenatalVisit(PregnancyProfile $profile): AntenatalVisit
    {
        return AntenatalVisit::create([
            'pregnancy_profile_id' => $profile->id,
            'patient_id' => $profile->patient_id,
            'visit_id' => $profile->visit_id,
            'admission_id' => $profile->admission_id,
            'department_id' => $profile->department_id,
            'recorded_by' => $this->user->id,
            'visit_number' => 1,
            'visit_date' => now()->subWeek(),
            'gestational_age_weeks' => 38,
            'gestational_age_days' => 0,
            'assessment' => 'Term ANC',
            'status' => 'recorded',
            'created_by' => $this->user->id,
        ]);
    }

    private function createLaborEpisode(PregnancyProfile $profile): LaborEpisode
    {
        return LaborEpisode::create([
            'pregnancy_profile_id' => $profile->id,
            'patient_id' => $profile->patient_id,
            'visit_id' => $profile->visit_id,
            'admission_id' => $profile->admission_id,
            'department_id' => $profile->department_id,
            'started_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'started_at' => now(),
            'labor_onset_at' => now()->subHours(2),
            'membranes_status' => MembranesStatus::INTACT,
            'labor_stage' => LaborStage::FIRST_STAGE,
            'status' => LaborEpisodeStatus::ACTIVE,
            'risk_level' => 'low',
        ]);
    }

    private function episodePayload(array $overrides = []): array
    {
        return array_merge([
            'started_at' => now()->format('Y-m-d H:i:s'),
            'labor_onset_at' => now()->subHours(2)->format('Y-m-d H:i:s'),
            'membranes_status' => MembranesStatus::INTACT->value,
            'liquor_colour' => LiquorColour::CLEAR->value,
            'presentation' => 'cephalic',
            'labor_stage' => LaborStage::FIRST_STAGE->value,
            'status' => LaborEpisodeStatus::ACTIVE->value,
            'clinical_summary' => 'Labor started',
            'initial_assessment' => 'Initial labor assessment',
        ], $overrides);
    }

    private function observationPayload(array $overrides = []): array
    {
        return array_merge([
            'observed_at' => now()->format('Y-m-d H:i:s'),
            'labor_stage' => LaborStage::FIRST_STAGE->value,
            'cervical_dilation_cm' => 5,
            'fetal_heart_rate' => 170,
            'contractions_per_10_min' => 4,
            'contraction_duration_seconds' => 60,
            'membranes_status' => MembranesStatus::RUPTURED->value,
            'liquor_colour' => LiquorColour::MECONIUM_STAINED->value,
            'maternal_pulse' => 98,
            'blood_pressure_systolic' => 150,
            'blood_pressure_diastolic' => 96,
            'temperature' => 37.6,
            'danger_signs' => [LaborDangerSign::SEVERE_BLEEDING->value],
            'risk_flags' => [LaborRiskFlag::PREVIOUS_CAESAREAN->value],
            'notes' => 'Observation with risk markers',
        ], $overrides);
    }

    private function observationModelPayload(LaborEpisode $episode, array $overrides = []): array
    {
        return array_merge([
            'labor_episode_id' => $episode->id,
            'pregnancy_profile_id' => $episode->pregnancy_profile_id,
            'patient_id' => $episode->patient_id,
            'visit_id' => $episode->visit_id,
            'admission_id' => $episode->admission_id,
            'recorded_by' => $this->user->id,
            'observed_at' => now(),
            'labor_stage' => LaborStage::FIRST_STAGE,
            'fetal_heart_rate' => 140,
            'status' => LaborObservationStatus::RECORDED,
        ], $overrides);
    }

    private function deliveryPayload(array $overrides = []): array
    {
        return array_merge([
            'delivery_at' => now()->format('Y-m-d H:i:s'),
            'delivery_mode' => DeliveryMode::SPONTANEOUS_VAGINAL_DELIVERY->value,
            'delivery_outcome' => DeliveryOutcome::LIVE_BIRTH->value,
            'estimated_blood_loss_ml' => 250,
            'newborn_count' => 1,
            'notes' => 'Delivery foundation record',
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
            'maternity.labor.view',
            'maternity.labor.start',
            'maternity.labor.update',
            'maternity.labor.close',
            'maternity.labor.cancel',
            'maternity.labor.observe',
            'maternity.labor.observation.update',
            'maternity.labor.observation.cancel',
            'maternity.labor.risk.manage',
            'maternity.labor.escalate',
            'maternity.delivery.view',
            'maternity.delivery.record',
            'maternity.delivery.update',
            'maternity.delivery.complete',
            'maternity.labor.admission.request',
            'maternity.labor.reports.view',
            'admission.requests.view',
            'admission.requests.create',
        ];
    }
}
