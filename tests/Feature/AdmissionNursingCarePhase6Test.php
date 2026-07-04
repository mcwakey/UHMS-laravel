<?php

namespace Tests\Feature;

use App\Enums\AdmissionCareFlag;
use App\Enums\BedStatus;
use App\Enums\NursingNoteType;
use App\Enums\NursingTaskStatus;
use App\Enums\NursingTaskType;
use App\Enums\VisitStatus;
use App\Models\Bed;
use App\Models\Department;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\Vital;
use App\Models\Ward;
use App\Services\AdmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdmissionNursingCarePhase6Test extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;
    private Visit $visit;
    private Ward $ward;
    private Bed $bed;

    protected function setUp(): void
    {
        parent::setUp();
        config(['audit_streaming.async_writes' => false]);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $department = Department::factory()->create();
        $this->user = User::factory()->create(['department_id' => $department->id]);

        $role = Role::findOrCreate('Admission Nursing Tester', 'web');
        foreach ([
            'ward.view', 'ward.admit', 'beds.capacity.view',
            'admission.nursing.view', 'admission.nursing.notes.create', 'admission.nursing.notes.update',
            'admission.nursing.tasks.create', 'admission.nursing.tasks.update', 'admission.nursing.tasks.complete',
            'admission.care_flags.manage', 'admission.care_overview.view',
            'admission.medication_board.view', 'admission.mar_chart.view',
            'vitals.view', 'vitals.create',
        ] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->user->assignRole($role);

        $this->patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $this->visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'current_department_id' => $department->id,
            'created_by' => $this->user->id,
            'status' => VisitStatus::ADMITTING,
        ]);
        MedicalRecord::create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->user->id,
            'department_id' => $department->id,
        ]);

        $this->ward = Ward::create([
            'name' => 'Male Ward',
            'code' => 'MW01',
            'department_id' => $department->id,
            'capacity' => 20,
            'is_active' => true,
        ]);
        $this->bed = Bed::create([
            'ward_id' => $this->ward->id,
            'bed_number' => 'B001',
            'bed_type' => 'standard',
            'status' => BedStatus::AVAILABLE,
            'daily_rate' => 0,
        ]);
    }

    public function test_admission_show_renders_nursing_handover_and_checklist(): void
    {
        $admission = $this->admission();

        $this->actingAs($this->user)
            ->get(route('admin.admissions.show', $admission))
            ->assertOk()
            ->assertSee(__('admissions.nursing_handover'))
            ->assertSee(__('admissions.inpatient_care_checklist'))
            ->assertSee(__('admissions.check_latest_vitals'));
    }

    public function test_nursing_note_can_be_created_for_admission(): void
    {
        $admission = $this->admission();

        $this->actingAs($this->user)
            ->post(route('admin.admissions.nursing-notes.store', $admission), [
                'note_type' => NursingNoteType::OBSERVATION->value,
                'note' => 'Patient comfortable and responsive.',
                'observed_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('admin.admissions.show', $admission) . '#tab-nursing');

        $this->assertDatabaseHas('nursing_notes', [
            'admission_id' => $admission->id,
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'note_type' => NursingNoteType::OBSERVATION->value,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_nursing_note_creation_requires_permission(): void
    {
        $admission = $this->admission();
        $viewer = User::factory()->create();
        $role = Role::findOrCreate('Ward Viewer Only', 'web');
        $role->givePermissionTo(Permission::findOrCreate('ward.view', 'web'));
        $viewer->assignRole($role);

        $this->actingAs($viewer)
            ->post(route('admin.admissions.nursing-notes.store', $admission), [
                'note' => 'Should not save.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('nursing_notes', 0);
    }

    public function test_nursing_task_can_be_created_and_completed(): void
    {
        $admission = $this->admission();

        $this->actingAs($this->user)
            ->post(route('admin.admissions.nursing-tasks.store', $admission), [
                'task_type' => NursingTaskType::VITALS->value,
                'title' => 'Repeat vitals',
                'priority' => 'high',
                'due_at' => now()->subHour()->format('Y-m-d H:i:s'),
                'assigned_to' => $this->user->id,
            ])
            ->assertRedirect(route('admin.admissions.show', $admission) . '#tab-nursing');

        $task = $admission->fresh('nursingTasks')->nursingTasks->first();
        $this->assertSame(NursingTaskStatus::OPEN, $task->status);

        $this->actingAs($this->user)
            ->patch(route('admin.admissions.nursing-tasks.complete', [$admission, $task]))
            ->assertRedirect(route('admin.admissions.show', $admission) . '#tab-nursing');

        $task->refresh();
        $this->assertSame(NursingTaskStatus::COMPLETED, $task->status);
        $this->assertSame($this->user->id, $task->completed_by);
        $this->assertNotNull($task->completed_at);
    }

    public function test_care_flags_can_be_updated_and_rendered(): void
    {
        $admission = $this->admission();

        $this->actingAs($this->user)
            ->patch(route('admin.admissions.care-flags.update', $admission), [
                'care_flags' => [
                    AdmissionCareFlag::FALL_RISK->value,
                    AdmissionCareFlag::OXYGEN_REQUIRED->value,
                ],
            ])
            ->assertRedirect(route('admin.admissions.show', $admission) . '#tab-nursing');

        $this->assertSame([
            AdmissionCareFlag::FALL_RISK->value,
            AdmissionCareFlag::OXYGEN_REQUIRED->value,
        ], $admission->fresh()->care_flags);

        $this->actingAs($this->user)
            ->get(route('admin.admissions.show', $admission))
            ->assertOk()
            ->assertSee(AdmissionCareFlag::FALL_RISK->label())
            ->assertSee(AdmissionCareFlag::OXYGEN_REQUIRED->label());
    }

    public function test_capacity_board_shows_nursing_indicators_for_occupied_bed(): void
    {
        $admission = $this->admission();
        $admission->nursingTasks()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'created_by' => $this->user->id,
            'task_type' => NursingTaskType::VITALS,
            'title' => 'Repeat vitals',
            'priority' => 'high',
            'status' => NursingTaskStatus::OPEN,
            'due_at' => now()->subHour(),
        ]);
        Vital::create([
            'admission_id' => $admission->id,
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'recorded_by' => $this->user->id,
            'recorded_at' => now()->subHours(12),
        ]);

        $this->actingAs($this->user)
            ->get(route('admin.wards.bed-map'))
            ->assertOk()
            ->assertSee(__('admissions.open_nursing_tasks'))
            ->assertSee(__('admissions.vitals_due'));
    }

    private function admission()
    {
        $this->actingAs($this->user);

        return app(AdmissionService::class)->admit([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'bed_id' => $this->bed->id,
            'admission_type' => 'admission',
        ]);
    }
}
