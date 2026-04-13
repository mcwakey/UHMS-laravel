<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVitalRequest;
use App\Models\Visit;
use App\Models\Vital;
use Illuminate\Http\Request;

class VitalController extends Controller
{
    /**
     * Show vitals recording form for a visit.
     */
    public function create(Request $request)
    {
        $visit = null;
        if ($request->filled('visit_id')) {
            $visit = Visit::with('patient', 'latestVitals')->findOrFail($request->visit_id);
        }

        // Get today's visits in triage or waiting status
        $triageVisits = Visit::with('patient')
            ->whereIn('status', ['triage', 'waiting', 'consulting'])
            ->today()
            ->latest()
            ->get();

        return view('vitals.record', compact('visit', 'triageVisits'));
    }

    /**
     * Store vitals for a visit.
     */
    public function store(StoreVitalRequest $request)
    {
        $visit = Visit::with('patient')->findOrFail($request->visit_id);

        $data = $request->validated();
        $data['patient_id'] = $visit->patient_id;
        $data['recorded_by'] = auth()->id();
        $data['recorded_at'] = now();

        // Auto-calculate BMI if weight and height provided
        if (!empty($data['weight']) && !empty($data['height'])) {
            $heightInMeters = $data['height'] / 100;
            if ($heightInMeters > 0) {
                $data['bmi'] = round($data['weight'] / ($heightInMeters * $heightInMeters), 1);
            }
        }

        Vital::create($data);

        return redirect()
            ->back()
            ->with('success', 'Vitals recorded successfully for ' . $visit->patient->full_name);
    }

    /**
     * Show vitals history for a visit.
     */
    public function show(Visit $visit)
    {
        $vitals = $visit->vitals()->with('recordedBy')->latest()->get();
        $visit->load('patient');

        return view('vitals.show', compact('visit', 'vitals'));
    }
}
