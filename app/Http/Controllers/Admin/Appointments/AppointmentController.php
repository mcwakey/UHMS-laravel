<?php

namespace App\Http\Controllers\Admin\Appointments;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\InsuranceService;
use App\Services\PatientPrivacyService;
use App\Services\VisitService;
use App\Services\VisitStatusFlowService;
use App\Services\WorkspaceRouteResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function __construct(
        private AppointmentService $appointmentService,
        private VisitService $visitService,
        private InsuranceService $insuranceService,
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

    public function patientSearch(Request $request)
    {
        $privacy = app(PatientPrivacyService::class);
        $term = $request->get('q', '');
        if (strlen($term) < 2) {
            return response()->json([]);
        }

        $patients = Patient::search($term)
            ->whereIn('status', ['active', 'inactive', 'deceased'])
            ->select('id', 'patient_number', 'first_name', 'last_name', 'other_names', 'date_of_birth', 'gender', 'phone', 'phone_secondary', 'email', 'ghana_card_number', 'status', 'is_deceased')
            ->with('activeAdmission.bed.ward')
            ->limit(10)
            ->get()
            ->map(function (Patient $patient) use ($privacy) {
                $lastVisit = $patient->visits()->latest('visit_date')->value('visit_date');
                $activeAdmission = $patient->activeAdmission;

                return [
                    'id' => $patient->id,
                    'text' => "{$patient->patient_number} — {$patient->full_name}",
                    'patient_number' => $patient->patient_number,
                    'full_name' => $patient->full_name,
                    'phone' => $privacy->display('phone', $patient->phone),
                    'phone_secondary' => $privacy->display('phone_secondary', $patient->phone_secondary),
                    'email' => $privacy->display('email', $patient->email),
                    'ghana_card_number' => $privacy->display('ghana_card_number', $patient->ghana_card_number),
                    'age' => $patient->age,
                    'gender' => $patient->gender?->translatedLabel(),
                    'last_visit_date' => $lastVisit ? Carbon::parse($lastVisit)->format('d M Y') : null,
                    'is_deceased' => (bool) $patient->is_deceased,
                    'active_admission' => $activeAdmission ? [
                        'id' => $activeAdmission->id,
                        'admission_number' => $activeAdmission->admission_number,
                        'bed' => $activeAdmission->bed?->bed_number,
                        'ward' => $activeAdmission->bed?->ward?->name,
                    ] : null,
                ];
            });

        return response()->json($patients);
    }

    public function patientInsurances(Request $request)
    {
        $patient = Patient::findOrFail($request->patient_id);
        $insurances = $this->insuranceService->getPatientInsurances($patient);
        $resolved = $this->insuranceService->resolveForVisit($patient);

        return response()->json([
            'insurances' => $insurances,
            'default_insurance_id' => $resolved['insurance']?->id,
            'is_fallback' => $resolved['is_fallback'],
        ]);
    }

    public function departmentServices(Request $request)
    {
        $services = $this->visitService->getServicesForDepartment($request->department_id);

        return response()->json($services->map(fn ($service) => $this->formatServiceForJson($service)));
    }

    public function doctorsForServices(Request $request)
    {
        $serviceIds = $request->input('service_ids', []);
        $doctors = $this->visitService->getDoctorsForServices($serviceIds);

        return response()->json($doctors->map(fn ($doctor) => [
            'id' => $doctor->id,
            'name' => 'Dr. '.$doctor->full_name,
            'specialties' => $doctor->specialties->pluck('name')->toArray(),
        ]));
    }

    public function servicesForDoctor(Request $request)
    {
        $services = $this->visitService->getServicesForDoctor($request->doctor_id);

        return response()->json($services->map(fn ($service) => $this->formatServiceForJson($service)));
    }

    public function servicePrice(Request $request)
    {
        $request->validate([
            'service_id' => ['required', 'exists:service_catalog,id'],
            'insurance_id' => ['nullable', 'exists:patient_insurances,id'],
        ]);

        $service = ServiceCatalog::with('prices')->findOrFail($request->service_id);

        $insuranceType = null;
        $providerId = null;

        if ($request->insurance_id) {
            $patientInsurance = PatientInsurance::with('insuranceProvider')->find($request->insurance_id);
            if ($patientInsurance) {
                $insuranceType = $patientInsurance->insuranceProvider?->type;
                $providerId = $patientInsurance->insurance_provider_id;
            }
        }

        $price = $service->getPriceForInsurance($insuranceType, $providerId);

        return response()->json([
            'price' => $price,
            'formatted_price' => '₵'.number_format($price, 2),
        ]);
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

    private function formatServiceForJson(ServiceCatalog $service): array
    {
        $typePrices = [];
        $providerPrices = [];

        foreach ($service->prices as $price) {
            if ($price->insurance_provider_id === null) {
                $typePrices[$price->insurance_type] = (float) $price->price;
            } else {
                $providerPrices[$price->insurance_provider_id][$price->insurance_type] = (float) $price->price;
            }
        }

        $departmentType = $service->department?->type ?? $service->department_type;

        return [
            'id' => $service->id,
            'name' => $service->name,
            'code' => $service->code,
            'category' => $service->category,
            'price' => (float) $service->price,
            'formatted_price' => $service->formatted_price,
            'base_price' => (float) $service->price,
            'department_id' => $service->department_id,
            'department_type' => $departmentType instanceof \UnitEnum ? $departmentType->value : (string) $departmentType,
            'type_prices' => $typePrices,
            'provider_prices' => $providerPrices,
        ];
    }
}
