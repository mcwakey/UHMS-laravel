<?php

namespace Tests\Feature\Accounting;

use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\InventoryAccountingPostingService;
use Database\Seeders\AccountingChartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 6 — Inventory accounting: costed stock-out movements post COGS /
 * consumables expense against inventory; adjustments and damage post to the
 * correct gain/loss accounts; non-costed transfers create no journal.
 */
class InventoryAccountingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccountingChartSeeder::class);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    private function accountId(string $code): int
    {
        return Account::where('code', $code)->value('id');
    }

    private function product(ProductType $type): Product
    {
        return Product::create([
            'name' => 'Item '.uniqid(), 'code' => 'P-'.strtoupper(uniqid()),
            'product_type' => $type, 'unit' => 'unit', 'is_active' => true, 'created_by' => $this->user->id,
        ]);
    }

    private function location(): StockLocation
    {
        return StockLocation::create(['name' => 'Loc '.uniqid(), 'type' => 'pharmacy', 'is_active' => true]);
    }

    private function movement(StockMovementType $type, Product $product, float $totalCost): StockMovement
    {
        return StockMovement::create([
            'product_id' => $product->id,
            'stock_location_id' => $this->location()->id,
            'movement_type' => $type,
            'direction' => in_array($type, [StockMovementType::ADJUSTMENT_IN], true) ? 'in' : 'out',
            'quantity' => 1,
            'unit_cost' => $totalCost,
            'total_cost' => $totalCost,
            'performed_by' => $this->user->id,
            'movement_date' => now(),
        ]);
    }

    public function test_pharmacy_dispense_posts_cogs_against_inventory(): void
    {
        $movement = $this->movement(StockMovementType::PHARMACY_DISPENSED, $this->product(ProductType::DRUG), 30);

        $entry = app(InventoryAccountingPostingService::class)->postForMovement($movement);

        $this->assertNotNull($entry);
        $this->assertEqualsWithDelta(30, (float) $entry->lines->firstWhere('account_id', $this->accountId('5100'))->debit, 0.001); // COGS
        $this->assertEqualsWithDelta(30, (float) $entry->lines->firstWhere('account_id', $this->accountId('1310'))->credit, 0.001); // Pharmacy Inventory
        $this->assertTrue($entry->is_balanced);
    }

    public function test_consumable_usage_posts_expense_against_inventory(): void
    {
        $movement = $this->movement(StockMovementType::WARD_CONSUMED, $this->product(ProductType::CONSUMABLE), 12);

        $entry = app(InventoryAccountingPostingService::class)->postForMovement($movement);

        $this->assertEqualsWithDelta(12, (float) $entry->lines->firstWhere('account_id', $this->accountId('5200'))->debit, 0.001); // Consumables Expense
        $this->assertEqualsWithDelta(12, (float) $entry->lines->firstWhere('account_id', $this->accountId('1320'))->credit, 0.001); // Consumables Inventory
    }

    public function test_stock_adjustment_increase_posts_inventory_against_gain(): void
    {
        $movement = $this->movement(StockMovementType::ADJUSTMENT_IN, $this->product(ProductType::DRUG), 40);

        $entry = app(InventoryAccountingPostingService::class)->postForMovement($movement);

        $this->assertEqualsWithDelta(40, (float) $entry->lines->firstWhere('account_id', $this->accountId('1310'))->debit, 0.001);  // Inventory
        $this->assertEqualsWithDelta(40, (float) $entry->lines->firstWhere('account_id', $this->accountId('4970'))->credit, 0.001); // Adjustment Gain
    }

    public function test_stock_adjustment_decrease_posts_loss_against_inventory(): void
    {
        $movement = $this->movement(StockMovementType::ADJUSTMENT_OUT, $this->product(ProductType::DRUG), 25);

        $entry = app(InventoryAccountingPostingService::class)->postForMovement($movement);

        $this->assertEqualsWithDelta(25, (float) $entry->lines->firstWhere('account_id', $this->accountId('5210'))->debit, 0.001);  // Adjustment Loss
        $this->assertEqualsWithDelta(25, (float) $entry->lines->firstWhere('account_id', $this->accountId('1310'))->credit, 0.001); // Inventory
    }

    public function test_damaged_stock_posts_expense_against_inventory(): void
    {
        $movement = $this->movement(StockMovementType::DAMAGED, $this->product(ProductType::DRUG), 18);

        $entry = app(InventoryAccountingPostingService::class)->postForMovement($movement);

        $this->assertEqualsWithDelta(18, (float) $entry->lines->firstWhere('account_id', $this->accountId('5220'))->debit, 0.001);  // Damaged/Expired Expense
        $this->assertEqualsWithDelta(18, (float) $entry->lines->firstWhere('account_id', $this->accountId('1310'))->credit, 0.001); // Inventory
    }

    public function test_transfer_creates_no_journal(): void
    {
        $movement = $this->movement(StockMovementType::TRANSFER_OUT, $this->product(ProductType::DRUG), 50);

        $entry = app(InventoryAccountingPostingService::class)->postForMovement($movement);

        $this->assertNull($entry);
        $this->assertSame('not_applicable', (string) $movement->fresh()->accounting_status);
        $this->assertSame(0, JournalEntry::where('reference_type', StockMovement::class)->where('reference_id', $movement->id)->count());
    }

    public function test_duplicate_stock_posting_is_blocked(): void
    {
        $movement = $this->movement(StockMovementType::PHARMACY_DISPENSED, $this->product(ProductType::DRUG), 30);

        app(InventoryAccountingPostingService::class)->postForMovement($movement);
        app(InventoryAccountingPostingService::class)->postForMovement($movement->fresh());

        $this->assertSame(1, JournalEntry::where('reference_type', StockMovement::class)->where('reference_id', $movement->id)->count());
    }

    public function test_stock_dispense_logs_activity(): void
    {
        $movement = $this->movement(StockMovementType::PHARMACY_DISPENSED, $this->product(ProductType::DRUG), 30);

        app(InventoryAccountingPostingService::class)->postForMovement($movement);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'ACCOUNTING',
            'event' => 'ACCOUNTING_POSTED_FOR_STOCK_DISPENSE',
        ]);
    }
}
