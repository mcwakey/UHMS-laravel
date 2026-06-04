<?php

namespace Tests\Feature;

use App\Enums\BillingType;
use App\Enums\InvoiceStatus;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BillingDiscountPermissionTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;
    private Patient $patient;
    private Visit $visit;
    private Invoice $invoice;
    private InvoiceItem $item;

    protected function setUp(): void
    {
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        config(['billing.discount.max_without_override_percent' => 10]);

        $this->department = Department::factory()->create();
        $registrar = User::factory()->create(['department_id' => $this->department->id]);
        $this->patient = Patient::factory()->create(['registered_by' => $registrar->id]);
        $this->visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'current_department_id' => $this->department->id,
            'created_by' => $registrar->id,
        ]);
        $this->invoice = Invoice::create([
            'invoice_number' => 'INV-DISC-001',
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'billing_type' => BillingType::CASH->value,
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'nhis_amount' => 0,
            'total_amount' => 100,
            'amount_paid' => 0,
            'balance' => 100,
            'status' => InvoiceStatus::PENDING,
            'created_by' => $registrar->id,
        ]);
        $this->item = InvoiceItem::create([
            'invoice_id' => $this->invoice->id,
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'description' => 'Consultation',
            'quantity' => 1,
            'unit_price' => 100,
            'cash_price' => 100,
            'selected_price' => 100,
            'insurance_covered' => 0,
            'discount_amount' => 0,
            'patient_payable' => 100,
            'paid_amount' => 0,
            'balance' => 100,
            'payment_status' => 'unpaid',
            'total_price' => 100,
            'payer_type' => 'cash',
            'created_by' => $registrar->id,
        ]);
    }

    public function test_discount_permissions_are_seeded_and_low_trust_roles_do_not_get_critical_discount_permissions(): void
    {
        $this->seed(RoleSeeder::class);

        foreach ([
            'billing.discount.view',
            'billing.discount.apply',
            'billing.discount.approve',
            'billing.discount.remove',
            'billing.discount.override_limit',
            'billing.discount.report',
        ] as $permission) {
            $this->assertDatabaseHas('permissions', ['name' => $permission]);
        }

        $cashier = Role::where('name', 'Cashier')->firstOrFail();
        $this->assertTrue($cashier->hasPermissionTo('billing.discount.apply'));
        $this->assertFalse($cashier->hasPermissionTo('billing.discount.override_limit'));
        $this->assertFalse($cashier->hasPermissionTo('billing.discount.remove'));

        $doctor = Role::where('name', 'Doctor')->firstOrFail();
        $this->assertFalse($doctor->hasPermissionTo('billing.discount.apply'));
    }

    public function test_unauthorized_user_cannot_apply_discount_and_button_is_hidden(): void
    {
        $user = $this->userWithPermissions('billing-clerk', ['invoices.view']);

        $this->actingAs($user)
            ->post(route('admin.billing.invoices.items.discount', [$this->invoice, $this->item]), [
                'discount_amount' => 5,
                'reason' => 'Courtesy adjustment',
            ])
            ->assertStatus(403);

        $this->actingAs($user)
            ->get(route('admin.billing.invoices.show', $this->invoice))
            ->assertOk()
            ->assertDontSee('Apply Discount', false)
            ->assertDontSee('discountModal', false);
    }

    public function test_authorized_user_can_apply_discount_within_limit_and_action_is_logged(): void
    {
        $user = $this->userWithPermissions('cashier', [
            'invoices.view',
            'billing.discount.view',
            'billing.discount.apply',
        ]);

        $this->actingAs($user)
            ->post(route('admin.billing.invoices.items.discount', [$this->invoice, $this->item]), [
                'discount_amount' => 5,
                'reason' => 'Approved courtesy discount',
            ])
            ->assertRedirect();

        $this->assertEquals(5.0, (float) $this->item->fresh()->discount_amount);
        $this->assertEquals(95.0, (float) $this->item->fresh()->patient_payable);
        $this->assertDatabaseHas('invoice_discounts', [
            'invoice_id' => $this->invoice->id,
            'invoice_item_id' => $this->item->id,
            'new_discount_amount' => 5.00,
            'reason' => 'Approved courtesy discount',
            'performed_by' => $user->id,
            'is_override' => false,
        ]);
        $this->assertTrue(ActivityLog::where('event', 'DISCOUNT_APPLIED')->exists());
    }

    public function test_user_without_override_cannot_exceed_discount_limit(): void
    {
        $user = $this->userWithPermissions('cashier', [
            'invoices.view',
            'billing.discount.apply',
        ]);

        $this->actingAs($user)
            ->post(route('admin.billing.invoices.items.discount', [$this->invoice, $this->item]), [
                'discount_amount' => 25,
                'reason' => 'Large hardship request',
            ])
            ->assertStatus(403);

        $this->assertEquals(0.0, (float) $this->item->fresh()->discount_amount);
        $this->assertDatabaseMissing('invoice_discounts', [
            'invoice_item_id' => $this->item->id,
            'new_discount_amount' => 25.00,
        ]);
    }

    public function test_user_with_override_can_exceed_discount_limit_with_reason(): void
    {
        $user = $this->userWithPermissions('billing-manager', [
            'invoices.view',
            'billing.discount.apply',
            'billing.discount.override_limit',
        ]);

        $this->actingAs($user)
            ->post(route('admin.billing.invoices.items.discount', [$this->invoice, $this->item]), [
                'discount_amount' => 25,
                'reason' => 'Management-approved hardship discount',
            ])
            ->assertRedirect();

        $this->assertEquals(25.0, (float) $this->item->fresh()->discount_amount);
        $this->assertDatabaseHas('invoice_discounts', [
            'invoice_item_id' => $this->item->id,
            'new_discount_amount' => 25.00,
            'is_override' => true,
        ]);
        $this->assertTrue(ActivityLog::where('event', 'DISCOUNT_OVERRIDE_APPLIED')->exists());
    }

    public function test_remove_discount_requires_remove_permission(): void
    {
        $this->item->forceFill([
            'discount_amount' => 5,
            'patient_payable' => 95,
            'balance' => 95,
        ])->save();

        $withoutRemove = $this->userWithPermissions('cashier', [
            'invoices.view',
            'billing.discount.apply',
        ]);

        $this->actingAs($withoutRemove)
            ->delete(route('admin.billing.invoices.items.discount.remove', [$this->invoice, $this->item]), [
                'reason' => 'Issued in error',
            ])
            ->assertStatus(403);

        $withRemove = $this->userWithPermissions('billing-supervisor', [
            'invoices.view',
            'billing.discount.remove',
        ]);

        $this->actingAs($withRemove)
            ->delete(route('admin.billing.invoices.items.discount.remove', [$this->invoice, $this->item]), [
                'reason' => 'Issued in error',
            ])
            ->assertRedirect();

        $this->assertEquals(0.0, (float) $this->item->fresh()->discount_amount);
        $this->assertDatabaseHas('invoice_discounts', [
            'invoice_item_id' => $this->item->id,
            'action' => 'removed',
            'reason' => 'Issued in error',
        ]);
    }

    public function test_discount_report_permission_is_enforced(): void
    {
        $viewer = $this->userWithPermissions('invoice-viewer', ['invoices.view']);

        $this->actingAs($viewer)
            ->get(route('admin.billing.reports.discounts'))
            ->assertStatus(403);

        $reportUser = $this->userWithPermissions('discount-reporter', [
            'billing.discount.report',
        ]);

        $this->actingAs($reportUser)
            ->get(route('admin.billing.reports.discounts'))
            ->assertOk();
    }

    public function test_permissions_audit_does_not_flag_discount_routes_as_missing(): void
    {
        $this->seed(RoleSeeder::class);

        $reportPath = storage_path('reports/permissions_audit.json');
        if (file_exists($reportPath)) {
            @unlink($reportPath);
        }

        $this->artisan('permissions:audit', ['--json' => true])
            ->assertExitCode(0);

        $payload = json_decode(file_get_contents($reportPath), true);
        $this->assertNotContains('billing.discount.apply', $payload['referenced_not_in_db']);
        $this->assertNotContains('billing.discount.remove', $payload['referenced_not_in_db']);
        $this->assertNotContains('billing.discount.report', $payload['referenced_not_in_db']);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWithPermissions(string $roleName, array $permissions): User
    {
        $user = User::factory()->create(['department_id' => $this->department->id]);
        $role = Role::firstOrCreate(['name' => $roleName]);

        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission]));
        }

        $user->assignRole($role);

        return $user;
    }
}
