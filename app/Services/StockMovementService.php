<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Enums\StockMovementDirection;
use App\Enums\StockMovementType;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class StockMovementService
{
    public function __construct(
        private StockBalanceService $balances,
        private ?ActivityLogService $logger = null,
    ) {
        $this->logger = $this->logger ?: app(ActivityLogService::class);
    }

    /**
     * Create a single stock movement and update the corresponding balance.
     *
     * Required keys: drug_id, stock_location_id, movement_type, quantity
     * Optional keys: direction, unit_cost, batch_no, expiry_date,
     *                source_type, source_id, performed_by, movement_date, notes
     */
    public function createMovement(array $data): StockMovement
    {
        $drugId     = (int) ($data['drug_id'] ?? 0);
        $locationId = (int) ($data['stock_location_id'] ?? 0);
        $quantity   = (float) ($data['quantity'] ?? 0);

        if ($drugId <= 0) {
            throw new InvalidArgumentException('drug_id is required.');
        }
        if ($locationId <= 0) {
            throw new InvalidArgumentException('stock_location_id is required.');
        }
        if ($quantity <= 0) {
            throw new InvalidArgumentException('quantity must be greater than zero.');
        }

        $type = $data['movement_type'] instanceof StockMovementType
            ? $data['movement_type']
            : StockMovementType::tryFrom((string) ($data['movement_type'] ?? ''));

        if (! $type) {
            throw new InvalidArgumentException('movement_type is invalid.');
        }

        $direction = $data['direction'] ?? null;
        if ($direction instanceof StockMovementDirection) {
            // ok
        } elseif (is_string($direction) && $direction !== '') {
            $direction = StockMovementDirection::tryFrom($direction);
        } else {
            $direction = $type->direction();
        }

        if (! $direction) {
            throw new InvalidArgumentException('direction is invalid.');
        }

        if ($direction !== $type->direction()) {
            throw new InvalidArgumentException(sprintf(
                'movement_type %s does not match direction %s.',
                $type->value,
                $direction->value,
            ));
        }

        return DB::transaction(function () use ($data, $drugId, $locationId, $quantity, $type, $direction) {
            // For OUT movements, ensure we have enough stock unless explicitly allowed.
            if ($direction === StockMovementDirection::OUT) {
                $available = $this->balances->getCurrentStock($drugId, $locationId);
                $allowNegative = (bool) ($data['allow_negative'] ?? false);
                if (! $allowNegative && $available < $quantity) {
                    throw new RuntimeException(sprintf(
                        'Insufficient stock at location %d for drug %d: available %.4f, requested %.4f',
                        $locationId,
                        $drugId,
                        $available,
                        $quantity,
                    ));
                }
            }

            // Resolve product_id from drug (Phase 2 unified ledger). Cached per call.
            $productId = $data['product_id'] ?? null;
            if ($productId === null) {
                $productId = \App\Models\Drug::query()->whereKey($drugId)->value('product_id');
            }

            $movement = StockMovement::create([
                'drug_id'           => $drugId,
                'product_id'        => $productId,
                'stock_location_id' => $locationId,
                'movement_type'     => $type,
                'direction'         => $direction,
                'quantity'          => $quantity,
                'unit_cost'         => $data['unit_cost'] ?? null,
                'batch_no'          => $data['batch_no'] ?? null,
                'expiry_date'       => $data['expiry_date'] ?? null,
                'source_type'       => $data['source_type'] ?? null,
                'source_id'         => $data['source_id'] ?? null,
                'performed_by'      => $data['performed_by'] ?? Auth::id(),
                'movement_date'     => $data['movement_date'] ?? now(),
                'notes'             => $data['notes'] ?? null,
            ]);

            // Update balance
            if ($direction === StockMovementDirection::IN) {
                $this->balances->increase($drugId, $locationId, $quantity);
            } else {
                $this->balances->decrease($drugId, $locationId, $quantity);
            }

            Log::info('stock.movement.created', [
                'movement_id'       => $movement->id,
                'drug_id'           => $drugId,
                'stock_location_id' => $locationId,
                'movement_type'     => $type->value,
                'direction'         => $direction->value,
                'quantity'          => $quantity,
                'source_type'       => $movement->source_type,
                'source_id'         => $movement->source_id,
                'performed_by'      => $movement->performed_by,
            ]);

            $action = $type->value === 'ADJUSTMENT' ? 'STOCK_ADJUSTED' : 'STOCK_' . $direction->value;
            $severity = $type->value === 'ADJUSTMENT' ? LogSeverity::WARNING : LogSeverity::INFO;
            $this->logger?->log(LogModule::STOCK, $action, [
                'severity' => $severity,
                'metadata' => [
                    'drug_id' => $drugId,
                    'location_id' => $locationId,
                    'type' => $type->value,
                    'direction' => $direction->value,
                    'quantity' => $quantity,
                    'batch_no' => $movement->batch_no,
                    'notes' => $movement->notes,
                ],
            ], $movement, sprintf('Stock %s: %s qty %.2f', $direction->value, $type->value, $quantity));

            return $movement;
        });
    }
}
