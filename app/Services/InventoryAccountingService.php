<?php

namespace App\Services;

use App\Enums\ProductType;
use App\Models\Account;
use App\Models\Product;
use RuntimeException;

/**
 * Resolves the chart-of-accounts entries for inventory accounting (Phase 6):
 * inventory (by product type), COGS, consumables expense, inventory adjustment
 * gain/loss, and damaged/expired stock expense.
 */
class InventoryAccountingService
{
    public function __construct(protected AccountingSettingsService $settings) {}

    public function inventoryAccountForProduct(?Product $product): Account
    {
        $type = $product?->product_type;
        $type = $type instanceof ProductType ? $type : ProductType::tryFrom((string) $type);

        $key = match ($type) {
            ProductType::DRUG => 'pharmacy_inventory_account_id',
            ProductType::REAGENT => 'laboratory_reagents_inventory_account_id',
            ProductType::CONSUMABLE,
            ProductType::SURGICAL_SUPPLY,
            ProductType::MEDICAL_SUPPLY,
            ProductType::SUPPLY => 'consumables_inventory_account_id',
            default => 'inventory_account_id',
        };

        return $this->settings->account($key)
            ?? $this->require('inventory_account_id', 'Inventory control');
    }

    public function cogsAccount(): Account
    {
        return $this->require('cost_of_goods_sold_account_id', 'Cost of Goods Sold');
    }

    public function consumablesExpenseAccount(): Account
    {
        return $this->require('consumables_expense_account_id', 'Consumables Expense');
    }

    public function adjustmentGainAccount(): Account
    {
        return $this->require('inventory_adjustment_gain_account_id', 'Inventory Adjustment Gain');
    }

    public function adjustmentLossAccount(): Account
    {
        return $this->require('inventory_adjustment_loss_account_id', 'Inventory Adjustment Loss');
    }

    public function damagedExpiredAccount(): Account
    {
        return $this->settings->account('damaged_expired_stock_expense_account_id')
            ?? $this->require('inventory_adjustment_loss_account_id', 'Damaged/Expired Stock Expense');
    }

    private function require(string $key, string $label): Account
    {
        $account = $this->settings->account($key);
        if (! $account) {
            throw new RuntimeException("Accounting is not configured: missing {$label} account ({$key}). Set it in Accounting → Settings.");
        }

        return $account;
    }
}
