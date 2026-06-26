<?php

namespace Tests\Feature\Departments;

use App\Enums\DepartmentType;
use App\Http\Controllers\Admin\Dashboard\DepartmentDashboardController;
use App\Models\Department;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Services\Department\DepartmentDashboardLayoutRegistry;
use App\Services\DepartmentMenuProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class DepartmentDashboardDesignDifferentiationTest extends TestCase
{
    use RefreshDatabase;

    private function department(DepartmentType $type, string $name, string $code): Department
    {
        return Department::create(['name' => $name, 'code' => $code, 'type' => $type->value, 'status' => 'active']);
    }

    /** Render the department dashboard payload for a user (bypasses Inertia middleware). */
    private function payloadFor(User $user, array $query = []): array
    {
        $request = Request::create('/admin/my-dashboard', 'GET', $query);
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession(app('session.store'));

        return app(DepartmentDashboardController::class)->index($request)->getData();
    }

    public function test_all_19_types_have_a_translated_dashboard_name(): void
    {
        foreach (DepartmentType::cases() as $type) {
            $key = 'departments.dashboards.'.$type->value.'.name';
            $this->assertTrue(Lang::has($key), "Missing dashboard name for {$type->value}");
            $this->assertStringContainsString('Dashboard', __($key));
        }
    }

    public function test_every_type_resolves_a_known_layout_family(): void
    {
        $registry = app(DepartmentDashboardLayoutRegistry::class);
        $this->assertTrue($registry->hasLayoutFamilyForEveryDepartmentType());

        $expected = [
            'consultation' => 'clinical_queue',
            'emergency' => 'emergency_command',
            'investigation' => 'diagnostic_workbench',
            'radiology' => 'imaging_workbench',
            'theatre' => 'surgery_board',
            'pharmacy' => 'dispensing_stock',
            'finance' => 'finance_control',
            'stores' => 'stores_inventory',
            'records' => 'records_office',
            'inpatient' => 'ward_board',
            'mortuary' => 'generic_department',
            'support' => 'generic_department',
        ];
        foreach ($expected as $type => $family) {
            $this->assertSame($family, $registry->layoutFamilyFor(DepartmentType::from($type)));
        }
    }

    public function test_every_layout_family_has_a_blade_partial(): void
    {
        foreach (DepartmentDashboardLayoutRegistry::LAYOUT_FAMILIES as $family) {
            $this->assertTrue(
                View::exists('admin.dashboards.department.partials.layouts.'.$family),
                "Missing layout partial for {$family}",
            );
        }
    }

    public function test_menu_heading_personalises_and_varies_by_type(): void
    {
        $service = app(DepartmentMenuProfileService::class);

        $this->assertSame('Laboratory Department Workbench', $service->headingForType(DepartmentType::INVESTIGATION, 'Laboratory Department'));
        $this->assertSame('Emergency / Casualty Command Center', $service->headingForType(DepartmentType::EMERGENCY, 'Emergency / Casualty'));
        $this->assertSame('Main Pharmacy Operations', $service->headingForType(DepartmentType::PHARMACY, 'Main Pharmacy'));
        $this->assertSame('Billing Office Control Room', $service->headingForType(DepartmentType::FINANCE, 'Billing Office'));

        // No department name → safe fallback to the dashboard name, not ":department".
        $this->assertSame('Investigation Dashboard', $service->headingForType(DepartmentType::INVESTIGATION, null));
    }

    public function test_lab_user_gets_investigation_identity_and_layout(): void
    {
        $lab = $this->department(DepartmentType::INVESTIGATION, 'Laboratory Department', 'LAB');
        $user = User::factory()->create(['department_id' => $lab->id]);

        $data = $this->payloadFor($user);

        $this->assertSame('Investigation Dashboard', $data['dashboard']['title']);
        $this->assertSame('diagnostic_workbench', $data['layout_family']);
        $this->assertStringContainsString('Laboratory Department', $data['dashboard']['subtitle']);
        $this->assertStringContainsString('Laboratory Department', $data['dashboard']['scope_message']);
        $this->assertSame('Laboratory Department Workbench', $data['menu_heading']);
    }

    public function test_radiology_user_distinct_from_lab(): void
    {
        $xray = $this->department(DepartmentType::RADIOLOGY, 'X-Ray Unit', 'XR');
        $user = User::factory()->create(['department_id' => $xray->id]);

        $data = $this->payloadFor($user);

        $this->assertSame('Radiology Dashboard', $data['dashboard']['title']);
        $this->assertSame('imaging_workbench', $data['layout_family']);
        $this->assertStringContainsString('X-Ray Unit', $data['dashboard']['subtitle']);
    }

    public function test_services_are_scoped_to_the_users_own_department(): void
    {
        $lab = $this->department(DepartmentType::INVESTIGATION, 'Laboratory Department', 'LAB');
        $xray = $this->department(DepartmentType::RADIOLOGY, 'X-Ray Unit', 'XR');
        $this->service($lab, 'CBC', 'Full Blood Count');
        $this->service($xray, 'XR1', 'Chest X-Ray');

        $user = User::factory()->create(['department_id' => $lab->id]);
        $names = collect($this->payloadFor($user)['services']['rows'])->pluck('name');

        $this->assertTrue($names->contains('Full Blood Count'));
        $this->assertFalse($names->contains('Chest X-Ray'));
    }

    public function test_user_without_department_gets_safe_fallback(): void
    {
        $user = User::factory()->create(['department_id' => null]);

        $data = $this->payloadFor($user);

        $this->assertSame('generic_department', $data['layout_family']);
        $this->assertNotEmpty($data['dashboard']['title']);
        $this->assertSame(__('departments.dashboard.no_department_assigned_dashboard'), $data['dashboard']['scope_message']);
    }

    private function service(Department $department, string $code, string $name): void
    {
        ServiceCatalog::create([
            'name' => $name,
            'code' => $code,
            'category' => 'lab',
            'price' => 10,
            'department_id' => $department->id,
            'is_active' => true,
        ]);
    }
}
