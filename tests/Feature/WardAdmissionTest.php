<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\Ward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WardAdmissionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;
    private Visit $visit;
    private Ward $ward;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $dept = Department::factory()->create();
        $this->user = User::factory()->create(['department_id' => $dept->id]);
        $role = Role::create(['name' => 'Admin']);
        foreach ([
            'ward.view', 'ward.manage', 'ward.admit', 'ward.discharge',
            'beds.view', 'beds.manage', 'patients.view', 'visits.view',
        ] as $p) {
            $perm = Permission::create(['name' => $p]);
            $role->givePermissionTo($perm);
        }
        $this->user->assignRole($role);

        $this->patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $this->visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'department_id' => $dept->id,
            'created_by' => $this->user->id,
        ]);
        $this->ward = Ward::create([
            'name' => 'Male Ward',
            'code' => 'MW01',
            'department_id' => $dept->id,
            'capacity' => 20,
            'is_active' => true,
        ]);
    }

    public function test_ward_index_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.wards.index'));
        $response->assertStatus(200);
    }

    public function test_ward_can_be_created(): void
    {
        $dept = Department::factory()->create();
        $data = [
            'name' => 'Female Ward',
            'code' => 'FW01',
            'department_id' => $dept->id,
            'capacity' => 15,
        ];

        $response = $this->actingAs($this->user)->post(route('admin.wards.store'), $data);
        $response->assertRedirect();
        $this->assertDatabaseHas('wards', ['code' => 'FW01']);
    }

    public function test_admission_index_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.admissions.index'));
        $response->assertStatus(200);
    }

    public function test_bed_can_be_created_for_ward(): void
    {
        $data = [
            'ward_id' => $this->ward->id,
            'bed_number' => 'B001',
            'bed_type' => 'standard',
        ];

        $response = $this->actingAs($this->user)->post(route('admin.beds.store'), $data);
        $response->assertRedirect();
        $this->assertDatabaseHas('beds', ['bed_number' => 'B001']);
    }
}
