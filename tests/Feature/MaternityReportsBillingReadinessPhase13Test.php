<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Models\Department;
use App\Models\InvoiceItem;
use App\Models\MaternityServiceMapping;
use App\Models\Patient;
use App\Models\PregnancyProfile;
use App\Models\ServiceCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MaternityReportsBillingReadinessPhase13Test extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config(['audit_streaming.async_writes' => false]);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $department = Department::factory()->create([
            'name' => 'Maternity Reports',
            'code' => 'MTR',
            'type' => DepartmentType::MATERNITY->value,
            'status' => 'active',
        ]);

        $this->user = User::factory()->create(['department_id' => $department->id]);
        $role = Role::findOrCreate('Maternity Phase 13 Tester', 'web');
        foreach ($this->permissions() as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->user->assignRole($role);

        $this->artisan('maternity:seed-manual-test-data', ['--count' => 1, '--fresh-manual' => true])
            ->assertExitCode(0);
    }

    public function test_maternity_reports_pages_render(): void
    {
        $this->actingAs($this->user)
            ->get(route('maternity.reports.index'))
            ->assertOk()
            ->assertSee('Maternity Reports');

        foreach (['antenatal', 'labor', 'deliveries', 'newborns', 'postnatal', 'risk'] as $report) {
            $this->actingAs($this->user)
                ->get(route('maternity.reports.'.$report))
                ->assertOk()
                ->assertSee('Export CSV');
        }
    }

    public function test_csv_export_returns_download_response(): void
    {
        $this->actingAs($this->user)
            ->get(route('maternity.reports.export', ['report' => 'antenatal']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_billing_readiness_shows_missing_mappings_and_can_save_without_posting_billing(): void
    {
        $service = ServiceCatalog::create([
            'name' => 'MT Maternity Delivery Package',
            'code' => 'MT-MAT-PKG',
            'category' => 'maternity',
            'price' => 100,
            'is_active' => true,
            'is_billable' => true,
        ]);

        $this->actingAs($this->user)
            ->get(route('maternity.billing-readiness.show'))
            ->assertOk()
            ->assertSee('Billing Mapping Readiness')
            ->assertSee('Mapping missing');

        $invoiceItemsBefore = InvoiceItem::count();

        $this->actingAs($this->user)
            ->patch(route('admin.maternity.billing-readiness.update'), [
                'mappings' => [
                    'normal_delivery' => [
                        'service_id' => $service->id,
                        'is_active' => 1,
                        'description' => 'Manual test mapping',
                    ],
                    'assisted_delivery' => [
                        'service_id' => $service->id,
                        'is_active' => 1,
                        'description' => 'Duplicate advisory mapping',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.maternity.billing-readiness.show'));

        $this->assertDatabaseHas('maternity_service_mappings', [
            'mapping_key' => 'normal_delivery',
            'service_id' => $service->id,
        ]);
        $this->actingAs($this->user)
            ->get(route('maternity.billing-readiness.show'))
            ->assertOk()
            ->assertSee('Service used by multiple mappings');
        $this->assertSame($invoiceItemsBefore, InvoiceItem::count());
    }

    public function test_manual_seed_command_creates_identifiable_records_and_default_seeders_do_not_call_it(): void
    {
        $this->assertTrue(Patient::where('patient_number', 'like', 'MT-MAT-%')->exists());
        $this->assertTrue(PregnancyProfile::whereHas('patient', fn ($query) => $query->where('patient_number', 'like', 'MT-MAT-%'))->exists());

        $this->assertStringNotContainsString(
            'maternity:seed-manual-test-data',
            file_get_contents(database_path('seeders/DatabaseSeeder.php'))
        );
    }

    public function test_non_permitted_user_cannot_view_reports(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('maternity.reports.index'))
            ->assertForbidden();
    }

    private function permissions(): array
    {
        return [
            'maternity.view',
            'maternity.dashboard.view',
            'maternity.reports.view',
            'maternity.reports.export',
            'maternity.billing_readiness.view',
            'maternity.billing_readiness.manage',
            'maternity.manual_seed.run',
        ];
    }
}
