<?php

namespace Tests\Feature;

use App\Enums\AdmissionStatus;
use App\Enums\BedStatus;
use App\Enums\BedType;
use App\Enums\DepartmentType;
use App\Enums\ProcedureStatus;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Admission;
use App\Models\Bed;
use App\Models\ClinicalTask;
use App\Models\Department;
use App\Models\LabRequest;
use App\Models\Patient;
use App\Models\ProcedureRequest;
use App\Models\User;
use App\Models\Visit;
use App\Models\Ward;
use App\Services\ModuleService;
use App\Services\SidebarMenuBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InpatientWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(ModuleService::class)->flush();
    }

    public function test_inpatient_routes_use_workspace_prefix_and_department_guard(): void
    {
        $this->assertSame('http://localhost/inpatient', route('inpatient.dashboard'));
        $this->assertSame('http://localhost/inpatient/admissions/active', route('inpatient.admissions.active'));
        $this->assertSame('http://localhost/inpatient/beds/availability', route('inpatient.beds.availability'));
        $this->assertSame('http://localhost/inpatient/sessions', route('inpatient.sessions.index'));
        $this->assertSame('http://localhost/inpatient/discharges/readiness', route('inpatient.discharges.readiness'));

        $middleware = Route::getRoutes()->getByName('inpatient.admissions.index')->gatherMiddleware();
        $this->assertContains('auth', $middleware);
        $this->assertContains('department.type:inpatient', $middleware);
        $this->assertContains('inpatient.scope', $middleware);
        $this->assertContains('can:ward.view', $middleware);
    }

    public function test_non_inpatient_department_cannot_access_workspace(): void
    {
        $department = $this->department(DepartmentType::CONSULTATION, 'CON');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['ward.view', 'patients.view', 'visits.view']);

        $this->actingAs($user)->get(route('inpatient.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('inpatient.admissions.index'))->assertForbidden();
        $this->actingAs($user)->get(route('inpatient.beds.index'))->assertForbidden();
    }

    public function test_inpatient_sidebar_is_workspace_specific_and_permission_filtered(): void
    {
        $department = $this->department(DepartmentType::INPATIENT, 'WARD');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, [
            'ward.view', 'admission.requests.view', 'patients.view', 'visits.view', 'consultations.view',
            'vitals.view', 'clinical_tasks.view', 'admission.medication_board.view', 'lab.requests.view',
            'procedure.view', 'beds.transfer', 'admission.discharge.plan', 'reports.view', 'admissions.readmit',
        ]);

        $items = collect(app(SidebarMenuBuilder::class)->build($user, 'inpatient.admissions.active'))
            ->flatMap(fn (array $section) => $section['items']);
        $routes = $items->pluck('route');

        $this->assertContains('inpatient.dashboard', $routes);
        $this->assertContains('inpatient.admissions.active', $routes);
        $this->assertContains('inpatient.beds.availability', $routes);
        $this->assertContains('inpatient.sessions.index', $routes);
        $this->assertContains('inpatient.medications.index', $routes);
        $this->assertContains('inpatient.discharges.readiness', $routes);
        $this->assertContains('inpatient.readmissions.index', $routes);
        $this->assertNotContains('emergency.dashboard', $routes);
        $this->assertNotContains('doctor.dashboard', $routes);
        $this->assertTrue((bool) $items->firstWhere('route', 'inpatient.admissions.active')['active']);
    }

    public function test_admission_worklists_and_direct_access_are_scoped_to_active_ward_department(): void
    {
        $department = $this->department(DepartmentType::INPATIENT, 'WARD');
        $other = $this->department(DepartmentType::INPATIENT, 'OTHER');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['ward.view']);

        $included = $this->admission($department, $user, 'ADM-IN', 'PAT-IN');
        $excluded = $this->admission($other, $user, 'ADM-OUT', 'PAT-OUT');

        $dashboard = $this->actingAs($user)
            ->get(route('inpatient.dashboard'))
            ->assertOk();
        $dashboardHtml = $this->legacyHtml($dashboard->getContent());
        $this->assertStringContainsString(__('admissions.pending_requests'), $dashboardHtml);
        $this->assertStringContainsString(__('admissions.admitted_today'), $dashboardHtml);
        $this->assertStringContainsString(__('admissions.discharged_today'), $dashboardHtml);
        $this->assertStringContainsString(__('admissions.available_beds'), $dashboardHtml);
        $this->assertStringNotContainsString(__('emergency.waiting_triage'), $dashboardHtml);
        $this->assertStringContainsString('inpatient/admissions/active', $dashboardHtml);

        $this->actingAs($user)
            ->get(route('inpatient.admissions.index'))
            ->assertOk()
            ->assertSee($included->admission_number)
            ->assertDontSee($excluded->admission_number)
            ->assertSee('inpatient\\/admissions\\/'.$included->id, false)
            ->assertSee(__('inpatient.breadcrumbs.inpatient'));

        $this->actingAs($user)->get(route('inpatient.admissions.show', $excluded))->assertNotFound();
    }

    public function test_ward_census_page_is_scoped_and_shows_beds_and_admitted_patients(): void
    {
        $department = $this->department(DepartmentType::INPATIENT, 'WARD');
        $other = $this->department(DepartmentType::INPATIENT, 'OTHER');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['ward.view']);

        $included = $this->admission($department, $user, 'ADM-IN', 'PAT-IN');
        $excluded = $this->admission($other, $user, 'ADM-OUT', 'PAT-OUT');
        $includedWard = $included->bed->ward;
        $excludedWard = $excluded->bed->ward;

        // In-scope ward: census renders under /inpatient with beds + patients.
        $response = $this->actingAs($user)
            ->get(route('inpatient.wards.show', $includedWard))
            ->assertOk()
            ->assertSee($includedWard->name)
            ->assertSee('B-1')
            ->assertSee($included->patient->full_name)
            ->assertSee(__('wards.admitted_patients'))
            ->assertSee(__('wards.occupancy'));
        $this->assertStringContainsString('/inpatient/wards', route('inpatient.wards.show', $includedWard));
        $this->assertStringContainsString('inpatient/admissions/'.$included->id, $this->legacyHtml($response->getContent()));

        // Another department's ward is invisible in the workspace.
        $this->actingAs($user)->get(route('inpatient.wards.show', $excludedWard))->assertNotFound();

        // Permission still gates the page.
        $bare = User::factory()->create(['department_id' => $department->id]);
        $this->actingAs($bare)->get(route('inpatient.wards.show', $includedWard))->assertForbidden();
    }

    public function test_legacy_browser_admission_route_redirects_but_json_stays_compatible(): void
    {
        $department = $this->department(DepartmentType::INPATIENT, 'WARD');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['ward.view']);

        $this->actingAs($user)
            ->get(route('admin.admissions.index'))
            ->assertRedirect(route('inpatient.admissions.index'));

        $this->actingAs($user)
            ->getJson(route('admin.admissions.index'))
            ->assertOk()
            ->assertHeaderMissing('Location');
    }

    public function test_login_and_department_switch_land_on_inpatient_dashboard(): void
    {
        $consultation = $this->department(DepartmentType::CONSULTATION, 'CON');
        $inpatient = $this->department(DepartmentType::INPATIENT, 'WARD');
        $user = User::factory()->create(['department_id' => $consultation->id]);
        $user->departments()->attach($inpatient->id);
        $this->give($user, ['departments.context.switch']);

        $this->actingAs($user)
            ->post(route('admin.my-dashboard.context.store'), ['department_id' => $inpatient->id])
            ->assertRedirect(route('inpatient.dashboard'));

        $inpatientUser = User::factory()->create([
            'department_id' => $inpatient->id,
            'email' => 'inpatient-login@example.test',
        ]);

        auth()->logout();
        $this->app['session']->flush();

        $this->post(route('login'), ['email' => $inpatientUser->email, 'password' => 'password'])
            ->assertRedirect(route('inpatient.dashboard'));
    }

    public function test_clinical_task_worklist_is_department_scoped_and_actions_are_audited(): void
    {
        $department = $this->department(DepartmentType::INPATIENT, 'WARD');
        $other = $this->department(DepartmentType::INPATIENT, 'OTHER');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['ward.view', 'clinical_tasks.view', 'clinical_tasks.complete']);

        $includedAdmission = $this->admission($department, $user, 'ADM-TASK-IN', 'PAT-TASK-IN');
        $excludedAdmission = $this->admission($other, $user, 'ADM-TASK-OUT', 'PAT-TASK-OUT');
        $included = $this->task($includedAdmission, 'Included ward task');
        $excluded = $this->task($excludedAdmission, 'Other ward task');

        $this->actingAs($user)
            ->get(route('inpatient.tasks.index'))
            ->assertOk()
            ->assertSee($included->title)
            ->assertDontSee($excluded->title);

        $this->actingAs($user)->get(route('inpatient.tasks.show', $excluded))->assertNotFound();

        $this->actingAs($user)
            ->patch(route('inpatient.tasks.update', $included), ['action' => 'claim'])
            ->assertRedirect(route('inpatient.tasks.show', $included));

        $this->assertDatabaseHas('clinical_tasks', [
            'id' => $included->id,
            'assigned_to' => $user->id,
            'status' => ClinicalTask::STATUS_IN_PROGRESS,
        ]);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'CLINICAL_TASKS',
            'event' => 'INPATIENT_TASK_CLAIMED',
        ]);
    }

    public function test_inpatient_locales_have_recursive_key_parity(): void
    {
        $english = array_keys(Arr::dot(require lang_path('en/inpatient.php')));
        $french = array_keys(Arr::dot(require lang_path('fr/inpatient.php')));

        sort($english);
        sort($french);

        $this->assertSame($english, $french);
    }

    public function test_investigation_and_procedure_worklists_do_not_leak_other_wards(): void
    {
        $department = $this->department(DepartmentType::INPATIENT, 'WARD');
        $other = $this->department(DepartmentType::INPATIENT, 'OTHER');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['lab.requests.view', 'procedure.view']);

        $includedAdmission = $this->admission($department, $user, 'ADM-SVC-IN', 'PAT-SVC-IN');
        $excludedAdmission = $this->admission($other, $user, 'ADM-SVC-OUT', 'PAT-SVC-OUT');

        $includedLab = $this->labRequest($includedAdmission, $user, 'INV-WARD-IN');
        $excludedLab = $this->labRequest($excludedAdmission, $user, 'INV-WARD-OUT');
        $includedProcedure = $this->procedureRequest($includedAdmission, $user, $department, 'PROC-WARD-IN');
        $excludedProcedure = $this->procedureRequest($excludedAdmission, $user, $other, 'PROC-WARD-OUT');

        $this->actingAs($user)
            ->get(route('inpatient.investigations.index'))
            ->assertOk()
            ->assertSee($includedLab->request_number)
            ->assertDontSee($excludedLab->request_number);
        $this->actingAs($user)->get(route('inpatient.investigations.show', $excludedLab))->assertNotFound();

        $this->actingAs($user)
            ->get(route('inpatient.procedures.index'))
            ->assertOk()
            ->assertSee($includedProcedure->request_number)
            ->assertDontSee($excludedProcedure->request_number);
        $this->actingAs($user)->get(route('inpatient.procedures.show', $excludedProcedure))->assertNotFound();
    }

    public function test_readmission_uses_existing_extension_workflow_and_preserves_workspace_url(): void
    {
        $department = $this->department(DepartmentType::INPATIENT, 'WARD');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['ward.view', 'admissions.readmit']);
        $admission = $this->admission($department, $user, 'ADM-READMIT', 'PAT-READMIT');

        $admission->update([
            'status' => AdmissionStatus::DISCHARGED->value,
            'actual_discharge_date' => now()->subHour(),
            'discharged_by' => $user->id,
        ]);
        $admission->bed->update(['status' => BedStatus::AVAILABLE->value]);
        $admission->visit->update(['status' => VisitStatus::COMPLETED->value]);

        $this->actingAs($user)
            ->get(route('inpatient.readmissions.create', $admission))
            ->assertOk()
            ->assertSee(__('inpatient.readmission.history_notice'));

        $this->actingAs($user)
            ->post(route('inpatient.readmissions.store', $admission), ['reason' => 'Condition requires continued inpatient care'])
            ->assertRedirect(route('inpatient.admissions.show', $admission));

        $this->assertDatabaseHas('admissions', [
            'id' => $admission->id,
            'status' => AdmissionStatus::ADMITTED->value,
            'actual_discharge_date' => null,
        ]);
        $this->assertDatabaseHas('beds', ['id' => $admission->bed_id, 'status' => BedStatus::OCCUPIED->value]);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'ADMISSION',
            'event' => 'ADMISSION_EXTENDED_FOR_CONTINUED_CARE',
        ]);
    }

    public function test_readmission_is_not_available_after_discharge_day(): void
    {
        $department = $this->department(DepartmentType::INPATIENT, 'WARD2');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['ward.view', 'admissions.readmit']);
        $admission = $this->admission($department, $user, 'ADM-READMIT-OLD', 'PAT-READMIT-OLD');

        $admission->update([
            'status' => AdmissionStatus::DISCHARGED->value,
            'actual_discharge_date' => now()->subDay(),
            'discharged_by' => $user->id,
        ]);
        $admission->bed->update(['status' => BedStatus::AVAILABLE->value]);

        $this->actingAs($user)
            ->get(route('inpatient.readmissions.create', $admission))
            ->assertStatus(409);

        $this->actingAs($user)
            ->post(route('inpatient.readmissions.store', $admission), ['reason' => 'Late extension should not be allowed'])
            ->assertSessionHasErrors('admission');

        $this->assertDatabaseHas('admissions', [
            'id' => $admission->id,
            'status' => AdmissionStatus::DISCHARGED->value,
        ]);
    }

    private function admission(Department $department, User $actor, string $number, string $patientNumber): Admission
    {
        $ward = Ward::create([
            'name' => $department->name.' Ward', 'code' => $department->code.'-W',
            'department_id' => $department->id, 'capacity' => 10, 'is_active' => true,
        ]);
        $bed = Bed::create([
            'ward_id' => $ward->id, 'bed_number' => 'B-1', 'bed_type' => BedType::STANDARD->value,
            'status' => BedStatus::OCCUPIED->value, 'daily_rate' => 100,
        ]);
        $patient = Patient::factory()->create(['patient_number' => $patientNumber]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id, 'visit_type' => VisitType::INPATIENT->value,
            'status' => VisitStatus::ADMITTED->value,
        ]);

        return Admission::create([
            'admission_number' => $number, 'visit_id' => $visit->id, 'patient_id' => $patient->id,
            'bed_id' => $bed->id, 'admitted_by' => $actor->id, 'admission_date' => now()->subDay(),
            'status' => AdmissionStatus::ADMITTED->value,
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

    private function task(Admission $admission, string $title): ClinicalTask
    {
        return ClinicalTask::create([
            'visit_id' => $admission->visit_id,
            'admission_id' => $admission->id,
            'patient_id' => $admission->patient_id,
            'task_type' => ClinicalTask::TYPE_NURSING_OBSERVATION,
            'title' => $title,
            'status' => ClinicalTask::STATUS_SCHEDULED,
            'due_at' => now()->addHour(),
        ]);
    }

    private function labRequest(Admission $admission, User $user, string $number): LabRequest
    {
        return LabRequest::create([
            'request_number' => $number,
            'visit_id' => $admission->visit_id,
            'patient_id' => $admission->patient_id,
            'requested_by' => $user->id,
            'status' => 'pending',
        ]);
    }

    private function procedureRequest(Admission $admission, User $user, Department $department, string $number): ProcedureRequest
    {
        return ProcedureRequest::create([
            'request_number' => $number,
            'visit_id' => $admission->visit_id,
            'patient_id' => $admission->patient_id,
            'requested_by' => $user->id,
            'department_id' => $department->id,
            'indication' => 'Inpatient clinical requirement',
            'status' => ProcedureStatus::REQUESTED->value,
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
