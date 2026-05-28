<?php

namespace Tests\Feature;

use App\Enums\Priority;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\EmergencyCase;
use App\Models\Patient;
use App\Models\PatientAlias;
use App\Models\PatientMergeRequest;
use App\Models\User;
use App\Models\Visit;
use App\Services\EmergencyCaseService;
use App\Services\EmergencyPatientIdentityService;
use App\Services\PatientMergeService;
use App\Services\VisitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PatientMergeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->user = User::factory()->create();
        $role = Role::findOrCreate('Patient Merge Tester', 'web');

        foreach ([
            'patients.view',
            'patients.merge.view',
            'patients.merge.request',
            'patients.merge.execute',
            'patients.merge.confirm_identity',
            'emergency.case.view',
            'visits.create',
            'emergency.case.create',
        ] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $this->user->assignRole($role);
    }

    public function test_merge_marks_duplicate_as_locked_and_moves_records(): void
    {
        [$mainPatient, $duplicatePatient] = $this->patients();
        $visit = Visit::factory()->create([
            'patient_id' => $duplicatePatient->id,
            'created_by' => $this->user->id,
        ]);

        $mergeRequest = $this->executeMerge($mainPatient, $duplicatePatient);

        $this->assertSame(PatientMergeRequest::STATUS_COMPLETED, $mergeRequest->status);
        $this->assertDatabaseHas('patients', [
            'id' => $duplicatePatient->id,
            'merge_status' => 'MERGED',
            'merged_to_patient_id' => $mainPatient->id,
            'is_active' => false,
            'status' => 'inactive',
        ]);
        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'patient_id' => $mainPatient->id,
        ]);
        $this->assertDatabaseHas('patient_aliases', [
            'patient_id' => $mainPatient->id,
            'source_patient_id' => $duplicatePatient->id,
            'alias_type' => PatientAlias::TYPE_PATIENT_NUMBER,
            'alias_value' => $duplicatePatient->patient_number,
        ]);
    }

    public function test_insurance_dedup_does_not_violate_unique_constraint(): void
    {
        [$mainPatient, $duplicatePatient] = $this->patients();
        $providerId = DB::table('insurance_providers')->insertGetId([
            'name' => 'National Test Insurance',
            'short_name' => 'NTI',
            'type' => 'public',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('patient_insurances')->insert([
            [
                'patient_id' => $mainPatient->id,
                'insurance_provider_id' => $providerId,
                'membership_number' => 'MAIN-001',
                'is_primary' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'patient_id' => $duplicatePatient->id,
                'insurance_provider_id' => $providerId,
                'membership_number' => 'DUP-001',
                'is_primary' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->executeMerge($mainPatient, $duplicatePatient);

        $this->assertSame(1, DB::table('patient_insurances')
            ->where('patient_id', $mainPatient->id)
            ->where('insurance_provider_id', $providerId)
            ->count());
        $this->assertDatabaseHas('patient_insurances', [
            'patient_id' => $duplicatePatient->id,
            'insurance_provider_id' => $providerId,
            'is_active' => false,
        ]);
    }

    public function test_old_patient_number_alias_resolves_to_main_patient_in_active_search(): void
    {
        [$mainPatient, $duplicatePatient] = $this->patients();

        $this->executeMerge($mainPatient, $duplicatePatient);

        $results = Patient::active()->search($duplicatePatient->patient_number)->pluck('id')->all();

        $this->assertContains($mainPatient->id, $results);
        $this->assertNotContains($duplicatePatient->id, $results);
    }

    public function test_merge_ui_uses_patient_numbers_and_table_selection_actions(): void
    {
        [$mainPatient, $duplicatePatient] = $this->patients();

        $indexResponse = $this->actingAs($this->user)->get(route('admin.patients.merge.index', [
            'search' => $mainPatient->patient_number,
        ]));
        $escapedMainPatientNumber = str_replace('/', '\\/', $mainPatient->patient_number);
        $escapedDuplicatePatientNumber = str_replace('/', '\\/', $duplicatePatient->patient_number);

        $indexResponse->assertOk()
            ->assertSee($escapedMainPatientNumber, false)
            ->assertSee('Select as main folder', false)
            ->assertSee('Select as duplicate folder', false)
            ->assertDontSee('<th>ID</th>', false)
            ->assertDontSee('main_patient_id', false)
            ->assertDontSee('duplicate_patient_id', false);

        $compareResponse = $this->actingAs($this->user)->get(route('admin.patients.merge.compare', [
            'main_patient_number' => $mainPatient->patient_number,
            'duplicate_patient_number' => $duplicatePatient->patient_number,
        ]));

        $compareResponse->assertOk()
            ->assertSee($escapedMainPatientNumber, false)
            ->assertSee($escapedDuplicatePatientNumber, false)
            ->assertDontSee('Main Patient ID')
            ->assertDontSee('Duplicate Patient ID')
            ->assertDontSee('main_patient_id', false)
            ->assertDontSee('duplicate_patient_id', false);
    }

    public function test_cannot_merge_patient_into_themselves(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id, 'status' => 'active']);

        $this->expectException(\InvalidArgumentException::class);

        app(PatientMergeService::class)->createRequest($patient, $patient, $this->user, [], 'same folder', true);
    }

    public function test_merged_patient_cannot_receive_new_visit_or_emergency_case(): void
    {
        [$mainPatient, $duplicatePatient] = $this->patients();
        $this->executeMerge($mainPatient, $duplicatePatient);

        $this->expectException(\InvalidArgumentException::class);
        app(VisitService::class)->create([
            'patient_id' => $duplicatePatient->id,
            'visit_type' => VisitType::OUTPATIENT->value,
            'priority' => Priority::NORMAL->value,
            'visit_date' => now()->toDateString(),
        ]);
    }

    public function test_merged_patient_cannot_receive_new_emergency_case(): void
    {
        [$mainPatient, $duplicatePatient] = $this->patients();
        $this->executeMerge($mainPatient, $duplicatePatient);

        $this->expectException(\InvalidArgumentException::class);
        app(EmergencyCaseService::class)->create([
            'patient_id' => $duplicatePatient->id,
            'arrival_mode' => 'WALK_IN',
            'arrival_time' => now()->format('Y-m-d H:i:s'),
        ], $this->user);
    }

    public function test_emergency_identity_confirmation_merges_temporary_folder(): void
    {
        [$mainPatient, $temporaryPatient] = $this->patients();
        $temporaryPatient->forceFill([
            'patient_number' => 'TEMP-ER-0001',
            'is_temporary' => true,
            'temporary_reason' => 'Unknown emergency arrival',
        ])->save();

        $visit = Visit::factory()->create([
            'patient_id' => $temporaryPatient->id,
            'visit_type' => VisitType::EMERGENCY->value,
            'status' => VisitStatus::EMERGENCY->value,
            'created_by' => $this->user->id,
        ]);
        $case = EmergencyCase::create([
            'emergency_number' => 'ER-TEST-0001',
            'visit_id' => $visit->id,
            'patient_id' => $temporaryPatient->id,
            'arrival_mode' => 'UNKNOWN',
            'arrival_time' => now(),
            'emergency_status' => EmergencyCase::STATUS_WAITING_TRIAGE,
            'created_by' => $this->user->id,
        ]);

        app(EmergencyPatientIdentityService::class)->confirm($case->load('patient'), $mainPatient, $this->user, 'Family confirmed identity.');

        $this->assertDatabaseHas('emergency_cases', [
            'id' => $case->id,
            'patient_id' => $mainPatient->id,
        ]);
        $this->assertDatabaseHas('patient_aliases', [
            'patient_id' => $mainPatient->id,
            'alias_type' => PatientAlias::TYPE_TEMPORARY_PATIENT_NUMBER,
            'alias_value' => 'TEMP-ER-0001',
        ]);
        $this->assertDatabaseHas('patients', [
            'id' => $temporaryPatient->id,
            'merge_status' => 'MERGED',
            'merged_to_patient_id' => $mainPatient->id,
        ]);
    }

    public function test_temporary_emergency_patient_can_be_registered_as_new_patient(): void
    {
        $temporaryPatient = Patient::factory()->create([
            'registered_by' => $this->user->id,
            'patient_number' => 'TEMP-ER-0002',
            'first_name' => 'Unknown',
            'last_name' => 'Emergency',
            'is_temporary' => true,
            'temporary_reason' => 'Arrived without identity documents',
            'status' => 'active',
            'phone' => '0000000000',
        ]);
        $visit = Visit::factory()->create([
            'patient_id' => $temporaryPatient->id,
            'visit_type' => VisitType::EMERGENCY->value,
            'status' => VisitStatus::EMERGENCY->value,
            'created_by' => $this->user->id,
        ]);
        $case = EmergencyCase::create([
            'emergency_number' => 'ER-TEST-0002',
            'visit_id' => $visit->id,
            'patient_id' => $temporaryPatient->id,
            'arrival_mode' => 'UNKNOWN',
            'arrival_time' => now(),
            'emergency_status' => EmergencyCase::STATUS_WAITING_TRIAGE,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->post(route('admin.emergency.cases.register-identity', $case), [
            '_identity_action' => 'register',
            'first_name' => 'Kojo',
            'last_name' => 'Boateng',
            'date_of_birth' => now()->subYears(32)->toDateString(),
            'gender' => 'male',
            'phone' => '0245550101',
            'reason' => 'Patient provided details after stabilization.',
            'confirmed' => '1',
        ]);

        $registeredPatient = Patient::where('phone', '0245550101')->first();

        $response->assertRedirect(route('admin.emergency.cases.show', $case));
        $this->assertNotNull($registeredPatient);
        $this->assertFalse($registeredPatient->is_temporary);
        $this->assertNotSame($temporaryPatient->id, $registeredPatient->id);
        $this->assertDatabaseHas('emergency_cases', [
            'id' => $case->id,
            'patient_id' => $registeredPatient->id,
        ]);
        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'patient_id' => $registeredPatient->id,
        ]);
        $this->assertDatabaseHas('patient_aliases', [
            'patient_id' => $registeredPatient->id,
            'alias_type' => PatientAlias::TYPE_TEMPORARY_PATIENT_NUMBER,
            'alias_value' => 'TEMP-ER-0002',
        ]);
        $this->assertDatabaseHas('patients', [
            'id' => $temporaryPatient->id,
            'merge_status' => 'MERGED',
            'merged_to_patient_id' => $registeredPatient->id,
        ]);
    }

    private function patients(): array
    {
        return [
            Patient::factory()->create([
                'registered_by' => $this->user->id,
                'status' => 'active',
                'first_name' => 'Ama',
                'last_name' => 'Mensah',
                'phone' => '0241000001',
            ]),
            Patient::factory()->create([
                'registered_by' => $this->user->id,
                'status' => 'active',
                'first_name' => 'Ama',
                'last_name' => 'Mensah',
                'phone' => '0241000002',
            ]),
        ];
    }

    private function executeMerge(Patient $mainPatient, Patient $duplicatePatient): PatientMergeRequest
    {
        $service = app(PatientMergeService::class);
        $request = $service->createRequest($mainPatient, $duplicatePatient, $this->user, [], 'Duplicate folder consolidation.', true);

        return $service->execute($request, $this->user);
    }
}
