<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\SampleStatus;
use App\Enums\VisitStatus;
use App\Models\Department;
use App\Models\LabRequest;
use App\Models\Patient;
use App\Models\Sample;
use App\Models\User;
use App\Models\Visit;
use App\Services\ModuleService;
use App\Services\SidebarMenuBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InvestigationsWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(ModuleService::class)->flush();
    }

    public function test_investigations_routes_use_workspace_prefix_and_department_guard(): void
    {
        $this->assertSame('http://localhost/investigations', route('investigations.dashboard'));
        $this->assertSame('http://localhost/investigations/requests', route('investigations.lab.requests.index'));
        $this->assertSame('http://localhost/investigations/specimens', route('investigations.lab.samples.index'));
        $this->assertSame('http://localhost/investigations/results', route('investigations.lab.results.index'));
        $this->assertSame('http://localhost/investigations/handoffs', route('investigations.handoffs.index'));

        $middleware = Route::getRoutes()->getByName('investigations.lab.requests.index')->gatherMiddleware();
        $this->assertContains('auth', $middleware);
        $this->assertContains('department.type:investigation', $middleware);
        $this->assertContains('can:lab.requests.view', $middleware);
    }

    public function test_non_investigation_department_cannot_access_workspace(): void
    {
        $department = $this->department(DepartmentType::CONSULTATION, 'CON');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['lab.requests.view', 'lab.samples.view', 'lab.results.view']);

        $this->actingAs($user)->get(route('investigations.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('investigations.lab.requests.index'))->assertForbidden();
        $this->actingAs($user)->get(route('investigations.lab.results.index'))->assertForbidden();
    }

    public function test_investigations_sidebar_is_workspace_specific_and_permission_filtered(): void
    {
        $department = $this->department(DepartmentType::INVESTIGATION, 'LAB');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, [
            'lab.requests.view', 'lab.samples.view', 'lab.results.view', 'patients.view',
            'lab.tests.manage', 'pharmacy.stock.manage', 'reports.investigations', 'notifications.view',
        ]);

        $items = collect(app(SidebarMenuBuilder::class)->build($user, 'investigations.lab.requests.index'))
            ->flatMap(fn (array $section) => $section['items']);
        $routes = $items->pluck('route');

        $this->assertContains('investigations.dashboard', $routes);
        $this->assertContains('investigations.lab.requests.index', $routes);
        $this->assertContains('investigations.lab.samples.index', $routes);
        $this->assertContains('investigations.lab.results.index', $routes);
        $this->assertContains('investigations.patients.index', $routes);
        $this->assertContains('investigations.investigation-catalogue.index', $routes);
        $this->assertContains('investigations.reports.index', $routes);
        $this->assertNotContains('inpatient.dashboard', $routes);
        $this->assertNotContains('admin.lab.requests.index', $routes);
        $this->assertTrue((bool) $items->firstWhere('route', 'investigations.lab.requests.index')['active']);

        // A user without catalogue/stock permissions loses those entries.
        $bare = User::factory()->create(['department_id' => $department->id]);
        $this->give($bare, ['lab.requests.view']);
        $bareRoutes = collect(app(SidebarMenuBuilder::class)->build($bare, 'investigations.dashboard'))
            ->flatMap(fn (array $section) => $section['items'])
            ->pluck('route');
        $this->assertContains('investigations.lab.requests.index', $bareRoutes);
        $this->assertNotContains('investigations.lab.tests.index', $bareRoutes);
        $this->assertNotContains('investigations.stock.index', $bareRoutes);
    }

    public function test_request_queue_and_direct_access_are_scoped_to_target_department(): void
    {
        $department = $this->department(DepartmentType::INVESTIGATION, 'LAB');
        $other = $this->department(DepartmentType::INVESTIGATION, 'RAD');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['lab.requests.view']);

        $included = $this->labRequest($department, $user, 'INV-LAB-IN');
        $excluded = $this->labRequest($other, $user, 'INV-LAB-OUT');

        $this->actingAs($user)
            ->get(route('investigations.lab.requests.index'))
            ->assertOk()
            ->assertSee($included->request_number)
            ->assertDontSee($excluded->request_number);

        $this->actingAs($user)->get(route('investigations.lab.requests.show', $excluded))->assertNotFound();
    }

    public function test_specimen_queue_and_actions_are_scoped_to_target_department(): void
    {
        $department = $this->department(DepartmentType::INVESTIGATION, 'LAB');
        $other = $this->department(DepartmentType::INVESTIGATION, 'RAD');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['lab.samples.view', 'lab.samples.collect']);

        $included = $this->sample($this->labRequest($department, $user, 'INV-SAMP-IN'), 'SMP-IN');
        $excluded = $this->sample($this->labRequest($other, $user, 'INV-SAMP-OUT'), 'SMP-OUT');

        $this->actingAs($user)
            ->get(route('investigations.lab.samples.index'))
            ->assertOk()
            ->assertSee($included->sample_number)
            ->assertDontSee($excluded->sample_number);

        $this->actingAs($user)
            ->patch(route('investigations.lab.samples.collect', $excluded))
            ->assertNotFound();
    }

    public function test_dashboard_renders_scoped_diagnostic_metrics(): void
    {
        $department = $this->department(DepartmentType::INVESTIGATION, 'LAB');
        $other = $this->department(DepartmentType::INVESTIGATION, 'RAD');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['lab.requests.view', 'lab.samples.view']);

        $this->labRequest($department, $user, 'INV-DASH-IN');
        $this->labRequest($other, $user, 'INV-DASH-OUT');

        $this->actingAs($user)
            ->get(route('investigations.dashboard'))
            ->assertOk()
            ->assertSee(__('investigations.dashboard.title'))
            // Only the active department's pending request feeds the insight banner.
            ->assertSee(__('investigations.dashboard.pending_requests_count', ['count' => 1]))
            ->assertSee('INV-DASH-IN')
            ->assertDontSee('INV-DASH-OUT')
            ->assertSee('volumeChart', false);

        $this->actingAs($user)
            ->get(route('investigations.dashboard.redirect'))
            ->assertRedirect(route('investigations.dashboard'));
    }

    public function test_legacy_browser_lab_route_redirects_but_json_stays_compatible(): void
    {
        $department = $this->department(DepartmentType::INVESTIGATION, 'LAB');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['lab.requests.view']);

        $this->actingAs($user)
            ->get(route('admin.lab.requests.index'))
            ->assertRedirect(route('investigations.lab.requests.index'));

        $this->actingAs($user)
            ->getJson(route('admin.lab.requests.index'))
            ->assertOk()
            ->assertHeaderMissing('Location');
    }

    public function test_login_and_department_switch_land_on_investigations_dashboard(): void
    {
        $consultation = $this->department(DepartmentType::CONSULTATION, 'CON');
        $investigation = $this->department(DepartmentType::INVESTIGATION, 'LAB');
        $user = User::factory()->create(['department_id' => $consultation->id]);
        $user->departments()->attach($investigation->id);
        $this->give($user, ['departments.context.switch']);

        $this->actingAs($user)
            ->post(route('admin.my-dashboard.context.store'), ['department_id' => $investigation->id])
            ->assertRedirect(route('investigations.dashboard'));

        $labUser = User::factory()->create([
            'department_id' => $investigation->id,
            'email' => 'investigations-login@example.test',
        ]);

        auth()->logout();
        $this->app['session']->flush();

        $this->post(route('login'), ['email' => $labUser->email, 'password' => 'password'])
            ->assertRedirect(route('investigations.dashboard'));
    }

    public function test_investigations_locales_have_recursive_key_parity(): void
    {
        $english = array_keys(Arr::dot(require lang_path('en/investigations.php')));
        $french = array_keys(Arr::dot(require lang_path('fr/investigations.php')));

        sort($english);
        sort($french);

        $this->assertSame($english, $french);
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

    private function labRequest(Department $targetDepartment, User $user, string $number): LabRequest
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'status' => VisitStatus::ACTIVE->value,
        ]);

        return LabRequest::create([
            'request_number' => $number,
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'requested_by' => $user->id,
            'target_department_id' => $targetDepartment->id,
            'urgency' => 'routine',
            'status' => 'pending',
        ]);
    }

    private function sample(LabRequest $labRequest, string $number): Sample
    {
        return Sample::create([
            'sample_number' => $number,
            'lab_request_id' => $labRequest->id,
            'specimen_type' => 'blood',
            'status' => SampleStatus::PENDING,
        ]);
    }

    private function give(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);
    }
}
