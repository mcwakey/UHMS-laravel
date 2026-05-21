<?php

namespace Tests\Feature;

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Diagnosis;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Triage;
use App\Models\User;
use App\Models\Visit;
use App\Services\VisitPreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VisitPreviewTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->user = User::factory()->create();

        $role = Role::create(['name' => 'TestClinician']);
        foreach (['visits.view', 'visits.preview'] as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }
        $role->givePermissionTo(['visits.view', 'visits.preview']);
        $this->user->assignRole($role);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->user = $this->user->fresh();

        $this->visit = Visit::factory()->create([
            'visit_type'   => VisitType::OUTPATIENT,
            'status'       => VisitStatus::REGISTERED,
            'chief_complaint' => 'Headache and fever',
            'created_by'   => $this->user->id,
        ]);
    }

    // ── Authorization ────────────────────────────────────────────

    public function test_authorized_user_can_open_visit_preview(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('admin.visits.preview', $this->visit));

        $response->assertStatus(200);
        $response->assertSee('Visit Preview');
    }

    public function test_unauthorized_user_cannot_open_visit_preview(): void
    {
        $noPermsUser = User::factory()->create();

        $response = $this->actingAs($noPermsUser)
            ->get(route('admin.visits.preview', $this->visit));

        $response->assertStatus(403);
    }

    public function test_guest_cannot_open_visit_preview(): void
    {
        $response = $this->get(route('admin.visits.preview', $this->visit));
        $response->assertRedirect(route('login'));
    }

    // ── Page loading ─────────────────────────────────────────────

    public function test_visit_preview_page_loads_for_opd_visit(): void
    {
        $this->visit->update(['visit_type' => VisitType::OUTPATIENT]);

        $response = $this->actingAs($this->user)
            ->get(route('admin.visits.preview', $this->visit));

        $response->assertStatus(200);
        $response->assertSee($this->visit->visit_number);
        $response->assertSee($this->visit->patient->full_name);
    }

    public function test_visit_preview_page_loads_for_emergency_visit(): void
    {
        $this->visit->update(['visit_type' => VisitType::EMERGENCY]);

        $response = $this->actingAs($this->user)
            ->get(route('admin.visits.preview', $this->visit));

        $response->assertStatus(200);
        $response->assertSee($this->visit->visit_number);
    }

    public function test_visit_preview_page_shows_chief_complaint(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('admin.visits.preview', $this->visit));

        $response->assertStatus(200);
        $response->assertSee('Headache and fever');
    }

    public function test_nonexistent_visit_returns_404(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('admin.visits.preview', 99999));

        $response->assertStatus(404);
    }

    // ── Service: timeline contents ───────────────────────────────

    public function test_timeline_includes_visit_registered_entry(): void
    {
        $service = app(VisitPreviewService::class);
        $preview = $service->build($this->visit);

        $titles = collect($preview['timeline'])->pluck('title');
        $this->assertTrue($titles->contains('Visit Registered'));
    }

    public function test_timeline_includes_triage_when_triage_exists(): void
    {
        Triage::factory()->create([
            'visit_id'   => $this->visit->id,
            'patient_id' => $this->visit->patient_id,
            'heart_rate' => 82,
            'temperature' => 37.2,
        ]);

        $service = app(VisitPreviewService::class);
        $preview = $service->build($this->visit->fresh());

        $titles = collect($preview['timeline'])->pluck('title');
        $this->assertTrue($titles->contains('Triage Completed'));
    }

    public function test_timeline_includes_consultation_when_medical_record_exists(): void
    {
        $mr = MedicalRecord::create([
            'visit_id'   => $this->visit->id,
            'patient_id' => $this->visit->patient_id,
            'doctor_id'  => $this->user->id,
        ]);

        $service = app(VisitPreviewService::class);
        $preview = $service->build($this->visit->fresh());

        $titles = collect($preview['timeline'])->pluck('title');
        $this->assertTrue($titles->contains('Consultation Started'));
    }

    public function test_timeline_includes_diagnosis_entries(): void
    {
        $mr = MedicalRecord::create([
            'visit_id'   => $this->visit->id,
            'patient_id' => $this->visit->patient_id,
            'doctor_id'  => $this->user->id,
        ]);

        Diagnosis::create([
            'medical_record_id' => $mr->id,
            'description'       => 'Malaria, uncomplicated',
            'icd_code'          => 'B54',
            'is_primary'        => true,
        ]);

        $service = app(VisitPreviewService::class);
        $preview = $service->build($this->visit->fresh());

        $titles = collect($preview['timeline'])->pluck('title');
        $this->assertTrue(
            $titles->contains('Primary Diagnosis Recorded') || $titles->contains('Diagnosis Recorded')
        );
    }

    public function test_timeline_includes_prescription_entries(): void
    {
        $mr = MedicalRecord::create([
            'visit_id'   => $this->visit->id,
            'patient_id' => $this->visit->patient_id,
            'doctor_id'  => $this->user->id,
        ]);

        Prescription::create([
            'visit_id'          => $this->visit->id,
            'patient_id'        => $this->visit->patient_id,
            'medical_record_id' => $mr->id,
            'doctor_id'         => $this->user->id,
            'prescription_number' => 'RX-0001',
            'status'            => 'pending',
        ]);

        $service = app(VisitPreviewService::class);
        $preview = $service->build($this->visit->fresh());

        $titles = collect($preview['timeline'])->pluck('title');
        $this->assertTrue($titles->contains('Prescription Created'));
    }

    public function test_timeline_is_sorted_chronologically(): void
    {
        // Create triage (older timestamp) and medical record (newer timestamp)
        Triage::factory()->create([
            'visit_id'   => $this->visit->id,
            'patient_id' => $this->visit->patient_id,
            'triaged_at' => now()->subHours(2),
        ]);

        MedicalRecord::create([
            'visit_id'   => $this->visit->id,
            'patient_id' => $this->visit->patient_id,
            'doctor_id'  => $this->user->id,
            'created_at' => now()->subHour(),
        ]);

        $service = app(VisitPreviewService::class);
        $preview = $service->build($this->visit->fresh());

        $titles = collect($preview['timeline'])->pluck('title');

        // Triage must appear before Consultation in sorted timeline
        $triageIdx = $titles->search('Triage Completed');
        $consultIdx = $titles->search('Consultation Started');

        $this->assertNotFalse($triageIdx);
        $this->assertNotFalse($consultIdx);
        $this->assertLessThan($consultIdx, $triageIdx);
    }

    public function test_missing_optional_relationships_do_not_crash_preview(): void
    {
        // Visit with no triage, no medical record, no lab requests, no prescriptions
        $bareVisit = Visit::factory()->create();

        $service = app(VisitPreviewService::class);
        $preview = $service->build($bareVisit);

        $this->assertIsArray($preview['timeline']);
        $this->assertIsArray($preview['summary']);
        // At minimum the "Visit Registered" entry is always present
        $this->assertNotEmpty($preview['timeline']);
    }

    public function test_summary_shows_correct_chief_complaint(): void
    {
        $service = app(VisitPreviewService::class);
        $preview = $service->build($this->visit);

        $this->assertEquals('Headache and fever', $preview['summary']['chief_complaint']);
    }

    public function test_summary_counts_prescriptions_correctly(): void
    {
        $mr = MedicalRecord::create([
            'visit_id'   => $this->visit->id,
            'patient_id' => $this->visit->patient_id,
            'doctor_id'  => $this->user->id,
        ]);

        Prescription::create([
            'visit_id'          => $this->visit->id,
            'patient_id'        => $this->visit->patient_id,
            'medical_record_id' => $mr->id,
            'doctor_id'         => $this->user->id,
            'prescription_number' => 'RX-0002',
            'status'            => 'pending',
        ]);

        $service = app(VisitPreviewService::class);
        $preview = $service->build($this->visit->fresh());

        $this->assertEquals(1, $preview['summary']['prescriptions_count']);
    }
}
