<?php

namespace App\Services;

use App\Models\ConsultationSessionContributor;
use App\Models\Department;
use App\Models\EmergencyCase;
use App\Models\EmergencySession;
use App\Models\MedicalRecord;
use App\Models\User;
use App\Models\VisitConsultationRoute;
use App\Models\VisitConsultationRouteLog;
use Illuminate\Support\Facades\DB;

class EmergencySessionService
{
    public function getOrCreateForCase(EmergencyCase $case, ?User $user = null): EmergencySession
    {
        $case->loadMissing(['visit', 'assignedDoctor', 'assignedNurse']);

        return DB::transaction(function () use ($case, $user) {
            $session = $case->emergencySessions()
                ->whereIn('status', [
                    EmergencySession::STATUS_PENDING,
                    EmergencySession::STATUS_ACTIVE,
                    EmergencySession::STATUS_OBSERVATION,
                ])
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $session && in_array($case->emergency_status, [EmergencyCase::STATUS_DISPOSED, EmergencyCase::STATUS_CANCELLED], true)) {
                $session = $case->emergencySessions()
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();
            }

            $departmentId = $this->resolveEmergencyDepartmentId($case);
            $route = $this->getOrCreateConsultationRoute($case, $user, $departmentId, $session);
            $record = $this->getOrCreateMedicalRecord($route, $case, $user);

            if ($session) {
                $session->forceFill([
                    'consultation_route_id' => $route->id,
                    'visit_id' => $case->visit_id,
                    'patient_id' => $case->patient_id,
                    'department_id' => $departmentId,
                    'medical_record_id' => $record->id,
                    'main_doctor_id' => $case->assigned_doctor_id,
                    'primary_nurse_id' => $case->assigned_nurse_id,
                    'status' => $this->emergencySessionStatusForCase($case, $session->status),
                    'started_at' => $session->started_at ?: ($case->arrival_time ?? now()),
                    'started_by' => $session->started_by ?: ($user?->id ?? $case->created_by),
                ])->save();

                return $session->fresh($this->sessionRelations());
            }

            return $case->emergencySessions()->create([
                'visit_id' => $case->visit_id,
                'patient_id' => $case->patient_id,
                'consultation_route_id' => $route->id,
                'department_id' => $departmentId,
                'medical_record_id' => $record->id,
                'main_doctor_id' => $case->assigned_doctor_id,
                'primary_nurse_id' => $case->assigned_nurse_id,
                'status' => $this->emergencySessionStatusForCase($case),
                'started_at' => $case->arrival_time ?? now(),
                'started_by' => $user?->id ?? $case->created_by,
            ])->fresh($this->sessionRelations());
        });
    }

    public function syncTeam(EmergencyCase $case): ?EmergencySession
    {
        $session = $case->activeEmergencySession ?: $case->emergencySessions()->latest('id')->first();

        if (! $session) {
            return null;
        }

        $session->update([
            'main_doctor_id' => $case->assigned_doctor_id,
            'primary_nurse_id' => $case->assigned_nurse_id,
            'status' => $case->emergency_status === EmergencyCase::STATUS_OBSERVATION
                ? EmergencySession::STATUS_OBSERVATION
                : $session->status,
        ]);

        if ($session->consultationRoute) {
            $session->consultationRoute->forceFill([
                'doctor_id' => $case->assigned_doctor_id ?: $session->consultationRoute->doctor_id,
                'main_doctor_id' => $case->assigned_doctor_id,
                'primary_nurse_id' => $case->assigned_nurse_id,
                'status' => $this->consultationRouteStatusForCase($case),
            ])->save();
        }

        return $session->fresh(['mainDoctor', 'primaryNurse', 'consultationRoute']);
    }

    public function recordContribution(EmergencyCase $case, User $user, ?string $role = null): void
    {
        $session = $case->activeEmergencySession
            ?: $case->emergencySessions()->latest('id')->first()
            ?: $this->getOrCreateForCase($case, $user);

        $existingFirstContribution = $session->contributors()
            ->where('user_id', $user->id)
            ->value('first_contributed_at');

        $session->contributors()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'emergency_case_id' => $case->id,
                'role' => $role,
                'first_contributed_at' => $existingFirstContribution ?: now(),
                'last_contributed_at' => now(),
            ],
        );

        if (! $session->consultation_route_id) {
            $session = $this->getOrCreateForCase($case, $user);
        }

        if ($session->consultation_route_id) {
            $existingConsultationContribution = ConsultationSessionContributor::where('consultation_route_id', $session->consultation_route_id)
                ->where('user_id', $user->id)
                ->value('first_contributed_at');

            ConsultationSessionContributor::updateOrCreate(
                [
                    'consultation_route_id' => $session->consultation_route_id,
                    'user_id' => $user->id,
                ],
                [
                    'role' => $role,
                    'first_contributed_at' => $existingConsultationContribution ?: now(),
                    'last_contributed_at' => now(),
                ],
            );
        }
    }

    public function completeForDisposition(EmergencyCase $case, User $user): void
    {
        $session = $case->activeEmergencySession ?: $case->emergencySessions()->latest('id')->first();
        if (! $session) {
            return;
        }

        $session->update([
            'status' => EmergencySession::STATUS_COMPLETED,
            'ended_at' => now(),
            'ended_by' => $user->id,
        ]);

        $route = $session->consultationRoute;
        if ($route && ! in_array($route->status, [VisitConsultationRoute::STATUS_COMPLETED, VisitConsultationRoute::STATUS_CANCELLED], true)) {
            $fromStatus = $route->status;
            $route->forceFill([
                'status' => VisitConsultationRoute::STATUS_COMPLETED,
                'completed_by' => $user->id,
                'completed_at' => now(),
            ])->save();

            $this->logRoute($route, $fromStatus, VisitConsultationRoute::STATUS_COMPLETED, 'completed', 'Emergency case disposed.', $user);
        }

        $this->recordContribution($case, $user, 'Disposition');
    }

    private function getOrCreateConsultationRoute(EmergencyCase $case, ?User $user, ?int $departmentId, ?EmergencySession $session = null): VisitConsultationRoute
    {
        $route = VisitConsultationRoute::query()
            ->where('emergency_case_id', $case->id)
            ->lockForUpdate()
            ->oldest('id')
            ->first();

        if (! $route && $session?->consultation_route_id) {
            $route = VisitConsultationRoute::query()
                ->lockForUpdate()
                ->find($session->consultation_route_id);
        }

        $startedAt = $case->arrival_time ?? now();
        $status = $this->consultationRouteStatusForCase($case);

        if (! $route) {
            $route = VisitConsultationRoute::create([
                'visit_id' => $case->visit_id,
                'patient_id' => $case->patient_id,
                'emergency_case_id' => $case->id,
                'department_id' => $departmentId,
                'service_id' => null,
                'doctor_id' => $case->assigned_doctor_id,
                'main_doctor_id' => $case->assigned_doctor_id,
                'primary_nurse_id' => $case->assigned_nurse_id,
                'session_type' => VisitConsultationRoute::SESSION_TYPE_EMERGENCY,
                'status' => $status,
                'routed_by' => $user?->id ?? $case->created_by,
                'started_by' => $user?->id ?? $case->created_by,
                'started_at' => $startedAt,
                'activated_at' => $startedAt,
                'completed_by' => $status === VisitConsultationRoute::STATUS_COMPLETED ? ($user?->id ?? $case->disposed_by) : null,
                'completed_at' => $status === VisitConsultationRoute::STATUS_COMPLETED ? ($case->disposition_time ?? now()) : null,
                'notes' => $case->initial_condition ?: $case->chief_complaint,
            ]);

            $this->logRoute($route, null, $status, 'routed', 'Emergency Department Session opened from emergency case.', $user);

            return $route->fresh(['department', 'doctor', 'mainDoctor', 'primaryNurse']);
        }

        $updates = [
            'visit_id' => $case->visit_id,
            'patient_id' => $case->patient_id,
            'emergency_case_id' => $case->id,
            'department_id' => $departmentId ?: $route->department_id,
            'session_type' => VisitConsultationRoute::SESSION_TYPE_EMERGENCY,
            'doctor_id' => $case->assigned_doctor_id ?: $route->doctor_id,
            'main_doctor_id' => $case->assigned_doctor_id,
            'primary_nurse_id' => $case->assigned_nurse_id,
            'started_by' => $route->started_by ?: ($user?->id ?? $case->created_by),
            'started_at' => $route->started_at ?: $startedAt,
            'activated_at' => $route->activated_at ?: $startedAt,
            'notes' => $route->notes ?: ($case->initial_condition ?: $case->chief_complaint),
        ];

        if ($status !== $route->status && ! in_array($route->status, [VisitConsultationRoute::STATUS_COMPLETED, VisitConsultationRoute::STATUS_CANCELLED], true)) {
            $updates['status'] = $status;
            if ($status === VisitConsultationRoute::STATUS_COMPLETED) {
                $updates['completed_by'] = $user?->id ?? $case->disposed_by;
                $updates['completed_at'] = $case->disposition_time ?? now();
            }
            if ($status === VisitConsultationRoute::STATUS_CANCELLED) {
                $updates['cancelled_by'] = $user?->id;
                $updates['cancelled_at'] = now();
                $updates['cancellation_reason'] = 'Emergency case cancelled.';
            }
        }

        $route->forceFill($updates)->save();

        return $route->fresh(['department', 'doctor', 'mainDoctor', 'primaryNurse']);
    }

    private function getOrCreateMedicalRecord(VisitConsultationRoute $route, EmergencyCase $case, ?User $user): MedicalRecord
    {
        $record = MedicalRecord::where('consultation_route_id', $route->id)->first();
        $doctorId = $route->doctor_id ?: $case->assigned_doctor_id ?: $user?->id ?: $case->created_by ?: User::query()->oldest('id')->value('id');

        if (! $doctorId) {
            throw new \RuntimeException('Emergency consultation sessions require at least one user to own the medical record.');
        }

        if (! $record) {
            return MedicalRecord::create([
                'visit_id' => $case->visit_id,
                'patient_id' => $case->patient_id,
                'doctor_id' => $doctorId,
                'department_id' => $route->department_id,
                'service_id' => $route->service_id,
                'consultation_route_id' => $route->id,
            ]);
        }

        $record->forceFill([
            'visit_id' => $case->visit_id,
            'patient_id' => $case->patient_id,
            'doctor_id' => $record->doctor_id ?: $doctorId,
            'department_id' => $route->department_id,
            'service_id' => $route->service_id,
            'consultation_route_id' => $route->id,
        ])->save();

        return $record->fresh(['doctor', 'department', 'service', 'consultationRoute']);
    }

    private function resolveEmergencyDepartmentId(EmergencyCase $case): ?int
    {
        $currentDepartment = $case->visit?->current_department_id
            ? Department::find($case->visit->current_department_id)
            : null;

        if ($currentDepartment && $this->isEmergencyDepartment($currentDepartment)) {
            return $currentDepartment->id;
        }

        $department = Department::query()
            ->where(function ($query) {
                $query->whereRaw('LOWER(code) IN (?, ?, ?, ?, ?)', ['er', 'ed', 'emr', 'emer', 'emergency'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%emergency%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%casualty%'])
                    ->orWhereRaw('LOWER(type) = ?', ['emergency']);
            })
            ->orderByRaw("CASE WHEN LOWER(code) IN ('er', 'ed', 'emr') THEN 0 ELSE 1 END")
            ->oldest('id')
            ->first();

        return $department?->id ?? $case->visit?->current_department_id;
    }

    private function isEmergencyDepartment(Department $department): bool
    {
        $code = strtolower((string) $department->code);
        $name = strtolower((string) $department->name);
        $typeValue = $department->type instanceof \BackedEnum
            ? $department->type->value
            : (string) $department->type;
        $type = strtolower($typeValue);

        return in_array($code, ['er', 'ed', 'emr', 'emer', 'emergency'], true)
            || str_contains($name, 'emergency')
            || str_contains($name, 'casualty')
            || $type === 'emergency';
    }

    private function consultationRouteStatusForCase(EmergencyCase $case): string
    {
        return match ($case->emergency_status) {
            EmergencyCase::STATUS_DISPOSED => VisitConsultationRoute::STATUS_COMPLETED,
            EmergencyCase::STATUS_CANCELLED => VisitConsultationRoute::STATUS_CANCELLED,
            default => VisitConsultationRoute::STATUS_ACTIVE,
        };
    }

    private function emergencySessionStatusForCase(EmergencyCase $case, ?string $currentStatus = null): string
    {
        if ($case->emergency_status === EmergencyCase::STATUS_DISPOSED) {
            return EmergencySession::STATUS_COMPLETED;
        }

        if ($case->emergency_status === EmergencyCase::STATUS_CANCELLED) {
            return EmergencySession::STATUS_CANCELLED;
        }

        if ($case->emergency_status === EmergencyCase::STATUS_OBSERVATION) {
            return EmergencySession::STATUS_OBSERVATION;
        }

        return in_array($currentStatus, [EmergencySession::STATUS_PENDING, EmergencySession::STATUS_OBSERVATION], true)
            ? $currentStatus
            : EmergencySession::STATUS_ACTIVE;
    }

    private function logRoute(VisitConsultationRoute $route, ?string $fromStatus, string $toStatus, string $action, ?string $notes, ?User $user): void
    {
        VisitConsultationRouteLog::create([
            'visit_consultation_route_id' => $route->id,
            'visit_id' => $route->visit_id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'action' => $action,
            'notes' => $notes,
            'performed_by' => $user?->id,
        ]);
    }

    private function sessionRelations(): array
    {
        return [
            'consultationRoute.department',
            'consultationRoute.medicalRecord',
            'medicalRecord',
            'mainDoctor',
            'primaryNurse',
            'contributors.user',
        ];
    }
}