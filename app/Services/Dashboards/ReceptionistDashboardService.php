<?php

namespace App\Services\Dashboards;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\QueueEntry;
use App\Models\Visit;
use App\Services\Dashboards\Concerns\BuildsPressure;
use App\Services\Journey\JourneyBottleneckService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * Metrics for the modern Receptionist dashboard. Read-only aggregate queries —
 * no state is mutated and no activity is logged.
 */
class ReceptionistDashboardService
{
    use BuildsPressure;

    public function __construct(private readonly JourneyBottleneckService $bottlenecks) {}

    /** @return array<string, mixed> */
    public function build(): array
    {
        return [
            'insight' => $this->flowInsight(),
            'pressure' => $this->queuePressure(),
            'kpis' => $this->kpis(),
            'footfall' => $this->footfall(),
            'channels' => $this->channels(),
            'todaysAppointments' => Appointment::with(['patient:id,patient_number,first_name,last_name,other_names', 'doctor:id,first_name,last_name'])
                ->whereDate('appointment_date', today())
                ->orderBy('start_time')
                ->take(5)
                ->get(),
            'liveQueue' => QueueEntry::with(['visit.patient:id,patient_number,first_name,last_name,other_names'])
                ->whereDate('created_at', today())
                ->whereIn('status', ['waiting', 'called', 'serving'])
                ->orderByRaw("CASE status WHEN 'serving' THEN 0 WHEN 'called' THEN 1 ELSE 2 END")
                ->orderBy('id')
                ->take(4)
                ->get(),
            'payments' => $this->paymentsToday(),
            'appointmentRequests' => Appointment::with(['patient:id,patient_number,first_name,last_name,other_names', 'doctor:id,first_name,last_name'])
                ->where('status', AppointmentStatus::SCHEDULED->value)
                ->whereDate('appointment_date', '>=', today())
                ->orderBy('appointment_date')
                ->orderBy('start_time')
                ->take(5)
                ->get(),
        ];
    }

    /**
     * Hospital-wide flow insight: the worst current bottleneck + most common
     * delay cause (counts only — no patient data). Optional context; never throws.
     *
     * @return array<string, mixed>|null
     */
    private function flowInsight(): ?array
    {
        try {
            $summary = $this->bottlenecks->summary();
            $worst = $summary['worst'] ?? null;
            if ($worst === null || ($worst['delayed'] ?? 0) < 1) {
                return null;
            }

            $cause = $summary['most_common_cause'] ?? null;

            return [
                'variant' => 'warning',
                'icon' => 'ti-route',
                'title' => __('journey.insight.title'),
                'badge' => __('journey.insight.delayed_patients', ['count' => $worst['delayed']]),
                'cause' => $cause ? ['icon' => $cause->icon(), 'label' => $cause->translatedLabel()] : null,
                'action' => $cause?->action(),
                'link' => Route::has('admin.journey.worklist')
                    ? ['url' => route('admin.journey.worklist'), 'label' => __('journey.insight.view_worklist')]
                    : null,
            ];
        } catch (Throwable) {
            return null;
        }
    }

    /** @return array<string, mixed> */
    private function queuePressure(): array
    {
        $waiting = QueueEntry::whereDate('created_at', today())->where('status', 'waiting')->count();
        $serving = QueueEntry::whereDate('created_at', today())->whereIn('status', ['called', 'serving'])->count();
        $served = QueueEntry::whereDate('created_at', today())->where('status', 'completed')->count();

        return $this->pressure(
            __('dashboards.department.widget.waiting_pressure'),
            'ti-users-group',
            $waiting,
            [8, 18, 30],
            [
                __('dashboards.department.widget.metric.waiting', ['count' => $waiting]),
                __('role_dashboards.receptionist.being_served_now', ['count' => $serving]),
                __('dashboards.department.widget.metric.completed', ['count' => $served]),
            ],
        );
    }

    /** @return array<string, array<string, mixed>> */
    private function kpis(): array
    {
        $apptToday = Appointment::whereDate('appointment_date', today())->count();
        $apptYesterday = Appointment::whereDate('appointment_date', today()->subDay())->count();

        $checkedToday = Visit::whereDate('visit_date', today())->whereNotNull('checked_in_at')->count();
        $checkedYesterday = Visit::whereDate('visit_date', today()->subDay())->whereNotNull('checked_in_at')->count();

        return [
            'appointments' => ['value' => $apptToday, 'trend' => $this->trend($apptToday, $apptYesterday)],
            'checked_in' => ['value' => $checkedToday, 'trend' => $this->trend($checkedToday, $checkedYesterday)],
            'waiting' => ['value' => QueueEntry::whereDate('created_at', today())->where('status', 'waiting')->count(), 'trend' => null],
            'pending_requests' => [
                'value' => Appointment::where('status', AppointmentStatus::SCHEDULED->value)
                    ->whereDate('appointment_date', '>=', today())->count(),
                'trend' => null,
            ],
        ];
    }

    /** Hourly checked-in vs completed today (8AM–6PM). @return array<string, mixed> */
    private function footfall(): array
    {
        // Per-hour buckets computed in PHP to stay driver-agnostic (sqlite/mysql).
        $checkedIn = $this->hourBuckets('checked_in_at');
        $completed = $this->hourBuckets('completed_at');

        $labels = [];
        $inSeries = [];
        $doneSeries = [];
        for ($h = 8; $h <= 18; $h++) {
            $labels[] = date('gA', mktime($h, 0));
            $inSeries[] = $checkedIn[$h] ?? 0;
            $doneSeries[] = $completed[$h] ?? 0;
        }

        return ['labels' => $labels, 'checked_in' => $inSeries, 'completed' => $doneSeries];
    }

    /** @return array<int, int> hour => count */
    private function hourBuckets(string $column): array
    {
        return Visit::whereDate('visit_date', today())
            ->whereNotNull($column)
            ->pluck($column)
            ->countBy(fn ($ts) => (int) \Illuminate\Support\Carbon::parse($ts)->format('G'))
            ->all();
    }

    /** Today's visits grouped by source. @return array<string, int> */
    private function channels(): array
    {
        return Visit::whereDate('visit_date', today())
            ->select('visit_source', DB::raw('COUNT(*) as c'))
            ->groupBy('visit_source')
            ->pluck('c', 'visit_source')
            ->mapWithKeys(fn ($c, $src) => [ucfirst(str_replace('_', ' ', (string) ($src ?: 'direct'))) => (int) $c])
            ->all();
    }

    /** @return array{total: float, methods: array<string, float>} */
    private function paymentsToday(): array
    {
        $methods = Payment::whereDate('created_at', today())
            ->where('status', '!=', 'voided')
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method')
            ->mapWithKeys(fn ($t, $m) => [ucfirst(str_replace('_', ' ', (string) $m)) => (float) $t])
            ->all();

        return ['total' => array_sum($methods), 'methods' => $methods];
    }

    private function trend(int $current, int $previous): ?int
    {
        if ($previous === 0) {
            return $current > 0 ? 100 : null;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }
}
