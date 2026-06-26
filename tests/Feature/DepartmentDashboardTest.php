<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Models\Department;
use App\Models\User;
use App\Http\Controllers\Admin\Dashboard\DepartmentDashboardController;
use App\Services\Dashboard\DepartmentDashboardRegistry;
use App\Services\Dashboard\DepartmentDashboardResolver;
use App\Services\Dashboard\DepartmentDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DepartmentDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /** Every department type must resolve to a non-empty dashboard key (no UnhandledMatchError). */
    public function test_every_department_type_resolves_a_dashboard_key(): void
    {
        $resolver = app(DepartmentDashboardResolver::class);

        foreach (DepartmentType::cases() as $i => $type) {
            // Use Department::create (not the factory) to avoid the factory's
            // unique() name generator overflowing across all 19 types.
            $department = Department::create([
                'name' => 'Dept '.$type->value,
                'code' => 'D'.$i,
                'type' => $type->value,
                'status' => 'active',
            ]);
            $user = User::factory()->create(['department_id' => $department->id]);

            $key = $resolver->resolveKey($user);

            $this->assertNotEmpty($key, "Type {$type->value} resolved an empty dashboard key");
        }
    }

    /** Every previewable dashboard key must build without error on an empty DB. */
    public function test_every_previewable_dashboard_builds(): void
    {
        $registry = app(DepartmentDashboardRegistry::class);
        $service = app(DepartmentDashboardService::class);
        $user = User::factory()->create();

        foreach ($registry->previewableKeys() as $key) {
            $data = $service->build($key, $user);

            $this->assertIsArray($data);
            $this->assertSame($key, $data['key'] ?? null);
        }
    }

    public function test_registry_maps_types_to_expected_dashboards(): void
    {
        $registry = app(DepartmentDashboardRegistry::class);

        $this->assertSame(DepartmentDashboardResolver::EMERGENCY, $registry->keyForType(DepartmentType::EMERGENCY));
        $this->assertSame(DepartmentDashboardResolver::ADMISSION, $registry->keyForType(DepartmentType::INPATIENT));
        $this->assertSame(DepartmentDashboardResolver::ADMISSION, $registry->keyForType(DepartmentType::MATERNITY));
        $this->assertSame(DepartmentDashboardResolver::THEATRE, $registry->keyForType(DepartmentType::THEATRE));
        $this->assertSame(DepartmentDashboardResolver::INVESTIGATION, $registry->keyForType(DepartmentType::RADIOLOGY));
        $this->assertSame(DepartmentDashboardResolver::ACCOUNTING, $registry->keyForType(DepartmentType::FINANCE));
        $this->assertSame(DepartmentDashboardResolver::STOCK, $registry->keyForType(DepartmentType::STORES));

        // Types with no dedicated dashboard deliberately fall through (null).
        $this->assertNull($registry->keyForType(DepartmentType::MORTUARY));
        $this->assertNull($registry->keyForType(DepartmentType::AMBULANCE));
        $this->assertNull($registry->keyForType(DepartmentType::SUPPORT));
        $this->assertNull($registry->keyForType(null));
    }

    public function test_user_in_new_type_department_can_load_my_dashboard(): void
    {
        // Emergency was one of the new types that 500'd before this work.
        $department = Department::factory()->create(['type' => DepartmentType::EMERGENCY->value]);
        $user = User::factory()->create(['department_id' => $department->id]);

        $this->actingAs($user)
            ->get(route('admin.my-dashboard'))
            ->assertOk();
    }

    public function test_admin_preview_selects_requested_dashboard_and_rejects_invalid(): void
    {
        // Asserted at the controller level: a Blade->Inertia middleware rewrites
        // the HTTP response, so assertViewHas can't see the Blade data.
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Super Admin', 'web'));
        $controller = app(DepartmentDashboardController::class);

        // Valid preview key → switch to it.
        $valid = $controller->index($this->requestAs($admin, DepartmentDashboardResolver::PHARMACY))->getData();
        $this->assertSame(DepartmentDashboardResolver::PHARMACY, $valid['key']);
        $this->assertSame(DepartmentDashboardResolver::MANAGEMENT, $valid['resolved_key']);
        $this->assertTrue($valid['is_preview']);
        $this->assertArrayHasKey(DepartmentDashboardResolver::PHARMACY, $valid['available_dashboards']);

        // Unknown preview key → fall back to the admin's own dashboard (no crash).
        $invalid = $controller->index($this->requestAs($admin, 'totally-invalid'))->getData();
        $this->assertSame(DepartmentDashboardResolver::MANAGEMENT, $invalid['key']);
        $this->assertFalse($invalid['is_preview']);
    }

    public function test_non_admin_cannot_preview_other_dashboards(): void
    {
        $department = Department::factory()->create(['type' => DepartmentType::PHARMACY->value, 'name' => 'Rx', 'code' => 'RX1']);
        $user = User::factory()->create(['department_id' => $department->id]);
        $controller = app(DepartmentDashboardController::class);

        // A pharmacy-department user asking ?as=management is ignored.
        $data = $controller->index($this->requestAs($user, DepartmentDashboardResolver::MANAGEMENT))->getData();

        $this->assertSame(DepartmentDashboardResolver::PHARMACY, $data['key']);
        $this->assertFalse($data['is_preview']);
        $this->assertSame([], $data['available_dashboards']);
    }

    private function requestAs(User $user, string $as): Request
    {
        $request = Request::create('/admin/my-dashboard', 'GET', ['as' => $as]);
        $request->setUserResolver(fn () => $user);

        return $request;
    }
}
