<?php

namespace App\Services;

use App\Models\StockBalance;
use Carbon\Carbon;

class InventoryReconciliationService extends AbstractReconciliationDomainService
{
    public function __construct(
        AccountingSettingsService $settings,
        protected InventoryAccountingService $inventoryAccounts,
    ) {
        parent::__construct($settings);
    }

    public function calculate(Carbon $periodStart, Carbon $periodEnd, Carbon $asOf): array
    {
        $balances = StockBalance::query()
            ->with(['product', 'location'])
            ->whereNotNull('product_id')
            ->where('quantity_on_hand', '>', 0)
            ->get();
        $groups = [];
        $details = [];

        foreach ($balances as $balance) {
            try {
                $account = $this->inventoryAccounts->inventoryAccountForProduct($balance->product);
                $key = (string) $account->id;
                $groups[$key]['account'] = $account;
                $groups[$key]['value'] = round(($groups[$key]['value'] ?? 0) + (float) $balance->total_value, 2);
                $groups[$key]['count'] = ($groups[$key]['count'] ?? 0) + 1;
                $details[] = [
                    'stock_balance_id' => $balance->id,
                    'product_id' => $balance->product_id,
                    'product' => $balance->product?->name,
                    'location' => $balance->location?->name,
                    'account_id' => $account->id,
                    'quantity' => (float) $balance->quantity_on_hand,
                    'average_cost' => (float) $balance->average_cost,
                    'value' => (float) $balance->total_value,
                ];
            } catch (\Throwable $exception) {
                $groups['unmapped']['value'] = round(($groups['unmapped']['value'] ?? 0) + (float) $balance->total_value, 2);
                $groups['unmapped']['count'] = ($groups['unmapped']['count'] ?? 0) + 1;
                $groups['unmapped']['error'] = $exception->getMessage();
            }
        }

        $items = [];
        $accounts = collect();
        $glSnapshot = [];
        foreach ($groups as $key => $group) {
            $account = $group['account'] ?? null;
            if ($account) {
                $accounts->push($account);
            }
            $source = (float) $group['value'];
            $gl = $this->glBalance($account, $asOf);
            $glSnapshot[$key] = ['account_id' => $account?->id, 'balance' => $gl];
            $items[] = $this->item(
                'inventory_control',
                $account?->id,
                $account?->code ?? 'UNMAPPED',
                $account?->display_name ?? 'Unmapped inventory valuation',
                $account,
                $source,
                $gl,
                ! $account ? 'mapping_issue' : (abs($source - $gl) < 0.01 ? 'balanced' : 'unknown_difference'),
                ['stock_balance_count' => $group['count'], 'mapping_error' => $group['error'] ?? null],
            );
        }
        foreach ($details as $detail) {
            $account = $accounts->firstWhere('id', $detail['account_id']);
            $items[] = $this->item(
                StockBalance::class,
                $detail['stock_balance_id'],
                $detail['product'] ?: 'Stock balance #'.$detail['stock_balance_id'],
                trim(($detail['location'] ?: 'Unknown location').' / '.($detail['product'] ?: 'Unknown product')),
                $account,
                (float) $detail['value'],
                (float) $detail['value'],
                'balanced',
                $detail + [
                    'comparison_basis' => 'Valuation attributed to the mapped inventory control account; the control summary row holds the authoritative GL comparison.',
                ],
            );
        }
        $items = array_merge($items, $this->manualJournalItems($accounts->unique('id'), $asOf));
        $subledger = round((float) $balances->sum('total_value'), 2);
        $gl = round((float) collect($glSnapshot)->sum('balance'), 2);

        return $this->result(
            'available',
            $subledger,
            $gl,
            $items,
            ['record_count' => $balances->count(), 'balances' => $details],
            ['accounts' => $glSnapshot],
        );
    }
}
