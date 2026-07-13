<?php

namespace App\Services\Dashboards;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\LabRequest;
use App\Models\Prescription;
use App\Models\User;
use App\Models\VisitConsultationRoute;
use App\Services\Dashboards\Concerns\BuildsPressure;
use App\Services\Journey\JourneyBottleneckService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * Metrics for the modern Doctor dashboard (scoped to one doctor). Read-only.
 *
 * Workflow-first: the doctor's day in UHMS revolves around the CONSULTATION
 * QUEUE (waiting patients → consult → order labs/prescriptions → review
 * results). Appointments are supporting context, not the focal point.
 */
class DoctorDashboardService
{
    use BuildsPressure;

    public function __construct(private readonly JourneyBottleneckService $bottlenecks) {}

    /** @return array<string, mixed> */
    public function build(User $doctor): array
    {
        // "My work" — anything this doctor is/was personally responsible for.
        // Routes are often unassigned until started, so started_by/completed_by
        // count as personal work too.
        $mine = fn () => VisitConsultationRoute::where(fn ($q) => $q
            ->where('doctor_id', $doctor->id)
            ->orWhere('main_doctor_id', $doctor->id)
            ->orWhere('started_by', $doctor->id)
            ->orWhere('completed_by', $doctor->id));

        // The working queue — mirrors the consultation workbench visibility rule:
        // a doctor sees their DEPARTMENT's route pool (routes may be unassigned),
        // plus anything explicitly assigned to them in other departments.
        $pool = fn () => VisitConsultationRoute::where(function ($q) use ($doctor) {
            $q->where('doctor_id', $doctor->id)->orWhere('main_doctor_id', $doctor->id);
            if ($doctor->department_id) {
                $q->orWhere('department_id', $doctor->department_id);
            }
        });

        return [
            'doctor' => $doctor,
            'insight' => $this->flowInsight($doctor, $pool),
            'pressure' => $this->waitingPressure($pool),
            'kpis' => $this->kpis($doctor, $pool, $mine),
            'nextPatient' => $this->nextPatient($pool),
            'activity' => $this->activity($doctor, $mine),
            'miniStats' => $this->miniStats($doctor, $mine),
            'queue' => $pool()->with([
                'patient:id,patient_number,first_name,last_name,other_names',
                'visit:id,visit_number,chief_complaint,priority,triage_score,checked_in_at,created_at',
            ])
                ->whereIn('status', [VisitConsultationRoute::STATUS_ACTIVE, VisitConsultationRoute::STATUS_PENDING, VisitConsultationRoute::STATUS_PAUSED])
                ->orderByRaw("CASE status WHEN 'ACTIVE' THEN 0 WHEN 'PAUSED' THEN 1 ELSE 2 END")
                ->orderBy('created_at')
                ->take(6)
                ->get(),
            'resultsToReview' => LabRequest::with([
                'patient:id,patient_number,first_name,last_name,other_names',
            ])->withCount('items')
                ->where('requested_by', $doctor->id)
                ->where('status', 'completed')
                ->latest('updated_at')
                ->take(4)
                ->get(),
            'todaySchedule' => Appointment::where('doctor_id', $doctor->id)
                ->with('patient:id,first_name,last_name,other_names')
                ->whereDate('appointment_date', today())
                ->whereNotIn('status', [AppointmentStatus::CANCELLED->value, AppointmentStatus::NO_SHOW->value])
                ->orderBy('start_time')->take(6)->get(),
            'outcomes' => $this->outcomes($pool),
            'recentPrescriptions' => Prescription::withCount('items')
                ->with('patient:id,patient_number,first_name,last_name,other_names')
                ->where(fn ($q) => $q->where('doctor_id', $doctor->id)->orWhere('created_by', $doctor->id))
                ->latest()
                ->take(5)
                ->get(),
        ];
    }

    /**
     * Patient-flow bottleneck banner for the doctor's current department. Falls
     * back silently — insight is optional context, never an error source.
     *
     * @return array<string, mixed>|null
     */
    private function flowInsight(User $doctor, callable $routes): ?array
    {
        try {
            $departmentId = $routes()
                ->whereIn('status', [VisitConsultationRoute::STATUS_PENDING, VisitConsultationRoute::STATUS_ACTIVE, VisitConsultationRoute::STATUS_PAUSED])
                ->select('department_id', DB::raw('COUNT(*) as c'))
                ->whereNotNull('department_id')
                ->groupBy('department_id')->orderByDesc('c')->value('department_id')
                ?? $routes()->whereNotNull('department_id')->latest('id')->value('department_id');

            $insight = $departmentId
                ? $this->bottlenecks->insightForUser($doctor, (int) $departmentId, 'consultation')
                : null;
            if (empty($insight) || ($insight['delayed'] ?? 0) < 1) {
                return null;
            }

            $cause = $insight['top_cause'] ?? null;

            return [
                'variant' => ($insight['severity'] ?? null) === 'critical' ? 'danger' : 'warning',
                'icon' => 'ti-route',
                'title' => __('journey.insight.title'),
                'badge' => __('journey.insight.delayed_patients', ['count' => $insight['delayed']]),
                'cause' => $cause ? ['icon' => $cause->icon(), 'label' => $cause->translatedLabel()] : null,
                'action' => $cause?->action(),
                'link' => Route::has('admin.journey.worklist')
                    ? ['url' => route('admin.journey.worklist', ['department_id' => $departmentId]), 'label' => __('journey.insight.view_worklist')]
                    : null,
            ];
        } catch (Throwable) {
            return null;
        }
    }

    /** @return array<string, mixed> */
    private function waitingPressure(callable $routes): array
    {
        $waiting = $routes()->whereIn('status', [VisitConsultationRoute::STATUS_PENDING, VisitConsultationRoute::STATUS_PAUSED])->count();
        $inConsult = $routes()->where('status', VisitConsultationRoute::STATUS_ACTIVE)->count();
        $completedToday = $routes()->where('status', VisitConsultationRoute::STATUS_COMPLETED)->whereDate('completed_at', today())->count();

        return $this->pressure(
            __('dashboards.department.widget.waiting_pressure'),
            'ti-users-group',
            $waiting,
            [5, 12, 20],
            [
                __('dashboards.department.widget.metric.waiting', ['count' => $waiting]),
                __('role_dashboards.doctor.in_consultation_now', ['count' => $inConsult]),
                __('dashboards.department.widget.metric.completed', ['count' => $completedToday]),
            ],
        );
    }

    /** @return array<string, array<string, mixed>> */
    private function kpis(User $doctor, callable $pool, callable $mine): array
    {
        $completedToday = $mine()->where('status', VisitConsultationRoute::STATUS_COMPLETED)
            ->whereDate('completed_at', today())->count();
        $completedYesterday = $mine()->where('status', VisitConsultationRoute::STATUS_COMPLETED)
            ->whereDate('completed_at', today()->subDay())->count();

        $myRx = fn () => Prescription::where(fn ($q) => $q->where('doctor_id', $doctor->id)->orWhere('created_by', $doctor->id));

        return [
            'waiting' => [
                'value' => $pool()->whereIn('status', [VisitConsultationRoute::STATUS_PENDING, VisitConsultationRoute::STATUS_PAUSED])->count(),
                'active' => $pool()->where('status', VisitConsultationRoute::STATUS_ACTIVE)->count(),
            ],
            'consultations' => [
                'value' => $completedToday,
                'trend' => $this->trend($completedToday, $completedYesterday),
                'spark' => $this->spark($mine()->where('status', VisitConsultationRoute::STATUS_COMPLETED), 'completed_at'),
            ],
            'results' => [
                'value' => LabRequest::where('requested_by', $doctor->id)->where('status', 'completed')
                    ->where('updated_at', '>=', now()->subDays(14))->count(),
                'spark' => $this->spark(LabRequest::where('requested_by', $doctor->id)->where('status', 'completed'), 'updated_at'),
            ],
            'prescriptions' => [
                'value' => $myRx()->whereDate('created_at', today())->count(),
                'spark' => $this->spark($myRx(), 'created_at'),
            ],
        ];
    }

    /** Next patient to be seen: an ACTIVE/PAUSED route first, else the oldest PENDING. */
    private function nextPatient(callable $routes): ?array
    {
        $route = $routes()->with([
            'patient:id,patient_number,first_name,last_name,other_names',
            'visit:id,visit_number,chief_complaint,priority,triage_score,checked_in_at,created_at',
            'visit.latestVitals',
            'department:id,name',
        ])
            ->whereIn('status', [VisitConsultationRoute::STATUS_ACTIVE, VisitConsultationRoute::STATUS_PAUSED, VisitConsultationRoute::STATUS_PENDING])
            ->orderByRaw("CASE status WHEN 'ACTIVE' THEN 0 WHEN 'PAUSED' THEN 1 ELSE 2 END")
            ->orderBy('created_at')
            ->first();

        if ($route === null) {
            return null;
        }

        $since = $route->visit?->checked_in_at ?? $route->created_at;

        return [
            'route' => $route,
            'vitals' => $route->visit?->latestVitals,
            'waiting_minutes' => $since ? (int) Carbon::parse($since)->diffInMinutes(now()) : null,
        ];
    }

    /** Last 14 days: consultations completed (bars) + lab requests ordered (line). @return array<string, mixed> */
    private function activity(User $doctor, callable $routes): array
    {
        $completed = $routes()->where('status', VisitConsultationRoute::STATUS_COMPLETED)
            ->where('completed_at', '>=', today()->subDays(13))
            ->pluck('completed_at')
            ->countBy(fn ($d) => Carbon::parse($d)->toDateString());
        $labs = LabRequest::where('requested_by', $doctor->id)
            ->where('created_at', '>=', today()->subDays(13))
            ->pluck('created_at')
            ->countBy(fn ($d) => Carbon::parse($d)->toDateString());

        $labels = [];
        $consultSeries = [];
        $labSeries = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = today()->subDays($i);
            $labels[] = $day->format('d M');
            $consultSeries[] = (int) ($completed[$day->toDateString()] ?? 0);
            $labSeries[] = (int) ($labs[$day->toDateString()] ?? 0);
        }

        return ['labels' => $labels, 'consultations' => $consultSeries, 'labs' => $labSeries];
    }

    /** @return array<string, array<string, mixed>> */
    private function miniStats(User $doctor, callable $routes): array
    {
        // Average consultation duration this week (activated → completed).
        $durations = $routes()->where('status', VisitConsultationRoute::STATUS_COMPLETED)
            ->where('completed_at', '>=', now()->subDays(7))
            ->whereNotNull('activated_at')
            ->get(['activated_at', 'completed_at'])
            ->map(fn ($r) => Carbon::parse($r->activated_at)->diffInMinutes(Carbon::parse($r->completed_at)))
            ->filter(fn ($m) => $m >= 0 && $m <= 240);

        return [
            'patients' => ['value' => $routes()->distinct('patient_id')->count('patient_id')],
            'consults_week' => ['value' => $routes()->where('status', VisitConsultationRoute::STATUS_COMPLETED)->where('completed_at', '>=', now()->subDays(7))->count()],
            'avg_duration' => ['value' => $durations->isNotEmpty() ? round($durations->avg()).'m' : '—'],
            'labs_week' => ['value' => LabRequest::where('requested_by', $doctor->id)->where('created_at', '>=', now()->subDays(7))->count()],
            'rx_week' => ['value' => Prescription::where('doctor_id', $doctor->id)->where('created_at', '>=', now()->subDays(7))->count()],
            'appointments_today' => ['value' => Appointment::where('doctor_id', $doctor->id)->whereDate('appointment_date', today())->whereNotIn('status', [AppointmentStatus::CANCELLED->value])->count()],
        ];
    }

    /** This week's queue outcomes for the donut. @return array<string, int> */
    private function outcomes(callable $routes): array
    {
        $thisWeek = fn () => $routes()->where('created_at', '>=', now()->subDays(7));

        return [
            'completed' => $thisWeek()->where('status', VisitConsultationRoute::STATUS_COMPLETED)->count(),
            'in_progress' => $thisWeek()->whereIn('status', [VisitConsultationRoute::STATUS_ACTIVE, VisitConsultationRoute::STATUS_PAUSED])->count(),
            'waiting' => $thisWeek()->where('status', VisitConsultationRoute::STATUS_PENDING)->count(),
        ];
    }

    /** Events per day for the last 7 days. @return array<int, int> */
    private function spark($query, string $column): array
    {
        $counts = (clone $query)
            ->where($column, '>=', today()->subDays(6)->startOfDay())
            ->pluck($column)
            ->filter()
            ->countBy(fn ($d) => Carbon::parse($d)->toDateString());

        $out = [];
        for ($i = 6; $i >= 0; $i--) {
            $out[] = (int) ($counts[today()->subDays($i)->toDateString()] ?? 0);
        }

        return $out;
    }

    private function trend(int $current, int $previous): ?int
    {
        if ($previous === 0) {
            return $current > 0 ? 100 : null;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }

    /** Pick a sensible doctor for admin preview when the viewer is not a doctor. */
    public function resolveDoctor(User $user, ?int $requestedId): User
    {
        if ($requestedId !== null && $user->can('appointments.view')) {
            return User::findOrFail($requestedId);
        }
        if ($user->hasRole(['Doctor', 'Consultant', 'Specialist', 'Physician Assistant'])) {
            return $user;
        }

        // Routes are often unassigned (doctor_id null) — fall back through the
        // users who actually worked them, then any doctor with a department.
        foreach (['doctor_id', 'main_doctor_id', 'started_by', 'completed_by'] as $column) {
            $busiest = VisitConsultationRoute::select($column, DB::raw('COUNT(*) as c'))
                ->whereNotNull($column)
                ->groupBy($column)->orderByDesc('c')->value($column);
            if ($busiest && ($found = User::find($busiest))) {
                return $found;
            }
        }

        return User::role('Doctor')->whereNotNull('department_id')->first() ?? $user;
    }
}
