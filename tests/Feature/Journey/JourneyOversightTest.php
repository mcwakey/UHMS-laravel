<?php

namespace Tests\Feature\Journey;

use App\Data\Journey\JourneyHandoff;
use App\Enums\VisitStatus;
use App\Models\Department;
use App\Models\User;
use App\Models\Visit;
use App\Services\Journey\JourneyHandoffNotificationService;
use App\Services\Journey\JourneyHandoffResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class JourneyOversightTest extends TestCase
{
    use RefreshDatabase;

    private Department $lab;

    private function permission(string $name): void
    {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    private function labHandoff(): JourneyHandoff
    {
        $consult = Department::create(['name' => 'OPD '.uniqid(), 'code' => 'OV'.uniqid(), 'type' => 'consultation', 'status' => 'active']);
        $this->lab = Department::create(['name' => 'Lab '.uniqid(), 'code' => 'OL'.uniqid(), 'type' => 'investigation', 'status' => 'active']);
        $creator = User::factory()->create();
        $visit = Visit::factory()->create([
            'status' => VisitStatus::LAB, 'current_department_id' => $consult->id, 'created_by' => $creator->id,
            'created_at' => now()->subHours(5),
        ]);
        DB::table('lab_requests')->insert([
            'request_number' => 'LR-'.uniqid(), 'requested_by' => $creator->id,
            'visit_id' => $visit->id, 'patient_id' => $visit->patient_id,
            'target_department_id' => $this->lab->id, 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return app(JourneyHandoffResolver::class)->resolve($visit->fresh(), null);
    }

    public function test_oversight_user_receives_critical_notification(): void
    {
        $this->permission('journey.oversight');
        $handoff = $this->labHandoff();
        $oversight = User::factory()->create();
        $oversight->givePermissionTo('journey.oversight');

        app(JourneyHandoffNotificationService::class)->notifyCriticalUnassigned($handoff);

        $this->assertGreaterThan(0, $oversight->fresh()->notifications()->count());
    }

    public function test_non_oversight_user_does_not_see_oversight_tab(): void
    {
        $this->permission('journey.oversight');
        $user = User::factory()->create(['department_id' => Department::create(['name' => 'OPD', 'code' => 'C'.uniqid(), 'type' => 'consultation', 'status' => 'active'])->id]);

        $response = $this->actingAs($user)->get(route('admin.journey.worklist', ['tab' => 'oversight']));

        $response->assertOk();
        $response->assertDontSee(__('journey.handoff.tab_oversight'), false);
    }

    public function test_oversight_user_sees_oversight_tab(): void
    {
        $this->permission('journey.oversight');
        $user = User::factory()->create(['department_id' => Department::create(['name' => 'OPD', 'code' => 'C'.uniqid(), 'type' => 'consultation', 'status' => 'active'])->id]);
        $user->givePermissionTo('journey.oversight');

        $response = $this->actingAs($user)->get(route('admin.journey.worklist', ['tab' => 'oversight']));

        $response->assertOk();
        $response->assertSee(__('journey.handoff.tab_oversight'), false);
    }

    public function test_department_admin_saves_eligible_supervisor(): void
    {
        $this->permission('departments.view');
        $this->permission('departments.edit');
        $admin = User::factory()->create();
        $admin->givePermissionTo(['departments.view', 'departments.edit']);
        $dept = Department::create(['name' => 'Lab', 'code' => 'D'.substr(uniqid(),-8), 'type' => 'investigation', 'status' => 'active', 'result_type' => 'none']);
        $eligible = User::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.departments.update', $dept), [
            'name' => $dept->name, 'code' => $dept->code, 'type' => 'investigation',
            'result_type' => 'none', 'status' => 'active', 'supervisor_user_id' => $eligible->id,
        ]);
        $response->assertSessionDoesntHaveErrors();

        $this->assertSame($eligible->id, $dept->fresh()->supervisor_user_id);
    }

    public function test_ineligible_inactive_supervisor_is_rejected(): void
    {
        $this->permission('departments.view');
        $this->permission('departments.edit');
        $admin = User::factory()->create();
        $admin->givePermissionTo(['departments.view', 'departments.edit']);
        $dept = Department::create(['name' => 'Lab', 'code' => 'D'.substr(uniqid(),-8), 'type' => 'investigation', 'status' => 'active', 'result_type' => 'none']);
        $inactive = User::factory()->create(['status' => \App\Enums\UserStatus::INACTIVE]);

        $this->actingAs($admin)->put(route('admin.departments.update', $dept), [
            'name' => $dept->name, 'code' => $dept->code, 'type' => 'investigation',
            'result_type' => 'none', 'status' => 'active', 'supervisor_user_id' => $inactive->id,
        ])->assertSessionHasErrors('supervisor_user_id');

        $this->assertNull($dept->fresh()->supervisor_user_id);
    }
}
