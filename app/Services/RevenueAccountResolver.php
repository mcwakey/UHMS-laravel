<?php

namespace App\Services;

use App\Enums\DepartmentType;
use App\Models\Account;
use App\Models\InvoiceItem;
use RuntimeException;

class RevenueAccountResolver
{
    public function __construct(protected AccountingSettingsService $settings) {}

    public function accountForInvoiceItem(InvoiceItem $item): Account
    {
        $settingKey = $this->settingKeyFor($item);

        return $this->requiredAccount($settingKey);
    }

    protected function settingKeyFor(InvoiceItem $item): string
    {
        $sourceType = strtolower((string) $item->source_type);
        $category = strtolower((string) ($item->serviceCatalog?->category ?? ''));
        $departmentType = $item->department?->type instanceof DepartmentType
            ? $item->department->type->value
            : strtolower((string) $item->department?->type);

        $haystack = implode(' ', array_filter([
            $sourceType,
            $category,
            $departmentType,
            strtolower((string) $item->description),
        ]));

        if ($item->product_id || str_contains($haystack, 'pharmacy') || str_contains($haystack, 'drug')) {
            return 'pharmacy_revenue_account_id';
        }

        if (str_contains($haystack, 'emergency')) {
            return 'emergency_revenue_account_id';
        }

        if (str_contains($haystack, 'admission') || str_contains($haystack, 'ward') || str_contains($haystack, 'bed_charge')) {
            return 'admission_revenue_account_id';
        }

        if (str_contains($haystack, 'investigation') || str_contains($haystack, 'lab') || str_contains($haystack, 'scan') || str_contains($haystack, 'xray') || str_contains($haystack, 'radiology')) {
            return 'laboratory_revenue_account_id';
        }

        if (str_contains($haystack, 'procedure') || str_contains($haystack, 'theatre') || str_contains($haystack, 'surgery')) {
            return 'procedure_revenue_account_id';
        }

        if (str_contains($haystack, 'consultation') || str_contains($haystack, 'visit_service')) {
            return 'consultation_revenue_account_id';
        }

        return 'default_revenue_account_id';
    }

    protected function requiredAccount(string $key): Account
    {
        $account = $this->settings->account($key);

        if (! $account) {
            throw new RuntimeException("Accounting setting {$key} is not configured.");
        }

        if (! $account->is_active) {
            throw new RuntimeException("Accounting account {$account->display_name} is inactive.");
        }

        return $account;
    }
}
