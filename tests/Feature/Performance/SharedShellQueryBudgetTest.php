<?php

namespace Tests\Feature\Performance;

use App\Enums\DepartmentType;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\InteractsWithDatabaseQueryBudgets;
use Tests\TestCase;

class SharedShellQueryBudgetTest extends TestCase
{
    use InteractsWithDatabaseQueryBudgets;
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $role = Role::findOrCreate('Super Admin', 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function seedDepartments(int $count): void
    {
        $start = Department::query()->count() + 1;

        foreach (range($start, $start + $count - 1) as $index) {
            Department::create([
                'name' => 'Performance Department '.$index,
                'code' => 'PD'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'type' => DepartmentType::CONSULTATION->value,
                'status' => 'active',
            ]);
        }
    }

    public function test_departments_page_stays_within_the_shared_shell_query_budget(): void
    {
        $this->seedDepartments(20);
        $user = $this->superAdmin();

        $metrics = $this->assertMaxDatabaseQueries(50, fn () => $this
            ->actingAs($user)
            ->get(route('admin.departments.index'))
            ->assertOk());

        $this->assertLessThanOrEqual(15, $metrics['duplicate']);
    }

    public function test_departments_ajax_request_stays_within_its_query_budget(): void
    {
        $this->seedDepartments(20);
        $user = $this->superAdmin();

        $metrics = $this->assertMaxDatabaseQueries(25, fn () => $this
            ->actingAs($user)
            ->get(route('admin.departments.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk());

        $this->assertLessThanOrEqual(10, $metrics['duplicate']);
    }

    public function test_departments_query_count_does_not_scale_with_page_size(): void
    {
        $this->seedDepartments(5);
        $user = $this->superAdmin();

        $small = $this->measureDatabaseQueries(fn () => $this
            ->actingAs($user)
            ->get(route('admin.departments.index'))
            ->assertOk());

        $this->seedDepartments(60);

        $large = $this->measureDatabaseQueries(fn () => $this
            ->actingAs($user)
            ->get(route('admin.departments.index', ['page' => 2]))
            ->assertOk());

        $this->assertLessThanOrEqual(
            $small['total'] + 3,
            $large['total'],
            "Department query count grew from {$small['total']} to {$large['total']} when row count increased.",
        );
    }
}
