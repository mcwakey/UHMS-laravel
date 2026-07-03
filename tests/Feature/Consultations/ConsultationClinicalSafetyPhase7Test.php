<?php

namespace Tests\Feature\Consultations;

use App\Enums\DepartmentType;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Department;
use App\Models\Drug;
use App\Models\DrugCategory;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\ConsultationSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConsultationClinicalSafetyPhase7Test extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private Department $department;

    private ServiceCatalog $service;

    private Drug $amoxicillin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['audit_streaming.async_writes' => false]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::findOrCreate('Doctor', 'web');
        Role::findOrCreate('Pharmacist', 'web');
        foreach ([
            'consultations.view',
            'consultations.create',
            'prescriptions.create',
            'visits.transition',
        ] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $this->department = Department::factory()->create([
            'name' => 'General Medicine',
            'type' => DepartmentType::CONSULTATION->value,
            'status' => 'active',
        ]);
        $this->service = ServiceCatalog::create([
            'name' => 'General Consultation',
            'code' => 'CONS'.random_int(1000, 9999),
            'category' => ServiceType::CONSULTATION->value,
            'department_id' => $this->department->id,
            'department_type' => DepartmentType::CONSULTATION->value,
            'price' => 50,
            'is_active' => true,
            'is_billable' => true,
        ]);

        $this->doctor = User::factory()->create(['department_id' => $this->department->id]);
        $this->doctor->assignRole($role);

        $category = DrugCategory::create(['name' => 'Antibiotics', 'is_active' => true]);
        $this->amoxicillin = Drug::create([
            'category_id' => $category->id,
            'name' => 'Amoxicillin',
            'generic_name' => 'Amoxicillin',
            'dosage_form' => 'tablet',
            'strength' => '500mg',
            'unit' => 'tablet',
            'price' => 10,
            'requires_prescription' => true,
            'is_active' => true,
        ]);
    }

    public function test_allergy_warning_requires_override_before_prescription_is_created(): void
    {
        [$visit, $route] = $this->consultingVisit(['allergies' => 'Amoxicillin']);

        $this->postJsonWithKey(route('admin.consultations.prescriptions.store', $visit), $this->prescriptionPayload($route), 'rx-allergy-warning')
            ->assertStatus(409)
            ->assertJsonPath('requires_override', true)
            ->assertJsonFragment(['code' => 'allergy_conflict']);

        $this->assertSame(0, Prescription::where('visit_id', $visit->id)->count());
        $this->assertDatabaseHas('activity_log', [
            'event' => 'PRESCRIPTION_SAFETY_WARNING_TRIGGERED',
            'patient_id' => $visit->patient_id,
            'visit_id' => $visit->id,
        ]);
    }

    public function test_override_reason_allows_warned_prescription_and_audits_reason(): void
    {
        [$visit, $route] = $this->consultingVisit(['allergies' => 'Amoxicillin']);
        $payload = $this->prescriptionPayload($route) + [
            'safety_override_reason' => 'Benefits outweigh documented mild rash history.',
            'safety_override_codes' => ['allergy_conflict'],
        ];

        $this->postJsonWithKey(route('admin.consultations.prescriptions.store', $visit), $payload, 'rx-allergy-override')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(1, Prescription::where('visit_id', $visit->id)->count());
        $this->assertDatabaseHas('activity_log', [
            'event' => 'PRESCRIPTION_SAFETY_OVERRIDE_ACCEPTED',
            'patient_id' => $visit->patient_id,
            'visit_id' => $visit->id,
        ]);
    }

    public function test_duplicate_active_medication_warns_but_can_be_overridden_for_legitimate_repeat(): void
    {
        [$visit, $route] = $this->consultingVisit();

        $firstPayload = $this->prescriptionPayload($route);
        $firstPayload['items'][0]['dosage'] = '250mg';
        $this->postJsonWithKey(route('admin.consultations.prescriptions.store', $visit), $firstPayload, 'rx-first')->assertOk();

        $this->postJsonWithKey(route('admin.consultations.prescriptions.store', $visit), $this->prescriptionPayload($route), 'rx-duplicate-warning')
            ->assertStatus(409)
            ->assertJsonFragment(['code' => 'duplicate_active_medication']);

        $repeatPayload = $this->prescriptionPayload($route) + [
            'safety_override_reason' => 'Second course for split regimen.',
            'safety_override_codes' => ['duplicate_active_medication'],
        ];
        $this->postJsonWithKey(route('admin.consultations.prescriptions.store', $visit), $repeatPayload, 'rx-duplicate-override')
            ->assertOk();

        $this->assertSame(2, Prescription::where('visit_id', $visit->id)->count());
    }

    public function test_missing_diagnosis_blocks_when_policy_requires_it(): void
    {
        config(['consultation.prescriptions.require_diagnosis_before_prescribing' => true]);
        [$visit, $route] = $this->consultingVisit();

        $this->postJsonWithKey(route('admin.consultations.prescriptions.store', $visit), $this->prescriptionPayload($route), 'rx-missing-dx')
            ->assertStatus(422)
            ->assertJsonFragment(['code' => 'missing_diagnosis']);

        $this->assertSame(0, Prescription::where('visit_id', $visit->id)->count());
    }

    public function test_completion_is_blocked_and_audited_when_requirements_are_missing(): void
    {
        [$visit, $route] = $this->consultingVisit();
        $this->addClinicalRecord($route, ['examination', 'diagnosis', 'plan']);

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.routes.complete', [$visit, $route]), [
                'notes' => 'Ready to close',
            ])
            ->assertStatus(422)
            ->assertJsonPath('event', 'CONSULTATION_COMPLETION_BLOCKED')
            ->assertJsonFragment(['code' => 'complaint']);

        $this->assertSame(VisitConsultationRoute::STATUS_ACTIVE, $route->fresh()->status);
        $this->assertDatabaseHas('activity_log', [
            'event' => 'CONSULTATION_COMPLETION_BLOCKED',
            'patient_id' => $visit->patient_id,
            'visit_id' => $visit->id,
        ]);
    }

    public function test_completion_succeeds_when_checklist_is_satisfied(): void
    {
        [$visit, $route] = $this->consultingVisit();
        $this->addClinicalRecord($route);

        $this->actingAs($this->doctor)
            ->postJson(route('admin.consultations.routes.complete', [$visit, $route]), [
                'notes' => 'Clinical session complete',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(VisitConsultationRoute::STATUS_COMPLETED, $route->fresh()->status);
    }

    public function test_readiness_card_renders_missing_and_met_requirements(): void
    {
        [$visit, $route] = $this->consultingVisit();
        $this->addClinicalRecord($route, ['complaint', 'diagnosis']);

        $this->actingAs($this->doctor)
            ->get(route('admin.consultations.routes.show', [$visit, $route]))
            ->assertOk()
            ->assertSee(__('consultation.completion.readiness_title'))
            ->assertSee(__('consultation.completion.not_ready'))
            ->assertSee(__('consultation.completion.requirement.examination'));
    }

    private function postJsonWithKey(string $uri, array $payload, string $key)
    {
        return $this->actingAs($this->doctor)
            ->withHeaders(['Idempotency-Key' => $key])
            ->postJson($uri, $payload);
    }

    private function consultingVisit(array $patientOverrides = []): array
    {
        $patient = Patient::factory()->create(array_merge(['registered_by' => $this->doctor->id], $patientOverrides));
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->doctor->id,
            'visit_type' => VisitType::OUTPATIENT,
            'status' => VisitStatus::CONSULTING,
            'current_department_id' => $this->department->id,
            'chief_complaint' => null,
        ]);

        $route = VisitConsultationRoute::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'department_id' => $this->department->id,
            'service_id' => $this->service->id,
            'doctor_id' => $this->doctor->id,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'routed_by' => $this->doctor->id,
            'started_by' => $this->doctor->id,
            'started_at' => now(),
            'activated_at' => now(),
        ]);

        app(ConsultationSessionService::class)->getOrCreateMedicalRecordForRoute($route, $this->doctor);

        return [$visit, $route->fresh('medicalRecord')];
    }

    private function prescriptionPayload(VisitConsultationRoute $route): array
    {
        return [
            'consultation_route_id' => $route->id,
            'notes' => 'Phase 7 safety fixture',
            'items' => [[
                'drug_id' => $this->amoxicillin->id,
                'drug_name' => $this->amoxicillin->name,
                'dosage' => '500mg',
                'frequency' => 'BD',
                'duration' => '5 days',
                'quantity' => 10,
                'route' => 'oral',
                'instructions' => 'After meals',
            ]],
        ];
    }

    private function addClinicalRecord(VisitConsultationRoute $route, array $sections = ['complaint', 'examination', 'diagnosis', 'plan']): void
    {
        $record = $route->medicalRecord;
        $base = [
            'consultation_route_id' => $route->id,
            'visit_id' => $route->visit_id,
            'patient_id' => $route->patient_id,
            'department_id' => $route->department_id,
            'doctor_id' => $this->doctor->id,
            'created_by' => $this->doctor->id,
        ];

        if (in_array('complaint', $sections, true)) {
            $record->complaints()->create($base + ['description' => 'Fever and headache']);
        }

        if (in_array('examination', $sections, true)) {
            $record->physicalExaminations()->create($base + ['findings' => 'Febrile, stable vitals']);
        }

        if (in_array('diagnosis', $sections, true)) {
            $record->diagnoses()->create($base + [
                'description' => 'Upper respiratory tract infection',
                'type' => 'provisional',
                'is_primary' => true,
            ]);
        }

        if (in_array('plan', $sections, true)) {
            $record->treatments()->create($base + [
                'type' => 'advice',
                'description' => 'Hydration and follow-up if symptoms persist',
            ]);
        }
    }
}
