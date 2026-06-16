<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountingSetting;
use App\Models\User;
use Illuminate\Support\Collection;

class AccountingSettingsService
{
    public function definitions(): array
    {
        return [
            'default_cash_account_id' => 'Default cash account',
            'default_bank_account_id' => 'Default bank account',
            'default_mobile_money_account_id' => 'Default mobile money account',
            'patient_receivable_account_id' => 'Patient receivables',
            'insurance_receivable_account_id' => 'Insurance receivables',
            'sponsor_receivable_account_id' => 'Sponsor receivables',
            'corporate_receivable_account_id' => 'Corporate receivables',
            'supplier_payable_account_id' => 'Supplier payables',
            'payroll_expense_account_id' => 'Payroll expense',
            'employer_pension_expense_account_id' => 'Employer pension expense',
            'payroll_payable_account_id' => 'Payroll payable',
            'paye_payable_account_id' => 'PAYE payable',
            'pension_payable_account_id' => 'Pension / SSNIT payable',
            'payroll_other_deductions_payable_account_id' => 'Other payroll deductions payable',
            'patient_deposit_liability_account_id' => 'Patient deposit liability',
            'default_revenue_account_id' => 'Default revenue account',
            'consultation_revenue_account_id' => 'Consultation revenue',
            'laboratory_revenue_account_id' => 'Laboratory revenue',
            'pharmacy_revenue_account_id' => 'Pharmacy revenue',
            'procedure_revenue_account_id' => 'Procedure/theatre revenue',
            'admission_revenue_account_id' => 'Admission revenue',
            'emergency_revenue_account_id' => 'Emergency revenue',
            'default_discount_account_id' => 'Discount contra revenue',
            'default_credit_note_account_id' => 'Credit note account',
            'default_write_off_account_id' => 'Write-off account',
            'default_refund_account_id' => 'Refund account',
            'inventory_account_id' => 'Inventory control',
            'pharmacy_inventory_account_id' => 'Pharmacy inventory',
            'consumables_inventory_account_id' => 'Consumables inventory',
            'laboratory_reagents_inventory_account_id' => 'Laboratory reagents inventory',
            'cost_of_goods_sold_account_id' => 'Cost of goods sold',
            'consumables_expense_account_id' => 'Consumables expense',
            'inventory_adjustment_gain_account_id' => 'Inventory adjustment gain',
            'inventory_adjustment_loss_account_id' => 'Inventory adjustment loss',
            'damaged_expired_stock_expense_account_id' => 'Damaged/expired stock expense',
            'bad_debt_expense_account_id' => 'Bad debt/write-off expense',
            'rounding_difference_account_id' => 'Rounding difference account',
            'retained_earnings_account_id' => 'Retained earnings',
            'allow_manual_control_account_posting' => 'Allow manual posting to control accounts',
        ];
    }

    public function all(): Collection
    {
        $existing = AccountingSetting::with('account')->get()->keyBy('key');

        return collect($this->definitions())->map(function (string $description, string $key) use ($existing) {
            return $existing->get($key) ?? new AccountingSetting([
                'key' => $key,
                'description' => $description,
            ]);
        })->values();
    }

    public function ensureDefaults(): void
    {
        foreach ($this->definitions() as $key => $description) {
            AccountingSetting::firstOrCreate(['key' => $key], ['description' => $description]);
        }
    }

    public function update(array $settings, ?User $user = null): void
    {
        foreach ($this->definitions() as $key => $description) {
            if (! array_key_exists($key, $settings)) {
                continue;
            }

            $payload = [
                'description' => $description,
                'updated_by' => $user?->id,
            ];

            if (str_ends_with($key, '_account_id')) {
                $payload['account_id'] = $settings[$key] ?: null;
                $payload['value'] = null;
            } else {
                $payload['account_id'] = null;
                $payload['value'] = is_bool($settings[$key]) ? ($settings[$key] ? '1' : '0') : (string) $settings[$key];
            }

            AccountingSetting::updateOrCreate(['key' => $key], $payload);
        }
    }

    public function account(string $key): ?Account
    {
        $setting = AccountingSetting::with('account')->where('key', $key)->first();

        return $setting?->account;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $setting = AccountingSetting::where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return in_array((string) $setting->value, ['1', 'true', 'yes', 'on'], true);
    }
}
