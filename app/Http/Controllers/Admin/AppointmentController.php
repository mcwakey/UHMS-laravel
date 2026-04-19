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
use Illuminate\Http\Request;

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
        $stats = $this->appointmentService->getStats();
        $appointments = $this->appointmentService->list($request->all());
        $doctors = User::role('Doctor')->orderBy('first_name')->get();
        $departments = Department::active()->orderBy('name')->get();
        $statuses = AppointmentStatus::cases();

        return view('appointments.index', compact(
            'appointments', 'stats', 'doctors', 'departments', 'statuses'
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
            $this->appointmentService->checkIn($appointment);
            return redirect()
                ->route('admin.appointments.show', $appointment)
                ->with('success', 'Patient checked in and visit created successfully.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Transition appointment status.
     */
    public function transition(Request $request, Appointment $appointment)
    {
        $request->validate([
            'status' => ['required', 'string'],
        ]);

        try {
            $newStatus = AppointmentStatus::from($request->status);
            $this->appointmentService->transition($appointment, $newStatus);

            return back()->with('success', "Appointment status changed to {$newStatus->label()}.");
        } catch (\InvalidArgumentException $e) {
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
        $from = $request->get('from', now()->startOfWeek()->toDateString());
        $to = $request->get('to', now()->endOfWeek()->toDateString());

        $calendarData = $this->appointmentService->getCalendarData($from, $to, $request->all());

        if ($request->ajax()) {
            return response()->json($calendarData);
        }

        $doctors = User::role('Doctor')->orderBy('first_name')->get();
        $departments = Department::active()->orderBy('name')->get();

        return view('appointments.calendar', compact('calendarData', 'from', 'to', 'doctors', 'departments'));
    }
}
