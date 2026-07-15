<?php

namespace App\Http\Controllers\Admin\Appointments;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Patient;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\VisitStatusFlowService;
use App\Services\WorkspaceRouteResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function __construct(
        private AppointmentService $appointmentService,
        private WorkspaceRouteResolver $workspaceRoutes,
    ) {}

    /**
     * List appointments with optional calendar view.
     */
    public function index(Request $request)
    {
        $filters = $this->normalizedDateFilters($request);
        $stats = $this->appointmentService->getStats($filters);
        $appointments = $this->appointmentService->list($filters);
        $doctors = User::role('Doctor')->orderBy('first_name')->get();
        $departments = Department::active()->orderBy('name')->get();
        $statuses = AppointmentStatus::cases();

        return view('appointments.index', compact(
            'appointments', 'stats', 'doctors', 'departments', 'statuses', 'filters'
        ));
    }

    /**
     * Show create appointment form.
     */
    public function create(Request $request)
    {
        $selectedPatient = $request->has('patient_id')
            ? Patient::find($request->patient_id)
            : null;

        $departments = Department::active()->consultation()->orderBy('name')->get();
        $doctors = User::role('Doctor')->orderBy('first_name')->get();
        $appointmentDate = $this->defaultAppointmentDate($request->query('date'));

        return view('appointments.create', compact(
            'selectedPatient', 'departments', 'doctors', 'appointmentDate'
        ));
    }

    /**
     * Store a new appointment.
     */
    public function store(StoreAppointmentRequest $request)
    {
        $data = $request->validated();

        // Check for conflicts if doctor is assigned
        if (! empty($data['doctor_id'])) {
            $hasConflict = $this->appointmentService->hasConflict(
                $data['doctor_id'],
                $data['appointment_date'],
                $data['start_time'],
                $data['end_time'] ?? null,
            );

            if ($hasConflict) {
                return back()->withInput()->with('error', __('messages.appointments.doctor_conflict'));
            }
        }

        $appointment = $this->appointmentService->create($data);

        return redirect()
            ->to($this->workspaceRoutes->appointmentShow($appointment))
            ->with('success', __('messages.appointments.created'));
    }

    /**
     * Show appointment details.
     */
    public function show(Appointment $appointment, VisitStatusFlowService $flowService)
    {
        $appointment->load([
            'patient',
            'doctor',
            'department',
            'services.department',
            'visit',
            'visitInsurance.insuranceProvider',
            'visitInsurance.insuranceTier',
            'createdByUser',
            'cancelledByUser',
        ]);

        // Attendance classification the resulting visit would receive on check-in.
        $attendanceClass = $appointment->patient
            ? $flowService->determineAttendanceClass(
                $appointment->patient,
                $appointment->appointment_date instanceof Carbon
                    ? $appointment->appointment_date
                    : Carbon::parse($appointment->appointment_date),
            )
            : null;

        return view('appointments.show', compact('appointment', 'attendanceClass'));
    }

    /**
     * Show edit form.
     */
    public function edit(Appointment $appointment)
    {
        $appointment->load(['patient', 'services.department', 'services.prices', 'visitInsurance.insuranceProvider']);
        $doctors = User::role('Doctor')->orderBy('first_name')->get();
        $departments = Department::active()->consultation()->orderBy('name')->get();

        return view('appointments.edit', compact(
            'appointment', 'doctors', 'departments'
        ));
    }

    /**
     * Update appointment.
     */
    public function update(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        $data = $request->validated();

        // Check for conflicts if doctor is assigned
        if (! empty($data['doctor_id'])) {
            $hasConflict = $this->appointmentService->hasConflict(
                $data['doctor_id'],
                $data['appointment_date'],
                $data['start_time'],
                $data['end_time'] ?? null,
                $appointment->id,
            );

            if ($hasConflict) {
                return back()->withInput()->with('error', __('messages.appointments.doctor_conflict'));
            }
        }

        $this->appointmentService->update($appointment, $data);

        return redirect()
            ->to($this->workspaceRoutes->appointmentShow($appointment))
            ->with('success', __('messages.appointments.updated'));
    }

    /**
     * Check in patient (creates visit from appointment).
     */
    public function checkIn(Request $request, Appointment $appointment)
    {
        $data = $request->validate([
            'visit_insurance_id' => ['nullable', 'exists:patient_insurances,id'],
            'insurance_verification_id' => ['nullable', 'exists:insurance_verifications,id'],
            'verification_reference_code' => ['nullable', 'string', 'max:80'],
        ]);

        try {
            $appointment = $this->appointmentService->checkIn($appointment, $data);

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => __('messages.appointments.checked_in'),
                    'appointment_id' => $appointment->id,
                    'appointment_number' => $appointment->appointment_number,
                    'appointment_status' => $appointment->status->value,
                    'appointment_status_label' => $appointment->status->label(),
                    'visit_id' => $appointment->visit?->id,
                    'visit_number' => $appointment->visit?->visit_number,
                    'visit_status' => $appointment->visit?->status?->value,
                    'visit_status_label' => $appointment->visit?->status?->label(),
                    'redirect_url' => $this->workspaceRoutes->appointmentShow($appointment),
                    'visit_redirect_url' => $appointment->visit
                        ? $this->workspaceRoutes->visitShow($appointment->visit)
                        : null,
                ]);
            }

            return redirect()
                ->to($this->workspaceRoutes->appointmentShow($appointment))
                ->with('success', __('messages.appointments.checked_in'));
        } catch (\InvalidArgumentException $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Transition appointment status.
     */
    public function transition(Request $request, Appointment $appointment)
    {
        $request->validate([
            'status' => ['required', Rule::enum(AppointmentStatus::class)],
        ]);

        try {
            $newStatus = AppointmentStatus::from($request->status);
            $appointment = $this->appointmentService->transition($appointment, $newStatus)->fresh();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('messages.appointments.status_changed', ['status' => $newStatus->label()]),
                    'appointment_id' => $appointment->id,
                    'appointment_number' => $appointment->appointment_number,
                    'appointment_status' => $appointment->status->value,
                    'appointment_status_label' => $appointment->status->label(),
                    'status_color' => $appointment->status->color(),
                    'redirect_url' => $this->workspaceRoutes->appointmentShow($appointment),
                ]);
            }

            return back()->with('success', __('messages.appointments.status_changed', ['status' => $newStatus->label()]));
        } catch (\InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel appointment.
     */
    public function cancel(Request $request, Appointment $appointment)
    {
        $request->validate([
            'cancellation_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->appointmentService->cancel($appointment, $request->cancellation_reason);

        return redirect()
            ->to($this->workspaceRoutes->appointmentIndex())
            ->with('success', __('messages.appointments.cancelled'));
    }

    /**
     * Mark appointment as no-show.
     */
    public function noShow(Request $request, Appointment $appointment)
    {
        $appointment = $this->appointmentService->markNoShow($appointment)->fresh();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('messages.appointments.no_show'),
                'appointment_id' => $appointment->id,
                'appointment_number' => $appointment->appointment_number,
                'appointment_status' => $appointment->status->value,
                'appointment_status_label' => $appointment->status->label(),
                'status_color' => $appointment->status->color(),
                'redirect_url' => $this->workspaceRoutes->appointmentShow($appointment),
            ]);
        }

        return back()->with('success', __('messages.appointments.no_show'));
    }

    /**
     * Calendar view data (AJAX).
     */
    public function calendar(Request $request)
    {
        $filters = $this->normalizedDateFilters($request, true);
        $from = $filters['date_from'];
        $to = $filters['date_to'];

        $calendarData = $this->appointmentService->getCalendarData($from, $to, $filters);

        if ($request->ajax() && ! $request->headers->has('X-Inertia')) {
            return response()->json($calendarData);
        }

        $doctors = User::role('Doctor')->orderBy('first_name')->get();
        $departments = Department::active()->orderBy('name')->get();
        $statuses = AppointmentStatus::cases();

        return view('appointments.calendar', compact('calendarData', 'from', 'to', 'doctors', 'departments', 'statuses', 'filters'));
    }

    private function normalizedDateFilters(Request $request, bool $expandTodayRange = false): array
    {
        $filters = $request->all();
        $filters['per_page'] = $this->normalizePerPage($filters['per_page'] ?? null);
        $dateRange = trim((string) ($filters['date_range'] ?? ''));

        if ($dateRange !== '') {
            $parts = preg_split('/\s+(?:to|-)\s+/', $dateRange);
            $filters['date_from'] = $parts[0] ?? null;
            $filters['date_to'] = $parts[1] ?? ($parts[0] ?? null);
        } elseif (! empty($filters['from'])) {
            $filters['date_from'] = $filters['from'];
            $filters['date_to'] = $filters['to'] ?? $filters['from'];
        } elseif (! empty($filters['date'])) {
            $filters['date_from'] = $filters['date'];
            $filters['date_to'] = $filters['date'];
        }

        $filters['date_from'] = $this->normalizeDateValue($filters['date_from'] ?? null);
        $filters['date_to'] = $this->normalizeDateValue($filters['date_to'] ?? null);

        if (empty($filters['date_from']) && empty($filters['date_to'])) {
            $this->applyDefaultAppointmentRange($filters);
        }

        if (! empty($filters['date_from']) && empty($filters['date_to'])) {
            $filters['date_to'] = $filters['date_from'];
        }

        if (! empty($filters['date_to']) && empty($filters['date_from'])) {
            $filters['date_from'] = $filters['date_to'];
        }

        if (Carbon::parse($filters['date_from'])->gt(Carbon::parse($filters['date_to']))) {
            [$filters['date_from'], $filters['date_to']] = [$filters['date_to'], $filters['date_from']];
        }

        if ($expandTodayRange && $this->isTodayOnlyRange($filters)) {
            $this->applyDefaultAppointmentRange($filters);
        }

        $filters['date_range'] = $filters['date_from'].' to '.$filters['date_to'];
        unset($filters['date'], $filters['from'], $filters['to']);

        return $filters;
    }

    private function normalizePerPage(mixed $value): int|string
    {
        if (is_string($value) && strtolower($value) === 'all') {
            return 'all';
        }

        $perPage = (int) $value;
        $allowed = [10, 25, 50, 100];

        return in_array($perPage, $allowed, true) ? $perPage : 10;
    }

    private function applyDefaultAppointmentRange(array &$filters): void
    {
        $filters['date_from'] = today()->subDays(3)->toDateString();
        $filters['date_to'] = today()->addDays(10)->toDateString();
    }

    private function isTodayOnlyRange(array $filters): bool
    {
        $today = today()->toDateString();

        return ($filters['date_from'] ?? null) === $today
            && ($filters['date_to'] ?? null) === $today;
    }

    private function normalizeDateValue(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function defaultAppointmentDate(?string $requestedDate = null): string
    {
        $tomorrow = today()->addDay();
        $date = $this->normalizeDateValue($requestedDate) ?: $tomorrow->toDateString();
        $date = Carbon::parse($date);

        return $date->lt($tomorrow) ? $tomorrow->toDateString() : $date->toDateString();
    }
}
