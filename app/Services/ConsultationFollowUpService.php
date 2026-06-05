<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\DepartmentType;
use App\Enums\LogModule;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\MedicalRecord;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use Illuminate\Support\Facades\DB;

class ConsultationFollowUpService
{
    public function __construct(
        private AppointmentService $appointments,
        private ActivityLogService $logger,
    ) {}

    public function create(Visit $visit, VisitConsultationRoute $route, ?MedicalRecord $record, array $data, User $user): Appointment
    {
        $this->assertRouteBelongsToVisit($visit, $route);
        $this->assertRouteCanBeModified($route, $user);

        return DB::transaction(function () use ($visit, $route, $record, $data, $user) {
            $payload = $this->appointmentPayload($visit, $route, $record, $data);
            $appointment = $this->appointments->create($payload)->fresh([
                'department',
                'doctor',
                'services',
                'consultationRoute',
                'medicalRecord',
                'createdByUser',
            ]);

            $this->logger->log(
                LogModule::CONSULTATION,
                'FOLLOW_UP_APPOINTMENT_CREATED',
                [
                    'patient_id' => $visit->patient_id,
                    'visit_id' => $visit->id,
                    'consultation_route_id' => $route->id,
                    'medical_record_id' => $record?->id,
                    'appointment_id' => $appointment->id,
                    'department_id' => $appointment->department_id,
                    'doctor_id' => $appointment->doctor_id,
                    'created_by' => $user->id,
                    'new_values' => $this->snapshot($appointment),
                    'metadata' => [
                        'notify_patient_requested' => (bool) ($data['notify_patient'] ?? false),
                    ],
                ],
                $appointment,
                'Next appointment set: '.$appointment->appointment_date?->format('d M Y').', '.($appointment->department?->name ?? 'department pending'),
            );

            return $appointment;
        });
    }

    public function update(Appointment $appointment, Visit $visit, VisitConsultationRoute $route, ?MedicalRecord $record, array $data, User $user): Appointment
    {
        $this->assertAppointmentContext($appointment, $visit, $route);
        $this->assertRouteCanBeModified($route, $user);

        return DB::transaction(function () use ($appointment, $visit, $route, $record, $data, $user) {
            $old = $this->snapshot($appointment->fresh(['department', 'doctor', 'services']));
            $payload = $this->appointmentPayload($visit, $route, $record, $data);
            unset($payload['patient_id'], $payload['visit_id'], $payload['consultation_route_id'], $payload['medical_record_id']);

            $appointment = $this->appointments->update($appointment, $payload)->fresh([
                'department',
                'doctor',
                'services',
                'consultationRoute',
                'medicalRecord',
                'createdByUser',
            ]);

            $this->logger->log(
                LogModule::CONSULTATION,
                'FOLLOW_UP_APPOINTMENT_UPDATED',
                [
                    'patient_id' => $visit->patient_id,
                    'visit_id' => $visit->id,
                    'consultation_route_id' => $route->id,
                    'medical_record_id' => $record?->id,
                    'appointment_id' => $appointment->id,
                    'department_id' => $appointment->department_id,
                    'doctor_id' => $appointment->doctor_id,
                    'created_by' => $user->id,
                    'old_values' => $old,
                    'new_values' => $this->snapshot($appointment),
                    'metadata' => [
                        'notify_patient_requested' => (bool) ($data['notify_patient'] ?? false),
                    ],
                ],
                $appointment,
                'Next appointment updated: '.$appointment->appointment_date?->format('d M Y').', '.($appointment->department?->name ?? 'department pending'),
            );

            return $appointment;
        });
    }

    public function cancel(Appointment $appointment, Visit $visit, VisitConsultationRoute $route, string $reason, User $user): Appointment
    {
        $this->assertAppointmentContext($appointment, $visit, $route);
        $this->assertRouteCanBeModified($route, $user);

        return DB::transaction(function () use ($appointment, $visit, $route, $reason, $user) {
            $old = $this->snapshot($appointment->fresh(['department', 'doctor', 'services']));
            $appointment = $this->appointments->cancel($appointment, $reason)->fresh([
                'department',
                'doctor',
                'services',
                'consultationRoute',
                'medicalRecord',
                'createdByUser',
            ]);

            $this->logger->log(
                LogModule::CONSULTATION,
                'FOLLOW_UP_APPOINTMENT_CANCELLED',
                [
                    'patient_id' => $visit->patient_id,
                    'visit_id' => $visit->id,
                    'consultation_route_id' => $route->id,
                    'medical_record_id' => $route->medicalRecord?->id,
                    'appointment_id' => $appointment->id,
                    'department_id' => $appointment->department_id,
                    'doctor_id' => $appointment->doctor_id,
                    'created_by' => $user->id,
                    'reason' => $reason,
                    'old_values' => $old,
                    'new_values' => $this->snapshot($appointment),
                ],
                $appointment,
                'Next appointment cancelled: '.$reason,
            );

            return $appointment;
        });
    }

    private function appointmentPayload(Visit $visit, VisitConsultationRoute $route, ?MedicalRecord $record, array $data): array
    {
        $departmentId = (int) ($data['department_id'] ?? $route->department_id);
        $serviceId = $data['service_id'] ?? null;

        $department = Department::query()
            ->whereKey($departmentId)
            ->where('type', DepartmentType::CONSULTATION->value)
            ->first();

        if (! $department) {
            throw new \InvalidArgumentException('Selected follow-up department must be a consultation department.');
        }

        if ($serviceId) {
            $belongs = ServiceCatalog::query()
                ->active()
                ->whereKey($serviceId)
                ->where(function ($query) use ($departmentId) {
                    $query->where('department_id', $departmentId)
                        ->orWhereHas('specialties', fn ($specialties) => $specialties->where('specialties.department_id', $departmentId));
                })
                ->exists();

            if (! $belongs) {
                throw new \InvalidArgumentException('Selected service does not belong to the selected follow-up department.');
            }
        }

        return [
            'patient_id' => $visit->patient_id,
            'visit_id' => $visit->id,
            'consultation_route_id' => $route->id,
            'medical_record_id' => $record?->id,
            'department_id' => $departmentId,
            'doctor_id' => $data['doctor_id'] ?? null,
            'appointment_date' => $data['appointment_date'],
            'start_time' => ($data['start_time'] ?? null) ?: '09:00',
            'end_time' => $data['end_time'] ?? null,
            'visit_type' => $visit->visit_type?->value ?? 'outpatient',
            'priority' => $data['priority'] ?? 'normal',
            'chief_complaint' => $data['reason'] ?? $visit->chief_complaint,
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'consultation_mode' => 'in_person',
            'visit_insurance_id' => $visit->visit_insurance_id,
            'status' => AppointmentStatus::SCHEDULED,
            'services' => $serviceId ? [[
                'service_catalog_id' => (int) $serviceId,
                'quantity' => 1,
            ]] : [],
        ];
    }

    private function assertRouteBelongsToVisit(Visit $visit, VisitConsultationRoute $route): void
    {
        if ((int) $route->visit_id !== (int) $visit->id) {
            throw new \InvalidArgumentException('Consultation session does not belong to this visit.');
        }
    }

    private function assertAppointmentContext(Appointment $appointment, Visit $visit, VisitConsultationRoute $route): void
    {
        $this->assertRouteBelongsToVisit($visit, $route);

        if ((int) $appointment->visit_id !== (int) $visit->id || (int) $appointment->consultation_route_id !== (int) $route->id) {
            throw new \InvalidArgumentException('Follow-up appointment does not belong to this consultation session.');
        }
    }

    private function assertRouteCanBeModified(VisitConsultationRoute $route, User $user): void
    {
        if (! $route->isLocked() && ! in_array($route->status, [
            VisitConsultationRoute::STATUS_COMPLETED,
            VisitConsultationRoute::STATUS_CANCELLED,
        ], true)) {
            return;
        }

        if ($user->can('consultation.entries.correct_completed') || $user->can('visits.reopen_locked_session')) {
            return;
        }

        throw new \RuntimeException('This consultation session is locked or completed. Follow-up appointment changes require correction permission.');
    }

    private function snapshot(Appointment $appointment): array
    {
        $appointment->loadMissing(['department', 'doctor', 'services']);

        return [
            'appointment_date' => $appointment->appointment_date?->toDateString(),
            'start_time' => $appointment->start_time,
            'end_time' => $appointment->end_time,
            'department_id' => $appointment->department_id,
            'department' => $appointment->department?->name,
            'doctor_id' => $appointment->doctor_id,
            'doctor' => $appointment->doctor?->full_name,
            'service_ids' => $appointment->services->pluck('id')->values()->all(),
            'reason' => $appointment->reason,
            'notes' => $appointment->notes,
            'priority' => $appointment->priority,
            'status' => $appointment->status?->value ?? $appointment->status,
        ];
    }
}
