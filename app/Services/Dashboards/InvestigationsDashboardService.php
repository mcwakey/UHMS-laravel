<?php

namespace App\Services\Dashboards;

use App\Enums\SampleStatus;
use App\Models\LabRequest;
use App\Models\LabResult;
use App\Models\Sample;
use App\Services\Dashboards\Concerns\BuildsPressure;
use App\Services\InvestigationWorkspaceScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Metrics for the Investigations workspace dashboard. Read-only aggregate
 * queries, all bounded to the active performing (target) department by
 * InvestigationWorkspaceScope.
 */
class InvestigationsDashboardService
{
    use BuildsPressure;

    public function __construct(
        private InvestigationWorkspaceScope $scope,
    ) {}

    /** @return array<string, mixed> */
    public function build(): array
    {
        return [
            'insight' => $this->backlogInsight(),
            'pressure' => $this->diagnosticPressure(),
            'kpis' => $this->kpis(),
            'volumeTrend' => $this->volumeTrend(),
            'sampleStatuses' => $this->sampleStatuses(),
            'recentRequests' => $this->requests()
                ->with([
                    'patient:id,patient_number,first_name,last_name,other_names',
                    'requestedBy:id,first_name,last_name',
                    'items:id,lab_request_id,name,status',
                ])
                ->whereIn('status', ['pending', 'processing'])
                ->orderByRaw("CASE WHEN urgency = 'emergency' THEN 0 WHEN urgency = 'urgent' THEN 1 ELSE 2 END")
                ->latest()
                ->take(6)
                ->get(),
            'awaitingVerification' => $this->unverifiedResults()
                ->with([
                    'requestItem:id,lab_request_id,name,service_id',
                    'labRequest:id,request_number,patient_id',
                    'labRequest.patient:id,patient_number,first_name,last_name,other_names',
                    'performedBy:id,first_name,last_name',
                ])
                ->oldest('performed_at')
                ->take(5)
                ->get(),
        ];
    }

    /**
     * Request backlog banner: pending requests + oldest waiting + urgent count.
     *
     * @return array<string, mixed>|null
     */
    private function backlogInsight(): ?array
    {
        $pendingQuery = $this->requests()->pending();
        $pending = (clone $pendingQuery)->count();
        if ($pending < 1) {
            return null;
        }

        $urgent = (clone $pendingQuery)->whereIn('urgency', ['urgent', 'emergency'])->count();
        $oldest = (clone $pendingQuery)->oldest()->value('created_at');
        $oldestMinutes = $oldest ? (int) Carbon::parse($oldest)->diffInMinutes(now()) : 0;
        $oldestText = $oldestMinutes >= 1440
            ? intdiv($oldestMinutes, 1440).'d'
            : ($oldestMinutes >= 60 ? intdiv($oldestMinutes, 60).'h' : $oldestMinutes.'m');

        return [
            'variant' => ($urgent > 0 || $oldestMinutes >= 120) ? 'danger' : 'warning',
            'icon' => 'ti-test-pipe',
            'title' => __('investigations.dashboard.backlog_insight'),
            'badge' => __('investigations.dashboard.pending_requests_count', ['count' => $pending]),
            'cause' => $urgent > 0
                ? ['icon' => 'ti-urgent', 'label' => __('investigations.dashboard.urgent_waiting', ['count' => $urgent])]
                : ['icon' => 'ti-clock-exclamation', 'label' => __('investigations.dashboard.oldest_waiting', ['time' => $oldestText])],
            'action' => __('investigations.dashboard.action_accept'),
            'link' => ['url' => route('investigations.lab.requests.index'), 'label' => __('investigations.dashboard.open_requests')],
        ];
    }

    /** @return array<string, mixed> */
    private function diagnosticPressure(): array
    {
        $pending = $this->requests()->pending()->count();
        $processing = $this->requests()->processing()->count();
        $awaitingCollection = $this->samples()->where('status', SampleStatus::PENDING)->count();
        $unverified = $this->unverifiedResults()->count();

        return $this->pressure(
            __('investigations.dashboard.diagnostic_load'),
            'ti-microscope',
            $pending + $processing,
            [6, 15, 25],
            [
                __('dashboards.department.widget.metric.pending', ['count' => $pending]),
                __('investigations.dashboard.in_process', ['count' => $processing]),
                __('investigations.dashboard.awaiting_collection', ['count' => $awaitingCollection]),
                __('investigations.dashboard.awaiting_verification', ['count' => $unverified]),
            ],
        );
    }

    /** @return array<string, array<string, mixed>> */
    private function kpis(): array
    {
        $completedToday = $this->requests()->completed()->whereDate('updated_at', today())->count();
        $abnormalToday = $this->results()->where('is_abnormal', true)->whereDate('performed_at', today())->count();

        return [
            'pending' => ['value' => $this->requests()->pending()->count()],
            'processing' => ['value' => $this->requests()->processing()->count()],
            'completed_today' => ['value' => $completedToday],
            'abnormal_today' => ['value' => $abnormalToday],
        ];
    }

    /** Requests received vs completed per day, last 7 days. @return array<string, mixed> */
    private function volumeTrend(): array
    {
        $received = $this->requests()
            ->where('created_at', '>=', now()->subDays(7)->startOfDay())
            ->get(['created_at']);
        $completed = $this->requests()
            ->completed()
            ->where('updated_at', '>=', now()->subDays(7)->startOfDay())
            ->get(['updated_at']);

        $labels = [];
        $in = [];
        $out = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = today()->subDays($i);
            $labels[] = $day->format('D');
            $in[] = $received->filter(fn ($r) => Carbon::parse($r->created_at)->isSameDay($day))->count();
            $out[] = $completed->filter(fn ($r) => Carbon::parse($r->updated_at)->isSameDay($day))->count();
        }

        return ['labels' => $labels, 'received' => $in, 'completed' => $out];
    }

    /** Specimen counts by status for the donut. @return array<string, int> */
    private function sampleStatuses(): array
    {
        $counts = $this->samples()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $out = [];
        foreach (SampleStatus::cases() as $status) {
            $count = (int) ($counts[$status->value] ?? 0);
            if ($count > 0) {
                $out[__('samples.status.'.$status->value)] = $count;
            }
        }

        return $out;
    }

    private function requests(): Builder
    {
        return $this->scope->labRequests(LabRequest::query());
    }

    private function samples(): Builder
    {
        return $this->scope->samples(Sample::query());
    }

    private function results(): Builder
    {
        $departmentId = $this->scope->departmentId();

        return LabResult::query()->when(
            $departmentId,
            fn (Builder $query) => $query->whereHas('labRequest', fn (Builder $request) => $request->where('target_department_id', $departmentId)),
        );
    }

    private function unverifiedResults(): Builder
    {
        return $this->results()->whereNull('verified_at');
    }
}
