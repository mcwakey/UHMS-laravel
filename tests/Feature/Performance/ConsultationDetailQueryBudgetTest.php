<?php

namespace Tests\Feature\Performance;

use App\Enums\DepartmentType;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Department;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Models\VisitConsultationRouteService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\InteractsWithDatabaseQueryBudgets;
use Tests\TestCase;

class ConsultationDetailQueryBudgetTest extends TestCase
{
    use InteractsWithDatabaseQueryBudgets;
    use RefreshDatabase;

    public function test_doctor_consultation_detail_stays_within_its_query_budget(): void
    {
        [$doctor, $visit, $route] = $this->consultationFixture();

        $metrics = $this->assertMaxDatabaseQueries(110, fn () => $this
            ->actingAs($doctor)
            ->get(route('doctor.consultations.routes.show', [$visit, $route]))
            ->assertOk());

        $this->assertLessThanOrEqual(20, $metrics['duplicate']);
    }

    public function test_consultation_detail_query_count_does_not_scale_with_clinical_entries(): void
    {
        [$doctor, $visit, $route, $record] = $this->consultationFixture();

        $small = $this->measureDatabaseQueries(fn () => $this
            ->actingAs($doctor)
            ->get(route('doctor.consultations.routes.show', [$visit, $route]))
            ->assertOk());

        foreach (range(1, 30) as $index) {
            $this->addInvestigation($record, $doctor, "Additional investigation {$index}");
        }

        $large = $this->measureDatabaseQueries(fn () => $this
            ->actingAs($doctor)
            ->get(route('doctor.consultations.routes.show', [$visit, $route]))
            ->assertOk());

        $this->assertLessThanOrEqual(
            $small['total'] + 3,
            $large['total'],
            "Consultation query count grew from {$small['total']} to {$large['total']} with additional entries.",
        );
    }

    private function consultationFixture(): array
    {
        $department = Department::create([
            'name' => 'Consultation Performance',
            'code' => 'CONS-PERF',
            'type' => DepartmentType::CONSULTATION->value,
            'status' => 'active',
        ]);
        $doctor = User::factory()->create(['department_id' => $department->id]);
        Role::findOrCreate('Doctor', 'web');
        $doctor->assignRole(Role::findOrCreate('Super Admin', 'web'));
        app(ConsultationSpecialtyProfileService::class)->ensureGeneralProfileExists();

        $patient = Patient::factory()->create(['registered_by' => $doctor->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $doctor->id,
            'visit_type' => VisitType::OUTPATIENT,
            'status' => VisitStatus::CONSULTING,
            'current_department_id' => $department->id,
        ]);
        $service = ServiceCatalog::create([
            'name' => 'Performance Consultation',
            'code' => 'CONS-PERF-SVC',
            'category' => ServiceType::CONSULTATION->value,
            'department_id' => $department->id,
            'department_type' => DepartmentType::CONSULTATION->value,
            'price' => 50,
            'is_active' => true,
            'is_billable' => true,
        ]);
        $route = VisitConsultationRoute::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'department_id' => $department->id,
            'service_id' => $service->id,
            'doctor_id' => $doctor->id,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'routed_by' => $doctor->id,
            'started_by' => $doctor->id,
            'started_at' => now(),
            'activated_at' => now(),
        ]);
        VisitConsultationRouteService::create([
            'visit_consultation_route_id' => $route->id,
            'visit_id' => $visit->id,
            'service_id' => $service->id,
        ]);

        $record = MedicalRecord::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $department->id,
            'service_id' => $service->id,
            'consultation_route_id' => $route->id,
        ]);
        $this->addInvestigation($record, $doctor, 'Baseline investigation');

        return [$doctor, $visit, $route, $record];
    }

    private function addInvestigation(MedicalRecord $record, User $doctor, string $description): void
    {
        $record->investigations()->create([
            'consultation_route_id' => $record->consultation_route_id,
            'visit_id' => $record->visit_id,
            'patient_id' => $record->patient_id,
            'department_id' => $record->department_id,
            'doctor_id' => $doctor->id,
            'created_by' => $doctor->id,
            'investigation_type' => 'laboratory',
            'description' => $description,
            'urgency' => 'routine',
            'status' => 'pending',
        ]);
    }
}
