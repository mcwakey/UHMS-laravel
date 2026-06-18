<?php

namespace Tests\Feature;

use App\Enums\Priority;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Department;
use App\Models\Patient;
use App\Models\QueueEntry;
use App\Models\User;
use App\Models\Visit;
use App\Services\VisitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class VisitManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->department = Department::factory()->create();
        $this->user = User::factory()->create(['department_id' => $this->department->id]);
        Role::findOrCreate('Doctor', 'web');
        $role = Role::findOrCreate('Admin', 'web');
        foreach (['visits.view', 'visits.create', 'visits.edit', 'visits.transition', 'patients.view'] as $p) {
            $perm = Permission::findOrCreate($p, 'web');
            $role->givePermissionTo($perm);
        }
        $this->user->assignRole($role);
    }

    // ── Index ───────────────────────────────────

    public function test_visit_index_page_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.visits.index'));
        $response->assertStatus(200);
    }

    // ── Create Visit ────────────────────────────

    public function test_visit_can_be_created(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);

        $data = [
            'patient_id' => $patient->id,
            'visit_type' => VisitType::OUTPATIENT->value,
            'priority' => Priority::NORMAL->value,
            'chief_complaint' => 'Headache and fever',
        ];

        $response = $this->actingAs($this->user)->post(route('admin.visits.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('visits', [
            'patient_id' => $patient->id,
            'visit_type' => 'outpatient',
        ]);
    }

    public function test_visit_number_is_auto_generated(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);

        $this->actingAs($this->user)->post(route('admin.visits.store'), [
            'patient_id' => $patient->id,
            'visit_type' => VisitType::OUTPATIENT->value,
            'priority' => Priority::NORMAL->value,
            'chief_complaint' => 'Test visit',
        ]);

        $visit = Visit::where('patient_id', $patient->id)->first();
        $this->assertNotNull($visit);
        $this->assertStringStartsWith('VST', $visit->visit_number);
    }

    // ── Show ────────────────────────────────────

    public function test_visit_detail_page_loads(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'current_department_id' => $this->department->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('admin.visits.show', $visit));
        $response->assertStatus(200);
    }

    // ── Status Transitions ──────────────────────

    public function test_visit_can_transition_status(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'current_department_id' => $this->department->id,
            'created_by' => $this->user->id,
            'status' => VisitStatus::REGISTERED,
        ]);

        $response = $this->actingAs($this->user)->patch(
            route('admin.visits.transition', $visit),
            ['status' => VisitStatus::WAITING->value]
        );

        $response->assertRedirect();
        $visit->refresh();
        $this->assertEquals(VisitStatus::WAITING, $visit->status);

        $this->assertDatabaseHas('queue_entries', [
            'visit_id' => $visit->id,
            'department_id' => null,
            'status' => 'waiting',
        ]);

        $this->actingAs($this->user)->patch(
            route('admin.visits.transition', $visit),
            ['status' => VisitStatus::WAITING->value]
        );

        $this->assertSame(
            1,
            QueueEntry::where('visit_id', $visit->id)
                ->whereNull('department_id')
                ->where('status', 'waiting')
                ->count()
        );
    }

    // ── Today's Visits ──────────────────────────

    public function test_visit_index_filters_by_today(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        Visit::factory()->create([
            'patient_id' => $patient->id,
            'current_department_id' => $this->department->id,
            'created_by' => $this->user->id,
            'visit_date' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('admin.visits.index', ['date' => now()->toDateString()]));

        $response->assertStatus(200);
    }

    public function test_visit_list_uses_first_come_first_served_order(): void
    {
        $firstPatient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $secondPatient = Patient::factory()->create(['registered_by' => $this->user->id]);

        $firstVisit = Visit::factory()->create([
            'patient_id' => $firstPatient->id,
            'current_department_id' => $this->department->id,
            'created_by' => $this->user->id,
            'visit_date' => today(),
            'checked_in_at' => now()->subMinutes(30),
            'created_at' => now()->subMinutes(30),
        ]);
        $secondVisit = Visit::factory()->create([
            'patient_id' => $secondPatient->id,
            'current_department_id' => $this->department->id,
            'created_by' => $this->user->id,
            'visit_date' => today(),
            'checked_in_at' => now()->subMinutes(5),
            'created_at' => now()->subMinutes(5),
        ]);

        $visits = app(VisitService::class)->list([
            'date_from' => today()->toDateString(),
            'date_to' => today()->toDateString(),
        ]);

        $this->assertSame([$firstVisit->id, $secondVisit->id], $visits->pluck('id')->take(2)->all());
    }
}
