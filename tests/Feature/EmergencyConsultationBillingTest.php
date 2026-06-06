<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\EmergencyCase;
use App\Models\InvoiceItem;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\Setting;
use App\Models\User;
use App\Models\VisitConsultationRoute;
use App\Models\VisitConsultationRouteService;
use App\Services\EmergencyCaseService;
use App\Services\EmergencySessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Emergency case creation must treat the case as a billable Emergency / Casualty
 * consultation: the consultation route carries the emergency consultation service,
 * the service is billed onto the running invoice (no pay-before-service gate), and
 * the route↔service↔invoice link exists — all idempotently.
 */
class EmergencyConsultationBillingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;
    private Department $emergencyDept;
    private ServiceCatalog $emergencyService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->emergencyDept = Department::factory()->create([
            'name' => 'Emergency / Casualty', 'code' => 'EMR', 'type' => 'consultation',
        ]);
        $this->emergencyService = ServiceCatalog::create([
            'name' => 'Emergency Consultation', 'code' => 'EMR-CON', 'category' => 'consultation',
            'price' => 250, 'is_active' => true, 'is_billable' => true,
            'department_id' => $this->emergencyDept->id,
        ]);

        $this->patient = Patient::factory()->create(['registered_by' => $this->user->id]);
    }

    private function createCase(): EmergencyCase
    {
        return app(EmergencyCaseService::class)->create([
            'patient_id' => $this->patient->id,
            'arrival_mode' => 'AMBULANCE',
            'arrival_time' => now()->format('Y-m-d H:i:s'),
            'chief_complaint' => 'RTA',
        ], $this->user);
    }

    public function test_emergency_route_carries_the_consultation_service(): void
    {
        $case = $this->createCase();

        $route = VisitConsultationRoute::where('emergency_case_id', $case->id)->first();
        $this->assertNotNull($route);
        $this->assertSame($this->emergencyService->id, (int) $route->service_id);
        $this->assertSame(VisitConsultationRoute::SESSION_TYPE_EMERGENCY, $route->session_type);

        // The medical record (clinical history) inherits the service.
        $record = MedicalRecord::where('consultation_route_id', $route->id)->first();
        $this->assertNotNull($record);
        $this->assertSame($this->emergencyService->id, (int) $record->service_id);
    }

    public function test_emergency_consultation_service_is_billed_under_running_bill(): void
    {
        $case = $this->createCase();

        $item = InvoiceItem::where('visit_id', $case->visit_id)
            ->where('service_catalog_id', $this->emergencyService->id)
            ->first();

        $this->assertNotNull($item, 'Emergency consultation must be billed.');
        $this->assertSame('emergency_service', $item->source_type);
        $this->assertSame(250.0, (float) $item->total_price);

        // Running-bill: the item is unpaid but care is NOT blocked — case + session active.
        $this->assertSame('unpaid', $item->payment_status);
        $this->assertSame(EmergencyCase::STATUS_WAITING_TRIAGE, $case->emergency_status);
        $this->assertDatabaseHas('emergency_sessions', [
            'emergency_case_id' => $case->id,
            'status' => 'ACTIVE',
        ]);

        // route ↔ service ↔ invoice link exists.
        $route = VisitConsultationRoute::where('emergency_case_id', $case->id)->first();
        $this->assertDatabaseHas('visit_consultation_route_services', [
            'visit_consultation_route_id' => $route->id,
            'service_id' => $this->emergencyService->id,
            'invoice_item_id' => $item->id,
        ]);
    }

    public function test_billing_and_session_are_idempotent_on_re_ensure(): void
    {
        $case = $this->createCase();

        // Simulate the show page re-ensuring the session repeatedly.
        app(EmergencySessionService::class)->getOrCreateForCase($case->fresh(), $this->user);
        app(EmergencySessionService::class)->getOrCreateForCase($case->fresh(), $this->user);

        $this->assertSame(1, VisitConsultationRoute::where('emergency_case_id', $case->id)->count());
        $this->assertSame(1, InvoiceItem::where('visit_id', $case->visit_id)
            ->where('service_catalog_id', $this->emergencyService->id)->count());
        $this->assertSame(1, VisitConsultationRouteService::where('service_id', $this->emergencyService->id)->count());
    }

    public function test_configured_setting_overrides_the_default_service(): void
    {
        $custom = ServiceCatalog::create([
            'name' => 'Casualty Consultation', 'code' => 'CAS-CON', 'category' => 'consultation',
            'price' => 300, 'is_active' => true, 'is_billable' => true,
            'department_id' => $this->emergencyDept->id,
        ]);
        Setting::setValue('emergency', 'default_consultation_service_id', $custom->id, 'integer');

        $case = $this->createCase();

        $route = VisitConsultationRoute::where('emergency_case_id', $case->id)->first();
        $this->assertSame($custom->id, (int) $route->service_id);
        $this->assertDatabaseHas('invoice_items', [
            'visit_id' => $case->visit_id,
            'service_catalog_id' => $custom->id,
            'source_type' => 'emergency_service',
        ]);
    }

    public function test_no_emergency_service_configured_does_not_break_creation(): void
    {
        // Remove the only emergency consultation service → resolver returns null.
        $this->emergencyService->update(['is_active' => false]);

        $case = $this->createCase();

        $this->assertNotNull($case);
        $route = VisitConsultationRoute::where('emergency_case_id', $case->id)->first();
        $this->assertNotNull($route, 'Session/route still created even without a billable service.');
        $this->assertNull($route->service_id);
        $this->assertSame(0, InvoiceItem::where('visit_id', $case->visit_id)->count());
    }
}
