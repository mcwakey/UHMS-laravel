<?php

namespace Tests\Feature\Departments;

use App\Enums\DepartmentType;
use App\Http\Controllers\Admin\Dashboard\DepartmentDashboardController;
use App\Models\Department;
use App\Models\User;
use App\Services\Department\DepartmentDashboardCapabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DepartmentDashboardCapabilityTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    private function userInType(DepartmentType $type): User
    {
        $this->seq++;
        $dept = Department::create([
            'name' => $type->value.' '.$this->seq,
            'code' => 'CAP'.$this->seq,
            'type' => $type->value,
            'status' => 'active',
        ]);

        return User::factory()->create(['department_id' => $dept->id]);
    }

    private function payloadFor(User $user): array
    {
        $request = Request::create('/admin/my-dashboard', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession(app('session.store'));

        return app(DepartmentDashboardController::class)->index($request)->getData();
    }

    private function isRestricted(array $card): bool
    {
        return is_array($card['value']) && ($card['value']['restricted'] ?? false);
    }

    public function test_department_type_grants_its_own_capability_via_workflow(): void
    {
        $cap = app(DepartmentDashboardCapabilityService::class);

        $this->assertTrue($cap->can($this->userInType(DepartmentType::CONSULTATION), 'consultation_access'));
        $this->assertTrue($cap->can($this->userInType(DepartmentType::INVESTIGATION), 'investigation_access'));
        $this->assertTrue($cap->can($this->userInType(DepartmentType::PHARMACY), 'pharmacy_access'));
        $this->assertTrue($cap->can($this->userInType(DepartmentType::PHARMACY), 'stock_access'));
        $this->assertTrue($cap->can($this->userInType(DepartmentType::FINANCE), 'financial_access'));
        $this->assertTrue($cap->can($this->userInType(DepartmentType::INPATIENT), 'ward_access'));
        $this->assertTrue($cap->can($this->userInType(DepartmentType::EMERGENCY), 'emergency_access'));
    }

    public function test_cross_domain_capabilities_are_restricted(): void
    {
        $cap = app(DepartmentDashboardCapabilityService::class);

        $doctor = $this->userInType(DepartmentType::CONSULTATION);
        $this->assertFalse($cap->can($doctor, 'financial_access'));
        $this->assertFalse($cap->can($doctor, 'pharmacy_access'));
        $this->assertFalse($cap->can($doctor, 'stock_access'));

        $storeKeeper = $this->userInType(DepartmentType::STORES);
        $this->assertTrue($cap->can($storeKeeper, 'stock_access'));
        $this->assertFalse($cap->can($storeKeeper, 'consultation_access'));
        $this->assertFalse($cap->can($storeKeeper, 'financial_access'));
    }

    public function test_explicit_permission_grants_capability_without_workflow(): void
    {
        Permission::findOrCreate('reports.financial_values.view', 'web');
        $role = Role::findOrCreate('CapAccountant', 'web');
        $role->givePermissionTo('reports.financial_values.view');

        // In a SUPPORT department (no financial workflow) but holding the permission.
        $user = $this->userInType(DepartmentType::SUPPORT);
        $user->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertTrue(app(DepartmentDashboardCapabilityService::class)->can($user->fresh(), 'financial_access'));
    }

    public function test_metric_visibility_matches_capability(): void
    {
        // Doctor (consultation) — sees visit metrics, revenue restricted.
        $doctorCards = collect($this->payloadFor($this->userInType(DepartmentType::CONSULTATION))['primary_cards'])->keyBy('key');
        $this->assertFalse($this->isRestricted($doctorCards['visits_today']));
        $this->assertTrue($this->isRestricted($doctorCards['department_revenue_today']));

        // Finance — sees revenue.
        $financeCards = collect($this->payloadFor($this->userInType(DepartmentType::FINANCE))['primary_cards'])->keyBy('key');
        $this->assertFalse($this->isRestricted($financeCards['department_revenue_today']));

        // Pharmacist — sees stock + prescriptions.
        $pharmCards = collect($this->payloadFor($this->userInType(DepartmentType::PHARMACY))['primary_cards'])->keyBy('key');
        $this->assertFalse($this->isRestricted($pharmCards['low_stock']));
        $this->assertFalse($this->isRestricted($pharmCards['pending_prescriptions']));
        $this->assertTrue($this->isRestricted($pharmCards['department_revenue_today']));
    }

    public function test_card_and_drilldown_visibility_are_unified(): void
    {
        $cards = collect($this->payloadFor($this->userInType(DepartmentType::CONSULTATION))['primary_cards'])->keyBy('key');

        // Visible card → drilldown present.
        $this->assertNotNull($cards['visits_today']['route']);
        // Restricted card → no drilldown (never card-visible/drilldown-hidden or vice-versa).
        $this->assertNull($cards['department_revenue_today']['route']);
    }

    public function test_quick_actions_follow_the_same_capability(): void
    {
        $pharmacistActions = collect($this->payloadFor($this->userInType(DepartmentType::PHARMACY))['quick_actions'])->pluck('label');
        $this->assertTrue($pharmacistActions->contains(__('dashboards.department.actions.dispense')));

        // A consultation user never gets the pharmacy dispense action.
        $doctorActions = collect($this->payloadFor($this->userInType(DepartmentType::CONSULTATION))['quick_actions'])->pluck('label');
        $this->assertFalse($doctorActions->contains(__('dashboards.department.actions.dispense')));
    }
}
