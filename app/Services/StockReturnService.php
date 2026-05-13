<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class StockReturnService
{
    public function __construct(
        private StockMovementService $movements,
    ) {}

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

        return $this->movements->createMovement([
            'drug_id'           => $data['drug_id'],
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
