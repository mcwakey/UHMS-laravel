<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmergencyCase;
use App\Services\EmergencyTriageService;
use Illuminate\Http\Request;

class EmergencyTriageController extends Controller
{
    public function __construct(private EmergencyTriageService $triage) {}

    public function store(Request $request, EmergencyCase $emergencyCase)
    {
        $data = $request->validate([
            'chief_complaint' => ['nullable', 'string', 'max:2000'],
            'triage_category' => ['required', 'in:RED,ORANGE,YELLOW,GREEN,BLACK'],
            'triage_score' => ['nullable', 'integer', 'min:0', 'max:999'],
            'triage_notes' => ['nullable', 'string', 'max:3000'],
            'blood_pressure_systolic' => ['nullable', 'integer', 'min:0', 'max:300'],
            'blood_pressure_diastolic' => ['nullable', 'integer', 'min:0', 'max:200'],
            'heart_rate' => ['nullable', 'integer', 'min:0', 'max:300'],
            'temperature' => ['nullable', 'numeric', 'min:25', 'max:45'],
            'respiratory_rate' => ['nullable', 'integer', 'min:0', 'max:80'],
            'spo2' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'height' => ['nullable', 'numeric', 'min:0', 'max:250'],
            'blood_sugar' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->triage->record($emergencyCase, $data, $request->user());

        return back()->with('success', 'Emergency triage recorded.');
    }
}
