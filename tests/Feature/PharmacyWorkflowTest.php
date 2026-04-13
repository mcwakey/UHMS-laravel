<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PharmacyWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $dept = Department::factory()->create();
        $this->user = User::factory()->create(['department_id' => $dept->id]);
        $role = Role::create(['name' => 'Pharmacist']);
        foreach ([
            'pharmacy.dispensing.view', 'pharmacy.dispensing.create',
            'pharmacy.drugs.manage', 'pharmacy.stock.manage',
            'patients.view', 'visits.view', 'prescriptions.view',
        ] as $p) {
            $perm = Permission::create(['name' => $p]);
            $role->givePermissionTo($perm);
        }
        $this->user->assignRole($role);
    }

    public function test_dispensing_index_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.dispensing.index'));
        $response->assertStatus(200);
    }

    public function test_drug_index_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.drugs.index'));
        $response->assertStatus(200);
    }

    public function test_drug_stock_index_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.drug-stock.index'));
        $response->assertStatus(200);
    }
}
