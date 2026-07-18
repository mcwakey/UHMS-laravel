<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\Priority;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Department;
use App\Models\EmergencyCase;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\ModuleService;
use App\Services\SidebarMenuBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EmergencyWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(ModuleService::class)->flush();
    }

    public function test_emergency_routes_use_prefix_namespace_and_department_guard(): void
    {
        $this->assertSame('http://localhost/emergency', route('emergency.dashboard'));
        $this->assertSame('http://localhost/emergency/dashboard', route('emergency.dashboard.expanded'));
        $this->assertSame('http://localhost/emergency/board', route('emergency.board'));
        $this->assertSame('http://localhost/emergency/queue/critical', route('emergency.queue.critical'));
        $this->assertSame('http://localhost/emergency/cases/create', route('emergency.cases.create'));
        $this->assertSame('http://localhost/emergency/reports', route('emergency.reports.index'));

        $middleware = Route::getRoutes()->getByName('emergency.board')->gatherMiddleware();
        $this->assertContains('auth', $middleware);
        $this->assertContains('department.type:emergency', $middleware);
        $this->assertContains('can:emergency.board.view', $middleware);
    }

    public function test_non_emergency_department_cannot_access_workspace_even_with_permissions(): void
    {
        $department = $this->department(DepartmentType::CONSULTATION, 'CON');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['emergency.board.view', 'emergency.case.view', 'emergency.case.create']);

        $this->actingAs($user)->get(route('emergency.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('emergency.board'))->assertForbidden();
        $this->actingAs($user)->get(route('emergency.cases.create'))->assertForbidden();
    }

    public function test_emergency_sidebar_is_workspace_specific_and_permission_filtered(): void
    {
        $emergency = $this->department(DepartmentType::EMERGENCY, 'ER');
        $user = User::factory()->create(['department_id' => $emergency->id]);
        $this->give($user, [
            'emergency.board.view',
            'emergency.case.create',
            'emergency.case.view',
            'emergency.medication_board.view',
            'emergency.reports.view',
            'patients.view',
            'visits.view',
            'vitals.view',
            'consultations.view',
            'lab.requests.view',
            'procedure.view',
            'ward.view',
        ]);

        $sections = app(SidebarMenuBuilder::class)->build($user, 'emergency.queue.critical');
        $items = collect($sections)->flatMap(fn (array $section) => $section['items']);
        $routes = $items->pluck('route');

        $this->assertContains('emergency.dashboard', $routes);
        $this->assertContains('emergency.board', $routes);
        $this->assertContains('emergency.queue.critical', $routes);
        // 'emergency.cases.create' was deliberately removed from the menu
        // (cases are registered from the queue/board); the route itself stays.
        $this->assertContains('emergency.patients.index', $routes);
        $this->assertContains('emergency.visits.index', $routes);
        // 'emergency.triage.index' was likewise removed from the curated menu.
        $this->assertContains('emergency.consultations.index', $routes);
        $this->assertContains('emergency.medications.index', $routes);
        $this->assertContains('emergency.lab.requests.index', $routes);
        $this->assertContains('emergency.theatre.index', $routes);
        $this->assertContains('emergency.admissions.index', $routes);
        $this->assertContains('emergency.reports.index', $routes);
        $this->assertNotContains('records.dashboard', $routes);
        $this->assertNotContains('nursing.dashboard', $routes);
        $this->assertTrue((bool) $items->firstWhere('route', 'emergency.queue.critical')['active']);
    }

    public function test_admin_emergency_browser_routes_redirect_to_workspace_but_json_does_not(): void
    {
        $emergency = $this->department(DepartmentType::EMERGENCY, 'ER');
        $user = User::factory()->create(['department_id' => $emergency->id]);
        $this->give($user, ['emergency.board.view']);

        $this->actingAs($user)
            ->get(route('admin.emergency.board'))
            ->assertRedirect(route('emergency.board'));

        $this->actingAs($user)
            ->getJson(route('admin.emergency.board'))
            ->assertOk()
            ->assertHeaderMissing('Location');
    }

    public function test_emergency_board_is_scoped_to_active_department_for_non_admins(): void
    {
        $emergency = $this->department(DepartmentType::EMERGENCY, 'ER');
        $otherEmergency = $this->department(DepartmentType::EMERGENCY, 'ER2');
        $user = User::factory()->create(['department_id' => $emergency->id]);
        $this->give($user, ['emergency.board.view']);

        $included = $this->emergencyCase($emergency, 'PN-ER-IN');
        $excluded = $this->emergencyCase($otherEmergency, 'PN-ER-OUT');

        $this->actingAs($user)
            ->get(route('emergency.board'))
            ->assertOk()
            ->assertSee($included->patient->patient_number)
            ->assertDontSee($excluded->patient->patient_number)
            ->assertSee(__('emergency.breadcrumbs.emergency'));
    }

    public function test_emergency_dashboard_uses_nurse_layout_with_emergency_only_metrics(): void
    {
        $emergency = $this->department(DepartmentType::EMERGENCY, 'ER');
        $user = User::factory()->create(['department_id' => $emergency->id]);
        $this->give($user, ['emergency.board.view']);
        $this->emergencyCase($emergency, 'PN-ER-DASH');

        $response = $this->actingAs($user)
            ->get(route('emergency.dashboard'))
            ->assertOk();
        $html = $this->legacyHtml($response->getContent());
        $this->assertStringContainsString(__('emergency.active'), $html);
        $this->assertStringContainsString(__('emergency.waiting_triage'), $html);
        $this->assertStringContainsString(__('emergency.red_critical'), $html);
        $this->assertStringContainsString(__('emergency.ready_disposition'), $html);
        $this->assertStringNotContainsString(__('admissions.admitted_today'), $html);
    }

    public function test_login_and_active_department_switch_land_on_emergency_dashboard(): void
    {
        $consultation = $this->department(DepartmentType::CONSULTATION, 'CON');
        $emergency = $this->department(DepartmentType::EMERGENCY, 'ER');
        $user = User::factory()->create(['department_id' => $consultation->id]);
        $user->departments()->attach($emergency->id);
        $this->give($user, ['departments.context.switch', 'emergency.board.view']);

        $this->actingAs($user)
            ->post(route('admin.my-dashboard.context.store'), ['department_id' => $emergency->id])
            ->assertRedirect(route('emergency.dashboard'));

        $emergencyUser = User::factory()->create([
            'department_id' => $emergency->id,
            'email' => 'emergency-login@example.test',
        ]);
        $this->give($emergencyUser, ['emergency.board.view']);

        auth()->logout();
        $this->app['session']->flush();

        $this->post(route('login'), ['email' => $emergencyUser->email, 'password' => 'password'])
            ->assertRedirect(route('emergency.dashboard'));
    }

    private function emergencyCase(Department $department, string $patientNumber): EmergencyCase
    {
        $patient = Patient::factory()->create(['patient_number' => $patientNumber]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'current_department_id' => $department->id,
            'visit_type' => VisitType::EMERGENCY->value,
            'status' => VisitStatus::EMERGENCY->value,
            'priority' => Priority::EMERGENCY->value,
            'arrived_at' => now()->subMinutes(20),
            'checked_in_at' => now()->subMinutes(20),
        ]);

        return EmergencyCase::create([
            'emergency_number' => 'ER-'.str_replace('PN-', '', $patientNumber),
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'arrival_mode' => 'WALK_IN',
            'arrival_time' => now()->subMinutes(20),
            'chief_complaint' => 'Emergency board scope test',
            'emergency_status' => EmergencyCase::STATUS_WAITING_TRIAGE,
        ]);
    }

    private function department(DepartmentType $type, string $code): Department
    {
        return Department::create([
            'name' => $type->label().' '.$code,
            'code' => $code.random_int(100, 999),
            'type' => $type->value,
            'status' => 'active',
        ]);
    }

    private function give(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);
    }

    private function legacyHtml(string $content): string
    {
        preg_match('/<script data-page="app" type="application\/json">(.*?)<\/script>/s', $content, $matches);
        $page = json_decode($matches[1] ?? '{}', true, flags: JSON_THROW_ON_ERROR);

        return $page['props']['html'] ?? '';
    }
}
