<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\StatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        config(['audit_streaming.async_writes' => false]);
    }

    private function userWith(array $permissions): User
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'StatRole'.uniqid()]);
        foreach ($permissions as $name) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $name]));
        }
        $user->assignRole($role);

        return $user;
    }

    /* ── Dashboard ── */

    public function test_statistics_dashboard_loads(): void
    {
        $user = $this->userWith(['statistics.view', 'statistics.dashboard.view']);

        $this->actingAs($user)->get(route('admin.statistics.dashboard'))->assertStatus(200);
    }

    public function test_dashboard_uses_default_date_range(): void
    {
        $user = $this->userWith(['statistics.view', 'statistics.dashboard.view']);
        $data = app(StatisticsService::class)->dashboard([]);

        $this->assertSame(now()->startOfMonth()->toDateString(), $data['filters']['from']);
        $this->assertSame(now()->toDateString(), $data['filters']['to']);
    }

    public function test_dashboard_forbidden_without_permission(): void
    {
        $user = $this->userWith(['patients.view']);

        $this->actingAs($user)->get(route('admin.statistics.dashboard'))->assertStatus(403);
    }

    /* ── Every page loads with the umbrella permission ── */

    public function test_all_non_sensitive_pages_load_with_umbrella_permission(): void
    {
        $user = $this->userWith(['statistics.view']);

        foreach (array_keys(app(StatisticsService::class)->catalogue()) as $key) {
            if ($key === 'staff-performance') {
                continue; // sensitive — tested separately
            }
            $this->actingAs($user)->get(route('admin.statistics.'.$key))->assertStatus(200);
        }
    }

    /* ── Staff performance is sensitive ── */

    public function test_staff_performance_forbidden_with_only_umbrella(): void
    {
        $user = $this->userWith(['statistics.view']);

        $this->actingAs($user)->get(route('admin.statistics.staff-performance'))->assertStatus(403);
    }

    public function test_staff_performance_visible_with_specific_permission(): void
    {
        $user = $this->userWith(['statistics.staff_performance.view']);

        $this->actingAs($user)->get(route('admin.statistics.staff-performance'))->assertStatus(200);
    }

    /* ── Data-source correctness ── */

    public function test_blood_inventory_counts_only_available_units(): void
    {
        $this->seedUnit('AVAILABLE');
        $this->seedUnit('AVAILABLE');
        $this->seedUnit('EXPIRED');
        $this->seedUnit('ISSUED');

        $data = app(StatisticsService::class)->build('blood-bank', []);
        $available = collect($data['kpis'])->firstWhere('label', 'Units Available');

        $this->assertSame(2, $available['value']);
    }

    /* ── Export ── */

    public function test_export_requires_permission(): void
    {
        $user = $this->userWith(['statistics.view']); // no statistics.export

        $this->actingAs($user)
            ->get(route('admin.statistics.diagnoses', ['export' => 'csv']))
            ->assertStatus(403);
    }

    public function test_export_succeeds_and_is_logged(): void
    {
        $user = $this->userWith(['statistics.view', 'statistics.export']);

        $response = $this->actingAs($user)->get(route('admin.statistics.diagnoses', ['export' => 'csv']));
        $response->assertStatus(200);
        $this->assertStringContainsString('KPI', $response->streamedContent());

        $this->assertDatabaseHas('activity_log', ['event' => 'STATISTICS_EXPORTED']);
    }

    private function seedUnit(string $status): void
    {
        DB::table('blood_units')->insert([
            'unit_number' => 'BUN'.uniqid(),
            'blood_group' => 'O+',
            'component_type' => 'WHOLE_BLOOD',
            'collection_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(30)->toDateString(),
            'screening_status' => 'PASSED',
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
