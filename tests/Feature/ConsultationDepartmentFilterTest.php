<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Department;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConsultationDepartmentFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_only_sees_consultation_visits_for_their_department(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $ownDepartment = Department::factory()->create(['name' => 'General Medicine', 'type' => DepartmentType::CONSULTATION]);
        $otherDepartment = Department::factory()->create(['name' => 'Paediatrics', 'type' => DepartmentType::CONSULTATION]);
        /** @var User $staff */
        $staff = User::factory()->create(['department_id' => $ownDepartment->id]);
        $role = Role::findOrCreate('Department Doctor', 'web');
        $role->givePermissionTo(Permission::findOrCreate('consultations.view', 'web'));
        $staff->assignRole($role);

        $patient = Patient::factory()->create(['registered_by' => $staff->id]);
        $ownService = ServiceCatalog::create([
            'name' => 'General consultation',
            'code' => 'CONS-GEN',
            'category' => 'consultation',
            'price' => 50,
            'is_active' => true,
            'department_id' => $ownDepartment->id,
            'department_type' => DepartmentType::CONSULTATION,
        ]);
        $otherService = ServiceCatalog::create([
            'name' => 'Paediatrics consultation',
            'code' => 'CONS-PAED',
            'category' => 'consultation',
            'price' => 50,
            'is_active' => true,
            'department_id' => $otherDepartment->id,
            'department_type' => DepartmentType::CONSULTATION,
        ]);
        $ownVisit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $staff->id,
            'visit_type' => VisitType::OUTPATIENT,
            'status' => VisitStatus::CONSULTING,
            'current_department_id' => $ownDepartment->id,
            'chief_complaint' => 'Visible headache',
        ]);
        VisitConsultationRoute::create([
            'visit_id' => $ownVisit->id,
            'patient_id' => $patient->id,
            'department_id' => $ownDepartment->id,
            'service_id' => $ownService->id,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'routed_by' => $staff->id,
        ]);
        $otherVisit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $staff->id,
            'visit_type' => VisitType::OUTPATIENT,
            'status' => VisitStatus::CONSULTING,
            'current_department_id' => $otherDepartment->id,
            'chief_complaint' => 'Hidden cough',
        ]);
        VisitConsultationRoute::create([
            'visit_id' => $otherVisit->id,
            'patient_id' => $patient->id,
            'department_id' => $otherDepartment->id,
            'service_id' => $otherService->id,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'routed_by' => $staff->id,
        ]);

        $response = $this->actingAs($staff)->get(route('admin.consultations.index'));

        $response->assertOk()
            ->assertSee($ownVisit->visit_number)
            ->assertDontSee('Hidden cough');
    }
}
