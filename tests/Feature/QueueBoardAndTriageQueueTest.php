<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\Priority;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Department;
use App\Models\Patient;
use App\Models\QueueEntry;
use App\Models\User;
use App\Models\Visit;
use App\Services\QueueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class QueueBoardAndTriageQueueTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::findOrCreate('Queue Tester', 'web');
        foreach (['queue.view', 'queue.manage', 'vitals.view', 'vitals.create'] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $this->user = User::factory()->create();
        $this->user->assignRole($role);
    }

    public function test_queue_board_displays_triage_queue_entries(): void
    {
        [$visit] = $this->makeTriageQueueVisit('Ama', 'Mensah', 1);

        $this->actingAs($this->user)
            ->get(route('admin.queue.board'))
            ->assertOk()
            ->assertSee('Triage \/ Assessment', false)
            ->assertSee('#1')
            ->assertSee($visit->patient->full_name);
    }

    public function test_triage_list_displays_queue_number(): void
    {
        [$visit] = $this->makeTriageQueueVisit('Kojo', 'Owusu', 3);

        $this->actingAs($this->user)
            ->get(route('admin.triage.index'))
            ->assertOk()
            ->assertSee('#3')
            ->assertSee($visit->patient->full_name);
    }

    public function test_department_queue_uses_queue_number_before_priority(): void
    {
        $department = Department::factory()->create([
            'name' => 'General Medicine',
            'type' => DepartmentType::CONSULTATION->value,
        ]);
        [$firstVisit] = $this->makeDepartmentQueueVisit('First', 'Patient', $department, 1, Priority::NORMAL);
        [$secondVisit] = $this->makeDepartmentQueueVisit('Second', 'Patient', $department, 2, Priority::URGENT);

        $this->actingAs($this->user)
            ->get(route('admin.queue.board'))
            ->assertOk()
            ->assertSeeInOrder([
                '#1',
                $firstVisit->patient->full_name,
                '#2',
                $secondVisit->patient->full_name,
            ]);

        $entry = app(QueueService::class)->callNext($department->id);

        $this->assertSame($firstVisit->id, $entry?->visit_id);
    }

    private function makeTriageQueueVisit(string $firstName, string $lastName, int $queueNumber): array
    {
        $patient = Patient::factory()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'registered_by' => $this->user->id,
        ]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->user->id,
            'visit_type' => VisitType::OUTPATIENT,
            'status' => VisitStatus::WAITING,
            'visit_date' => today(),
            'checked_in_at' => now()->subMinutes(20),
        ]);
        $entry = QueueEntry::create([
            'visit_id' => $visit->id,
            'department_id' => null,
            'queue_number' => $queueNumber,
            'priority' => Priority::NORMAL,
            'status' => 'waiting',
        ]);

        return [$visit, $entry];
    }

    private function makeDepartmentQueueVisit(
        string $firstName,
        string $lastName,
        Department $department,
        int $queueNumber,
        Priority $priority,
    ): array {
        $patient = Patient::factory()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'registered_by' => $this->user->id,
        ]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->user->id,
            'visit_type' => VisitType::OUTPATIENT,
            'status' => VisitStatus::WAITING_CONSULTATION,
            'priority' => $priority,
            'visit_date' => today(),
            'current_department_id' => $department->id,
        ]);
        $entry = QueueEntry::create([
            'visit_id' => $visit->id,
            'department_id' => $department->id,
            'queue_number' => $queueNumber,
            'priority' => $priority,
            'status' => 'waiting',
        ]);

        return [$visit, $entry];
    }
}
