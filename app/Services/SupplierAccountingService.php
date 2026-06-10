<?php

namespace App\Services;

use App\Enums\ProductType;
use App\Models\Account;
use App\Models\Product;
use RuntimeException;

/**
 * Resolves the chart-of-accounts entries used by supplier-side (AP) posting.
 * Mirrors ReceivableAccountingService but for the payable side.
 *
 * Inventory account priority (per spec §7): product type → accounting settings
 * category inventory account → default inventory account.
 */
class SupplierAccountingService
{
    public function __construct(protected AccountingSettingsService $settings) {}

    public function supplierPayableAccount(): Account
    {
        return $this->require('supplier_payable_account_id', 'Supplier Payables');
    }

    /**
     * Inventory account for a received product, mapped by product type.
     */
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

        // Category-specific account, else fall back to the default inventory control account.
        return $this->settings->account($key)
            ?? $this->require('inventory_account_id', 'Inventory control');
    }

    public function paymentAccount(string $method): Account
    {
        $key = match ($method) {
            'bank' => 'default_bank_account_id',
            'mobile_money' => 'default_mobile_money_account_id',
            default => 'default_cash_account_id',
        };

        $label = match ($method) {
            'bank' => 'Bank',
            'mobile_money' => 'Mobile Money',
            default => 'Cash',
        };

        return $this->require($key, $label);
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
