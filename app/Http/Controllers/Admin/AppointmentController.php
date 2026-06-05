<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Patient;
use App\Models\User;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function __construct(
        private AppointmentService $appointmentService,
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

        $departments = Department::active()->orderBy('name')->get();
        $doctors = User::role('Doctor')->orderBy('first_name')->get();

        return view('appointments.create', compact(
            'selectedPatient', 'departments', 'doctors'
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
                return back()->withInput()->with('error', 'The selected doctor has a conflicting appointment at that time.');
            }
        }

        $appointment = $this->appointmentService->create($data);

        return redirect()
            ->route('admin.appointments.show', $appointment)
            ->with('success', 'Appointment scheduled successfully.');
    }

    /**
     * Show appointment details.
     */
    public function show(Appointment $appointment)
    {
        $appointment->load(['patient', 'doctor', 'department', 'visit', 'createdByUser', 'cancelledByUser']);

        return view('appointments.show', compact('appointment'));
    }

    /**
     * Show edit form.
     */
    public function edit(Appointment $appointment)
    {
        $appointment->load(['patient', 'services', 'visitInsurance.insuranceProvider']);
        $doctors = User::role('Doctor')->orderBy('first_name')->get();
        $departments = Department::active()->orderBy('name')->get();

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
                return back()->withInput()->with('error', 'The selected doctor has a conflicting appointment at that time.');
            }
        }

        $this->appointmentService->update($appointment, $data);

        return redirect()
            ->route('admin.appointments.show', $appointment)
            ->with('success', 'Appointment updated successfully.');
    }

    /**
     * Check in patient (creates visit from appointment).
     */
    public function checkIn(Appointment $appointment)
    {
        try {
            $appointment = $this->appointmentService->checkIn($appointment);

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Patient checked in and visit created successfully.',
                    'appointment_id' => $appointment->id,
                    'appointment_number' => $appointment->appointment_number,
                    'appointment_status' => $appointment->status->value,
                    'appointment_status_label' => $appointment->status->label(),
                    'visit_id' => $appointment->visit?->id,
                    'visit_number' => $appointment->visit?->visit_number,
                    'visit_status' => $appointment->visit?->status?->value,
                    'visit_status_label' => $appointment->visit?->status?->label(),
                    'redirect_url' => route('admin.appointments.show', $appointment),
                    'visit_redirect_url' => $appointment->visit
                        ? route('admin.visits.show', $appointment->visit)
                        : null,
                ]);
            }

            return redirect()
                ->route('admin.appointments.show', $appointment)
                ->with('success', 'Patient checked in and visit created successfully.');
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
                    'message' => "Appointment status changed to {$newStatus->label()}.",
                    'appointment_id' => $appointment->id,
                    'appointment_number' => $appointment->appointment_number,
                    'appointment_status' => $appointment->status->value,
                    'appointment_status_label' => $appointment->status->label(),
                    'status_color' => $appointment->status->color(),
                    'redirect_url' => route('admin.appointments.show', $appointment),
                ]);
            }

            return back()->with('success', "Appointment status changed to {$newStatus->label()}.");
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
            ->route('admin.appointments.index')
            ->with('success', 'Appointment cancelled.');
    }

    /**
     * Mark appointment as no-show.
     */
    public function noShow(Appointment $appointment)
    {
        $this->appointmentService->markNoShow($appointment);

        return back()->with('success', 'Appointment marked as no-show.');
    }

    /**
     * Calendar view data (AJAX).
     */
    public function calendar(Request $request)
    {
        $filters = $this->normalizedDateFilters($request);
        $from = $filters['date_from'];
        $to = $filters['date_to'];

        $calendarData = $this->appointmentService->getCalendarData($from, $to, $filters);

        if ($request->ajax() && ! $request->headers->has('X-Inertia')) {
            return response()->json($calendarData);
        }

        $doctors = User::role('Doctor')->orderBy('first_name')->get();
        $departments = Department::active()->orderBy('name')->get();

        return view('appointments.calendar', compact('calendarData', 'from', 'to', 'doctors', 'departments', 'filters'));
    }

    private function normalizedDateFilters(Request $request): array
    {
        $filters = $request->all();
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
            $filters['date_from'] = today()->toDateString();
            $filters['date_to'] = today()->toDateString();
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

        $filters['date_range'] = $filters['date_from'].' to '.$filters['date_to'];
        unset($filters['date'], $filters['from'], $filters['to']);

        return $filters;
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
}
