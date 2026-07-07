<?php

namespace App\Http\Controllers\Admin\Patients;

use App\Enums\DepartmentType;
use App\Enums\TriageScore;
use App\Enums\VisitStatus;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\QueueEntry;
use App\Models\Vital;
use App\Models\Visit;
use App\Services\ConsultationRouteService;
use App\Services\Billing\BillingPolicyService;
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

        $isEdit = $visit->triage !== null;

        if (! $isEdit && ! in_array($visit->status, [VisitStatus::QUEUED, VisitStatus::TRIAGE])) {
            return redirect()
                ->route('admin.visits.show', $visit)
                ->with('error', __('messages.triage.not_awaiting'));
        }

        // Auto-transition QUEUED to TRIAGE when nurse opens a new assessment.
        if (! $isEdit && $visit->status === VisitStatus::QUEUED) {
            $visit->update(['status' => VisitStatus::TRIAGE->value]);
            $visit->statusLogs()->create([
                'from_status' => VisitStatus::QUEUED->value,
                'to_status' => VisitStatus::TRIAGE->value,
                'changed_by' => auth()->id(),
                'notes' => 'Triage assessment started',
            ]);
            $visit = $visit->fresh([
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
        }

        $consultationDepts = $this->billableConsultationDepartmentsForVisit($visit);
        $billableConsultationDepartmentIds = $consultationDepts->pluck('id');
        $pendingRoutes = $isEdit
            ? collect()
            : $visit->pendingConsultationRoutes
                ->filter(fn ($route) => $billableConsultationDepartmentIds->contains($route->department_id))
                ->values();

        if ($isEdit) {
            return view('triage.edit', compact('visit', 'consultationDepts'));
        }

        return view('triage.create', compact('visit', 'consultationDepts', 'pendingRoutes'));
    }

    /**
     * Save triage record and transition the visit.
     */
    public function store(Request $request, Visit $visit, ConsultationRouteService $consultationRoutes, BillingPolicyService $billingPolicy)
    {
        if ($visit->status !== VisitStatus::TRIAGE) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('messages.triage.not_in_triage'),
                    'redirect_url' => route('admin.visits.show', $visit),
                ], 409);
            }

            return redirect()
                ->route('admin.visits.show', $visit)
                ->with('error', __('messages.triage.not_in_triage'));
        }

        $consultationDeptIds = $this->billableConsultationDepartmentsForVisit($visit)->pluck('id')->all();
        $pendingRouteIds = $visit->pendingConsultationRoutes()
            ->whereIn('department_id', $consultationDeptIds)
            ->pluck('id')
            ->all();

        $validated = $request->validate([
            'blood_pressure_systolic' => ['nullable', 'integer', 'min:40', 'max:300'],
            'blood_pressure_diastolic' => ['nullable', 'integer', 'min:20', 'max:200'],
            'heart_rate' => ['nullable', 'integer', 'min:20', 'max:300'],
            'temperature' => ['nullable', 'numeric', 'min:30', 'max:45'],
            'respiratory_rate' => ['nullable', 'integer', 'min:4', 'max:60'],
            'spo2' => ['nullable', 'integer', 'min:50', 'max:100'],
            'weight' => ['nullable', 'numeric', 'min:0.5', 'max:500'],
            'height' => ['nullable', 'numeric', 'min:20', 'max:250'],
            'consultation_route_id' => [Rule::requiredIf(! empty($pendingRouteIds)), Rule::in($pendingRouteIds)],
            'department_id' => ['nullable', Rule::in($consultationDeptIds)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'consultation_route_id.required' => 'Select the consultation route to queue after triage.',
            'consultation_route_id.in' => 'Select one of the pending consultation routes for this visit.',
            'department_id.in' => 'Select one of the consultation departments billed on this visit.',
        ]);

        // Resolve route → department_id so processTriage() does not need to
        // know about routes specifically.
        $selectedRoute = null;
        if (! empty($validated['consultation_route_id'])) {
            $selectedRoute = $visit->pendingConsultationRoutes()
                ->where('id', $validated['consultation_route_id'])
                ->first();
            if ($selectedRoute) {
                $validated['department_id'] = $selectedRoute->department_id;
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
            if ($selectedRoute) {
                $this->assertRouteServicesSettled($selectedRoute->fresh(), $billingPolicy, $request->user());
            }

            $visit = $this->visitService->processTriage($visit, $validated);
            if ($selectedRoute) {
                $consultationRoutes->activateRouteOnly($selectedRoute->fresh(), $request->user());
                $visit = $visit->fresh(['currentDepartment']);
            }
        } catch (\InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }

        $message = __('messages.triage.completed', ['status' => $visit->status->label()]);

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
                'queue_url' => route('admin.consultations.index'),
            ]);
        }

        return redirect()
            ->route('admin.visits.show', $visit)
            ->with('success', $message);
    }

    public function update(Request $request, Visit $visit)
    {
        $visit->load(['patient', 'triage']);
        abort_unless($visit->triage, 404);

        $validated = $request->validate($this->vitalRules());

        if (! empty($validated['weight']) && ! empty($validated['height'])) {
            $heightM = $validated['height'] / 100;
            if ($heightM > 0) {
                $validated['bmi'] = round($validated['weight'] / ($heightM * $heightM), 1);
            }
        }

        $score = TriageScore::compute($validated);
        $triage = $visit->triage;

        $triage->update(array_merge($validated, [
            'patient_id' => $visit->patient_id,
            'triage_score' => $score->value,
            'triaged_by' => $request->user()?->id,
            'triaged_at' => now(),
        ]));

        $this->syncTriageVitalRecord($triage->fresh(), $validated);
        $visit->update(['triage_score' => $score->value]);
        $visit = $visit->fresh(['triage', 'currentDepartment']);

        $message = __('triage.js_updated_successfully');

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
                'redirect_url' => route('admin.triage.show', $visit),
                'queue_url' => route('admin.consultations.index'),
            ]);
        }

        return redirect()
            ->route('admin.triage.show', $visit)
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
        $triageQueueNumber = QueueEntry::query()
            ->select('queue_number')
            ->whereColumn('visit_id', 'visits.id')
            ->whereNull('department_id')
            ->whereIn('status', ['waiting', 'serving'])
            ->whereDate('created_at', today())
            ->orderBy('queue_number')
            ->limit(1);

        $visits = Visit::with([
            'patient',
            'triage',
            'currentDepartment',
            'invoices.items',
            'queueEntries' => fn ($query) => $query
                ->whereNull('department_id')
                ->today()
                ->whereIn('status', ['waiting', 'serving'])
                ->orderBy('queue_number'),
        ])
            ->addSelect(['triage_queue_number' => $triageQueueNumber])
            ->whereIn('status', [VisitStatus::QUEUED->value, VisitStatus::TRIAGE->value])
            ->today()
            ->orderByRaw('triage_queue_number IS NULL')
            ->orderBy('triage_queue_number')
            ->orderBy('checked_in_at')
            ->orderBy('id')
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

    private function vitalRules(): array
    {
        return [
            'blood_pressure_systolic' => ['nullable', 'integer', 'min:40', 'max:300'],
            'blood_pressure_diastolic' => ['nullable', 'integer', 'min:20', 'max:200'],
            'heart_rate' => ['nullable', 'integer', 'min:20', 'max:300'],
            'temperature' => ['nullable', 'numeric', 'min:30', 'max:45'],
            'respiratory_rate' => ['nullable', 'integer', 'min:4', 'max:60'],
            'spo2' => ['nullable', 'integer', 'min:50', 'max:100'],
            'weight' => ['nullable', 'numeric', 'min:0.5', 'max:500'],
            'height' => ['nullable', 'numeric', 'min:20', 'max:250'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function syncTriageVitalRecord($triage, array $data): void
    {
        $vitalFields = [
            'blood_pressure_systolic',
            'blood_pressure_diastolic',
            'heart_rate',
            'temperature',
            'respiratory_rate',
            'spo2',
            'weight',
            'height',
            'bmi',
            'notes',
        ];

        $vitals = array_intersect_key($data, array_flip($vitalFields));

        Vital::updateOrCreate(
            ['triage_id' => $triage->id],
            array_merge($vitals, [
                'visit_id' => $triage->visit_id,
                'patient_id' => $triage->patient_id,
                'recorded_by' => $triage->triaged_by,
                'recorded_at' => $triage->triaged_at ?? now(),
            ])
        );
    }

    private function assertRouteServicesSettled($route, BillingPolicyService $billingPolicy, $user): void
    {
        $route->loadMissing([
            'routeServices.service',
            'routeServices.invoiceItem.visit.admission',
            'routeServices.invoiceItem.visit.emergencyCase',
        ]);

        foreach ($route->routeServices as $routeService) {
            $service = $routeService->service;
            $serviceName = $service?->name ?? 'Consultation service';
            $isBillable = ! $service || ! array_key_exists('is_billable', $service->getAttributes()) || $service->is_billable !== false;
            $hasCharge = ! $service || (float) ($service->price ?? 0) > 0;

            if (! $routeService->invoiceItem) {
                if ($isBillable && $hasCharge) {
                    throw new \RuntimeException("{$serviceName} has not been billed. Please bill and settle it before completing triage.");
                }

                continue;
            }

            $decision = $billingPolicy->getInvoiceItemPolicy($routeService->invoiceItem, $user);
            if (! $decision->allowed) {
                throw new \RuntimeException($decision->message ?: "{$serviceName} bill has not been settled.");
            }
        }
    }
}
