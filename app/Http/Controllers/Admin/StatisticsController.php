<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LogModule;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\ActivityLogService;
use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatisticsController extends Controller
{
    public function __construct(
        private StatisticsService $statistics,
        private ActivityLogService $log,
    ) {}

    public function dashboard(Request $request)
    {
        $this->authorizeView('statistics.dashboard.view');

        return view('statistics.dashboard', array_merge(
            $this->statistics->dashboard($request->only(['date_from', 'date_to'])),
            ['catalogue' => $this->visibleCatalogue($request)]
        ));
    }

    public function show(Request $request, string $report)
    {
        $catalogue = $this->statistics->catalogue();
        abort_unless(isset($catalogue[$report]), 404);
        // Staff performance is sensitive — it requires its own permission and is
        // never granted by the umbrella statistics.view.
        $this->authorizeView($catalogue[$report]['permission'], $report !== 'staff-performance');

        $filters = $request->only(['date_from', 'date_to', 'department_id']);
        $data = $this->statistics->build($report, $filters);

        if ($request->query('export') === 'csv') {
            return $this->exportCsv($request, $report, $data);
        }

        return view('statistics.show', array_merge($data, [
            'meta' => $catalogue[$report],
            'catalogue' => $this->visibleCatalogue($request),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'canExport' => $request->user()->can('statistics.export'),
        ]));
    }

    protected function exportCsv(Request $request, string $report, array $data): StreamedResponse
    {
        abort_unless($request->user()->can('statistics.export'), 403);

        $this->log->log(LogModule::SYSTEM, 'STATISTICS_EXPORTED', [
            'description' => __('messages.statistics.report_exported_to_csv', ['report' => $report]),
            'causer' => $request->user(),
            'metadata' => ['report' => $report, 'filters' => $data['filters'] ?? []],
        ]);

        $filename = "statistics-{$report}-".now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');

            // KPIs block
            fputcsv($handle, ['KPI', 'Value']);
            foreach ($data['kpis'] ?? [] as $kpi) {
                fputcsv($handle, [$kpi['label'], $kpi['value']]);
            }
            fputcsv($handle, []);

            // Each ranked / grouped list
            foreach ($data['lists'] ?? [] as $list) {
                fputcsv($handle, [$list['title']]);
                fputcsv($handle, $list['columns']);
                foreach ($list['rows'] as $row) {
                    fputcsv($handle, $row['cells']);
                }
                fputcsv($handle, []);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function authorizeView(string $permission, bool $allowUmbrella = true): void
    {
        $user = request()->user();
        abort_unless($user?->can($permission) || ($allowUmbrella && $user?->can('statistics.view')), 403);
    }

    /** Only the statistics pages the current user is permitted to see. */
    protected function visibleCatalogue(Request $request): array
    {
        $user = $request->user();

        return collect($this->statistics->catalogue())
            ->filter(function ($meta, $key) use ($user) {
                if ($key === 'staff-performance') {
                    return $user->can($meta['permission']); // sensitive: no umbrella access
                }

                return $user->can($meta['permission']) || $user->can('statistics.view');
            })
            ->all();
    }
}
