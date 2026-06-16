<?php

namespace Database\Seeders;

use App\Enums\Accounting\AccountType;
use App\Enums\Accounting\PeriodStatus;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\AccountingSetting;
use App\Models\FiscalYear;
use App\Services\AccountingSettingsService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AccountingChartSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['1000', 'Assets', AccountType::ASSET, null, null, true],
            ['1100', 'Cash and Bank', AccountType::ASSET, 'CURRENT_ASSET', '1000', true],
            ['1110', 'Cash on Hand', AccountType::ASSET, 'CURRENT_ASSET', '1100', false, true],
            ['1120', 'Bank Account', AccountType::ASSET, 'CURRENT_ASSET', '1100', false, false, true],
            ['1130', 'Mobile Money Account', AccountType::ASSET, 'CURRENT_ASSET', '1100', false, false, false],
            ['1200', 'Accounts Receivable', AccountType::ASSET, 'CURRENT_ASSET', '1000', true],
            ['1210', 'Patient Receivables', AccountType::ASSET, 'CURRENT_ASSET', '1200', true],
            ['1220', 'Insurance Receivables', AccountType::ASSET, 'CURRENT_ASSET', '1200', true],
            ['1230', 'Sponsor Receivables', AccountType::ASSET, 'CURRENT_ASSET', '1200', true],
            ['1240', 'Corporate Receivables', AccountType::ASSET, 'CURRENT_ASSET', '1200', true],
            ['1300', 'Inventory', AccountType::ASSET, 'CURRENT_ASSET', '1000', true],
            ['1310', 'Pharmacy Inventory', AccountType::ASSET, 'CURRENT_ASSET', '1300', true],
            ['1320', 'Medical Consumables Inventory', AccountType::ASSET, 'CURRENT_ASSET', '1300', true],
            ['1330', 'Laboratory Reagents Inventory', AccountType::ASSET, 'CURRENT_ASSET', '1300', true],
            ['1340', 'Theatre Supplies Inventory', AccountType::ASSET, 'CURRENT_ASSET', '1300', true],
            ['1400', 'Fixed Assets', AccountType::ASSET, 'NON_CURRENT_ASSET', '1000', true],
            ['1410', 'Medical Equipment', AccountType::ASSET, 'NON_CURRENT_ASSET', '1400'],
            ['1420', 'Furniture and Fixtures', AccountType::ASSET, 'NON_CURRENT_ASSET', '1400'],
            ['1430', 'Computers and IT Equipment', AccountType::ASSET, 'NON_CURRENT_ASSET', '1400'],
            ['1440', 'Vehicles', AccountType::ASSET, 'NON_CURRENT_ASSET', '1400'],

            ['2000', 'Liabilities', AccountType::LIABILITY, null, null, true],
            ['2100', 'Accounts Payable', AccountType::LIABILITY, 'CURRENT_LIABILITY', '2000', true],
            ['2110', 'Supplier Payables', AccountType::LIABILITY, 'CURRENT_LIABILITY', '2100', true],
            ['2200', 'Patient Deposits', AccountType::LIABILITY, 'CURRENT_LIABILITY', '2000', true],
            ['2300', 'Taxes Payable', AccountType::LIABILITY, 'CURRENT_LIABILITY', '2000', true],
            ['2310', 'PAYE Payable', AccountType::LIABILITY, 'CURRENT_LIABILITY', '2300', true],
            ['2320', 'Pension / SSNIT Payable', AccountType::LIABILITY, 'CURRENT_LIABILITY', '2300', true],
            ['2400', 'Salary Payable', AccountType::LIABILITY, 'CURRENT_LIABILITY', '2000', true],
            ['2500', 'Accrued Expenses', AccountType::LIABILITY, 'CURRENT_LIABILITY', '2000', true],

            ['3000', 'Equity', AccountType::EQUITY, null, null, true],
            ['3100', 'Owner Capital', AccountType::EQUITY, null, '3000'],
            ['3200', 'Retained Earnings', AccountType::EQUITY, null, '3000'],
            ['3300', 'Current Year Earnings', AccountType::EQUITY, null, '3000'],

            ['4000', 'Revenue', AccountType::INCOME, null, null, true],
            ['4100', 'Consultation Revenue', AccountType::INCOME, 'OPERATING_REVENUE', '4000'],
            ['4200', 'Laboratory Revenue', AccountType::INCOME, 'OPERATING_REVENUE', '4000'],
            ['4300', 'Pharmacy Revenue', AccountType::INCOME, 'OPERATING_REVENUE', '4000'],
            ['4400', 'Procedure / Theatre Revenue', AccountType::INCOME, 'OPERATING_REVENUE', '4000'],
            ['4500', 'Admission Revenue', AccountType::INCOME, 'OPERATING_REVENUE', '4000'],
            ['4600', 'Emergency Revenue', AccountType::INCOME, 'OPERATING_REVENUE', '4000'],
            ['4700', 'Insurance Claim Revenue', AccountType::INCOME, 'OPERATING_REVENUE', '4000'],
            ['4800', 'Sponsor-Funded Revenue', AccountType::INCOME, 'OPERATING_REVENUE', '4000'],
            ['4900', 'Other Revenue', AccountType::INCOME, 'OTHER_INCOME', '4000'],
            ['4950', 'Discounts and Allowances', AccountType::INCOME, 'OTHER_INCOME', '4000'],
            ['4960', 'Credit Notes and Refunds', AccountType::INCOME, 'OTHER_INCOME', '4000'],
            ['4970', 'Inventory Adjustment Gain', AccountType::INCOME, 'OTHER_INCOME', '4000'],

            ['5000', 'Expenses', AccountType::EXPENSE, null, null, true],
            ['5100', 'Cost of Goods Sold', AccountType::EXPENSE, 'COST_OF_SALES', '5000'],
            ['5110', 'Pharmacy Cost of Goods Sold', AccountType::EXPENSE, 'COST_OF_SALES', '5100'],
            ['5120', 'Consumables Cost of Goods Sold', AccountType::EXPENSE, 'COST_OF_SALES', '5100'],
            ['5200', 'Medical Consumables Expense', AccountType::EXPENSE, 'OPERATING_EXPENSE', '5000'],
            ['5210', 'Inventory Adjustment Loss', AccountType::EXPENSE, 'OPERATING_EXPENSE', '5000'],
            ['5220', 'Damaged / Expired Stock Expense', AccountType::EXPENSE, 'OPERATING_EXPENSE', '5000'],
            ['5300', 'Salaries and Wages', AccountType::EXPENSE, 'OPERATING_EXPENSE', '5000'],
            ['5400', 'Rent', AccountType::EXPENSE, 'OPERATING_EXPENSE', '5000'],
            ['5500', 'Utilities', AccountType::EXPENSE, 'OPERATING_EXPENSE', '5000'],
            ['5600', 'Maintenance', AccountType::EXPENSE, 'OPERATING_EXPENSE', '5000'],
            ['5700', 'Administrative Expenses', AccountType::EXPENSE, 'ADMIN_EXPENSE', '5000'],
            ['5800', 'Bad Debt / Write-off Expense', AccountType::EXPENSE, 'OPERATING_EXPENSE', '5000'],
            ['5900', 'Bank Charges', AccountType::EXPENSE, 'FINANCE_COST', '5000'],
            ['5990', 'Rounding Difference', AccountType::EXPENSE, 'ADMIN_EXPENSE', '5000'],
        ];

        foreach ($accounts as [$code, $name, $type, $subtype]) {
            Account::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'type' => $type,
                    'subtype' => $subtype,
                    'normal_balance' => $type->normalBalance(),
                    'is_active' => true,
                ]
            );
        }

        foreach ($accounts as $definition) {
            [$code, , , , $parentCode, $control, $cash, $bank] = array_pad($definition, 8, false);
            $account = Account::where('code', $code)->first();
            $account->update([
                'parent_id' => $parentCode ? Account::where('code', $parentCode)->value('id') : null,
                'is_control_account' => (bool) $control,
                'is_cash_account' => (bool) $cash,
                'is_bank_account' => (bool) $bank,
            ]);
        }

        $this->seedCurrentFiscalCalendar();
        $this->seedSettings();
    }

    protected function seedCurrentFiscalCalendar(): void
    {
        $year = now()->year;
        $fiscalYear = FiscalYear::firstOrCreate(
            ['name' => 'FY ' . $year],
            [
                'start_date' => Carbon::create($year, 1, 1)->toDateString(),
                'end_date' => Carbon::create($year, 12, 31)->toDateString(),
                'status' => PeriodStatus::OPEN,
            ]
        );

        for ($month = 1; $month <= 12; $month++) {
            $start = Carbon::create($year, $month, 1)->startOfMonth();
            AccountingPeriod::firstOrCreate(
                [
                    'fiscal_year_id' => $fiscalYear->id,
                    'name' => $start->format('M Y'),
                ],
                [
                    'start_date' => $start->toDateString(),
                    'end_date' => $start->copy()->endOfMonth()->toDateString(),
                    'status' => PeriodStatus::OPEN,
                ]
            );
        }
    }

    protected function seedSettings(): void
    {
        app(AccountingSettingsService::class)->ensureDefaults();

        $mapping = [
            'default_cash_account_id' => '1110',
            'default_bank_account_id' => '1120',
            'default_mobile_money_account_id' => '1130',
            'patient_receivable_account_id' => '1210',
            'insurance_receivable_account_id' => '1220',
            'sponsor_receivable_account_id' => '1230',
            'corporate_receivable_account_id' => '1240',
            'supplier_payable_account_id' => '2110',
            'payroll_expense_account_id' => '5300',
            'employer_pension_expense_account_id' => '5300',
            'payroll_payable_account_id' => '2400',
            'paye_payable_account_id' => '2310',
            'pension_payable_account_id' => '2320',
            'payroll_other_deductions_payable_account_id' => '2500',
            'patient_deposit_liability_account_id' => '2200',
            'default_revenue_account_id' => '4900',
            'consultation_revenue_account_id' => '4100',
            'laboratory_revenue_account_id' => '4200',
            'pharmacy_revenue_account_id' => '4300',
            'procedure_revenue_account_id' => '4400',
            'admission_revenue_account_id' => '4500',
            'emergency_revenue_account_id' => '4600',
            'default_discount_account_id' => '4950',
            'default_credit_note_account_id' => '4960',
            'default_write_off_account_id' => '5800',
            'default_refund_account_id' => '4960',
            'inventory_account_id' => '1300',
            'pharmacy_inventory_account_id' => '1310',
            'consumables_inventory_account_id' => '1320',
            'laboratory_reagents_inventory_account_id' => '1330',
            'cost_of_goods_sold_account_id' => '5100',
            'consumables_expense_account_id' => '5200',
            'inventory_adjustment_gain_account_id' => '4970',
            'inventory_adjustment_loss_account_id' => '5210',
            'damaged_expired_stock_expense_account_id' => '5220',
            'bad_debt_expense_account_id' => '5800',
            'rounding_difference_account_id' => '5990',
            'retained_earnings_account_id' => '3200',
        ];

        foreach ($mapping as $key => $code) {
            AccountingSetting::where('key', $key)->update([
                'account_id' => Account::where('code', $code)->value('id'),
            ]);
        }

        AccountingSetting::where('key', 'allow_manual_control_account_posting')->update([
            'value' => '0',
        ]);
    }
}
