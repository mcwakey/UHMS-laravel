<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockAdjustmentService
{
    public function __construct(
        private StockMovementService $movements,
    ) {}

    /**
     * Record several adjustments at once. Shared keys: stock_location_id,
     * reason, notes. Per-row keys (items[]): drug_id, type, quantity,
     * unit_cost, batch_no, expiry_date. Blank rows are skipped. The whole
     * batch is atomic — any failure rolls every line back.
     *
     * @return StockMovement[]
     */
    public function adjustBatch(array $data): array
    {
        $rows = collect($data['items'] ?? [])
            ->filter(fn ($row) => ! empty($row['drug_id']) && (float) ($row['quantity'] ?? 0) > 0)
            ->values();

        if ($rows->isEmpty()) {
            throw new InvalidArgumentException('Add at least one line to adjust.');
        }

        return DB::transaction(function () use ($rows, $data) {
            $movements = [];
            foreach ($rows as $row) {
                $movements[] = $this->adjust([
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
     * Record a single stock adjustment (IN, OUT, DAMAGED, or EXPIRED).
     *
     * Required: drug_id, stock_location_id, type, quantity, reason
     * Optional: notes, batch_no, expiry_date, unit_cost, allow_negative
     */
    public function adjust(array $data): StockMovement
    {
        $typeKey = (string) ($data['type'] ?? '');
        $type = match ($typeKey) {
            'in', 'adjustment_in'  => StockMovementType::ADJUSTMENT_IN,
            'out', 'adjustment_out' => StockMovementType::ADJUSTMENT_OUT,
            'damaged'              => StockMovementType::DAMAGED,
            'expired'              => StockMovementType::EXPIRED,
            default                => null,
        };

        if (! $type) {
            throw new InvalidArgumentException('Adjustment type is invalid.');
        }

        $reason = trim((string) ($data['reason'] ?? ''));
        if ($reason === '') {
            throw new InvalidArgumentException('A reason is required for stock adjustments.');
        }

        $notes = $data['notes'] ?? null;
        $combinedNotes = $reason . ($notes ? ' — ' . $notes : '');

        return $this->movements->createMovement([
            'drug_id'           => $data['drug_id'],
            'stock_location_id' => $data['stock_location_id'],
            'movement_type'     => $type,
            'quantity'          => $data['quantity'],
            'unit_cost'         => $data['unit_cost'] ?? null,
            'batch_no'          => $data['batch_no'] ?? null,
            'expiry_date'       => $data['expiry_date'] ?? null,
            'performed_by'      => Auth::id(),
            'allow_negative'    => (bool) ($data['allow_negative'] ?? false),
            'notes'             => $combinedNotes,
        ]);
    }
}
