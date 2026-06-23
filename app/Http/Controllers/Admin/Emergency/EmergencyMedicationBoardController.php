<?php

namespace App\Http\Controllers\Admin\Emergency;

use App\Http\Controllers\Controller;
use App\Models\StockLocation;
use App\Services\EmergencyMedicationBoardService;

class EmergencyMedicationBoardController extends Controller
{
    public function __construct(private EmergencyMedicationBoardService $board) {}

    public function index()
    {
        $payload = $this->board->board();
        $stockLocations = StockLocation::active()
            ->whereIn('type', ['emergency', 'ward'])
            ->orderBy('name')
            ->get();

        return view('medication-administration.emergency-board', array_merge($payload, [
            'stockLocations' => $stockLocations,
        ]));
    }
}
