<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\VisitStatus;
use App\Models\Appointment;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

/**
 * Attendance / visit-flow reporting. Built entirely on the dynamic scopes
 * (scopeSource / scopeAttendanceClass / scopeStatus / scopeBetweenVisitDates),
 * so reports can slice by date range, department, doctor, source, and
 * attendance class without hardcoding scenarios.
 */
class VisitStatisticsService
{
    /** Statuses that do NOT represent an actual attendance. */
    private const NON_ATTENDANCE = [
        VisitStatus::CANCELLED->value,
        VisitStatus::NO_SHOW->value,
        VisitStatus::RESCHEDULED->value,
    ];

    /**
     * @param array{date_from?:?string,date_to?:?string,department_id?:?int,doctor_id?:?int,visit_source?:?string,attendance_class?:?string} $filters
     */
    public function attendanceReport(array $filters = []): array
    {
        $from = $filters['date_from'] ?? null;
        $to = $filters['date_to'] ?? null;

        // Fresh, fully-filtered visit query each time (avoids clone pitfalls).
        $visits = fn () => Visit::query()
            ->betweenVisitDates($from, $to)
            ->when($filters['department_id'] ?? null, fn ($q, $d) => $q->where('current_department_id', $d))
            ->when($filters['doctor_id'] ?? null, fn ($q, $d) => $q->whereHas('consultationRoutes', fn ($r) => $r->where('doctor_id', $d)))
            ->when($filters['visit_source'] ?? null, fn ($q, $s) => $q->source($s))
            ->when($filters['attendance_class'] ?? null, fn ($q, $c) => $q->attendanceClass($c));

        return [
            'total' => $visits()->count(),

            // By visit_source
            'direct' => $visits()->source('direct')->count(),
            'appointment' => $visits()->source('appointment')->count(),
            'emergency' => $visits()->source('emergency')->count(),

            // By attendance_class
            'first_ever' => $visits()->attendanceClass('first_ever')->count(),
            'first_attendance_of_year' => $visits()->attendanceClass('first_attendance_of_year')->count(),
            'subsequent_attendance' => $visits()->attendanceClass('subsequent_attendance')->count(),

            // Operational
            'checked_in' => $visits()->whereNotNull('checked_in_at')->count(),
            // Every appointment-sourced visit exists because the patient came.
            'appointment_came' => $visits()->source('appointment')->count(),
            'cancelled_visits' => $visits()->status(VisitStatus::CANCELLED->value)->count(),

            // Patients who attended more than once in the period.
            'multi_attendance_patients' => $visits()
                ->whereNotIn('status', self::NON_ATTENDANCE)
                ->select('patient_id', DB::raw('COUNT(*) as c'))
                ->groupBy('patient_id')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->count(),

            // Breakdowns
            'by_source' => $visits()
                ->select('visit_source', DB::raw('COUNT(*) as total'))
                ->groupBy('visit_source')
                ->pluck('total', 'visit_source')
                ->toArray(),
            'by_attendance_class' => $visits()
                ->select('attendance_class', DB::raw('COUNT(*) as total'))
                ->groupBy('attendance_class')
                ->pluck('total', 'attendance_class')
                ->toArray(),
            'by_status' => $visits()
                ->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray(),

            // Appointment-side counts (a no-show/cancelled appointment never
            // becomes a visit, so these come from the Appointment model).
            'appointment_no_shows' => $this->appointmentCount($from, $to, $filters, AppointmentStatus::NO_SHOW),
            'cancelled_appointments' => $this->appointmentCount($from, $to, $filters, AppointmentStatus::CANCELLED),
        ];
    }

    private function appointmentCount(?string $from, ?string $to, array $filters, AppointmentStatus $status): int
    {
        return Appointment::query()
            ->where('status', $status->value)
            ->when($from, fn ($q, $d) => $q->whereDate('appointment_date', '>=', $d))
            ->when($to, fn ($q, $d) => $q->whereDate('appointment_date', '<=', $d))
            ->when($filters['department_id'] ?? null, fn ($q, $d) => $q->where('department_id', $d))
            ->when($filters['doctor_id'] ?? null, fn ($q, $d) => $q->where('doctor_id', $d))
            ->count();
    }
}
