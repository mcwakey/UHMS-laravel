<?php

namespace App\Http\Controllers\Admin\Journey;

use App\Enums\JourneyDelayCause;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Department\DepartmentDashboardCapabilityService;
use App\Services\Journey\JourneyAnalyticsExportService;
use App\Services\Journey\JourneyAnalyticsQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Phase 9.8 — journey SLA / operational performance report. Reads aggregate snapshots
 * only (never live scans). Capability-scoped: oversight sees hospital-wide; everyone
 * else is restricted to their capability domains via `scope_types`.
 */
class JourneyAnalyticsController extends Controller
{
    /** capability => department types it covers. */
    private const CAPABILITY_TYPES = [
        'consultation_access' => ['consultation', 'treatment', 'procedure', 'theatre', 'emergency', 'ambulance', 'nursing', 'records'],
        'investigation_access' => ['investigation', 'radiology', 'blood_bank'],
        'pharmacy_access' => ['pharmacy'],
        'ward_access' => ['inpatient', 'maternity'],
        'financial_access' => ['finance', 'administrative'],
        'stock_access' => ['stores'],
    ];

    public function __construct(
        private JourneyAnalyticsQueryService $analytics,
        private JourneyAnalyticsExportService $export,
        private DepartmentDashboardCapabilityService $capabilities,
        private \App\Services\Journey\JourneyPredictionService $predictions,
        private \App\Services\Journey\JourneyPredictionAccuracyService $accuracy,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $isOversight = $user->can('journey.oversight');
        abort_if(! $isOversight && $this->capabilities->capabilitiesFor($user) === [], 403);

        $filters = $this->filters($request, $user, $isOversight);
        $previous = $this->previousPeriodFilters($filters);

        // Phase 9.9 — live risk forecast (cached prediction summary), aggregate-only.
        $canPredict = $user->can('journey.predictions.view');
        $forecast = $canPredict ? $this->predictions->summaryForUser($user) : null;

        // Phase 9.10 — prediction accuracy (evaluated outcomes, aggregate-only).
        $accuracy = $canPredict ? $this->accuracy->accuracySummary($filters) : null;
        $accuracyComparison = $canPredict ? $this->accuracy->comparePeriods($filters, $previous) : [];
        $accuracyByConfidence = $canPredict ? $this->accuracy->breakdown($filters, 'confidence') : [];
        $accuracyByRisk = $canPredict ? $this->accuracy->breakdown($filters, 'risk_level') : [];
        $worstPaths = $canPredict ? $this->accuracy->worstPaths($filters) : [];

        return view('admin.journey.analytics', [
            'isOversight' => $isOversight,
            'canPredict' => $canPredict,
            'forecast' => $forecast,
            'accuracy' => $accuracy,
            'accuracyComparison' => $accuracyComparison,
            'accuracyByConfidence' => $accuracyByConfidence,
            'accuracyByRisk' => $accuracyByRisk,
            'worstPaths' => $worstPaths,
            'filters' => $filters,
            'summary' => $this->analytics->summary($filters),
            'comparison' => $this->analytics->comparePeriods($filters, $previous),
            'trend' => $this->analytics->trend($filters),
            'causes' => $this->analytics->causeBreakdown($filters),
            'slaBreakdown' => $this->analytics->slaBreakdown($filters),
            'blocking' => $this->analytics->departmentRanking($filters, 'to'),
            'waiting' => $this->analytics->departmentRanking($filters, 'from'),
            'matrix' => $this->analytics->handoffPathRanking($filters),
            'causeOptions' => JourneyDelayCause::cases(),
        ]);
    }

    public function export(Request $request)
    {
        $user = $request->user();
        $isOversight = $user->can('journey.oversight');
        abort_if(! $isOversight && $this->capabilities->capabilitiesFor($user) === [], 403);

        $datasets = ['matrix', 'departments', 'causes', 'risk_paths', 'prediction_accuracy', 'prediction_outcomes_summary'];
        $dataset = in_array($request->query('dataset'), $datasets, true) ? $request->query('dataset') : 'matrix';
        $file = $this->export->export($dataset, $this->filters($request, $user, $isOversight));

        return response($file['content'], 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$file['filename'].'"',
        ]);
    }

    /** @return array<string, mixed> */
    private function filters(Request $request, User $user, bool $isOversight): array
    {
        $maxDays = (int) config('journey.analytics.max_days', 92);
        $defaultDays = (int) config('journey.analytics.default_days', 7);

        $to = $this->safeDate($request->query('date_to')) ?? Carbon::today();
        $from = $this->safeDate($request->query('date_from')) ?? $to->copy()->subDays($defaultDays - 1);
        if ($from->diffInDays($to) > $maxDays) {
            $from = $to->copy()->subDays($maxDays);
        }
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->subDays($defaultDays - 1), $to];
        }

        return [
            'date_from' => $from->toDateString(),
            'date_to' => $to->toDateString(),
            'granularity' => $request->query('granularity') === 'hour' ? 'hour' : 'day',
            'cause' => $request->query('cause') && JourneyDelayCause::tryFrom((string) $request->query('cause')) ? $request->query('cause') : null,
            'from_department_type' => $request->query('from_department_type') ?: null,
            'to_department_type' => $request->query('to_department_type') ?: null,
            'sla_status' => in_array($request->query('sla_status'), ['within', 'near_breach', 'breached', 'critical_breach'], true) ? $request->query('sla_status') : null,
            // Capability scope — null = hospital-wide (oversight only).
            'scope_types' => $isOversight ? null : $this->scopeTypesFor($user),
            '_ttl' => (int) config('journey.analytics.report_cache_ttl', 1800),
        ];
    }

    /** @return list<string> the department types the user is allowed to analyse. */
    private function scopeTypesFor(User $user): array
    {
        $types = [];
        foreach (self::CAPABILITY_TYPES as $capability => $capTypes) {
            if ($this->capabilities->can($user, $capability)) {
                $types = array_merge($types, $capTypes);
            }
        }

        return array_values(array_unique($types)) ?: ['__none__'];
    }

    private function previousPeriodFilters(array $filters): array
    {
        $from = Carbon::parse($filters['date_from']);
        $to = Carbon::parse($filters['date_to']);
        $days = $from->diffInDays($to) + 1;

        return array_merge($filters, [
            'date_from' => $from->copy()->subDays($days)->toDateString(),
            'date_to' => $from->copy()->subDay()->toDateString(),
        ]);
    }

    private function safeDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }
        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
