<?php

namespace Tests\Feature\Consultations;

use App\Enums\BillingType;
use App\Enums\DepartmentType;
use App\Enums\InvoiceStatus;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Models\ConsultationSpecialtyBillingApplication;
use App\Models\ConsultationSpecialtyEntry;
use App\Models\ConsultationSpecialtyOrderSet;
use App\Models\ConsultationSpecialtyOrderSetApplication;
use App\Models\ConsultationSpecialtyOrderSetApplicationItem;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtyProfileMapping;
use App\Models\ConsultationSpecialtyServiceMapping;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\ConsultationSessionService;
use Database\Seeders\ConsultationSpecialtyOrderSetSeeder;
use Database\Seeders\ConsultationSpecialtySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConsultationSpecialtyReportingTest extends TestCase
{
    use RefreshDatabase;

    private User $reportUser;
    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(ConsultationSpecialtySeeder::class);
        $this->seed(ConsultationSpecialtyOrderSetSeeder::class);

        $this->reportUser = User::factory()->create();
        $role = Role::findOrCreate('Report Manager', 'web');
        foreach (['reports.view', 'reports.export'] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->reportUser->assignRole($role);

        $this->doctor = User::factory()->create();
        $doctorRole = Role::findOrCreate('Doctor', 'web');
        foreach (['consultations.view', 'consultations.create'] as $permission) {
            $doctorRole->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->doctor->assignRole($doctorRole);
    }

    public function test_specialty_report_page_renders_aggregates_without_raw_clinical_payload(): void
    {
        $this->specialistFixture();

        $response = $this->actingAs($this->reportUser)
            ->get(route('admin.reports.consultation-specialties.index'));

        $response->assertOk();
        $response->assertSee(__('reports.consultation_specialties.title'));
        $response->assertSee('Physiotherapy');
        $response->assertSee('GHS 150.00');
        $response->assertDontSee('Lower back clinical sentinel', false);
    }

    public function test_specialty_report_data_endpoint_returns_volume_billing_and_readiness_payload(): void
    {
        $this->specialistFixture();

        $response = $this->actingAs($this->reportUser)
            ->getJson(route('admin.reports.consultation-specialties.data'));

        $response->assertOk()
            ->assertJsonPath('summary.total_specialist_consultations', 1)
            ->assertJsonPath('summary.structured_entries_count', 2)
            ->assertJsonPath('summary.order_sets_applied', 1)
            ->assertJsonPath('summary.billing_applications_count', 1)
            ->assertJsonPath('summary.specialty_revenue', 150);

        $this->assertNotEmpty($response->json('readiness.by_status'));
    }

    public function test_specialty_report_export_requires_export_permission_and_streams_csv(): void
    {
        $this->specialistFixture();

        $viewerOnly = User::factory()->create();
        Permission::findOrCreate('reports.view', 'web');
        $viewerOnly->givePermissionTo('reports.view');

        $this->actingAs($viewerOnly)
            ->get(route('admin.reports.consultation-specialties.export'))
            ->assertForbidden();

        $response = $this->actingAs($this->reportUser)
            ->get(route('admin.reports.consultation-specialties.export', ['dataset' => 'summary']));

        $response->assertOk();
        $this->assertStringContainsString('SUMMARY', $response->streamedContent());
    }

    public function test_management_dashboard_contains_specialty_widget_for_report_users(): void
    {
        $this->specialistFixture();

        $response = $this->actingAs($this->reportUser)
            ->get(route('admin.reports.dashboard'));

        $response->assertOk();
        $response->assertSee(__('reports.consultation_specialties.widget_title'));
        $response->assertSee('Physiotherapy');
    }

    private function specialistFixture(): VisitConsultationRoute
    {
        $department = Department::factory()->create([
            'name' => 'Physiotherapy',
            'code' => 'PHY'.random_int(100, 999),
            'type' => DepartmentType::TREATMENT->value,
            'status' => 'active',
        ]);
        $this->doctor->forceFill(['department_id' => $department->id])->save();

        $patient = Patient::factory()->create(['registered_by' => $this->doctor->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->doctor->id,
            'status' => VisitStatus::CONSULTING->value,
            'current_department_id' => $department->id,
        ]);
        $service = ServiceCatalog::query()->create([
            'name' => 'Physiotherapy Consultation',
            'code' => 'PHYCONS'.random_int(1000, 9999),
            'category' => ServiceType::CONSULTATION->value,
            'department_id' => $department->id,
            'department_type' => DepartmentType::TREATMENT->value,
            'price' => 150,
            'is_active' => true,
            'is_billable' => true,
        ]);

        $route = VisitConsultationRoute::query()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'department_id' => $department->id,
            'service_id' => $service->id,
            'doctor_id' => $this->doctor->id,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'routed_by' => $this->doctor->id,
            'started_by' => $this->doctor->id,
            'started_at' => now(),
            'activated_at' => now(),
        ]);

        app(ConsultationSessionService::class)->getOrCreateMedicalRecordForRoute($route, $this->doctor);

        $profile = ConsultationSpecialtyProfile::query()->byCode('physiotherapy')->firstOrFail();
        ConsultationSpecialtyProfileMapping::query()->create([
            'consultation_specialty_profile_id' => $profile->id,
            'department_id' => $department->id,
            'source' => 'test',
            'priority' => 50,
            'is_active' => true,
        ]);

        ConsultationSpecialtyEntry::query()->create([
            'consultation_id' => $route->id,
            'consultation_specialty_profile_id' => $profile->id,
            'section_key' => 'pain_assessment',
            'entry' => ['pain_location' => 'Lower back clinical sentinel', 'pain_score' => 6],
            'created_by' => $this->doctor->id,
            'updated_by' => $this->doctor->id,
        ]);
        ConsultationSpecialtyEntry::query()->create([
            'consultation_id' => $route->id,
            'consultation_specialty_profile_id' => $profile->id,
            'section_key' => 'treatment_plan',
            'entry' => ['treatment_goals' => 'Improve mobility'],
            'created_by' => $this->doctor->id,
            'updated_by' => $this->doctor->id,
        ]);
        ConsultationSpecialtyEntry::query()->create([
            'consultation_id' => $route->id,
            'consultation_specialty_profile_id' => ConsultationSpecialtyProfile::query()->byCode(ConsultationSpecialtyProfile::GENERAL_MEDICINE)->firstOrFail()->id,
            'section_key' => 'complaints',
            'entry' => ['complaint' => 'General entry should not count'],
            'created_by' => $this->doctor->id,
            'updated_by' => $this->doctor->id,
        ]);

        $orderSet = ConsultationSpecialtyOrderSet::query()->where('consultation_specialty_profile_id', $profile->id)->firstOrFail();
        $application = ConsultationSpecialtyOrderSetApplication::query()->create([
            'consultation_id' => $route->id,
            'consultation_specialty_order_set_id' => $orderSet->id,
            'consultation_specialty_profile_id' => $profile->id,
            'applied_by' => $this->doctor->id,
            'status' => 'applied',
        ]);
        ConsultationSpecialtyOrderSetApplicationItem::query()->create([
            'consultation_specialty_order_set_application_id' => $application->id,
            'item_type' => 'task',
            'label' => 'Exercise review',
            'apply_mode' => 'suggest',
            'status' => 'applied',
        ]);

        $mapping = ConsultationSpecialtyServiceMapping::query()->create([
            'consultation_specialty_profile_id' => $profile->id,
            'service_id' => $service->id,
            'department_id' => $department->id,
            'mapping_context' => ConsultationSpecialtyServiceMapping::CONTEXT_CONSULTATION,
            'billing_trigger' => ConsultationSpecialtyServiceMapping::TRIGGER_MANUAL,
            'priority' => 10,
            'is_default' => true,
            'auto_bill' => false,
            'requires_confirmation' => true,
            'is_active' => true,
        ]);
        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-RPT-'.random_int(10000, 99999),
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'billing_type' => BillingType::CASH->value,
            'subtotal' => 150,
            'total_amount' => 150,
            'amount_paid' => 50,
            'balance' => 100,
            'status' => InvoiceStatus::PARTIALLY_PAID->value,
            'created_by' => $this->doctor->id,
        ]);
        $item = InvoiceItem::query()->create([
            'invoice_id' => $invoice->id,
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'service_catalog_id' => $service->id,
            'department_id' => $department->id,
            'source_type' => InvoiceItem::SOURCE_SPECIALTY_SERVICE_MAPPING,
            'source_id' => $mapping->id,
            'description' => 'Physiotherapy Consultation',
            'quantity' => 1,
            'unit_price' => 150,
            'cash_price' => 150,
            'selected_price' => 150,
            'patient_payable' => 150,
            'paid_amount' => 50,
            'balance' => 100,
            'total_price' => 150,
            'payment_status' => 'partially_paid',
            'created_by' => $this->doctor->id,
        ]);
        ConsultationSpecialtyBillingApplication::query()->create([
            'consultation_id' => $route->id,
            'consultation_specialty_profile_id' => $profile->id,
            'consultation_specialty_service_mapping_id' => $mapping->id,
            'service_id' => $service->id,
            'invoice_id' => $invoice->id,
            'invoice_item_id' => $item->id,
            'applied_by' => $this->doctor->id,
            'status' => ConsultationSpecialtyBillingApplication::STATUS_APPLIED,
            'trigger' => ConsultationSpecialtyServiceMapping::TRIGGER_MANUAL,
        ]);

        return $route;
    }
}
