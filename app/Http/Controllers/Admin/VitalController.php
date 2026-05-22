<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DepartmentType;
use App\Enums\TriageScore;
use App\Enums\VisitStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVitalRequest;
use App\Models\Department;
use App\Models\Triage;
use App\Models\Visit;
use App\Models\Vital;
use App\Services\VisitService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class VitalController extends Controller
{
    public function __construct(protected VisitService $visitService) {}

    /**
     * Show vitals recording form for a visit.
     */
    public function create(Request $request)
    {
        $visit = null;
        $consultationDepts = collect();

        if ($request->filled('visit_id')) {
            $visit = Visit::with([
                'patient',
                'latestVitals',
                'triage.triagedBy',
                'triage.department',
                'currentDepartment',
                'visitServices.department',
            ])->findOrFail($request->visit_id);

            // When redirected after vitals save, load consultation depts for dept chooser
            if ($request->boolean('dept_chooser') && $visit->status === VisitStatus::TRIAGE) {
                // First: depts from billed services that are consultation type
                $consultationDepts = $visit->visitServices
                    ->pluck('department')
                    ->filter()
                    ->unique('id')
                    ->filter(fn($d) => $d->type === DepartmentType::CONSULTATION)
                    ->values();

                // Fallback: all active consultation departments
                if ($consultationDepts->isEmpty()) {
                    $consultationDepts = Department::where('type', DepartmentType::CONSULTATION->value)
                        ->active()
                        ->orderBy('name')
                        ->get();
                }
            }
        }

        // Get today's visits in triage or waiting status
        $triageVisits = Visit::with('patient')
            ->whereIn('status', ['triage', 'waiting', 'consulting'])
            ->today()
            ->latest()
            ->get();

        // Pass departments for reference
        $departments = Department::active()->orderBy('name')->get();

        return view('vitals.record', compact('visit', 'triageVisits', 'departments', 'consultationDepts'));
    }

    /**
     * Store / update vitals for a visit (one set of vitals per visit — upsert).
     */
    public function store(StoreVitalRequest $request)
    {
        $visit = Visit::with(['patient', 'triage'])->findOrFail($request->visit_id);

        $data = $request->validated();
        $data['patient_id'] = $visit->patient_id;
        $data['recorded_by'] = Auth::id();
        $data['recorded_at'] = now();

        // Auto-calculate BMI if weight and height provided
        if (!empty($data['weight']) && !empty($data['height'])) {
            $heightInMeters = $data['height'] / 100;
            if ($heightInMeters > 0) {
                $data['bmi'] = round($data['weight'] / ($heightInMeters * $heightInMeters), 1);
            }
        }

        // One set of vitals per visit: upsert on visit_id
        Vital::updateOrCreate(
            ['visit_id' => $visit->id],
            $data
        );

        // Persist height to patient record so it auto-fills next time
        if (!empty($data['height']) && $data['height'] != $visit->patient->height) {
            $visit->patient->update(['height' => $data['height']]);
        }

        // For TRIAGE visits: compute + store triage score (keep visit in TRIAGE for dept chooser)
        if ($visit->status === VisitStatus::TRIAGE) {
            $score = TriageScore::compute($data);

            Triage::updateOrCreate(
                ['visit_id' => $visit->id],
                array_merge($data, [
                    'patient_id'   => $visit->patient_id,
                    'triage_score' => $score->value,
                    'triaged_by'   => Auth::id(),
                    'triaged_at'   => now(),
                ])
            );

            $visit->update(['triage_score' => $score->value]);

            return redirect()
                ->route('admin.vitals.create', ['visit_id' => $visit->id, 'dept_chooser' => 1])
                ->with('success', 'Vitals recorded for ' . $visit->patient->full_name . '. Please direct patient to a consultation department.');
        }

        return redirect()
            ->back()
            ->with('success', 'Vitals recorded for ' . $visit->patient->full_name);
    }

    /**
     * Update the priority of a visit from the vitals page.
     */
    public function updatePriority(Request $request, Visit $visit)
    {
        $request->validate([
            'priority' => ['required', 'string', 'in:' . implode(',', array_column(\App\Enums\Priority::cases(), 'value'))],
        ]);

        $visit->update(['priority' => $request->priority]);

        return back()->with('success', 'Priority updated to ' . \App\Enums\Priority::from($request->priority)->label() . '.');
    }

    /**
     * Assign a consultation department from the vitals page and send patient there.
     */
    public function assignConsultation(Request $request, Visit $visit)
    {
        $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
        ]);

        $this->visitService->assignDepartment(
            $visit,
            (int) $request->department_id,
            VisitStatus::CONSULTING
        );

        return redirect()
            ->route('admin.vitals.create')
            ->with('success', 'Patient assigned to consultation queue.');
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
