<?php

namespace App\Http\Controllers\Admin\AdmissionsWard;

use App\Enums\DepartmentType;
use App\Enums\LogModule;
use App\Enums\Priority;
use App\Enums\TriageScore;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVitalRequest;
use App\Models\Department;
use App\Models\Triage;
use App\Models\Visit;
use App\Models\Vital;
use App\Services\ActivityLogService;
use App\Services\Department\DepartmentContextSwitcherService;
use App\Services\VisitService;
use App\Services\WorkspaceRouteResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
                    ->filter(fn ($d) => $d->type === DepartmentType::CONSULTATION)
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
        $triageVisitsQuery = Visit::with('patient')
            ->whereIn('status', ['triage', 'queued', 'consulting'])
            ->today();

        if (app(WorkspaceRouteResolver::class)->isNursing()) {
            $department = app(DepartmentContextSwitcherService::class)
                ->currentDepartment($request->user(), $request);
            $triageVisitsQuery->where('visit_type', VisitType::OUTPATIENT->value)
                ->where(function ($query) use ($department) {
                    $query->where('current_department_id', $department?->id)
                        ->orWhereHas('triage.triagedBy', fn ($user) => $user
                            ->where('department_id', $department?->id)
                            ->orWhereHas('departments', fn ($assigned) => $assigned->whereKey($department?->id)));
                });
        }

        $triageVisits = $triageVisitsQuery
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
        if (! empty($data['weight']) && ! empty($data['height'])) {
            $heightInMeters = $data['height'] / 100;
            if ($heightInMeters > 0) {
                $data['bmi'] = round($data['weight'] / ($heightInMeters * $heightInMeters), 1);
            }
        }

        // One set of vitals per visit: upsert on visit_id
        $vital = Vital::updateOrCreate(
            ['visit_id' => $visit->id],
            $data
        );

        app(ActivityLogService::class)->log(LogModule::CONSULTATION, $vital->wasRecentlyCreated ? 'VITALS_RECORDED' : 'VITALS_CORRECTED', [
            'patient_id' => $visit->patient_id,
            'visit_id' => $visit->id,
            'department_id' => $visit->current_department_id,
        ], $vital, $vital->wasRecentlyCreated ? 'Vital signs recorded' : 'Vital signs corrected');

        // Persist height to patient record so it auto-fills next time
        if (! empty($data['height']) && $data['height'] != $visit->patient->height) {
            $visit->patient->update(['height' => $data['height']]);
        }

        // For TRIAGE visits: compute + store triage score (keep visit in TRIAGE for dept chooser)
        if ($visit->status === VisitStatus::TRIAGE) {
            $score = TriageScore::compute($data);

            Triage::updateOrCreate(
                ['visit_id' => $visit->id],
                array_merge($data, [
                    'patient_id' => $visit->patient_id,
                    'triage_score' => $score->value,
                    'triaged_by' => Auth::id(),
                    'triaged_at' => now(),
                ])
            );

            $visit->update(['triage_score' => $score->value]);

            return redirect()
                ->route(app(WorkspaceRouteResolver::class)->routeName('admin.vitals.create'), ['visit_id' => $visit->id, 'dept_chooser' => 1])
                ->with('success', __('messages.vitals.recorded_triage', ['name' => $visit->patient->full_name]));
        }

        return redirect()
            ->back()
            ->with('success', __('messages.vitals.recorded', ['name' => $visit->patient->full_name]));
    }

    /**
     * Update the priority of a visit from the vitals page.
     */
    public function updatePriority(Request $request, Visit $visit)
    {
        $request->validate([
            'priority' => ['required', 'string', 'in:'.implode(',', array_column(Priority::cases(), 'value'))],
        ]);

        $visit->update(['priority' => $request->priority]);

        return back()->with('success', __('messages.vitals.priority_updated', ['priority' => Priority::from($request->priority)->label()]));
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
            ->route(app(WorkspaceRouteResolver::class)->routeName('admin.vitals.create'))
            ->with('success', __('messages.vitals.assigned_consultation'));
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
