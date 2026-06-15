<?php

namespace App\Services;

use App\Models\AccountingPostingAttempt;
use App\Models\BankReconciliationAdjustment;
use App\Models\FinancialEntry;
use Illuminate\Support\Facades\Route;

class AccountingPostingSourceLinkService
{
    public function link(AccountingPostingAttempt $attempt): ?array
    {
        $type = strtolower($attempt->source_type);

        if ($this->matches($type, 'financialentry', 'financial_entry')) {
            $entry = FinancialEntry::query()->find($attempt->source_id);
            if (! $entry) {
                return null;
            }
            $route = $entry->type?->value === 'expense' ? 'admin.accounts.expenses.index' : 'admin.accounts.income.index';
            return Route::has($route) ? ['label' => $entry->entry_number, 'url' => route($route, ['search' => $entry->entry_number])] : null;
        }

        if ($this->matches($type, 'bankreconciliationadjustment', 'bank_reconciliation_adjustment')) {
            $adjustment = BankReconciliationAdjustment::query()->find($attempt->source_id);
            $route = 'admin.accounting.bank.reconciliations.show';
            return $adjustment && Route::has($route)
                ? ['label' => 'BANK-ADJ-'.$adjustment->id, 'url' => route($route, $adjustment->bank_reconciliation_id)]
                : null;
        }

        $routes = [
            'invoice' => 'admin.billing.invoices.show',
            'payment' => 'admin.billing.payments.receipt',
            'creditnote' => 'admin.billing.credit-notes.index',
            'credit_note' => 'admin.billing.credit-notes.index',
            'supplierpayment' => 'admin.accounts-payable.payments',
            'supplier_payment' => 'admin.accounts-payable.payments',
            'supplierpayable' => 'admin.accounts-payable.payables',
            'supplier_payable' => 'admin.accounts-payable.payables',
        ];
        foreach ($routes as $needle => $route) {
            if (($type === $needle || str_ends_with($type, '.'.$needle)) && Route::has($route)) {
                $parameters = str_ends_with($route, '.show') || str_ends_with($route, '.receipt')
                    ? [$attempt->source_id]
                    : ['source_id' => $attempt->source_id];
                return ['label' => class_basename(str_replace('.', '\\', $attempt->source_type)).' #'.$attempt->source_id, 'url' => route($route, $parameters)];
            }
        }

        return null;
    }

    protected function matches(string $type, string $classSuffix, string $alias): bool
    {
        return $type === $alias || $type === $classSuffix || str_ends_with($type, $classSuffix);
    }
}
