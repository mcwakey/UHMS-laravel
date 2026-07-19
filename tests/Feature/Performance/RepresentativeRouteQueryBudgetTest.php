<?php

namespace Tests\Feature\Performance;

use App\Enums\DepartmentType;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\Concerns\InteractsWithDatabaseQueryBudgets;
use Tests\TestCase;

class RepresentativeRouteQueryBudgetTest extends TestCase
{
    use InteractsWithDatabaseQueryBudgets;
    use RefreshDatabase;

    public static function routeBudgets(): array
    {
        return [
            'users list' => ['admin.users.index', null, 50, false],
            'patients AJAX list' => ['admin.patients.index', null, 25, true],
            'visits AJAX list' => ['admin.visits.index', null, 25, true],
            'products list' => ['admin.products.index', null, 50, false],
            'journey worklist' => ['admin.journey.worklist', null, 50, false],
            'recent notifications JSON' => ['admin.notifications.recent', null, 15, false],
            'finance invoices' => ['finance.billing.invoices.index', DepartmentType::FINANCE, 50, false],
            'stores products' => ['stores.products.index', DepartmentType::STORES, 50, false],
            'consultation workspace' => ['doctor.consultations.index', DepartmentType::CONSULTATION, 90, false],
        ];
    }

    #[DataProvider('routeBudgets')]
    public function test_representative_route_stays_within_its_query_budget(
        string $routeName,
        ?DepartmentType $departmentType,
        int $budget,
        bool $ajax,
    ): void {
        $user = $this->superAdmin($departmentType);

        $metrics = $this->assertMaxDatabaseQueries($budget, fn () => $this
            ->actingAs($user)
            ->get(route($routeName), $ajax ? ['X-Requested-With' => 'XMLHttpRequest'] : [])
            ->assertOk());

        $this->assertLessThanOrEqual(max(10, intdiv($budget, 2)), $metrics['duplicate']);
    }

    public function test_department_dashboard_stays_within_complex_page_budget(): void
    {
        $user = User::factory()->create();

        $this->assertMaxDatabaseQueries(90, fn () => $this
            ->actingAs($user)
            ->get(route('admin.my-dashboard'))
            ->assertOk());
    }

    private function superAdmin(?DepartmentType $departmentType): User
    {
        $department = $departmentType ? Department::create([
            'name' => ucfirst(str_replace('_', ' ', $departmentType->value)).' Performance',
            'code' => 'PF'.strtoupper(substr($departmentType->value, 0, 8)),
            'type' => $departmentType->value,
            'status' => 'active',
        ]) : null;

        $role = Role::findOrCreate('Super Admin', 'web');
        $user = User::factory()->create(['department_id' => $department?->id]);
        $user->assignRole($role);

        return $user;
    }
}
