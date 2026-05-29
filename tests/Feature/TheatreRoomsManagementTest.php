<?php

namespace Tests\Feature;

use App\Enums\ProcedureStatus;
use App\Enums\TheatreRoomBlockType;
use App\Enums\TheatreRoomStatus;
use App\Enums\TheatreRoomType;
use App\Models\Department;
use App\Models\Patient;
use App\Models\ProcedureRequest;
use App\Models\ProcedureSchedule;
use App\Models\ServiceCatalog;
use App\Models\TheatreRoom;
use App\Models\TheatreRoomBlock;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TheatreRoomsManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Department $department;
    private Patient $patient;
    private Visit $visit;
    private ServiceCatalog $service;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->department = Department::factory()->create([
            'name' => 'Theatre',
            'code' => 'THR',
            'type' => 'procedure',
        ]);

        $this->user = User::factory()->create([
            'department_id' => $this->department->id,
        ]);

        $role = Role::findOrCreate('Theatre Rooms Test User', 'web');
        foreach ([
            'procedure.view',
            'procedure.schedule',
            'theatre.rooms.view',
            'theatre.rooms.create',
            'theatre.rooms.update',
            'theatre.schedule.override',
        ] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->user->assignRole($role);

        $this->patient = Patient::factory()->create([
            'registered_by' => $this->user->id,
            'status' => 'active',
        ]);

        $this->visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'created_by' => $this->user->id,
        ]);

        $this->service = ServiceCatalog::create([
            'name' => 'Appendectomy',
            'code' => 'PROC-APP',
            'category' => 'procedure',
            'price' => 1200,
            'is_active' => true,
            'is_billable' => true,
            'department_id' => $this->department->id,
            'department_type' => 'procedure',
        ]);
    }

    public function test_user_can_create_theatre_room(): void
    {
        $response = $this->actingAs($this->user)->post(route('admin.theatre.rooms.store'), [
            'name' => 'Main Theatre 1',
            'code' => 'MT-1',
            'department_id' => $this->department->id,
            'room_type' => TheatreRoomType::MAJOR_THEATRE->value,
            'capacity' => 6,
            'location' => 'Block A',
            'status' => TheatreRoomStatus::AVAILABLE->value,
            'is_active' => 1,
            'notes' => 'General major theatre.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('theatre_rooms', [
            'name' => 'Main Theatre 1',
            'code' => 'MT-1',
            'room_type' => TheatreRoomType::MAJOR_THEATRE->value,
            'status' => TheatreRoomStatus::AVAILABLE->value,
            'is_active' => true,
        ]);
    }

    public function test_room_code_must_be_unique(): void
    {
        TheatreRoom::create([
            'name' => 'Minor Room',
            'code' => 'MIN-1',
            'room_type' => TheatreRoomType::MINOR_THEATRE,
            'status' => TheatreRoomStatus::AVAILABLE,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->from(route('admin.theatre.rooms.index'))
            ->post(route('admin.theatre.rooms.store'), [
                'name' => 'Duplicate Minor Room',
                'code' => 'MIN-1',
                'room_type' => TheatreRoomType::MINOR_THEATRE->value,
                'status' => TheatreRoomStatus::AVAILABLE->value,
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('admin.theatre.rooms.index'));
        $response->assertSessionHasErrors('code');
    }

    public function test_out_of_service_room_cannot_be_scheduled(): void
    {
        $room = TheatreRoom::create([
            'name' => 'Maintenance Theatre',
            'code' => 'MT-OOS',
            'room_type' => TheatreRoomType::MAJOR_THEATRE,
            'status' => TheatreRoomStatus::OUT_OF_SERVICE,
            'is_active' => true,
        ]);
        $procedure = $this->procedureRequest(ProcedureStatus::BILLED);

        $response = $this->actingAs($this->user)
            ->from(route('admin.theatre.show', $procedure))
            ->post(route('admin.theatre.schedule', $procedure), $this->schedulePayload($room));

        $response->assertRedirect(route('admin.theatre.show', $procedure));
        $response->assertSessionHas('error');
        $this->assertSame(0, ProcedureSchedule::where('procedure_request_id', $procedure->id)->count());
    }

    public function test_double_booking_same_room_window_is_prevented(): void
    {
        $room = TheatreRoom::create([
            'name' => 'Main Theatre 2',
            'code' => 'MT-2',
            'room_type' => TheatreRoomType::MAJOR_THEATRE,
            'status' => TheatreRoomStatus::AVAILABLE,
            'is_active' => true,
        ]);
        $first = $this->procedureRequest(ProcedureStatus::BILLED);
        $second = $this->procedureRequest(ProcedureStatus::BILLED);
        $start = now()->addDay()->setTime(8, 0);

        $this->actingAs($this->user)
            ->post(route('admin.theatre.schedule', $first), $this->schedulePayload($room, $start, $start->copy()->addMinutes(90)))
            ->assertRedirect();

        $response = $this->actingAs($this->user)
            ->from(route('admin.theatre.show', $second))
            ->post(route('admin.theatre.schedule', $second), $this->schedulePayload($room, $start->copy()->addMinutes(30), $start->copy()->addMinutes(120)));

        $response->assertRedirect(route('admin.theatre.show', $second));
        $response->assertSessionHas('error');
        $this->assertSame(0, ProcedureSchedule::where('procedure_request_id', $second->id)->count());
    }

    public function test_room_block_prevents_scheduling(): void
    {
        $room = TheatreRoom::create([
            'name' => 'Emergency Theatre',
            'code' => 'ER-T',
            'room_type' => TheatreRoomType::EMERGENCY_THEATRE,
            'status' => TheatreRoomStatus::AVAILABLE,
            'is_active' => true,
        ]);
        $procedure = $this->procedureRequest(ProcedureStatus::BILLED);
        $start = now()->addDay()->setTime(10, 0);

        TheatreRoomBlock::create([
            'theatre_room_id' => $room->id,
            'block_type' => TheatreRoomBlockType::MAINTENANCE,
            'start_at' => $start,
            'end_at' => $start->copy()->addHours(2),
            'reason' => 'Equipment repair',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->from(route('admin.theatre.show', $procedure))
            ->post(route('admin.theatre.schedule', $procedure), $this->schedulePayload($room, $start->copy()->addMinutes(30), $start->copy()->addMinutes(90)));

        $response->assertRedirect(route('admin.theatre.show', $procedure));
        $response->assertSessionHas('error');
        $this->assertSame(0, ProcedureSchedule::where('procedure_request_id', $procedure->id)->count());
    }

    private function procedureRequest(ProcedureStatus $status): ProcedureRequest
    {
        return ProcedureRequest::create([
            'request_number' => ProcedureRequest::generateNumber(),
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'requested_by' => $this->user->id,
            'department_id' => $this->department->id,
            'service_catalog_id' => $this->service->id,
            'priority' => 'routine',
            'indication' => 'Clinical indication for theatre procedure.',
            'status' => $status,
            'requested_at' => now(),
        ]);
    }

    private function schedulePayload(TheatreRoom $room, $start = null, $end = null): array
    {
        $start = $start ?: now()->addDay()->setTime(8, 0);
        $end = $end ?: $start->copy()->addHour();

        return [
            'theatre_room_id' => $room->id,
            'scheduled_start' => $start->format('Y-m-d H:i:s'),
            'scheduled_end' => $end->format('Y-m-d H:i:s'),
            'expected_duration_minutes' => $start->diffInMinutes($end),
            'surgeon_id' => $this->user->id,
            'anaesthetist_id' => $this->user->id,
            'assistant_surgeon_id' => null,
            'required_equipment' => 'Standard theatre tray',
            'notes' => 'Scheduled through test.',
        ];
    }
}