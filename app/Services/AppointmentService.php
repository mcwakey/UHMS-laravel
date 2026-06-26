<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\VisitStatus;
use App\Models\Appointment;
use App\Models\PatientInsurance;
use App\Models\ServiceCatalog;
use App\Models\Visit;
use App\Services\VisitService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
    public function __construct(
        private VisitService $visitService,
        private VisitWorkflowService $visitWorkflowService,
        private VisitStatusFlowService $flowService,
    ) {}
    /**
     * List appointments with filters.
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Appointment::with(['patient', 'doctor', 'department', 'visit'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['doctor_id'] ?? null, fn ($q, $d) => $q->byDoctor($d))
            ->when($filters['department_id'] ?? null, fn ($q, $d) => $q->byDepartment($d))
            ->when($filters['date'] ?? null, fn ($q, $d) => $q->whereDate('appointment_date', $d))
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->whereDate('appointment_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->whereDate('appointment_date', '<=', $d));

        $perPage = $filters['per_page'] ?? 10;
        if ($perPage === 'all') {
            $perPage = max((clone $query)->count(), 1);
        }

        return $query
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->paginate((int) $perPage);
    }

    /**
     * Create a new appointment.
     */
    public function create(array $data): Appointment
    {
        $data['appointment_number'] = Appointment::generateAppointmentNumber();
        $data['created_by'] = Auth::id();

        if (empty($data['end_time']) && ! empty($data['start_time'])) {
            $data['end_time'] = date('H:i', strtotime($data['start_time'] . ' +30 minutes'));
        }

        $services = $data['services'] ?? [];
        unset($data['services']);

        $appointment = Appointment::create($data);

        if (! empty($services)) {
            $this->syncServices($appointment, $services);
        }

        return $appointment;
    }

    /**
     * Update an existing appointment.
     */
    public function update(Appointment $appointment, array $data): Appointment
    {
        $servicesWereSubmitted = array_key_exists('_services_present', $data);
        $services = $data['services'] ?? null;
        unset($data['services'], $data['_services_present']);

        $appointment->update($data);

        if ($servicesWereSubmitted || $services !== null) {
            $this->syncServices($appointment, $services ?? []);
        }

        return $appointment->fresh();
    }

    /**
     * Sync appointment services (pivot).
     */
    private function syncServices(Appointment $appointment, array $services): void
    {
        $syncData = [];
        $serviceIds = collect($services)
            ->pluck('service_catalog_id')
            ->filter()
            ->unique()
            ->values();
        $catalogs = $serviceIds->isNotEmpty()
            ? ServiceCatalog::with('prices')->whereIn('id', $serviceIds)->get()->keyBy('id')
            : collect();
        $insurance = $appointment->visit_insurance_id
            ? PatientInsurance::with('insuranceProvider')->find($appointment->visit_insurance_id)
            : null;
        $insuranceType = $insurance?->insuranceProvider?->type;
        $providerId = $insurance?->insurance_provider_id;

        foreach ($services as $svc) {
            $catalog = $catalogs->get($svc['service_catalog_id']);

            if (! $catalog) {
                continue;
            }

            $quantity = max(1, (int) ($svc['quantity'] ?? 1));
            $unitPrice = round($catalog->getPriceForInsurance($insuranceType, $providerId), 2);

            $syncData[$catalog->id] = [
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => round($unitPrice * $quantity, 2),
            ];
        }

        $appointment->services()->sync($syncData);
    }

    /**
     * Transition appointment status.
     */
    public function transition(Appointment $appointment, AppointmentStatus $newStatus): Appointment
    {
        if (! $appointment->status->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from {$appointment->status->label()} to {$newStatus->label()}"
            );
        }

        $appointment->update(['status' => $newStatus]);

        return $appointment;
    }

    /**
     * Cancel an appointment.
     */
    public function cancel(Appointment $appointment, ?string $reason = null): Appointment
    {
        $appointment->update([
            'status' => AppointmentStatus::CANCELLED,
            'cancelled_by' => Auth::id(),
            'cancellation_reason' => $reason,
        ]);

        return $appointment;
    }

    /**
     * Check in a patient and create a visit from the appointment.
     */
    public function checkIn(Appointment $appointment, array $data = []): Appointment
    {
        return DB::transaction(function () use ($appointment, $data) {
            $appointment->load('services');

            if (array_key_exists('visit_insurance_id', $data)) {
                $appointment->visit_insurance_id = $data['visit_insurance_id'];
            }

            // Create a visit from this appointment. The visit-status flow owns
            // the source / attendance_class / initial-status; it starts at
            // SCHEDULED and is then walked scheduled → checked_in (→ queued).
            $visit = Visit::create([
                'visit_number'       => Visit::generateVisitNumber(),
                'patient_id'         => $appointment->patient_id,
                'visit_type'         => $appointment->visit_type,
                'visit_date'         => now(),
                'status'             => VisitStatus::SCHEDULED,
                'priority'           => $appointment->priority ?? 'normal',
                'chief_complaint'    => $appointment->chief_complaint ?? $appointment->reason,
                'notes'              => $appointment->notes,
                'consultation_mode'  => $appointment->consultation_mode ?? 'in_person',
                'visit_insurance_id' => $appointment->visit_insurance_id,
                'insurance_verification_id' => $data['insurance_verification_id'] ?? null,
                'verification_reference_code' => $data['verification_reference_code'] ?? null,
                'created_by'         => Auth::id(),
            ]);

            // visit_source = appointment; compute attendance_class; set the
            // class-driven initial status (created / registered / scheduled).
            $visit = $this->flowService->classifyAndInitialize($visit, 'appointment');
            // Walk to the arrival state — created/registered/scheduled → checked_in.
            $visit = $this->flowService->advanceToArrival($visit);

            // Attach pre-selected appointment services to the new visit
            if ($appointment->services->isNotEmpty()) {
                $this->visitService->attachServices($visit, $appointment->services->map(fn ($svc) => [
                    'service_catalog_id' => $svc->id,
                    'quantity'           => $svc->pivot->quantity,
                    'doctor_id'          => $appointment->doctor_id,
                ])->all());

                $visit = $this->visitWorkflowService->queueForTriage($visit->fresh());
            }

            // Link appointment to visit and mark as checked in
            $appointment->update([
                'status'   => AppointmentStatus::CHECKED_IN,
                'visit_id' => $visit->id,
                'visit_insurance_id' => $appointment->visit_insurance_id,
            ]);

            return $appointment->fresh(['visit']);
        });
    }

    /**
     * Mark appointment as no-show.
     */
    public function markNoShow(Appointment $appointment): Appointment
    {
        $appointment->update(['status' => AppointmentStatus::NO_SHOW]);
        return $appointment;
    }

    /**
     * Check if doctor has conflicting appointments.
     */
    public function hasConflict(int $doctorId, string $date, string $startTime, ?string $endTime = null, ?int $excludeId = null): bool
    {
        $endTime = $endTime ?? date('H:i', strtotime($startTime . ' +30 minutes'));

        return Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $date)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->whereNotIn('status', [
                AppointmentStatus::CANCELLED->value,
                AppointmentStatus::NO_SHOW->value,
            ])
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($inner) use ($startTime, $endTime) {
                    $inner->where('start_time', '<', $endTime)
                          ->where('end_time', '>', $startTime);
                });
            })
            ->exists();
    }

    /**
     * Get today's appointments for a specific doctor.
     */
    public function getDoctorTodayAppointments(int $doctorId): \Illuminate\Database\Eloquent\Collection
    {
        return Appointment::with(['patient', 'department'])
            ->where('doctor_id', $doctorId)
            ->today()
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Get statistics.
     */
    public function getStats(array $filters = []): array
    {
        $baseQuery = Appointment::query()
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->whereDate('appointment_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->whereDate('appointment_date', '<=', $d))
            ->when($filters['doctor_id'] ?? null, fn ($q, $d) => $q->byDoctor($d))
            ->when($filters['department_id'] ?? null, fn ($q, $d) => $q->byDepartment($d));

        return [
            'total_today' => (clone $baseQuery)->count(),
            'scheduled_today' => (clone $baseQuery)->where('status', AppointmentStatus::SCHEDULED)->count(),
            'confirmed_today' => (clone $baseQuery)->where('status', AppointmentStatus::CONFIRMED)->count(),
            'checked_in_today' => (clone $baseQuery)->where('status', AppointmentStatus::CHECKED_IN)->count(),
            'completed_today' => (clone $baseQuery)->where('status', AppointmentStatus::COMPLETED)->count(),
            'no_show_today' => (clone $baseQuery)->where('status', AppointmentStatus::NO_SHOW)->count(),
            'no_show_cancelled_today' => (clone $baseQuery)->whereIn('status', [
                AppointmentStatus::NO_SHOW->value,
                AppointmentStatus::CANCELLED->value,
            ])->count(),
            'upcoming' => Appointment::upcoming()->count(),
        ];
    }

    /**
     * Get appointments for calendar view (grouped by date).
     */
    public function getCalendarData(string $from, string $to, array $filters = []): \Illuminate\Support\Collection
    {
        return Appointment::with(['patient', 'doctor', 'department'])
            ->whereBetween('appointment_date', [$from, $to])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['doctor_id'] ?? null, fn ($q, $d) => $q->byDoctor($d))
            ->when($filters['department_id'] ?? null, fn ($q, $d) => $q->byDepartment($d))
            ->when(empty($filters['status']), fn ($q) => $q->whereNotIn('status', [AppointmentStatus::CANCELLED->value]))
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn ($apt) => $apt->appointment_date->format('Y-m-d'));
    }
}
