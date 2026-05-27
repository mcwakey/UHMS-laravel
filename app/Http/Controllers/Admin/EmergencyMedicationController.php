<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmergencyCase;
use App\Services\EmergencyMedicationService;
use Illuminate\Http\Request;

class EmergencyMedicationController extends Controller
{
    public function __construct(private EmergencyMedicationService $medications) {}

    public function store(Request $request, EmergencyCase $emergencyCase)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'drug_name' => ['nullable', 'string', 'max:180'],
            'dose' => ['required', 'string', 'max:80'],
            'route' => ['required', 'string', 'max:80'],
            'frequency_code' => ['required', 'string', 'max:40'],
            'duration' => ['nullable', 'string', 'max:80'],
            'quantity_ordered' => ['nullable', 'integer', 'min:0', 'max:999'],
            'quantity_dispensed' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'start_at' => ['nullable', 'date'],
            'instructions' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->medications->order($emergencyCase, $data, $request->user());

        return back()->with('success', 'Emergency medication ordered and MAR schedule updated.');
    }
}
