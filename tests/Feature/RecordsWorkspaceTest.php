<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\Priority;
use App\Enums\VisitType;
use App\Models\Department;
use App\Models\Module;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\ModuleService;
use App\Services\SidebarMenuBuilder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RecordsWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(ModuleService::class)->flush();
    }

    public function test_records_routes_use_the_records_namespace_prefix_and_guard(): void
    {
        $this->assertSame('http://localhost/records', route('records.dashboard'));
        $this->assertSame('http://localhost/records/patients', route('records.patients.index'));
        $this->assertSame('http://localhost/records/visits/create', route('records.visits.create'));
        $this->assertSame('http://localhost/records/appointments', route('records.appointments.index'));
        $this->assertSame('http://localhost/records/front-desk', route('records.front-desk.index'));
        $this->assertSame('http://localhost/records/triage', route('records.triage.index'));
        $this->assertSame('http://localhost/records/vitals', route('records.vitals.create'));
        $this->assertSame('http://localhost/records/service-renderings', route('records.service-renderings.index'));
        $this->assertSame('http://localhost/records/claims', route('records.claims.index'));
        $this->assertSame('http://localhost/records/reports/attendance', route('records.reports.attendance'));
        $this->assertSame('http://localhost/records/reports/claims', route('records.reports.claims'));
        $this->assertFileExists(resource_path('views/dashboards/records.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/dashboards/receptionist.blade.php'));

        $middleware = Route::getRoutes()->getByName('records.patients.index')->gatherMiddleware();
        $this->assertContains('auth', $middleware);
        $this->assertContains('department.type:records', $middleware);
        $this->assertContains('can:patients.view', $middleware);
    }

    public function test_records_department_is_required_even_with_patient_permission(): void
    {
        $consultation = $this->department(DepartmentType::CONSULTATION, 'OPD');
        $user = User::factory()->create(['department_id' => $consultation->id]);
        $this->give($user, ['patients.view', 'front_desk.view', 'front_desk.dashboard.view', 'vitals.view', 'service_rendering.view', 'claims.view', 'reports.view']);

        $this->actingAs($user)->get(route('records.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('records.patients.index'))->assertForbidden();
        $this->actingAs($user)->get(route('records.front-desk.index'))->assertForbidden();
        $this->actingAs($user)->get(route('records.triage.index'))->assertForbidden();
        $this->actingAs($user)->get(route('records.service-renderings.index'))->assertForbidden();
        $this->actingAs($user)->get(route('records.claims.index'))->assertForbidden();
    }

    public function test_records_dashboard_and_patient_links_stay_in_workspace(): void
    {
        $records = $this->department(DepartmentType::RECORDS, 'REC');
        $user = User::factory()->create(['department_id' => $records->id]);
        $this->give($user, ['patients.view', 'patients.create', 'patients.edit', 'visits.view', 'visits.create']);
        $patient = Patient::factory()->create(['registered_by' => $user->id]);

        $this->actingAs($user)
            ->get(route('records.dashboard'))
            ->assertOk()
            ->assertSee(__('records.dashboard.title'));

        $this->actingAs($user)
            ->get(route('records.patients.index'))
            ->assertOk()
            ->assertSee('records\\/patients\\/'.$patient->id, false)
            ->assertSee('records\\/patients\\/create', false)
            ->assertSee(__('records.breadcrumbs.records'));
    }

    public function test_permission_still_controls_records_routes_and_menu(): void
    {
        $records = $this->department(DepartmentType::RECORDS, 'REC');
        $user = User::factory()->create(['department_id' => $records->id]);
        $this->give($user, ['patients.view']);

        $this->actingAs($user)->get(route('records.patients.index'))->assertOk();
        $this->actingAs($user)->get(route('records.patients.create'))->assertForbidden();

        $sections = app(SidebarMenuBuilder::class)->build($user, 'records.patients.index');
        $routes = collect($sections)->flatMap(fn (array $section) => $section['items'])->pluck('route');

        $this->assertContains('records.dashboard', $routes);
        $this->assertContains('records.patients.index', $routes);
        $this->assertNotContains('records.patients.create', $routes);
        $this->assertNotContains('admin.billing.dashboard', $routes);
    }

    public function test_patient_creation_redirects_to_records_profile(): void
    {
        $records = $this->department(DepartmentType::RECORDS, 'REC');
        $user = User::factory()->create(['department_id' => $records->id]);
        $this->give($user, ['patients.view', 'patients.create']);

        $response = $this->actingAs($user)->post(route('records.patients.store'), [
            'first_name' => 'Akosua',
            'last_name' => 'Owusu',
            'date_of_birth' => '1991-03-12',
            'gender' => 'female',
            'phone' => '0244000000',
        ]);

        $patient = Patient::query()->where('first_name', 'Akosua')->firstOrFail();
        $response->assertRedirect(route('records.patients.show', $patient));
    }

    public function test_visit_creation_redirects_to_records_visit(): void
    {
        $records = $this->department(DepartmentType::RECORDS, 'REC');
        $user = User::factory()->create(['department_id' => $records->id]);
        $this->give($user, ['visits.view', 'visits.create']);
        $patient = Patient::factory()->create(['registered_by' => $user->id]);

        $response = $this->actingAs($user)->post(route('records.visits.store'), [
            'patient_id' => $patient->id,
            'visit_type' => VisitType::OUTPATIENT->value,
            'priority' => Priority::NORMAL->value,
            'chief_complaint' => 'Records workspace registration',
        ]);

        $visit = Visit::query()->where('patient_id', $patient->id)->firstOrFail();
        $response->assertRedirect(route('records.visits.show', $visit));
    }

    public function test_disabled_module_hides_menu_item_and_blocks_route(): void
    {
        $records = $this->department(DepartmentType::RECORDS, 'REC');
        $user = User::factory()->create(['department_id' => $records->id]);
        $this->give($user, ['appointments.view']);
        Module::create([
            'name' => 'Appointments',
            'slug' => 'appointments',
            'is_core' => false,
            'is_enabled' => false,
            'sort_order' => 1,
        ]);
        app(ModuleService::class)->flush();

        $sections = app(SidebarMenuBuilder::class)->build($user, 'records.dashboard');
        $routes = collect($sections)->flatMap(fn (array $section) => $section['items'])->pluck('route');

        $this->assertNotContains('records.appointments.index', $routes);
        $this->actingAs($user)->get(route('records.appointments.index'))->assertForbidden();
    }

    public function test_records_appointment_create_uses_appointment_patient_search_permission(): void
    {
        $records = $this->department(DepartmentType::RECORDS, 'REC');
        $user = User::factory()->create(['department_id' => $records->id]);
        \Spatie\Permission\Models\Role::findOrCreate('Doctor', 'web');
        $this->give($user, ['appointments.view', 'appointments.create']);

        Patient::factory()->create([
            'first_name' => 'Ama',
            'last_name' => 'Boateng',
            'phone' => '0247654321',
            'registered_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('records.appointments.create'));
        $page = $this->inertiaPage($response->getContent());
        $scripts = $this->legacyScripts($page);

        $response->assertOk();
        $this->assertStringContainsString(route('records.appointments.patient-search'), $scripts);
        $this->assertStringContainsString(route('records.appointments.patient-insurances'), $scripts);
        $this->assertStringContainsString(route('records.appointments.department-services'), $scripts);
        $this->assertStringContainsString(route('records.appointments.services-for-doctor'), $scripts);
        $this->assertStringContainsString(route('records.appointments.doctors-for-services'), $scripts);
        $this->assertStringNotContainsString(route('records.visits.patient-search'), $scripts);
        $this->assertStringNotContainsString(route('records.visits.patient-insurances'), $scripts);
        $this->assertStringNotContainsString(route('records.visits.department-services'), $scripts);
        $this->assertStringNotContainsString(route('records.visits.services-for-doctor'), $scripts);
        $this->assertStringNotContainsString(route('records.visits.doctors-for-services'), $scripts);

        $this->actingAs($user)
            ->getJson(route('records.appointments.patient-search', ['q' => 'Ama']))
            ->assertOk()
            ->assertJsonPath('0.phone', '024****321');

        $this->actingAs($user)
            ->getJson(route('records.visits.patient-search', ['q' => 'Ama']))
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson(route('records.appointments.department-services', ['department_id' => $records->id]))
            ->assertOk();

        $this->actingAs($user)
            ->getJson(route('records.visits.department-services', ['department_id' => $records->id]))
            ->assertForbidden();
    }

    public function test_records_menu_exposes_front_desk_triage_services_and_claims_by_permission(): void
    {
        $records = $this->department(DepartmentType::RECORDS, 'REC');
        $user = User::factory()->create(['department_id' => $records->id]);
        $this->give($user, [
            'front_desk.view',
            'front_desk.dashboard.view',
            'front_desk.visitors.view',
            'vitals.view',
            'service_rendering.view',
            'claims.view',
        ]);

        $sections = app(SidebarMenuBuilder::class)->build($user, 'records.dashboard');
        $routes = collect($sections)->flatMap(fn (array $section) => $section['items'])->pluck('route');

        $this->assertContains('records.front-desk.visitors.index', $routes);
        $this->assertContains('records.triage.index', $routes);
        $this->assertContains('records.service-renderings.index', $routes);
        $this->assertContains('records.claims.index', $routes);
        $this->assertNotContains('records.front-desk.calls.index', $routes);
    }

    public function test_seeded_records_roles_receive_the_complete_workspace_menu(): void
    {
        $this->seed(RoleSeeder::class);
        $records = $this->department(DepartmentType::RECORDS, 'REC');

        foreach (['Receptionist', 'Medical Records Officer'] as $role) {
            $user = User::factory()->create(['department_id' => $records->id]);
            $user->assignRole($role);

            $routes = collect(app(SidebarMenuBuilder::class)->build($user, 'records.dashboard'))
                ->flatMap(fn (array $section) => $section['items'])
                ->pluck('route');

            $this->assertContains('records.triage.index', $routes, $role);
            $this->assertContains('records.service-renderings.index', $routes, $role);
            $this->assertContains('records.claims.index', $routes, $role);
            $this->assertContains('records.reports.index', $routes, $role);
            $this->assertContains('records.reports.claims', $routes, $role);
            $this->assertContains('records.service-renderings.reports', $routes, $role);
        }
    }

    public function test_added_records_pages_are_guarded_and_render_workspace_links(): void
    {
        $records = $this->department(DepartmentType::RECORDS, 'REC');
        $user = User::factory()->create(['department_id' => $records->id]);
        $this->give($user, [
            'front_desk.view',
            'front_desk.dashboard.view',
            'front_desk.visitors.view',
            'vitals.view',
            'service_rendering.view',
            'claims.view',
        ]);

        $this->actingAs($user)
            ->get(route('records.front-desk.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('records.triage.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('records.service-renderings.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('records.claims.index'))
            ->assertOk();
    }

    public function test_records_reports_hub_lists_department_relevant_reports_only_when_authorized(): void
    {
        $records = $this->department(DepartmentType::RECORDS, 'REC');
        $user = User::factory()->create(['department_id' => $records->id]);
        $this->give($user, [
            'reports.view',
            'front_desk.view',
            'front_desk.reports.view',
            'service_rendering.view',
            'service_rendering.reports',
        ]);

        $this->actingAs($user)
            ->get(route('records.reports.index'))
            ->assertOk();

        $this->actingAs($user)->get(route('records.reports.claims'))->assertOk();
        $this->actingAs($user)->get(route('records.reports.insurance-claims'))->assertOk();
        $this->actingAs($user)->get(route('records.reports.daily-collection'))->assertOk();
        $this->actingAs($user)->get(route('records.front-desk.reports.index'))->assertOk();
        $this->actingAs($user)->get(route('records.service-renderings.reports'))->assertOk();
    }

    public function test_generic_browser_routes_redirect_but_json_does_not(): void
    {
        $records = $this->department(DepartmentType::RECORDS, 'REC');
        $user = User::factory()->create(['department_id' => $records->id]);
        $this->give($user, ['patients.view']);

        $this->actingAs($user)
            ->get(route('admin.patients.index'))
            ->assertRedirect(route('records.patients.index'));

        $this->give($user, [
            'front_desk.view',
            'front_desk.dashboard.view',
            'vitals.view',
            'service_rendering.view',
            'claims.view',
            'reports.view',
        ]);
        $this->actingAs($user)
            ->get(route('admin.front-desk.index'))
            ->assertRedirect(route('records.front-desk.index'));
        $this->actingAs($user)->get(route('admin.triage.index'))->assertRedirect(route('records.triage.index'));
        $this->actingAs($user)->get(route('admin.service-renderings.index'))->assertRedirect(route('records.service-renderings.index'));
        $this->actingAs($user)->get(route('admin.claims.index'))->assertRedirect(route('records.claims.index'));
        $this->actingAs($user)->get(route('admin.reports.claims'))->assertRedirect(route('records.reports.claims'));

        $this->actingAs($user)
            ->getJson(route('admin.patients.index'))
            ->assertOk()
            ->assertHeaderMissing('Location');
    }

    public function test_department_switch_and_login_land_on_records_dashboard(): void
    {
        $consultation = $this->department(DepartmentType::CONSULTATION, 'OPD');
        $records = $this->department(DepartmentType::RECORDS, 'REC');
        $user = User::factory()->create(['department_id' => $consultation->id]);
        $user->departments()->attach($records->id);
        $this->give($user, ['departments.context.switch']);

        $this->actingAs($user)
            ->post(route('admin.my-dashboard.context.store'), ['department_id' => $records->id])
            ->assertRedirect(route('records.dashboard'));

        $recordsUser = User::factory()->create([
            'department_id' => $records->id,
            'email' => 'records-login@example.test',
        ]);

        auth()->logout();
        $this->app['session']->flush();

        $this->post(route('login'), ['email' => $recordsUser->email, 'password' => 'password'])
            ->assertRedirect(route('records.dashboard'));
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

    private function inertiaPage(string $content): array
    {
        preg_match('/<script data-page="app" type="application\/json">(.*?)<\/script>/s', $content, $matches);

        return json_decode($matches[1] ?? '{}', true, flags: JSON_THROW_ON_ERROR);
    }

    private function legacyScripts(array $page): string
    {
        if (! empty($page['props']['scripts'])) {
            return $page['props']['scripts'];
        }

        return base64_decode($page['props']['scriptsEncoded'] ?? '', true) ?: '';
    }
}
