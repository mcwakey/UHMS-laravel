<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\PrescriptionStatus;
use App\Models\Department;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Prescription;
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

class PharmacyWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(ModuleService::class)->flush();
    }

    public function test_pharmacy_routes_use_workspace_prefix_and_department_guard(): void
    {
        $this->assertSame('http://localhost/pharmacy', route('pharmacy.dashboard'));
        $this->assertSame('http://localhost/pharmacy/prescriptions', route('pharmacy.prescriptions.index'));
        $this->assertSame('http://localhost/pharmacy/dispensing', route('pharmacy.dispensing.index'));
        $this->assertSame('http://localhost/pharmacy/stock', route('pharmacy.product-stock.balances'));
        $this->assertSame('http://localhost/pharmacy/handoffs', route('pharmacy.handoffs.index'));

        $middleware = Route::getRoutes()->getByName('pharmacy.dispensing.index')->gatherMiddleware();
        $this->assertContains('auth', $middleware);
        $this->assertContains('department.type:pharmacy', $middleware);
        $this->assertContains('can:pharmacy.dispensing.view', $middleware);
    }

    public function test_non_pharmacy_department_cannot_access_workspace(): void
    {
        $department = $this->department(DepartmentType::CONSULTATION, 'CON');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['pharmacy.dispensing.view', 'prescriptions.view', 'stock.view']);

        $this->actingAs($user)->get(route('pharmacy.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('pharmacy.dispensing.index'))->assertForbidden();
        $this->actingAs($user)->get(route('pharmacy.prescriptions.index'))->assertForbidden();
    }

    public function test_pharmacy_sidebar_is_workspace_specific_and_permission_filtered(): void
    {
        $department = $this->department(DepartmentType::PHARMACY, 'PHA');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, [
            'prescriptions.view', 'pharmacy.dispensing.view', 'pharmacy.drugs.manage',
            'stock.view', 'patients.view', 'reports.pharmacy', 'notifications.view', 'invoices.create',
        ]);

        $items = collect(app(SidebarMenuBuilder::class)->build($user, 'pharmacy.dispensing.index'))
            ->flatMap(fn (array $section) => $section['items']);
        $routes = $items->pluck('route');

        $this->assertContains('pharmacy.dashboard', $routes);
        $this->assertContains('pharmacy.prescriptions.index', $routes);
        $this->assertContains('pharmacy.dispensing.index', $routes);
        $this->assertContains('pharmacy.history', $routes);
        $this->assertContains('pharmacy.product-stock.balances', $routes);
        $this->assertContains('pharmacy.drugs.index', $routes);
        $this->assertContains('pharmacy.patients.index', $routes);
        $this->assertContains('pharmacy.reports.index', $routes);
        $this->assertNotContains('investigations.dashboard', $routes);
        $this->assertNotContains('admin.pharmacy.dispensing.index', $routes);
        $this->assertTrue((bool) $items->firstWhere('route', 'pharmacy.dispensing.index')['active']);

        // A user without stock/catalogue permissions loses those entries.
        $bare = User::factory()->create(['department_id' => $department->id]);
        $this->give($bare, ['pharmacy.dispensing.view']);
        $bareRoutes = collect(app(SidebarMenuBuilder::class)->build($bare, 'pharmacy.dashboard'))
            ->flatMap(fn (array $section) => $section['items'])
            ->pluck('route');
        $this->assertContains('pharmacy.dispensing.index', $bareRoutes);
        $this->assertNotContains('pharmacy.product-stock.balances', $bareRoutes);
        $this->assertNotContains('pharmacy.drugs.index', $bareRoutes);
    }

    public function test_prescription_worklist_and_details_render_in_workspace(): void
    {
        $department = $this->department(DepartmentType::PHARMACY, 'PHA');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['prescriptions.view']);

        $prescription = $this->prescription($user);

        $this->actingAs($user)
            ->get(route('pharmacy.prescriptions.index'))
            ->assertOk()
            ->assertSee($prescription->prescription_number);

        $this->actingAs($user)
            ->get(route('pharmacy.prescriptions.show', $prescription))
            ->assertOk()
            ->assertSee($prescription->prescription_number);
    }

    public function test_dashboard_renders_pharmacy_operations_board(): void
    {
        $department = $this->department(DepartmentType::PHARMACY, 'PHA');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['pharmacy.dispensing.view']);

        $this->actingAs($user)
            ->get(route('pharmacy.dashboard'))
            ->assertOk()
            ->assertSee(__('role_dashboards.pharmacist.title'))
            ->assertSee('salesChart', false);

        $this->actingAs($user)
            ->get(route('pharmacy.dashboard.redirect'))
            ->assertRedirect(route('pharmacy.dashboard'));
    }

    public function test_legacy_browser_routes_redirect_but_json_stays_compatible(): void
    {
        $department = $this->department(DepartmentType::PHARMACY, 'PHA');
        $user = User::factory()->create(['department_id' => $department->id]);
        $this->give($user, ['pharmacy.dispensing.view', 'prescriptions.view']);

        $this->actingAs($user)
            ->get(route('admin.pharmacy.dispensing.index'))
            ->assertRedirect(route('pharmacy.dispensing.index'));

        $this->actingAs($user)
            ->get(route('admin.prescriptions.index'))
            ->assertRedirect(route('pharmacy.prescriptions.index'));

        $this->actingAs($user)
            ->getJson(route('admin.pharmacy.dispensing.index'))
            ->assertOk()
            ->assertHeaderMissing('Location');
    }

    public function test_login_and_department_switch_land_on_pharmacy_dashboard(): void
    {
        $consultation = $this->department(DepartmentType::CONSULTATION, 'CON');
        $pharmacy = $this->department(DepartmentType::PHARMACY, 'PHA');
        $user = User::factory()->create(['department_id' => $consultation->id]);
        $user->departments()->attach($pharmacy->id);
        $this->give($user, ['departments.context.switch']);

        $this->actingAs($user)
            ->post(route('admin.my-dashboard.context.store'), ['department_id' => $pharmacy->id])
            ->assertRedirect(route('pharmacy.dashboard'));

        $pharmacyUser = User::factory()->create([
            'department_id' => $pharmacy->id,
            'email' => 'pharmacy-login@example.test',
        ]);

        auth()->logout();
        $this->app['session']->flush();

        $this->post(route('login'), ['email' => $pharmacyUser->email, 'password' => 'password'])
            ->assertRedirect(route('pharmacy.dashboard'));
    }

    public function test_pharmacy_locales_have_recursive_key_parity(): void
    {
        $english = array_keys(Arr::dot(require lang_path('en/pharmacy.php')));
        $french = array_keys(Arr::dot(require lang_path('fr/pharmacy.php')));

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

    private function prescription(User $doctor): Prescription
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create(['patient_id' => $patient->id]);
        $medicalRecord = MedicalRecord::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        return Prescription::create([
            'medical_record_id' => $medicalRecord->id,
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'prescription_number' => Prescription::generatePrescriptionNumber(),
            'status' => PrescriptionStatus::PENDING->value,
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
