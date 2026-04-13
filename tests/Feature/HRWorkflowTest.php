<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HRWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $dept = Department::factory()->create();
        $this->user = User::factory()->create(['department_id' => $dept->id]);
        $role = Role::create(['name' => 'HRManager']);
        foreach ([
            'hr.employees.view', 'hr.employees.create', 'hr.employees.edit',
            'hr.leave.view', 'hr.leave.create', 'hr.leave.approve',
            'hr.payroll.view', 'hr.payroll.process',
            'hr.attendance.view', 'hr.attendance.manage',
        ] as $p) {
            $perm = Permission::create(['name' => $p]);
            $role->givePermissionTo($perm);
        }
        $this->user->assignRole($role);
    }

    public function test_employee_index_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.employees.index'));
        $response->assertStatus(200);
    }

    public function test_leave_index_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.leave.index'));
        $response->assertStatus(200);
    }

    public function test_payroll_index_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.payroll.index'));
        $response->assertStatus(200);
    }

    public function test_attendance_index_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.attendance.index'));
        $response->assertStatus(200);
    }
}
