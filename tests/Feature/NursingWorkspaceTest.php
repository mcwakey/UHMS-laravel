<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\Priority;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\ClinicalTask;
use App\Models\Department;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\Dashboard\DepartmentDashboardRegistry;
use App\Services\Dashboard\DepartmentDashboardResolver;
use App\Services\ModuleService;
use App\Services\SidebarMenuBuilder;
use Database\Seeders\DepartmentTypeShowcaseSeeder;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NursingWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(ModuleService::class)->flush();
    }

    public function test_routes_use_nursing_prefix_namespace_and_department_guard(): void
    {
        $this->assertSame('http://localhost/nursing/dashboard', route('nursing.dashboard'));
        $this->assertSame('http://localhost/nursing', route('nursing.dashboard.redirect'));
        $this->assertSame('http://localhost/admin/dashboards/nurse', route('admin.dashboards.nurse'));
        $this->assertSame('http://localhost/nursing/opd/queue', route('nursing.opd.queue'));
        $this->assertSame('http://localhost/nursing/triage', route('nursing.triage.index'));
        $this->assertSame('http://localhost/nursing/vitals', route('nursing.vitals.create'));
        $this->assertSame('http://localhost/nursing/tasks', route('nursing.tasks.index'));
        $this->assertSame('http://localhost/nursing/consultations', route('nursing.consultations.index'));
        $this->assertSame('http://localhost/nursing/service-renderings', route('nursing.service-renderings.index'));
        $this->assertSame('http://localhost/nursing/service-renderings/reports', route('nursing.service-renderings.reports'));
        $this->assertSame('http://localhost/nursing/handoffs', route('nursing.handoffs.index'));
        $this->assertSame(DepartmentDashboardResolver::NURSING, app(DepartmentDashboardRegistry::class)->keyForType(DepartmentType::NURSING));

        $middleware = Route::getRoutes()->getByName('nursing.opd.queue')->gatherMiddleware();
        $this->assertContains('auth', $middleware);
        $this->assertContains('department.type:nursing', $middleware);
        $this->assertContains('can:visits.view', $middleware);
        $this->assertContains('nursing.opd.scope', $middleware);
    }

    public function test_non_nursing_department_cannot_access_workspace_even_with_permissions(): void
    {
        $department = $this->department(DepartmentType::CONSULTATION, 'CON');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, [
            'patients.view', 'visits.view', 'vitals.view', 'consultations.view',
            'service_rendering.view', 'reports.view',
        ]);

        $this->actingAs($user)->get(route('nursing.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('nursing.opd.queue'))->assertForbidden();
        $this->actingAs($user)->get(route('nursing.triage.index'))->assertForbidden();
        $this->actingAs($user)->get(route('nursing.consultations.index'))->assertForbidden();
        $this->actingAs($user)->get(route('nursing.service-renderings.index'))->assertForbidden();
    }

    public function test_queue_and_dashboard_are_opd_and_active_department_scoped(): void
    {
        $nursing = $this->department(DepartmentType::NURSING, 'NUR');
        $otherNursing = $this->department(DepartmentType::NURSING, 'NU2');
        $user = User::factory()->create(['department_id' => $nursing->id]);
        $this->give($user, ['visits.view']);
        Role::findOrCreate('Triage Nurse', 'web');
        $user->assignRole('Triage Nurse');

        $included = $this->visit($nursing, VisitType::OUTPATIENT, VisitStatus::QUEUED, 'PN-INCLUDED');
        $inpatient = $this->visit($nursing, VisitType::INPATIENT, VisitStatus::QUEUED, 'PN-INPATIENT');
        $other = $this->visit($otherNursing, VisitType::OUTPATIENT, VisitStatus::QUEUED, 'PN-OTHER');

        $this->actingAs($user)->get(route('nursing.opd.queue'))
            ->assertOk()->assertSee($included->patient->patient_number)
            ->assertDontSee($inpatient->patient->patient_number)
            ->assertDontSee($other->patient->patient_number);

        $this->actingAs($user)->get(route('nursing.dashboard'))
            ->assertOk()
            ->assertSee(__('role_dashboards.nurse.ward_occupancy'))
            ->assertSee(__('nursing.metrics.waiting_for_triage'))
            ->assertSee(__('nursing.metrics.triage_in_progress'))
            ->assertSee(__('nursing.metrics.vitals_incomplete'))
            ->assertSee(__('nursing.metrics.waiting_for_consultation'))
            ->assertSee(__('nursing.metrics.nursing_action_required'))
            ->assertSee(__('nursing.metrics.completed_today'))
            ->assertSee('>1<', false);
    }

    public function test_out_of_scope_or_inpatient_case_is_not_available(): void
    {
        $nursing = $this->department(DepartmentType::NURSING, 'NUR');
        $other = $this->department(DepartmentType::NURSING, 'NU2');
        $user = User::factory()->create(['department_id' => $nursing->id]);
        $this->give($user, ['visits.view']);

        $wrongDepartment = $this->visit($other, VisitType::OUTPATIENT, VisitStatus::ACTIVE, 'PN-OTHER');
        $inpatient = $this->visit($nursing, VisitType::INPATIENT, VisitStatus::ACTIVE, 'PN-INPATIENT');

        $this->actingAs($user)->get(route('nursing.opd.show', $wrongDepartment))->assertNotFound();
        $this->actingAs($user)->get(route('nursing.opd.show', $inpatient))->assertNotFound();
    }

    public function test_nursing_visit_show_allows_global_triage_visit_assigned_to_consultation_department(): void
    {
        $nursing = $this->department(DepartmentType::NURSING, 'NUR');
        $consultation = $this->department(DepartmentType::CONSULTATION, 'CON');
        $user = User::factory()->create(['department_id' => $nursing->id]);
        $this->give($user, ['visits.view']);

        $visit = $this->visit($consultation, VisitType::OUTPATIENT, VisitStatus::QUEUED, 'PN-GLOBAL-TRIAGE');

        $this->actingAs($user)
            ->get(route('nursing.visits.show', $visit))
            ->assertOk()
            ->assertSee('PN-GLOBAL-TRIAGE');
    }

    public function test_menu_is_nursing_specific_and_permission_filtered(): void
    {
        $nursing = $this->department(DepartmentType::NURSING, 'NUR');
        $user = User::factory()->create(['department_id' => $nursing->id]);
        $this->give($user, [
            'patients.view', 'visits.view', 'vitals.view', 'clinical_tasks.view',
            'consultations.view', 'service_rendering.view', 'service_rendering.reports', 'reports.view',
        ]);

        $sections = app(SidebarMenuBuilder::class)->build($user, 'nursing.triage.index');
        $items = collect($sections)->flatMap(fn (array $section) => $section['items']);
        $routes = $items->pluck('route');

        $this->assertContains('nursing.dashboard', $routes);
        $this->assertContains('nursing.opd.queue', $routes);
        $this->assertContains('nursing.triage.index', $routes);
        $this->assertContains('nursing.vitals.create', $routes);
        $this->assertContains('nursing.visits.index', $routes);
        $this->assertContains('nursing.tasks.index', $routes);
        $this->assertContains('nursing.consultations.index', $routes);
        $this->assertContains('nursing.service-renderings.index', $routes);
        $this->assertContains('nursing.reports.index', $routes);
        $this->assertContains('nursing.service-renderings.reports', $routes);
        $this->assertNotContains('admin.admissions.index', $routes);
        $this->assertTrue((bool) $items->firstWhere('route', 'nursing.triage.index')['active']);

        $activeItems = collect(app(SidebarMenuBuilder::class)->build($user, 'nursing.opd.active'))
            ->flatMap(fn (array $section) => $section['items'])
            ->where('active', true)
            ->pluck('route')
            ->values()
            ->all();
        $this->assertSame(['nursing.opd.active'], $activeItems);
    }

    public function test_generic_browser_routes_redirect_to_nursing_but_json_does_not(): void
    {
        $nursing = $this->department(DepartmentType::NURSING, 'NUR');
        $user = User::factory()->create(['department_id' => $nursing->id]);
        $this->give($user, ['patients.view', 'visits.view', 'vitals.view', 'consultations.view', 'service_rendering.view']);

        $this->actingAs($user)->get(route('admin.patients.index'))->assertRedirect(route('nursing.patients.index'));
        $this->actingAs($user)->get(route('admin.visits.index'))->assertRedirect(route('nursing.visits.index'));
        $this->actingAs($user)->get(route('admin.triage.index'))->assertRedirect(route('nursing.triage.index'));
        $this->actingAs($user)->get(route('admin.consultations.index'))->assertRedirect(route('nursing.consultations.index'));
        $this->actingAs($user)->get(route('admin.service-renderings.index'))->assertRedirect(route('nursing.service-renderings.index'));
        $this->actingAs($user)->getJson(route('admin.patients.index'))->assertOk()->assertHeaderMissing('Location');
    }

    public function test_login_and_active_department_switch_land_on_nursing_dashboard(): void
    {
        $consultation = $this->department(DepartmentType::CONSULTATION, 'CON');
        $nursing = $this->department(DepartmentType::NURSING, 'NUR');
        $user = User::factory()->create(['department_id' => $consultation->id]);
        $user->departments()->attach($nursing->id);
        $this->give($user, ['departments.context.switch']);

        $this->actingAs($user)->post(route('admin.my-dashboard.context.store'), ['department_id' => $nursing->id])
            ->assertRedirect(route('nursing.dashboard'));

        $nurse = User::factory()->create(['department_id' => $nursing->id, 'email' => 'nurse-login@example.test']);
        Role::findOrCreate('Triage Nurse', 'web');
        $nurse->assignRole('Triage Nurse');
        auth()->logout();
        $this->app['session']->flush();
        $this->post(route('login'), ['email' => $nurse->email, 'password' => 'password'])
            ->assertRedirect(route('nursing.dashboard'));
    }

    public function test_nursing_worklists_reports_and_handoffs_render_safe_empty_states(): void
    {
        $nursing = $this->department(DepartmentType::NURSING, 'NUR');
        $user = User::factory()->create(['department_id' => $nursing->id]);
        $this->give($user, [
            'visits.view', 'clinical_tasks.view', 'consultations.view',
            'service_rendering.view', 'service_rendering.reports', 'reports.view',
        ]);

        foreach ([
            'nursing.opd.active', 'nursing.opd.completed', 'nursing.tasks.index',
            'nursing.treatments.index', 'nursing.consultations.index',
            'nursing.service-renderings.index', 'nursing.service-renderings.reports',
            'nursing.reports.index', 'nursing.handoffs.index',
        ] as $routeName) {
            $this->actingAs($user)->get(route($routeName))->assertOk();
        }
    }

    public function test_consultation_pages_only_show_active_nursing_department_opd_visits(): void
    {
        $nursing = $this->department(DepartmentType::NURSING, 'NUR');
        $otherNursing = $this->department(DepartmentType::NURSING, 'NU2');
        $consultation = $this->department(DepartmentType::CONSULTATION, 'CON');
        $user = User::factory()->create(['department_id' => $nursing->id]);
        $this->give($user, ['consultations.view']);
        $service = ServiceCatalog::create([
            'name' => 'General consultation',
            'code' => 'CONSULT-'.random_int(100, 999),
            'category' => 'consultation',
            'price' => 0,
            'is_active' => true,
            'department_id' => $consultation->id,
        ]);
        $included = $this->visit($nursing, VisitType::OUTPATIENT, VisitStatus::WAITING, 'PN-CONSULT-IN');
        $excluded = $this->visit($otherNursing, VisitType::OUTPATIENT, VisitStatus::WAITING, 'PN-CONSULT-OUT');

        foreach ([$included, $excluded] as $visit) {
            VisitConsultationRoute::create([
                'visit_id' => $visit->id,
                'patient_id' => $visit->patient_id,
                'department_id' => $consultation->id,
                'service_id' => $service->id,
                'status' => VisitConsultationRoute::STATUS_PENDING,
            ]);
        }

        $this->actingAs($user)->get(route('nursing.consultations.index'))
            ->assertOk()
            ->assertSee($included->patient->patient_number)
            ->assertDontSee($excluded->patient->patient_number);
        $this->actingAs($user)->get(route('nursing.consultations.show', $included))->assertOk();
        $this->actingAs($user)->get(route('nursing.consultations.show', $excluded))->assertNotFound();
    }

    public function test_seeded_nursing_roles_receive_the_complete_workspace_menu(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(ModuleSeeder::class);
        app(ModuleService::class)->flush();
        $nursing = $this->department(DepartmentType::NURSING, 'NUR');

        $user = User::factory()->create(['department_id' => $nursing->id]);
        $user->assignRole('Triage Nurse');

        $routes = collect(app(SidebarMenuBuilder::class)->build($user, 'nursing.dashboard'))
            ->flatMap(fn (array $section) => $section['items'])
            ->pluck('route');

        $this->assertContains('nursing.dashboard', $routes);
        $this->assertContains('nursing.opd.queue', $routes);
        $this->assertContains('nursing.triage.index', $routes);
        $this->assertContains('nursing.vitals.create', $routes);
        $this->assertContains('nursing.visits.index', $routes);
        $this->assertContains('nursing.tasks.index', $routes);
        $this->assertContains('nursing.consultations.index', $routes);
        $this->assertContains('nursing.service-renderings.index', $routes);
        $this->assertContains('nursing.reports.index', $routes);
        $this->assertContains('nursing.service-renderings.reports', $routes);

        $this->seed(DepartmentTypeShowcaseSeeder::class);
        $showcaseUser = User::where('email', DepartmentType::NURSING->value.'@uhms.local')->firstOrFail();

        $this->assertTrue($showcaseUser->hasRole('Triage Nurse'));
    }

    public function test_english_and_french_nursing_locales_have_identical_keys(): void
    {
        $english = Arr::dot(require lang_path('en/nursing.php'));
        $french = Arr::dot(require lang_path('fr/nursing.php'));

        $this->assertSame(array_keys($english), array_keys($french));
    }

    public function test_triage_and_vitals_actions_remain_in_workspace_and_are_audited(): void
    {
        $nursing = $this->department(DepartmentType::NURSING, 'NUR');
        $user = User::factory()->create(['department_id' => $nursing->id]);
        $this->give($user, ['visits.view', 'vitals.view', 'vitals.create']);
        $triageVisit = $this->visit($nursing, VisitType::OUTPATIENT, VisitStatus::TRIAGE, 'PN-TRIAGE');

        $this->actingAs($user)->post(route('nursing.triage.store', $triageVisit), [
            'temperature' => 37.1,
            'heart_rate' => 82,
            'spo2' => 98,
        ])->assertRedirect(route('nursing.visits.show', $triageVisit));

        $this->assertDatabaseHas('activity_log', ['event' => 'TRIAGE_CREATED', 'subject_id' => $triageVisit->id]);

        $vitalsVisit = $this->visit($nursing, VisitType::OUTPATIENT, VisitStatus::ACTIVE, 'PN-VITALS');
        $this->actingAs($user)
            ->from(route('nursing.vitals.create', ['visit_id' => $vitalsVisit->id]))
            ->post(route('nursing.vitals.store'), ['visit_id' => $vitalsVisit->id, 'temperature' => 36.9, 'heart_rate' => 78])
            ->assertRedirect(route('nursing.vitals.create', ['visit_id' => $vitalsVisit->id]));

        $this->assertDatabaseHas('activity_log', ['event' => 'VITALS_RECORDED']);
    }

    public function test_authorized_nurse_can_complete_same_day_scoped_task(): void
    {
        $nursing = $this->department(DepartmentType::NURSING, 'NUR');
        $user = User::factory()->create(['department_id' => $nursing->id]);
        $this->give($user, ['visits.view', 'clinical_tasks.view', 'clinical_tasks.complete']);
        $visit = $this->visit($nursing, VisitType::OUTPATIENT, VisitStatus::ACTIVE, 'PN-TASK');
        $task = ClinicalTask::create([
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'assigned_department_id' => $nursing->id,
            'task_type' => ClinicalTask::TYPE_VITALS_MONITORING,
            'title' => 'Repeat vital signs',
            'status' => ClinicalTask::STATUS_DUE,
        ]);

        $this->actingAs($user)->patch(route('nursing.tasks.update', $task), ['action' => 'complete'])
            ->assertRedirect(route('nursing.tasks.show', $task));

        $this->assertSame(ClinicalTask::STATUS_COMPLETED, $task->fresh()->status);
        $this->assertDatabaseHas('activity_log', ['event' => 'NURSING_TASK_COMPLETED', 'subject_id' => $task->id]);
    }

    private function visit(Department $department, VisitType $type, VisitStatus $status, string $patientNumber): Visit
    {
        $patient = Patient::factory()->create(['patient_number' => $patientNumber]);

        return Visit::factory()->create([
            'patient_id' => $patient->id,
            'current_department_id' => $department->id,
            'visit_type' => $type->value,
            'status' => $status->value,
            'priority' => Priority::NORMAL->value,
            'arrived_at' => now()->subMinutes(15),
        ]);
    }

    private function department(DepartmentType $type, string $code): Department
    {
        return Department::create(['name' => $type->label().' '.$code, 'code' => $code.random_int(100, 999), 'type' => $type->value, 'status' => 'active']);
    }

    private function give(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $user->givePermissionTo($permissions);
    }
}
