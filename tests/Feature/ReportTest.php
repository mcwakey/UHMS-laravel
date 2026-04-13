<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $dept = Department::factory()->create();
        $this->user = User::factory()->create(['department_id' => $dept->id]);
        $role = Role::create(['name' => 'Admin']);
        $perm = Permission::create(['name' => 'reports.view']);
        $role->givePermissionTo($perm);
        $this->user->assignRole($role);
    }

    public function test_income_report_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.reports.income'));
        $response->assertStatus(200);
    }

    public function test_patient_report_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.reports.patients'));
        $response->assertStatus(200);
    }

    public function test_visit_report_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.reports.visits'));
        $response->assertStatus(200);
    }

    public function test_nhis_report_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.reports.nhis'));
        $response->assertStatus(200);
    }

    public function test_pharmacy_sales_report_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.reports.pharmacy-sales'));
        $response->assertStatus(200);
    }

    public function test_daily_collection_report_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.reports.daily-collection'));
        $response->assertStatus(200);
    }

    public function test_consultation_stats_report_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.reports.consultation-stats'));
        $response->assertStatus(200);
    }

    public function test_admissions_report_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.reports.admissions'));
        $response->assertStatus(200);
    }

    public function test_discharges_report_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.reports.discharges'));
        $response->assertStatus(200);
    }

    public function test_leave_report_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.reports.leave'));
        $response->assertStatus(200);
    }

    public function test_payroll_report_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.reports.payroll'));
        $response->assertStatus(200);
    }

    public function test_claims_report_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.reports.claims'));
        $response->assertStatus(200);
    }

    public function test_stock_valuation_report_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.reports.stock-valuation'));
        $response->assertStatus(200);
    }

    public function test_expired_stock_report_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.reports.expired-stock'));
        $response->assertStatus(200);
    }

    public function test_statement_search_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.reports.statement-search'));
        $response->assertStatus(200);
    }

    public function test_investigation_revenue_report_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.reports.investigation-revenue'));
        $response->assertStatus(200);
    }

    public function test_pharmacy_sales_summary_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.reports.pharmacy-sales-summary'));
        $response->assertStatus(200);
    }

    public function test_report_requires_permission(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'NoAccess']);
        $user->assignRole($role);

        $response = $this->actingAs($user)->get(route('admin.reports.income'));
        $response->assertStatus(403);
    }
}
