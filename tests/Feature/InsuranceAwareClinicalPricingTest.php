<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\InsuranceType;
use App\Enums\ProcedureStatus;
use App\Models\Department;
use App\Models\InsuranceProvider;
use App\Models\LabRequest;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Models\ProcedureRequest;
use App\Models\ServiceCatalog;
use App\Models\ServicePrice;
use App\Models\User;
use App\Models\Visit;
use App\Services\InvestigationRequestService;
use App\Services\ProcedureRequestService;
use App\Services\ProcedureWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsuranceAwareClinicalPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_investigation_preview_and_billing_use_the_visit_insurance_tariff(): void
    {
        [$user, $visit, $provider] = $this->insuredVisit();
        $department = Department::factory()->create();
        $service = $this->pricedService($department, $provider, 'LAB-INS', 120, 75);
        $request = LabRequest::create([
            'request_number' => 'INV-INS-001',
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'requested_by' => $user->id,
            'department_id' => $department->id,
            'target_department_id' => $department->id,
            'urgency' => 'routine',
            'status' => 'pending',
        ]);
        $item = $request->items()->create([
            'service_id' => $service->id,
            'name' => $service->name,
            'status' => 'pending',
        ]);

        $workflow = app(InvestigationRequestService::class);
        $preview = $workflow->billingPreview($request);

        $this->assertSame(75.0, $preview[$item->id]['selected_price']);
        $this->assertSame('provider_specific_price', $preview[$item->id]['pricing_source']);

        $result = $workflow->acceptSelectedItems($request, [$item->id], $user);
        $invoiceItem = $result['invoice']->items()->first();

        $this->assertSame(75.0, (float) $invoiceItem->selected_price);
        $this->assertSame(75.0, (float) $invoiceItem->unit_price);
        $this->assertSame($provider->id, $invoiceItem->insurance_provider_id);
    }

    public function test_procedure_picker_preview_and_billing_use_the_visit_insurance_tariff(): void
    {
        [$user, $visit, $provider] = $this->insuredVisit();
        $department = Department::factory()->create([
            'type' => DepartmentType::PROCEDURE->value,
        ]);
        $service = $this->pricedService($department, $provider, 'PROC-INS', 500, 320);

        $pickerItem = app(ProcedureRequestService::class)
            ->servicesForDepartment($department->id, $visit)
            ->firstWhere('id', $service->id);

        $this->assertSame(320.0, $pickerItem['selected_price']);
        $this->assertSame('provider_specific_price', $pickerItem['pricing_source']);

        $procedure = ProcedureRequest::create([
            'request_number' => 'PROC-INS-001',
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'requested_by' => $user->id,
            'department_id' => $department->id,
            'service_catalog_id' => $service->id,
            'priority' => 'routine',
            'indication' => 'Regression fixture',
            'status' => ProcedureStatus::ACCEPTED,
            'accepted_by' => $user->id,
            'accepted_at' => now(),
            'requested_at' => now(),
        ]);

        $workflow = app(ProcedureWorkflowService::class);
        $preview = $workflow->billingPreview($procedure);
        $billed = $workflow->generateBilling($procedure, $user);

        $this->assertSame(320.0, $preview['selected_price']);
        $this->assertSame(320.0, (float) $billed->billingItem->selected_price);
        $this->assertSame($provider->id, $billed->billingItem->insurance_provider_id);
    }

    private function insuredVisit(): array
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['registered_by' => $user->id]);
        $provider = InsuranceProvider::create([
            'name' => 'Clinical Tariff Assurance',
            'short_name' => 'CTA',
            'code' => 'CTA',
            'type' => InsuranceType::PRIVATE->value,
            'is_active' => true,
            'is_default' => false,
        ]);
        $insurance = PatientInsurance::create([
            'patient_id' => $patient->id,
            'insurance_provider_id' => $provider->id,
            'membership_number' => 'CTA-10001',
            'start_date' => now()->subMonth()->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
            'is_primary' => true,
            'is_active' => true,
        ]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'visit_insurance_id' => $insurance->id,
            'created_by' => $user->id,
        ]);

        return [$user, $visit, $provider];
    }

    private function pricedService(
        Department $department,
        InsuranceProvider $provider,
        string $code,
        float $cashPrice,
        float $insurancePrice,
    ): ServiceCatalog {
        $service = ServiceCatalog::create([
            'name' => $code,
            'code' => $code,
            'category' => 'clinical',
            'department_id' => $department->id,
            'price' => $cashPrice,
            'is_active' => true,
            'is_billable' => true,
        ]);

        ServicePrice::create([
            'service_catalog_id' => $service->id,
            'insurance_type' => InsuranceType::PRIVATE->value,
            'insurance_provider_id' => $provider->id,
            'price' => $insurancePrice,
        ]);

        return $service;
    }
}
