<?php

namespace App\Services;

use App\Models\MedicalRecord;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ConsultationSessionService
{
    public function getCurrentSession(Visit $visit): ?VisitConsultationRoute
    {
        return $visit->consultationRoutes()
            ->with(['department', 'service', 'doctor', 'medicalRecord'])
            ->orderByRaw("CASE status WHEN 'ACTIVE' THEN 0 WHEN 'PENDING' THEN 1 WHEN 'PAUSED' THEN 2 WHEN 'COMPLETED' THEN 3 ELSE 4 END")
            ->oldest()
            ->first();
    }

    public function getAllSessionsForVisit(Visit $visit): Collection
    {
        return $visit->consultationRoutes()
            ->with(['department', 'service', 'doctor', 'medicalRecord.doctor', 'logs.performedBy'])
            ->orderByRaw("CASE status WHEN 'ACTIVE' THEN 0 WHEN 'PENDING' THEN 1 WHEN 'PAUSED' THEN 2 WHEN 'COMPLETED' THEN 3 ELSE 4 END")
            ->oldest()
            ->get();
    }

    public function getOrCreateMedicalRecordForRoute(VisitConsultationRoute $route, User $user): MedicalRecord
    {
        $route->loadMissing(['visit', 'patient']);
        $visit = $route->visit;

        return DB::transaction(function () use ($route, $visit, $user) {
            $record = MedicalRecord::where('consultation_route_id', $route->id)->first();
            if ($record) {
                return $this->syncRecordContext($record, $route, $user);
            }

            $legacyRecord = null;
            if ($visit->medicalRecords()->count() === 1) {
                $legacyRecord = $visit->medicalRecords()
                    ->whereNull('consultation_route_id')
                    ->first();
            }

            if ($legacyRecord) {
                return $this->syncRecordContext($legacyRecord, $route, $user);
            }

            return MedicalRecord::create([
                'visit_id' => $route->visit_id,
                'patient_id' => $route->patient_id,
                'doctor_id' => $route->doctor_id ?: $user->id,
                'department_id' => $route->department_id,
                'service_id' => $route->service_id,
                'consultation_route_id' => $route->id,
            ]);
        });
    }

    public function resolveRouteForVisit(Visit $visit, ?int $routeId = null): ?VisitConsultationRoute
    {
        $query = $visit->consultationRoutes()
            ->with(['department', 'service', 'doctor', 'medicalRecord']);

        if ($routeId) {
            return $query->whereKey($routeId)->first();
        }

        return $query
            ->orderByRaw("CASE status WHEN 'ACTIVE' THEN 0 WHEN 'PENDING' THEN 1 WHEN 'PAUSED' THEN 2 WHEN 'COMPLETED' THEN 3 ELSE 4 END")
            ->oldest()
            ->first();
    }

    private function syncRecordContext(MedicalRecord $record, VisitConsultationRoute $route, User $user): MedicalRecord
    {
        $record->forceFill([
            'doctor_id' => $record->doctor_id ?: ($route->doctor_id ?: $user->id),
            'department_id' => $route->department_id,
            'service_id' => $route->service_id,
            'consultation_route_id' => $route->id,
        ])->save();

        return $record->fresh(['doctor', 'department', 'service', 'consultationRoute']);
    }
}
