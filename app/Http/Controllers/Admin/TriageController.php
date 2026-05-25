<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DepartmentType;
use App\Enums\VisitStatus;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Visit;
use App\Services\VisitService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

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
        if (! in_array($visit->status, [VisitStatus::WAITING, VisitStatus::TRIAGE])) {
            return redirect()
                ->route('admin.visits.show', $visit)
                ->with('error', 'This visit is not awaiting triage.');
        }

        // Auto-transition WAITING → TRIAGE when nurse opens the form
        if ($visit->status === VisitStatus::WAITING) {
            $visit->update(['status' => VisitStatus::TRIAGE->value]);
            $visit->statusLogs()->create([
                'from_status' => VisitStatus::WAITING->value,
                'to_status' => VisitStatus::TRIAGE->value,
                'changed_by' => auth()->id(),
                'notes' => 'Triage assessment started',
            ]);
            $visit = $visit->fresh();
        }

        $visit->load([
            'patient',
            'triage',
            'currentDepartment',
            'visitServices.department',
            'pendingConsultationRoutes.department',
            'pendingConsultationRoutes.service',
            'pendingConsultationRoutes.routeServices.service',
            'activeConsultationRoute.department',
            'activeConsultationRoute.service',
            'activeConsultationRoute.routeServices.service',
        ]);

        $consultationDepts = $this->billableConsultationDepartmentsForVisit($visit);
        $pendingRoutes = $visit->pendingConsultationRoutes;

        return view('triage.create', compact('visit', 'consultationDepts', 'pendingRoutes'));
    }

    /**
     * Save triage record and transition the visit.
     */
    public function store(Request $request, Visit $visit)
    {
        if ($visit->status !== VisitStatus::TRIAGE) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'This visit is not in TRIAGE status.',
                    'redirect_url' => route('admin.visits.show', $visit),
                ], 409);
            }

            return redirect()
                ->route('admin.visits.show', $visit)
                ->with('error', 'This visit is not in TRIAGE status.');
        }

        $consultationDeptIds = $this->billableConsultationDepartmentsForVisit($visit)->pluck('id')->all();
        $pendingRouteIds = $visit->pendingConsultationRoutes()->pluck('id')->all();

        $validated = $request->validate([
            'blood_pressure_systolic' => ['nullable', 'integer', 'min:40', 'max:300'],
            'blood_pressure_diastolic' => ['nullable', 'integer', 'min:20', 'max:200'],
            'heart_rate' => ['nullable', 'integer', 'min:20', 'max:300'],
            'temperature' => ['nullable', 'numeric', 'min:30', 'max:45'],
            'respiratory_rate' => ['nullable', 'integer', 'min:4', 'max:60'],
            'spo2' => ['nullable', 'integer', 'min:50', 'max:100'],
            'weight' => ['nullable', 'numeric', 'min:0.5', 'max:500'],
            'height' => ['nullable', 'numeric', 'min:20', 'max:250'],
            'consultation_route_id' => ['nullable', Rule::in($pendingRouteIds)],
            'department_id' => ['nullable', Rule::in($consultationDeptIds)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'consultation_route_id.in' => 'Select one of the pending consultation routes for this visit.',
            'department_id.in' => 'Select one of the consultation departments billed on this visit.',
        ]);

        // Resolve route → department_id so processTriage() does not need to
        // know about routes specifically.
        if (! empty($validated['consultation_route_id'])) {
            $route = $visit->pendingConsultationRoutes()
                ->where('id', $validated['consultation_route_id'])
                ->first();
            if ($route) {
                $validated['department_id'] = $route->department_id;
            }
        }

        // Auto-calculate BMI if weight and height provided
        if (! empty($validated['weight']) && ! empty($validated['height'])) {
            $heightM = $validated['height'] / 100;
            if ($heightM > 0) {
                $validated['bmi'] = round($validated['weight'] / ($heightM * $heightM), 1);
            }
        }

        try {
            $visit = $this->visitService->processTriage($visit, $validated);
        } catch (\InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }

        $message = 'Triage completed. Visit moved to '.$visit->status->label().'.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'visit_id' => $visit->id,
                'visit_number' => $visit->visit_number,
                'status' => $visit->status->value,
                'status_label' => $visit->status->label(),
                'triage_score' => $visit->triage_score?->value,
                'triage_score_label' => $visit->triage_score?->label(),
                'department' => $visit->currentDepartment?->name,
                'redirect_url' => route('admin.visits.show', $visit),
                'queue_url' => route('admin.triage.index'),
            ]);
        }

        return redirect()
            ->route('admin.visits.show', $visit)
            ->with('success', $message);
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
     * Queue listing of all visits currently waiting for triage.
     */
    public function index(Request $request)
    {
        $visits = Visit::with(['patient', 'triage', 'currentDepartment'])
            ->whereIn('status', [VisitStatus::WAITING->value, VisitStatus::TRIAGE->value])
            ->today()
            ->orderBy('checked_in_at')
            ->get();

        return view('triage.index', compact('visits'));
    }

    private function billableConsultationDepartmentsForVisit(Visit $visit): Collection
    {
        $visit->loadMissing('visitServices.department');

        return $visit->visitServices
            ->pluck('department')
            ->filter(fn (?Department $department) => $department
                && $department->isActive()
                && $department->type === DepartmentType::CONSULTATION)
            ->unique('id')
            ->sortBy('name')
            ->values();
    }
}
