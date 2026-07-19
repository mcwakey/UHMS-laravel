<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Models\AntenatalVisit;
use App\Models\Department;
use App\Models\InvoiceItem;
use App\Models\MaternityBillingEvent;
use App\Models\MaternityServiceMapping;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Services\Maternity\MaternityBillingPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MaternityBillingPostingPhase14_1Test extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config(['audit_streaming.async_writes' => false]);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $department = Department::factory()->create([
            'name' => 'Maternity Billing Preview',
            'code' => 'MBP',
            'type' => DepartmentType::MATERNITY->value,
            'status' => 'active',
        ]);

        $this->user = User::factory()->create(['department_id' => $department->id]);
        $role = Role::findOrCreate('Maternity Phase 14.1 Tester', 'web');
        foreach ($this->permissions() as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->user->assignRole($role);

        $this->artisan('maternity:seed-manual-test-data', ['--count' => 1, '--fresh-manual' => true])
            ->assertExitCode(0);
    }

    public function test_maternity_billing_config_defaults_to_safe_preview_mode(): void
    {
        $this->assertFalse((bool) config('billing.maternity_billing.enabled'));
        $this->assertFalse((bool) config('billing.maternity_billing.auto_post'));
        $this->assertSame('mother', config('billing.maternity_billing.newborn_billing_policy'));
    }

    public function test_preview_shows_missing_mapping_without_posting_invoice_items(): void
    {
        $service = app(MaternityBillingPostingService::class);
        $ancVisit = AntenatalVisit::firstOrFail();
        $invoiceItemsBefore = InvoiceItem::count();

        $preview = $service->previewForSource($ancVisit, 'anc_registration_package');

        $this->assertSame(MaternityBillingPostingService::STATUS_MISSING_MAPPING, $preview['status']);
        $this->assertFalse($preview['can_post']);
        $this->assertSame($invoiceItemsBefore, InvoiceItem::count());
    }

    public function test_preview_shows_configured_mapping_but_keeps_posting_disabled(): void
    {
        $catalogueService = $this->createMaternityService('MT-ANC-PKG', 'Maternity ANC Package', 125);
        MaternityServiceMapping::create([
            'mapping_key' => 'anc_registration_package',
            'service_id' => $catalogueService->id,
            'is_active' => true,
            'configured_by' => $this->user->id,
            'configured_at' => now(),
        ]);

        $service = app(MaternityBillingPostingService::class);
        $ancVisit = AntenatalVisit::firstOrFail();
        $invoiceItemsBefore = InvoiceItem::count();

        $preview = $service->previewForSource($ancVisit, 'anc_registration_package');

        $this->assertSame(MaternityBillingPostingService::STATUS_BILLING_DISABLED, $preview['status']);
        $this->assertSame($catalogueService->id, $preview['service']->id);
        $this->assertSame(125.0, $preview['amount']);
        $this->assertFalse($preview['can_post']);
        $this->assertSame($invoiceItemsBefore, InvoiceItem::count());
    }

    public function test_preview_detects_already_posted_maternity_billing_event(): void
    {
        $catalogueService = $this->createMaternityService('MT-ANC-DUP', 'Maternity ANC Duplicate Guard', 80);
        MaternityServiceMapping::create([
            'mapping_key' => 'anc_registration_package',
            'service_id' => $catalogueService->id,
            'is_active' => true,
        ]);

        $ancVisit = AntenatalVisit::firstOrFail();
        MaternityBillingEvent::create([
            'mapping_key' => 'anc_registration_package',
            'source_type' => 'antenatal_visit',
            'source_id' => $ancVisit->id,
            'patient_id' => $ancVisit->patient_id,
            'visit_id' => $ancVisit->visit_id,
            'admission_id' => $ancVisit->admission_id,
            'service_id' => $catalogueService->id,
            'amount' => 80,
            'status' => MaternityBillingEvent::STATUS_POSTED,
            'posted_by' => $this->user->id,
            'posted_at' => now(),
        ]);

        $preview = app(MaternityBillingPostingService::class)
            ->previewForSource($ancVisit, 'anc_registration_package');

        $this->assertSame(MaternityBillingPostingService::STATUS_ALREADY_POSTED, $preview['status']);
        $this->assertTrue($preview['already_posted']);
    }

    public function test_detail_page_renders_read_only_billing_preview(): void
    {
        $ancVisit = AntenatalVisit::firstOrFail();
        $invoiceItemsBefore = InvoiceItem::count();

        $this->actingAs($this->user)
            ->get(route('maternity.antenatal.show', $ancVisit))
            ->assertOk()
            ->assertSee('Billing Preview')
            ->assertSee('Preview only')
            ->assertSee('Mapping missing');

        $this->assertSame($invoiceItemsBefore, InvoiceItem::count());
    }

    public function test_post_method_is_present_but_does_not_create_invoice_items_in_phase_14_1(): void
    {
        $ancVisit = AntenatalVisit::firstOrFail();
        $invoiceItemsBefore = InvoiceItem::count();

        $result = app(MaternityBillingPostingService::class)
            ->postForSource($ancVisit, 'anc_registration_package', $this->user);

        $this->assertSame(MaternityBillingPostingService::STATUS_POSTING_NOT_IMPLEMENTED, $result['status']);
        $this->assertFalse($result['posted']);
        $this->assertSame($invoiceItemsBefore, InvoiceItem::count());
    }

    private function createMaternityService(string $code, string $name, float $price): ServiceCatalog
    {
        return ServiceCatalog::create([
            'name' => $name,
            'code' => $code,
            'category' => 'maternity',
            'price' => $price,
            'is_active' => true,
            'is_billable' => true,
        ]);
    }

    private function permissions(): array
    {
        return [
            'maternity.view',
            'maternity.dashboard.view',
            'maternity.anc.view',
            'maternity.billing.preview',
            'maternity.billing.audit.view',
            'maternity.manual_seed.run',
        ];
    }
}
