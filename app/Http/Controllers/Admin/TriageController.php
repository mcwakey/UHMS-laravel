<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VisitStatus;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Visit;
use App\Services\VisitService;
use Illuminate\Http\Request;

class TriageController extends Controller
{
    public function __construct(
        protected VisitService $visitService,
    ) {}

    /**
     * Show the triage form for a visit.
     */
    public function create(Visit $visit)
    {
        if ($visit->status !== VisitStatus::TRIAGE) {
            return redirect()
                ->route('admin.visits.show', $visit)
                ->with('error', 'This visit is not in TRIAGE status.');
        }

        $visit->load(['patient', 'triage', 'currentDepartment']);

        // Consultation departments for the department selector
        $consultationDepts = Department::where('type', \App\Enums\DepartmentType::CONSULTATION)
            ->active()
            ->orderBy('name')
            ->get();

        // Fallback: get all active departments if none tagged as consultation
        if ($consultationDepts->isEmpty()) {
            $consultationDepts = Department::active()->orderBy('name')->get();
        }

        return view('triage.create', compact('visit', 'consultationDepts'));
    }

    /**
     * Save triage record and transition the visit.
     */
    public function store(Request $request, Visit $visit)
    {
        if ($visit->status !== VisitStatus::TRIAGE) {
            return redirect()
                ->route('admin.visits.show', $visit)
                ->with('error', 'This visit is not in TRIAGE status.');
        }

        $validated = $request->validate([
            'blood_pressure_systolic'  => ['nullable', 'integer', 'min:40', 'max:300'],
            'blood_pressure_diastolic' => ['nullable', 'integer', 'min:20', 'max:200'],
            'heart_rate'               => ['nullable', 'integer', 'min:20', 'max:300'],
            'temperature'              => ['nullable', 'numeric', 'min:30', 'max:45'],
            'respiratory_rate'         => ['nullable', 'integer', 'min:4', 'max:60'],
            'spo2'                     => ['nullable', 'integer', 'min:50', 'max:100'],
            'weight'                   => ['nullable', 'numeric', 'min:0.5', 'max:500'],
            'height'                   => ['nullable', 'numeric', 'min:20', 'max:250'],
            'department_id'            => ['nullable', 'exists:departments,id'],
            'notes'                    => ['nullable', 'string', 'max:1000'],
        ]);

        // Auto-calculate BMI if weight and height provided
        if (!empty($validated['weight']) && !empty($validated['height'])) {
            $heightM = $validated['height'] / 100;
            if ($heightM > 0) {
                $validated['bmi'] = round($validated['weight'] / ($heightM * $heightM), 1);
            }
        }

        try {
            $visit = $this->visitService->processTriage($visit, $validated);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.visits.show', $visit)
            ->with('success', 'Triage completed. Visit moved to ' . $visit->status->label() . '.');
    }

    /**
     * Show triage summary for a visit (read-only).
     */
    public function show(Visit $visit)
    {
        $visit->load(['patient', 'triage.triagedBy', 'triage.department', 'currentDepartment']);

        return view('triage.show', compact('visit'));
    }

    /**
     * Queue listing of all visits currently in TRIAGE status.
     */
    public function index(Request $request)
    {
        $visits = Visit::with(['patient', 'triage', 'currentDepartment'])
            ->where('status', VisitStatus::TRIAGE->value)
            ->today()
            ->orderBy('checked_in_at')
            ->get();

        // Also include waiting consultation (recently triaged, waiting for doctor)
        $waitingConsultation = Visit::with(['patient', 'triage', 'currentDepartment'])
            ->where('status', VisitStatus::WAITING_CONSULTATION->value)
            ->today()
            ->orderBy('updated_at')
            ->get();

        return view('triage.index', compact('visits', 'waitingConsultation'));
    }
}
