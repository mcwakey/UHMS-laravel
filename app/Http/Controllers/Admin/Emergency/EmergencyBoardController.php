<?php

namespace App\Http\Controllers\Admin\Emergency;

use App\Http\Controllers\Controller;
use App\Models\EmergencyBay;
use App\Services\EmergencyBoardService;
use Illuminate\Http\Request;

class EmergencyBoardController extends Controller
{
    public function __construct(private EmergencyBoardService $board) {}

    public function index(Request $request)
    {
        $payload = $this->board->board($request->only(['search', 'status', 'triage_category', 'bay_id']));

        return view('emergency.board', array_merge($payload, [
            'filters' => $request->only(['search', 'status', 'triage_category', 'bay_id']),
            'bays' => EmergencyBay::active()->orderBy('name')->get(),
        ]));
    }
}
