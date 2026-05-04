<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\LabTest;
use App\Models\LabTestCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LabWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $dept = Department::factory()->create();
        $this->user = User::factory()->create(['department_id' => $dept->id]);
        $role = Role::create(['name' => 'LabTech']);
        foreach ([
            'lab.requests.view', 'lab.requests.create', 'lab.results.view',
            'lab.results.create', 'lab.tests.manage', 'patients.view', 'visits.view',
        ] as $p) {
            $perm = Permission::create(['name' => $p]);
            $role->givePermissionTo($perm);
        }
        $this->user->assignRole($role);
    }

    public function test_lab_request_index_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.lab.requests.index'));
        $response->assertStatus(200);
    }

    public function test_lab_test_management_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.lab.tests.index'));
        $response->assertStatus(200);
    }

    public function test_lab_result_index_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.lab.results.index'));
        $response->assertStatus(200);
    }

    public function test_lab_test_can_store_multiple_criteria(): void
    {
        $category = LabTestCategory::create([
            'name' => 'Haematology',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->post(route('admin.lab.tests.store'), [
            'category_id' => $category->id,
            'name' => 'Full Blood Count',
            'code' => 'FBC',
            'price' => 45,
            'criteria' => [
                ['name' => 'Haemoglobin', 'normal_range' => '12-16', 'unit' => 'g/dL'],
                ['name' => 'White Cell Count', 'normal_range' => '4-11', 'unit' => '10^9/L'],
            ],
        ]);

        $test = LabTest::where('code', 'FBC')->first();

        $response->assertRedirect();
        $this->assertNotNull($test);
        $this->assertSame(2, $test->criteria()->count());
        $this->assertDatabaseHas('lab_test_criteria', [
            'lab_test_id' => $test->id,
            'name' => 'Haemoglobin',
            'normal_range' => '12-16',
            'unit' => 'g/dL',
        ]);
    }
}
