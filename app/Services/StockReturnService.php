<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Drug;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockReturnService
{
    public function __construct(
        private ProductStockMovementService $movements,
    ) {}

    /**
     * Record several stock returns at once. Shared keys: stock_location_id,
     * reason, notes. Per-row keys (items[]): drug_id, type (in|out),
     * quantity, unit_cost, batch_no, expiry_date. Blank rows are skipped and
     * the whole batch is atomic.
     *
     * @return StockMovement[]
     */
    public function recordBatch(array $data): array
    {
        $rows = collect($data['items'] ?? [])
            ->filter(fn ($row) => ! empty($row['drug_id']) && (float) ($row['quantity'] ?? 0) > 0)
            ->values();

        if ($rows->isEmpty()) {
            throw new InvalidArgumentException('Add at least one line to return.');
        }

        return DB::transaction(function () use ($rows, $data) {
            $movements = [];
            foreach ($rows as $row) {
                $movements[] = $this->record([
                    'drug_id'           => $row['drug_id'],
                    'stock_location_id' => $data['stock_location_id'],
                    'type'              => $row['type'] ?? 'out',
                    'quantity'          => $row['quantity'],
                    'reason'            => $data['reason'] ?? '',
                    'notes'             => $data['notes'] ?? null,
                    'unit_cost'         => $row['unit_cost'] ?? null,
                    'batch_no'          => $row['batch_no'] ?? null,
                    'expiry_date'       => $row['expiry_date'] ?? null,
                ]);
            }

            return $movements;
        });
    }

    /**
     * Record a stock return movement.
     *
     * Required: drug_id, stock_location_id, type (in|out), quantity, reason
     * Optional: source_type, source_id, batch_no, expiry_date, unit_cost, notes
     */
    public function record(array $data): StockMovement
    {
        $typeKey = (string) ($data['type'] ?? '');
        $type = match ($typeKey) {
            'in', 'return_in'   => StockMovementType::RETURN_IN,
            'out', 'return_out' => StockMovementType::RETURN_OUT,
            default             => null,
        };

        if (! $type) {
            throw new InvalidArgumentException('Return type is invalid (expected in/out).');
        }

        $reason = trim((string) ($data['reason'] ?? ''));
        if ($reason === '') {
            throw new InvalidArgumentException('A reason is required for stock returns.');
        }

        $notes = $data['notes'] ?? null;
        $combinedNotes = $reason . ($notes ? ' — ' . $notes : '');

        $drugId    = (int) ($data['drug_id'] ?? 0);
        $productId = $data['product_id'] ?? ($drugId > 0 ? Drug::whereKey($drugId)->value('product_id') : null);
        if (! $productId) {
            throw new InvalidArgumentException('This drug is not linked to a stock product, so it cannot be returned.');
        }

        return $this->movements->createMovement([
            'drug_id'           => $drugId ?: null,
            'product_id'        => $productId,
            'stock_location_id' => $data['stock_location_id'],
            'movement_type'     => $type,
            'quantity'          => $data['quantity'],
            'unit_cost'         => $data['unit_cost'] ?? null,
            'batch_no'          => $data['batch_no'] ?? null,
            'expiry_date'       => $data['expiry_date'] ?? null,
            'source_type'       => $data['source_type'] ?? null,
            'source_id'         => $data['source_id'] ?? null,
            'performed_by'      => Auth::id(),
            'allow_negative'    => (bool) ($data['allow_negative'] ?? false),
            'notes'             => $combinedNotes,
        ]);
    }
}
