<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Department;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConsultationDepartmentFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_only_sees_consultation_visits_for_their_department(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $ownDepartment = Department::factory()->create(['name' => 'General Medicine', 'type' => DepartmentType::CONSULTATION]);
        $otherDepartment = Department::factory()->create(['name' => 'Paediatrics', 'type' => DepartmentType::CONSULTATION]);
        /** @var User $staff */
        $staff = User::factory()->create(['department_id' => $ownDepartment->id]);
        $role = Role::findOrCreate('Department Doctor', 'web');
        $role->givePermissionTo(Permission::findOrCreate('consultations.view', 'web'));
        $staff->assignRole($role);

        $patient = Patient::factory()->create(['registered_by' => $staff->id]);
        $ownVisit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $staff->id,
            'visit_type' => VisitType::OUTPATIENT,
            'status' => VisitStatus::CONSULTING,
            'current_department_id' => $ownDepartment->id,
            'chief_complaint' => 'Visible headache',
        ]);
        Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $staff->id,
            'visit_type' => VisitType::OUTPATIENT,
            'status' => VisitStatus::CONSULTING,
            'current_department_id' => $otherDepartment->id,
            'chief_complaint' => 'Hidden cough',
        ]);

        $response = $this->actingAs($staff)->get(route('admin.consultations.index'));

        $response->assertOk()
            ->assertSee($ownVisit->visit_number)
            ->assertDontSee('Hidden cough');
    }
}