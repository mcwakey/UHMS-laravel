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

    public function test_primary_cards_carry_a_sparkline_and_delta_when_recent_data_exists(): void
    {
        $opd = $this->department(DepartmentType::CONSULTATION, 'General OPD', 'OPD');
        // Create the viewing user first so patient/visit factory FKs (registered_by) resolve.
        $user = User::factory()->create(['department_id' => $opd->id]);
        $column = \Illuminate\Support\Facades\Schema::hasColumn('visits', 'current_department_id')
            ? 'current_department_id'
            : 'department_id';

        // Seed visits spread across the trailing 7 days, scoped to this department.
        foreach ([6 => 1, 4 => 2, 2 => 2, 0 => 4] as $daysAgo => $count) {
            \App\Models\Visit::factory()->count($count)->create([
                $column => $opd->id,
                'created_at' => now()->subDays($daysAgo),
            ]);
        }
        $cards = collect($this->payloadFor($user)['primary_cards'] ?? []);

        $visitsCard = $cards->firstWhere('key', 'visits_today');
        $this->assertNotNull($visitsCard, 'Expected a visits_today primary card for a consultation department');
        $this->assertArrayHasKey('spark', $visitsCard, 'visits_today card should carry a sparkline series');
        $this->assertCount(7, $visitsCard['spark']);
        $this->assertGreaterThan(0, array_sum($visitsCard['spark']));
        $this->assertArrayHasKey('delta', $visitsCard);
        $this->assertContains($visitsCard['delta_dir'], ['up', 'down']);

        // Static metrics (no time series) must NOT get a sparkline.
        $staticCard = $cards->firstWhere('key', 'waiting_queue');
        if ($staticCard !== null) {
            $this->assertArrayNotHasKey('spark', $staticCard);
        }
    }

    public function test_consultation_showcase_renders_with_rich_widgets(): void
    {
        $opd = $this->department(DepartmentType::CONSULTATION, 'General OPD', 'OPD');
        $user = User::factory()->create(['department_id' => $opd->id]);
        $this->service($opd, 'CONS', 'General Consultation');

        $data = $this->payloadFor($user);

        // Render the bespoke showcase body (no app layout / auth chrome needed).
        $html = view('admin.dashboards.department.partials.layouts.clinical_showcase', $data)->render();

        $this->assertNotEmpty($html);
        // Rich list card header (the live queue) renders even when the queue is empty.
        $this->assertStringContainsString($data['work_queue']['title'], $html);
        // Department service surfaced in the services card.
        $this->assertStringContainsString('General Consultation', $html);
    }

    public function test_pharmacy_showcase_renders_with_stock_alert_donut(): void
    {
        $pharmacy = $this->department(DepartmentType::PHARMACY, 'Main Pharmacy', 'PHARM');
        $user = User::factory()->create(['department_id' => $pharmacy->id]);

        $data = $this->payloadFor($user);

        // Stock-alert donut chart is present in the payload (restricted without stock perms).
        $this->assertArrayHasKey('stock_status_breakdown', $data['charts']);
        $this->assertSame('doughnut', $data['charts']['stock_status_breakdown']['type']);

        $html = view('admin.dashboards.department.partials.layouts.dispensing_showcase', $data)->render();

        $this->assertNotEmpty($html);
        $this->assertStringContainsString($data['work_queue']['title'], $html);
        // The stock-status card header renders its title.
        $this->assertStringContainsString(__('dashboards.department.charts.stock_status'), $html);
    }

    public function test_emergency_showcase_renders_with_priority_alert(): void
    {
        $er = $this->department(DepartmentType::EMERGENCY, 'Casualty', 'ER');
        $user = User::factory()->create(['department_id' => $er->id]);

        $data = $this->payloadFor($user);
        $html = view('admin.dashboards.department.partials.layouts.emergency_showcase', $data)->render();

        $this->assertNotEmpty($html);
        $this->assertStringContainsString('department-emergency-alert', $html);
        $this->assertStringContainsString(__('departments.sections.priority_alerts'), $html);
        $this->assertStringContainsString($data['work_queue']['title'], $html);
    }

    public function test_all_bespoke_showcases_render(): void
    {
        $cases = [
            ['investigation_showcase', DepartmentType::INVESTIGATION, 'LAB2'],
            ['blood_bank_showcase', DepartmentType::BLOOD_BANK, 'BB'],
            ['surgery_showcase', DepartmentType::THEATRE, 'OT'],
            ['ward_showcase', DepartmentType::INPATIENT, 'WARD'],
            ['finance_showcase', DepartmentType::FINANCE, 'FIN'],
            ['stores_showcase', DepartmentType::STORES, 'STR'],
            ['records_showcase', DepartmentType::RECORDS, 'REC'],
            ['generic_showcase', DepartmentType::SUPPORT, 'SUP'],
        ];

        foreach ($cases as [$showcase, $type, $code]) {
            $dept = $this->department($type, $code.' Department', $code);
            $user = User::factory()->create(['department_id' => $dept->id]);
            $data = $this->payloadFor($user);

            $html = view('admin.dashboards.department.partials.layouts.'.$showcase, $data)->render();

            $this->assertNotEmpty($html, "{$showcase} rendered empty");
            $this->assertStringContainsString($data['work_queue']['title'], $html, "{$showcase} missing queue title");
        }
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
