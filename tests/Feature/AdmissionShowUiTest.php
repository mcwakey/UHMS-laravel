<?php

namespace Tests\Feature;

use App\Enums\BedStatus;
use App\Enums\VisitStatus;
use App\Models\Bed;
use App\Models\Department;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\Ward;
use App\Services\AdmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Guards the admission show page UI/UX cleanup: the full management view must
 * render for a ward-manager role, and the (previously duplicated) vitals entry
 * form must now appear exactly once.
 */
class AdmissionShowUiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;
    private Visit $visit;
    private Bed $bed;

    protected function setUp(): void
    {
        parent::setUp();
        config(['audit_streaming.async_writes' => false]);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $department = Department::factory()->create();
        $this->user = User::factory()->create(['department_id' => $department->id]);

        $role = Role::findOrCreate('Ward Manager Tester', 'web');
        foreach ([
            'ward.view', 'ward.admit', 'ward.manage', 'ward.discharge', 'beds.transfer',
            'admission.nursing.view', 'admission.care_overview.view',
            'admission.discharge.readiness.view',
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

        $ward = Ward::create([
            'name' => 'Male Ward', 'code' => 'MW01', 'department_id' => $department->id,
            'capacity' => 20, 'is_active' => true,
        ]);
        $this->bed = Bed::create([
            'ward_id' => $ward->id, 'bed_number' => 'B001', 'bed_type' => 'standard',
            'status' => BedStatus::AVAILABLE, 'daily_rate' => 0,
        ]);
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

    public function test_full_management_view_renders_with_tabs(): void
    {
        $admission = $this->admission();

        $this->actingAs($this->user)
            ->get(route('admin.admissions.show', $admission))
            ->assertOk()
            ->assertSee($admission->patient->full_name)
            ->assertSee($admission->admission_number)
            ->assertSee(__('admissions.tab_consultation'))
            ->assertSee(__('admissions.tab_vitals'))
            ->assertSee(__('admissions.tab_billing'));
    }

    public function test_vitals_form_is_not_duplicated_in_full_view(): void
    {
        $admission = $this->admission();

        $content = $this->actingAs($this->user)
            ->get(route('admin.admissions.show', $admission))
            ->assertOk()
            ->getContent();

        // The vitals form posts to this route. It must appear exactly once now
        // that the nurse-view and vitals-tab copies were unified into a partial.
        // The page is delivered as an Inertia JSON payload, so the Blade HTML is
        // embedded with JSON-escaped slashes — match the escaped URL form.
        $needle = trim(json_encode(route('admin.admissions.vitals.store', $admission)), '"');
        $this->assertSame(
            1,
            substr_count($content, $needle),
            'The vitals entry form should render exactly once.'
        );
    }
}
