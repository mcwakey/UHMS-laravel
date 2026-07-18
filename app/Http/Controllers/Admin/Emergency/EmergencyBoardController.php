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
        $filters = array_filter(array_merge([
            'status' => $request->route('status'),
            'triage_category' => $request->route('triage_category'),
        ], $request->only(['search', 'status', 'triage_category', 'bay_id'])), fn ($value) => $value !== null && $value !== '');

        $payload = $this->board->board($filters);

        return view('emergency.board', array_merge($payload, [
            'filters' => $filters,
            'bays' => EmergencyBay::active()->orderBy('name')->get(),
        ]));
    }
}
