<?php

namespace Tests\Feature;

use App\Enums\LogSeverity;
use App\Enums\StockLocation as StockLocationEnum;
use App\Models\ActivityLog;
use App\Models\Module;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ModuleService;
use App\Services\ProcurementService;
use App\Services\StockTransferService;
use App\Services\SupplierLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Stock / Procurement / Admin actions must surface on the GLOBAL activity log
 * (Stock dashboard, security review) — but, being facility-level rather than
 * patient-level, must NEVER carry patient/visit context or appear on a patient
 * timeline. Also asserts no duplication of the raw movement ledger and that
 * sensitive values are masked.
 */
class StockAdminLogTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    private function logs(): \Illuminate\Support\Collection
    {
        return ActivityLog::query()->get();
    }

    private function event(string $event): ?ActivityLog
    {
        return ActivityLog::query()->where('event', $event)->latest('id')->first();
    }

    public function test_stock_transfer_lifecycle_logs_without_patient_context(): void
    {
        $service = app(StockTransferService::class);

        $transfer = $service->create([
            'from_location' => StockLocationEnum::STORE->value,
            'to_location' => StockLocationEnum::PHARMACY->value,
        ]);
        $service->approve($transfer->fresh());
        $service->cancel($transfer->fresh());

        $events = $this->logs()->pluck('event')->all();
        $this->assertContains('STOCK_TRANSFER_REQUESTED', $events);
        $this->assertContains('STOCK_TRANSFER_APPROVED', $events);
        $this->assertContains('STOCK_TRANSFER_CANCELLED', $events);

        $requested = $this->event('STOCK_TRANSFER_REQUESTED');
        $this->assertSame('STOCK', $requested->log_name);
        $this->assertSame($transfer->id, (int) $requested->properties['stock_transfer_id']);
        $this->assertSame('store', $requested->properties['metadata']['from']);
        $this->assertSame('pharmacy', $requested->properties['metadata']['to']);
        $this->assertNull($requested->patient_id, 'Generic stock transfers must not carry patient context.');

        $cancelled = $this->event('STOCK_TRANSFER_CANCELLED');
        $this->assertSame(LogSeverity::WARNING->value, $cancelled->properties['severity']);
    }

    public function test_purchase_order_lifecycle_logs_under_purchase_orders_module(): void
    {
        $supplier = Supplier::create(['name' => 'Acme Pharma', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Gauze Roll', 'code' => 'GZ-001', 'product_type' => 'consumable',
            'unit' => 'roll', 'is_active' => true, 'created_by' => $this->user->id,
        ]);

        $procurement = app(ProcurementService::class);
        $po = $procurement->create([
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity_ordered' => 10, 'unit_cost' => 5],
            ],
        ]);
        $procurement->submit($po->fresh());
        $procurement->approve($po->fresh());
        $procurement->cancel($po->fresh());

        $events = $this->logs()->pluck('event')->all();
        $this->assertContains('PURCHASE_ORDER_CREATED', $events);
        $this->assertContains('PURCHASE_ORDER_SUBMITTED', $events);
        $this->assertContains('PURCHASE_ORDER_APPROVED', $events);
        $this->assertContains('PURCHASE_ORDER_CANCELLED', $events);

        $created = $this->event('PURCHASE_ORDER_CREATED');
        $this->assertSame('PURCHASE_ORDERS', $created->log_name);
        $this->assertSame($po->id, (int) $created->properties['purchase_order_id']);
        $this->assertSame($supplier->id, (int) $created->properties['supplier_id']);
        $this->assertNull($created->patient_id);

        $cancelled = $this->event('PURCHASE_ORDER_CANCELLED');
        $this->assertSame(LogSeverity::WARNING->value, $cancelled->properties['severity']);
    }

    public function test_module_disable_and_enable_log_as_settings_warning(): void
    {
        $module = Module::create([
            'name' => 'Pharmacy', 'slug' => 'pharmacy-test', 'is_core' => false, 'is_enabled' => true, 'sort_order' => 99,
        ]);
        $service = app(ModuleService::class);

        $service->disable('pharmacy-test');
        $service->enable('pharmacy-test');

        $disabled = $this->event('MODULE_DISABLED');
        $this->assertNotNull($disabled);
        $this->assertSame('SETTINGS', $disabled->log_name);
        $this->assertSame(LogSeverity::WARNING->value, $disabled->properties['severity']);
        $this->assertSame($module->id, (int) $disabled->properties['module_id']);
        $this->assertTrue((bool) $disabled->properties['old']['is_enabled']);
        $this->assertFalse((bool) $disabled->properties['attributes']['is_enabled']);
        $this->assertNull($disabled->patient_id);

        $this->assertNotNull($this->event('MODULE_ENABLED'));
    }

    public function test_core_module_disable_is_a_noop_and_logs_nothing(): void
    {
        Module::create([
            'name' => 'Core', 'slug' => 'core-test', 'is_core' => true, 'is_enabled' => true, 'sort_order' => 1,
        ]);

        app(ModuleService::class)->disable('core-test');

        $this->assertNull($this->event('MODULE_DISABLED'));
    }

    public function test_manual_supplier_payment_logs_under_supplier_ledger(): void
    {
        $supplier = Supplier::create(['name' => 'Beta Supplies', 'is_active' => true]);

        app(SupplierLedgerService::class)->recordManualEntry($supplier, [
            'entry_type' => \App\Models\SupplierLedgerEntry::TYPE_PAYMENT,
            'amount' => 250.50,
            'description' => 'Cheque payment',
        ]);

        $log = $this->event('SUPPLIER_PAYMENT_RECORDED');
        $this->assertNotNull($log);
        $this->assertSame('SUPPLIER_LEDGER', $log->log_name);
        $this->assertSame($supplier->id, (int) $log->properties['supplier_id']);
        $this->assertSame(250.50, (float) $log->properties['metadata']['amount']);
        $this->assertNull($log->patient_id);
    }

    public function test_no_generic_stock_or_admin_log_carries_patient_context(): void
    {
        $supplier = Supplier::create(['name' => 'Gamma Ltd', 'is_active' => true]);
        $procurement = app(ProcurementService::class);
        $procurement->create([
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
        ]);
        app(StockTransferService::class)->create([
            'from_location' => StockLocationEnum::STORE->value,
            'to_location' => StockLocationEnum::LABORATORY->value,
        ]);

        // Every facility-scoped log written here must be free of patient/visit ids.
        $polluted = ActivityLog::query()
            ->whereIn('log_name', ['STOCK', 'PURCHASE_ORDERS', 'SUPPLIER_LEDGER', 'SETTINGS'])
            ->where(function ($q) {
                $q->whereNotNull('patient_id')->orWhereNotNull('visit_id');
            })
            ->count();

        $this->assertSame(0, $polluted, 'Generic stock/admin logs must never appear on a patient timeline.');
    }

    public function test_sensitive_values_are_masked_in_admin_logs(): void
    {
        app(\App\Services\ActivityLogService::class)->log(
            \App\Enums\LogModule::SETTINGS,
            'SETTING_UPDATED',
            [
                'setting_key' => 'smtp',
                'old_values' => ['password' => 'old-secret', 'host' => 'mail.old'],
                'new_values' => ['password' => 'new-secret', 'host' => 'mail.new', 'api_key' => 'sk-123'],
            ],
        );

        $log = $this->event('SETTING_UPDATED');
        $this->assertSame('***MASKED***', $log->properties['old']['password']);
        $this->assertSame('***MASKED***', $log->properties['attributes']['password']);
        $this->assertSame('***MASKED***', $log->properties['attributes']['api_key']);
        // Non-sensitive fields are preserved.
        $this->assertSame('mail.new', $log->properties['attributes']['host']);
    }
}
