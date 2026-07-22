<?php

namespace App\Services;

use App\Services\LegacyMigration\Foundation\Runtime\OperationalEffectGate;
use App\Services\LegacyMigration\Foundation\Runtime\ProhibitedSubsystem;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Enums\StockMovementType;
use App\Models\JournalEntry;
use App\Models\PurchaseReturnItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Posts inventory accounting from costed stock movements (Phase 6).
 *
 *   Pharmacy dispense            : Dr COGS                / Cr Inventory
 *   Consumable usage             : Dr Consumables Expense / Cr Inventory
 *   Adjustment increase          : Dr Inventory           / Cr Adjustment Gain
 *   Adjustment decrease          : Dr Adjustment Loss     / Cr Inventory
 *   Damaged / expired            : Dr Damaged/Expired Exp / Cr Inventory
 *   Transfer (same inv account)  : no journal (marked not_applicable)
 *
 * Goods receipt + purchase return are posted in Phase 5 (on the GRN / return),
 * so the matching stock movements are marked not_applicable and never re-posted.
 * Every movement posts at most once (guarded on journal_entry_id / status).
 */
class InventoryAccountingPostingService
{
    public const POSTED = BillingAccountingPostingService::STATUS_POSTED;
    public const FAILED = BillingAccountingPostingService::STATUS_FAILED;
    public const REVERSED = BillingAccountingPostingService::STATUS_REVERSED;
    public const NOT_APPLICABLE = 'not_applicable';

    public function __construct(
        protected JournalEntryService $journal,
        protected InventoryAccountingService $accounts,
    ) {}

    public function postForMovement(StockMovement $movement): ?JournalEntry
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::AccountingPosting);
        if ($movement->journal_entry_id
            || in_array((string) $movement->accounting_status, [self::POSTED, self::REVERSED, self::NOT_APPLICABLE], true)) {
            return $movement->journal_entry_id ? $movement->journalEntry : null;
        }

        $movement->loadMissing('product');

        return match ($movement->movement_type) {
            StockMovementType::PHARMACY_DISPENSED => $this->postOutCost($movement, fn () => $this->accounts->cogsAccount(), 'Cost of goods sold', 'ACCOUNTING_POSTED_FOR_STOCK_DISPENSE'),

            StockMovementType::INVESTIGATION_CONSUMED,
            StockMovementType::PROCEDURE_CONSUMED,
            StockMovementType::WARD_CONSUMED,
            StockMovementType::EMERGENCY_ADMINISTRATION_OUT => $this->postOutCost($movement, fn () => $this->accounts->consumablesExpenseAccount(), 'Consumables expense', 'ACCOUNTING_POSTED_FOR_CONSUMABLE_USAGE'),

            StockMovementType::DAMAGED => $this->postOutCost($movement, fn () => $this->accounts->damagedExpiredAccount(), 'Damaged stock written off', 'ACCOUNTING_POSTED_FOR_DAMAGED_STOCK'),
            StockMovementType::EXPIRED => $this->postOutCost($movement, fn () => $this->accounts->damagedExpiredAccount(), 'Expired stock written off', 'ACCOUNTING_POSTED_FOR_EXPIRED_STOCK'),

            StockMovementType::ADJUSTMENT_IN => $this->postAdjustment($movement, true),
            StockMovementType::ADJUSTMENT_OUT => $this->postAdjustment($movement, false),

            StockMovementType::RETURN_OUT => $this->isPhase5Return($movement) ? $this->markNotApplicable($movement) : $this->markNotApplicable($movement),

            // Transfers, internal returns-in, opening stock and goods receipts (Phase 5)
            // require no Phase-6 journal under the current account mapping.
            StockMovementType::TRANSFER_IN,
            StockMovementType::TRANSFER_OUT,
            StockMovementType::RETURN_IN,
            StockMovementType::OPENING_STOCK,
            StockMovementType::PURCHASE_RECEIVED,
            StockMovementType::REVERSAL_IN,
            StockMovementType::REVERSAL_OUT => $this->markNotApplicable($movement),

            default => null,
        };
    }

    /** Dr <expense/COGS account> / Cr Inventory for a costed OUT movement. */
    private function postOutCost(StockMovement $movement, callable $debitAccountResolver, string $desc, string $event): ?JournalEntry
    {
        $total = round((float) $movement->total_cost, 2);
        if ($total <= 0) {
            // Legacy stock with no cost basis — nothing to post.
            return $this->markNotApplicable($movement);
        }

        return $this->post($movement, $event, function () use ($movement, $debitAccountResolver, $total, $desc) {
            $debit = $debitAccountResolver();
            $inventory = $this->accounts->inventoryAccountForProduct($movement->product);

            return [
                $this->line($debit->id, $desc, $total, 0, $movement),
                $this->line($inventory->id, 'Inventory reduced', 0, $total, $movement),
            ];
        });
    }

    /** ADJUSTMENT_IN: Dr Inventory / Cr Gain. ADJUSTMENT_OUT: Dr Loss / Cr Inventory. */
    private function postAdjustment(StockMovement $movement, bool $isIncrease): ?JournalEntry
    {
        $total = round((float) $movement->total_cost, 2);
        if ($total <= 0) {
            return $this->markNotApplicable($movement);
        }

        return $this->post($movement, 'ACCOUNTING_POSTED_FOR_STOCK_ADJUSTMENT', function () use ($movement, $isIncrease, $total) {
            $inventory = $this->accounts->inventoryAccountForProduct($movement->product);
            if ($isIncrease) {
                return [
                    $this->line($inventory->id, 'Inventory adjustment increase', $total, 0, $movement),
                    $this->line($this->accounts->adjustmentGainAccount()->id, 'Inventory adjustment gain', 0, $total, $movement),
                ];
            }

            return [
                $this->line($this->accounts->adjustmentLossAccount()->id, 'Inventory adjustment loss', $total, 0, $movement),
                $this->line($inventory->id, 'Inventory adjustment decrease', 0, $total, $movement),
            ];
        });
    }

    private function post(StockMovement $movement, string $event, callable $linesResolver): ?JournalEntry
    {
        try {
            $entry = DB::transaction(function () use ($movement, $linesResolver) {
                $draft = $this->journal->createDraft([
                    'entry_date' => optional($movement->movement_date)->toDateString() ?? now()->toDateString(),
                    'reference_number' => 'STK-'.$movement->id,
                    'reference_type' => StockMovement::class,
                    'reference_id' => $movement->id,
                    'source_module' => 'INVENTORY',
                    'allow_control_accounts' => true,
                    'description' => $movement->movement_type->label().' — stock movement #'.$movement->id,
                    'lines' => $linesResolver(),
                ]);

                return $this->journal->post($draft, $this->user());
            });

            $movement->forceFill([
                'journal_entry_id' => $entry->id,
                'accounting_status' => self::POSTED,
                'accounting_posted_at' => now(),
                'accounting_error' => null,
            ])->save();

            $this->log($event, $movement, ['journal_entry_id' => $entry->id, 'total_cost' => (float) $movement->total_cost]);

            return $entry;
        } catch (Throwable $e) {
            $movement->forceFill([
                'accounting_status' => self::FAILED,
                'accounting_error' => mb_substr($e->getMessage(), 0, 2000),
            ])->save();

            $this->log('ACCOUNTING_POSTING_FAILED', $movement, [
                'severity' => LogSeverity::WARNING,
                'stock_movement_id' => $movement->id,
                'error' => mb_substr($e->getMessage(), 0, 500),
            ]);

            return null;
        }
    }

    /** Reverse a posted movement's journal entry (e.g. when stock is reversed). */
    public function reverseForMovement(StockMovement $movement, string $reason, User $user): ?JournalEntry
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::AccountingPosting);
        if (! $movement->journal_entry_id || $movement->reversal_journal_entry_id) {
            return null;
        }

        $original = JournalEntry::find($movement->journal_entry_id);
        if (! $original) {
            return null;
        }

        $reversal = $this->journal->reverse($original, $reason, $user);
        $movement->forceFill([
            'reversal_journal_entry_id' => $reversal->id,
            'accounting_status' => self::REVERSED,
        ])->save();

        $this->log('ACCOUNTING_REVERSAL_CREATED', $movement, ['journal_entry_id' => $reversal->id, 'reason' => $reason]);

        return $reversal;
    }

    private function markNotApplicable(StockMovement $movement): null
    {
        $movement->forceFill(['accounting_status' => self::NOT_APPLICABLE])->save();

        return null;
    }

    private function isPhase5Return(StockMovement $movement): bool
    {
        return $movement->source_type === PurchaseReturnItem::class;
    }

    private function line(int $accountId, string $description, float $debit, float $credit, StockMovement $movement): array
    {
        return [
            'account_id' => $accountId,
            'description' => $description,
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
            'reference_type' => StockMovement::class,
            'reference_id' => $movement->id,
        ];
    }

    private function user(): User
    {
        return auth()->user() ?? User::query()->firstOrFail();
    }

    private function log(string $action, StockMovement $movement, array $context = []): void
    {
        try {
            app(ActivityLogService::class)->log(
                LogModule::ACCOUNTING,
                $action,
                array_merge([
                    'severity' => LogSeverity::INFO,
                    'product_id' => $movement->product_id,
                    'stock_location_id' => $movement->stock_location_id,
                    'stock_movement_id' => $movement->id,
                    'unit_cost' => (float) $movement->unit_cost,
                    'total_cost' => (float) $movement->total_cost,
                    'valuation_method' => $movement->valuation_method,
                ], $context),
                $movement,
                str_replace('_', ' ', ucfirst(strtolower($action))),
            );
        } catch (Throwable) {
            // Accounting posting must never break the stock movement.
        }
    }
}
