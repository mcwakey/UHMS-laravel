<?php

namespace App\Http\Controllers\Admin\Reporting;

use App\Enums\DepartmentType;
use App\Http\Controllers\Controller;
use App\Services\Department\DepartmentComparisonService;
use App\Services\Department\DepartmentContextSwitcherService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DepartmentComparisonController extends Controller
{
    public function __construct(
        private DepartmentComparisonService $comparison,
        private DepartmentContextSwitcherService $switcher,
    ) {}

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $payload = $this->comparison->build($request->user(), $filters);

        return view('reports.department-comparison', array_merge($payload, [
            'filters' => $filters,
            'availableDepartments' => $this->switcher->availableDepartments($request->user()),
            'departmentTypes' => DepartmentType::cases(),
        ]));
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $payload = $this->comparison->build($request->user(), $filters);
        $canViewRevenue = $payload['can_view_revenue'];
        $canViewStock = $payload['can_view_stock'];

        return response()->streamDownload(function () use ($payload, $canViewRevenue, $canViewStock) {
            $handle = fopen('php://output', 'w');
            $headers = [
                __('reports.department_comparison.department'),
                __('reports.department_comparison.type'),
                __('reports.department_comparison.assigned_users'),
                __('reports.department_comparison.services'),
                __('reports.department_comparison.activity'),
                __('reports.department_comparison.pending_work'),
                __('reports.department_comparison.completed_work'),
            ];
            if ($canViewRevenue) {
                $headers[] = __('reports.department_comparison.revenue');
            }
            if ($canViewStock) {
                $headers[] = __('reports.department_comparison.stock_alerts');
            }
            fputcsv($handle, $headers);

            foreach ($payload['rows'] as $row) {
                $line = [
                    $row['department'],
                    $row['type_label'],
                    $row['assigned_users'],
                    $row['services'],
                    $row['activity'],
                    $row['pending_work'],
                    $row['completed_work'],
                ];
                if ($canViewRevenue) {
                    $line[] = $row['revenue'];
                }
                if ($canViewStock) {
                    $line[] = $row['stock_alerts'];
                }
                fputcsv($handle, $line);
            }

            fclose($handle);
        }, 'department-comparison.csv', ['Content-Type' => 'text/csv']);
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'department_type' => ['nullable', 'string'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['integer'],
            'metric_group' => ['nullable', 'string'],
            'include_inactive' => ['nullable', 'boolean'],
        ]);
    }
}
