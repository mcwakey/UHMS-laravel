<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\StockLocation;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Product-native stock operations for the three manual movement screens:
 * adjustments, returns (managed location -> Main Store) and transfers
 * (Main Store -> managed location).
 *
 * These all write to the canonical product-keyed ledger via
 * {@see ProductStockMovementService}, so on-hand checks and balances match the
 * Stock Balances page, pharmacy dispensing and PO receiving. (The legacy
 * drug-keyed path read a different balance row and reported "available 0".)
 */
class StockOperationService
{
    public function __construct(
        private ProductStockMovementService $movements,
    ) {}

    /**
     * Resolve the Main Store location or fail loudly.
     */
    public function mainStore(): StockLocation
    {
        $main = StockLocation::query()->where('is_main', true)->where('is_active', true)->first();
        if (! $main) {
            throw new RuntimeException('No active Main Store location is configured.');
        }

        return $main;
    }

    /**
     * Multi-line adjustment at a single location.
     *
     * Shared: stock_location_id, reason, notes.
     * Per row (items[]): product_id, type (in|out|damaged|expired), quantity,
     *                    unit_cost, batch_no, expiry_date.
     *
     * @return StockMovement[]
     */
    public function adjustBatch(array $data): array
    {
        $rows = $this->validRows($data, 'adjust');
        $locationId = (int) ($data['stock_location_id'] ?? 0);
        if ($locationId <= 0) {
            throw new InvalidArgumentException('Select a location.');
        }
        $notes = $this->combinedNotes($data);

        return DB::transaction(function () use ($rows, $locationId, $notes) {
            $out = [];
            foreach ($rows as $row) {
                $type = match ((string) ($row['type'] ?? 'out')) {
                    'in'      => StockMovementType::ADJUSTMENT_IN,
                    'damaged' => StockMovementType::DAMAGED,
                    'expired' => StockMovementType::EXPIRED,
                    default   => StockMovementType::ADJUSTMENT_OUT,
                };
                $out[] = $this->movements->createMovement([
                    'product_id'        => (int) $row['product_id'],
                    'stock_location_id' => $locationId,
                    'movement_type'     => $type,
                    'quantity'          => (float) $row['quantity'],
                    'unit_cost'         => $row['unit_cost'] ?? null,
                    'batch_no'          => $row['batch_no'] ?? null,
                    'expiry_date'       => $row['expiry_date'] ?? null,
                    'performed_by'      => Auth::id(),
                    'notes'             => $notes,
                ]);
            }

            return $out;
        });
    }

    /**
     * Move stock from a managed (non-main) location back to the Main Store.
     * One OUT at the source and one IN at Main Store per line.
     *
     * Shared: source_location_id, reason, notes.
     * Per row (items[]): product_id, quantity, unit_cost, batch_no, expiry_date.
     *
     * @return StockMovement[]
     */
    public function returnToMainBatch(array $data): array
    {
        $rows = $this->validRows($data, 'return');
        $source = $this->requireManagedLocation((int) ($data['source_location_id'] ?? 0), 'Select the location the stock is returned from.');
        $main = $this->mainStore();
        $notes = $this->combinedNotes($data);

        return DB::transaction(function () use ($rows, $source, $main, $notes) {
            $out = [];
            foreach ($rows as $row) {
                $line = $notes . ' (Return ' . $source->name . ' → ' . $main->name . ')';
                $out[] = $this->movements->createMovement([
                    'product_id'        => (int) $row['product_id'],
                    'stock_location_id' => $source->id,
                    'movement_type'     => StockMovementType::RETURN_OUT,
                    'quantity'          => (float) $row['quantity'],
                    'unit_cost'         => $row['unit_cost'] ?? null,
                    'batch_no'          => $row['batch_no'] ?? null,
                    'expiry_date'       => $row['expiry_date'] ?? null,
                    'performed_by'      => Auth::id(),
                    'notes'             => $line,
                ]);
                $out[] = $this->movements->createMovement([
                    'product_id'        => (int) $row['product_id'],
                    'stock_location_id' => $main->id,
                    'movement_type'     => StockMovementType::RETURN_IN,
                    'quantity'          => (float) $row['quantity'],
                    'unit_cost'         => $row['unit_cost'] ?? null,
                    'batch_no'          => $row['batch_no'] ?? null,
                    'expiry_date'       => $row['expiry_date'] ?? null,
                    'performed_by'      => Auth::id(),
                    'notes'             => $line,
                ]);
            }

            return $out;
        });
    }

    /**
     * Move stock from the Main Store to a managed (non-main) location.
     * One OUT at Main Store and one IN at the destination per line.
     *
     * Shared: dest_location_id, reason, notes.
     * Per row (items[]): product_id, quantity, unit_cost, batch_no, expiry_date.
     *
     * @return StockMovement[]
     */
    public function transferFromMainBatch(array $data): array
    {
        $rows = $this->validRows($data, 'transfer');
        $dest = $this->requireManagedLocation((int) ($data['dest_location_id'] ?? 0), 'Select the destination location.');
        $main = $this->mainStore();
        if ($dest->id === $main->id) {
            throw new InvalidArgumentException('Destination must be different from the Main Store.');
        }
        $notes = $this->combinedNotes($data);

        return DB::transaction(function () use ($rows, $dest, $main, $notes) {
            $out = [];
            foreach ($rows as $row) {
                $line = $notes . ' (Transfer ' . $main->name . ' → ' . $dest->name . ')';
                $out[] = $this->movements->createMovement([
                    'product_id'        => (int) $row['product_id'],
                    'stock_location_id' => $main->id,
                    'movement_type'     => StockMovementType::TRANSFER_OUT,
                    'quantity'          => (float) $row['quantity'],
                    'unit_cost'         => $row['unit_cost'] ?? null,
                    'batch_no'          => $row['batch_no'] ?? null,
                    'expiry_date'       => $row['expiry_date'] ?? null,
                    'performed_by'      => Auth::id(),
                    'notes'             => $line,
                ]);
                $out[] = $this->movements->createMovement([
                    'product_id'        => (int) $row['product_id'],
                    'stock_location_id' => $dest->id,
                    'movement_type'     => StockMovementType::TRANSFER_IN,
                    'quantity'          => (float) $row['quantity'],
                    'unit_cost'         => $row['unit_cost'] ?? null,
                    'batch_no'          => $row['batch_no'] ?? null,
                    'expiry_date'       => $row['expiry_date'] ?? null,
                    'performed_by'      => Auth::id(),
                    'notes'             => $line,
                ]);
            }

            return $out;
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function validRows(array $data, string $context): array
    {
        $rows = collect($data['items'] ?? [])
            ->filter(fn ($row) => ! empty($row['product_id']) && (float) ($row['quantity'] ?? 0) > 0)
            ->values()
            ->all();

        if (empty($rows)) {
            throw new InvalidArgumentException('Add at least one line with a product and quantity.');
        }

        if (trim((string) ($data['reason'] ?? '')) === '') {
            throw new InvalidArgumentException('A reason is required.');
        }

        return $rows;
    }

    private function requireManagedLocation(int $id, string $message): StockLocation
    {
        $location = StockLocation::query()->where('id', $id)->where('is_active', true)->where('is_main', false)->first();
        if (! $location) {
            throw new InvalidArgumentException($message);
        }

        return $location;
    }

    private function combinedNotes(array $data): string
    {
        $reason = trim((string) ($data['reason'] ?? ''));
        $notes = trim((string) ($data['notes'] ?? ''));

        return $reason . ($notes !== '' ? ' — ' . $notes : '');
    }
}
