<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmergencyCase;
use App\Services\EmergencyVitalsService;
use Illuminate\Http\Request;

class EmergencyVitalsController extends Controller
{
    public function __construct(private EmergencyVitalsService $vitals) {}

    public function store(Request $request, EmergencyCase $emergencyCase)
    {
        $data = $request->validate([
            'blood_pressure_systolic' => ['nullable', 'integer', 'min:0', 'max:300'],
            'blood_pressure_diastolic' => ['nullable', 'integer', 'min:0', 'max:200'],
            'heart_rate' => ['nullable', 'integer', 'min:0', 'max:300'],
            'temperature' => ['nullable', 'numeric', 'min:25', 'max:45'],
            'respiratory_rate' => ['nullable', 'integer', 'min:0', 'max:80'],
            'spo2' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'height' => ['nullable', 'numeric', 'min:0', 'max:250'],
            'blood_sugar' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        $this->vitals->record($emergencyCase, $data, $request->user());

        return back()->with('success', 'Emergency vitals recorded.');
    }
}
