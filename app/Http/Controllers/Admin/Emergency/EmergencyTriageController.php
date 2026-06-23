<?php

namespace App\Http\Controllers\Admin\Emergency;

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
            'triage_category' => ['nullable', 'in:RED,ORANGE,YELLOW,GREEN,BLACK'],
            'final_triage_category' => ['nullable', 'in:RED,ORANGE,YELLOW,GREEN,BLACK'],
            'triage_score' => ['nullable', 'integer', 'min:0', 'max:999'],
            'triage_notes' => ['nullable', 'string', 'max:3000'],
            'triage_override_reason' => ['nullable', 'string', 'max:1000'],
            'avpu' => ['nullable', 'in:A,V,P,U'],
            'pain_score' => ['nullable', 'integer', 'min:0', 'max:10'],
            'danger_signs' => ['nullable', 'array'],
            'danger_signs.*' => ['string', 'max:80'],
            'trauma' => ['nullable', 'boolean'],
            'bleeding' => ['nullable', 'boolean'],
            'seizure' => ['nullable', 'boolean'],
            'respiratory_distress' => ['nullable', 'boolean'],
            'pregnancy' => ['nullable', 'boolean'],
            'shock' => ['nullable', 'boolean'],
            'uncontrolled_bleeding' => ['nullable', 'boolean'],
            'dead_on_arrival' => ['nullable', 'boolean'],
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

        // Overriding the computed triage category must be justified.
        if (
            ! empty($data['final_triage_category'])
            && ! empty($data['triage_category'])
            && $data['final_triage_category'] !== $data['triage_category']
            && empty($data['triage_override_reason'])
        ) {
            return back()->withInput()->withErrors([
                'triage_override_reason' => 'Please provide a reason for overriding the triage category.',
            ]);
        }

        $this->triage->record($emergencyCase, $data, $request->user());

        return back()->with('success', __('messages.emergency.triage_recorded'));
    }
}
