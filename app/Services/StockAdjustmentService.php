<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class StockAdjustmentService
{
    public function __construct(
        private StockMovementService $movements,
    ) {}

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
