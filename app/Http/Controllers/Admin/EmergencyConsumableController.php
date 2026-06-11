<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmergencyCase;
use App\Services\EmergencyConsumableService;
use Illuminate\Http\Request;

class EmergencyConsumableController extends Controller
{
    public function __construct(private EmergencyConsumableService $consumables) {}

    public function store(Request $request, EmergencyCase $emergencyCase)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:0.0001', 'max:999999'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->consumables->useConsumable($emergencyCase, $data, $request->user());

        return back()->with('success', __('messages.emergency.consumable_recorded'));
    }
}